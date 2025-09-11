<?php

namespace Drupal\analyze_ai_brand_voice\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\analyze\HelperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService;

/**
 * Brand voice analyzer that uses AI to check content against brand guidelines.
 *
 * @Analyze(
 *   id = "analyze_ai_brand_voice_analyzer",
 *   label = @Translation("AI Brand Voice Analysis"),
 *   description = @Translation("Analyzes content for ai brand voice consistency.")
 * )
 */
final class AIBrandVoiceAnalyzer extends AnalyzePluginBase {

  use StringTranslationTrait;

  /**
   * The AI provider manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected AiProviderPluginManager $aiProvider;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The prompt JSON decoder service.
   *
   * @var \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface
   */
  protected PromptJsonDecoderInterface $promptJsonDecoder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The analyze helper.
   *
   * @var \Drupal\analyze\HelperInterface
   */
  protected HelperInterface $helper;

  /**
   * The brand voice storage service.
   *
   * @var \Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService
   */
  protected BrandVoiceStorageService $storage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected ?ConfigFactoryInterface $configFactory;

  /**
   * Creates the plugin.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\analyze\HelperInterface $helper
   *   The analyze helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\ai\AiProviderPluginManager $aiProvider
   *   The AI provider manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface $promptJsonDecoder
   *   The prompt JSON decoder service.
   * @param \Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService $storage
   *   The brand voice storage service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    AiProviderPluginManager $aiProvider,
    ?ConfigFactoryInterface $config_factory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected LanguageManagerInterface $languageManager,
    MessengerInterface $messenger,
    PromptJsonDecoderInterface $promptJsonDecoder,
    BrandVoiceStorageService $storage,
  ) {
    $this->helper = $helper;
    $this->currentUser = $currentUser;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
    $this->aiProvider = $aiProvider;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->promptJsonDecoder = $promptJsonDecoder;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('current_user'),
      $container->get('ai.provider'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('ai.prompt_json_decode'),
      $container->get('analyze_ai_brand_voice.storage'),
    );
  }

  /**
   * Creates a fallback status table.
   *
   * @param string $message
   *   The status message to display.
   *
   * @return array<string, mixed>
   *   The render array for the status table.
   */
  private function createStatusTable(string $message): array {
    // If this is the AI provider message and user has permission, append link.
    if ($message === 'No chat AI provider is configured for brand voice analysis.'
      && $this->currentUser->hasPermission('administer analyze settings')) {
      $link = Link::createFromRoute($this->t('Configure AI provider'), 'ai.settings_form');
      $message = $this->t(
        'No chat AI provider is configured for brand voice analysis. @link to set up AI services for brand voice analysis.',
        ['@link' => $link->toString()]
      );
    }

    return [
      '#theme' => 'analyze_table',
      '#table_title' => 'AI Brand Voice Analysis',
      '#rows' => [
        [
          'label' => 'Status',
          'data' => $message,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    // Try to get cached score first.
    $score = $this->storage->getScore($entity);

    // If no cached score, perform analysis.
    if ($score === NULL) {
      $score = $this->analyzeAiBrandVoice($entity);
      if ($score !== NULL) {
        $this->storage->saveScore($entity, $score);
      }
    }

    if ($score === NULL) {
      // Check if content exists first.
      if (!empty($this->getHtml($entity))) {
        $ai_provider = $this->getAiProvider();
        if (!$ai_provider) {
          return $this->createStatusTable('No chat AI provider is configured for brand voice analysis.');
        }
        else {
          return $this->createStatusTable('AI analysis failed to generate score. Check logs for details or try again.');
        }
      }
      else {
        return $this->createStatusTable('This content has no text available for brand voice analysis. Add content such as body text, fields, or descriptions to enable analysis.');
      }
    }

    /** @var array<string, mixed> $render */
    $render = [
      '#theme' => 'analyze_gauge',
      '#caption' => $this->t('AI Brand Voice Alignment'),
      '#range_min_label' => $this->t('Off-brand'),
      '#range_mid_label' => $this->t('Neutral'),
      '#range_max_label' => $this->t('On-brand'),
      '#range_min' => -1,
      '#range_max' => 1,
      '#value' => ($score + 1) / 2,
      '#display_value' => sprintf('%+.1f', $score),
    ];

    return $render;
  }

  /**
   * Helper to get the rendered entity content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   *
   * @return string
   *   The rendered content as plain text.
   */
  private function getHtml(EntityInterface $entity): string {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'default', $langcode);
    $rendered = $this->renderer->render($view);

    $content = (string) $rendered;

    $content = strip_tags($content);
    $content = str_replace('&nbsp;', ' ', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);

    return $content;
  }

  /**
   * Gets the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  private function getConfigFactory(): ConfigFactoryInterface {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Gets the brand voice guidelines from configuration.
   *
   * @return string
   *   The brand voice guidelines.
   */
  private function getBrandVoice(): string {
    $config = $this->getConfigFactory()->get('analyze_ai_brand_voice.settings');
    return $config->get('brand_voice') ?: 'Clear, approachable, professional, respectful';
  }

  /**
   * Analyze the ai brand voice of entity content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to analyze.
   *
   * @return float|null
   *   The ai brand voice score between -1.0 and 1.0, or NULL if analysis
   *   failed.
   */
  protected function analyzeAiBrandVoice(EntityInterface $entity): ?float {
    try {
      $content = $this->getHtml($entity);

      $ai_provider = $this->getAiProvider();
      if (!$ai_provider) {
        return NULL;
      }

      $defaults = $this->getDefaultModel();
      if (!$defaults) {
        return NULL;
      }

      $brand_voice = $this->getBrandVoice();

      $prompt = <<<EOT
<task>Analyze how well the following text aligns with our ai brand voice guidelines.</task>

<brand_voice>
$brand_voice
</brand_voice>

<text>
$content
</text>

<instructions>Provide a precise score between -1.0 and +1.0 that represents how well the text aligns with our ai brand voice guidelines, where:
-1.0 = Completely off-brand
 0.0 = Neutral/mixed alignment
+1.0 = Perfect ai brand voice alignment</instructions>

<output_format>Respond with a simple JSON object:
{
  "score": number  // Score from -1.0 to +1.0
}</output_format>
EOT;

      $chat_array = [
        new ChatMessage('user', $prompt),
      ];

      $messages = new ChatInput($chat_array);

      /** @var \Drupal\ai\OperationType\Chat\ChatInterface $ai_provider */
      $message = $ai_provider->chat($messages, $defaults['model_id'])->getNormalized();

      $decoded = $this->promptJsonDecoder->decode($message);

      if (!is_array($decoded) || !array_key_exists('score', $decoded) || !is_numeric($decoded['score'])) {
        return NULL;
      }

      return max(-1.0, min(1.0, (float) $decoded['score']));
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Gets the AI provider plugin.
   *
   * @return object|null
   *   The AI provider plugin or NULL if not configured.
   */
  private function getAiProvider() {
    if (!$this->aiProvider->hasProvidersForOperationType('chat', TRUE)) {
      return NULL;
    }

    $defaults = $this->getDefaultModel();
    if (empty($defaults['provider_id'])) {
      return NULL;
    }

    $ai_provider = $this->aiProvider->createInstance($defaults['provider_id']);

    // Configure provider with low temperature for more consistent results.
    if (method_exists($ai_provider, 'setConfiguration')) {
      $ai_provider->setConfiguration(['temperature' => 0.2]);
    }

    return $ai_provider;
  }

  /**
   * Gets the default model configuration.
   *
   * @return array{provider_id: string, model_id: string}|null
   *   The default model configuration or NULL if not available.
   */
  private function getDefaultModel(): ?array {
    /** @var array{provider_id: string, model_id: string}|null $defaults */
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if (empty($defaults['provider_id']) || empty($defaults['model_id'])) {
      return NULL;
    }
    return $defaults;
  }

}
