<?php

namespace Drupal\analyze_brand_voice\Plugin\Analyze;

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

/**
 * A brand voice analyzer that uses AI to check content against brand guidelines.
 *
 * @Analyze(
 *   id = "brand_voice_analyzer",
 *   label = @Translation("Brand Voice Analysis"),
 *   description = @Translation("Analyzes content for brand voice consistency.")
 * )
 */
final class BrandVoiceAnalyzer extends AnalyzePluginBase {

  /**
   * The AI provider manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The prompt JSON decoder service.
   *
   * @var \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface
   */
  protected PromptJsonDecoderInterface $promptJsonDecoder;

  /**
   * Creates the plugin.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $helper,
    $currentUser,
    AiProviderPluginManager $aiProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected LanguageManagerInterface $languageManager,
    MessengerInterface $messenger,
    PromptJsonDecoderInterface $promptJsonDecoder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
    $this->aiProvider = $aiProvider;
    $this->messenger = $messenger;
    $this->promptJsonDecoder = $promptJsonDecoder;
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
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('ai.prompt_json_decode'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $score = $this->analyzeBrandVoice($entity);
    
    if ($score === NULL) {
      return [
        '#theme' => 'analyze_table',
        '#table_title' => 'Brand Voice Analysis',
        '#rows' => [
          [
            'label' => 'Status',
            'data' => $this->t('No chat AI provider is configured for brand voice analysis. Please configure one in the %ai_settings_link.', [
              '%ai_settings_link' => Link::createFromRoute($this->t('AI settings'), 'ai.settings_form')
                  ->toString(),
            ]),
          ],
        ],
      ];
    }
    
    return [
      '#theme' => 'analyze_gauge',
      '#caption' => $this->t('Brand Voice Alignment'),
      '#range_min_label' => $this->t('Off-brand'),
      '#range_mid_label' => $this->t('Neutral'),
      '#range_max_label' => $this->t('On-brand'),
      '#range_min' => -1,
      '#range_max' => 1,
      '#value' => ($score + 1) / 2,
      '#display_value' => sprintf('%+.1f', $score),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    $score = $this->analyzeBrandVoice($entity);
    
    if ($score === NULL) {
      return [];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['analyze-brand-voice-report'],
      ],
    ];

    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Brand Voice Analysis'),
    ];
    
    $build['gauge'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => $this->t('Brand Voice Alignment'),
      '#range_min_label' => $this->t('Off-brand'),
      '#range_mid_label' => $this->t('Neutral'),
      '#range_max_label' => $this->t('On-brand'),
      '#range_min' => -1,
      '#range_max' => 1,
      '#value' => ($score + 1) / 2,
      '#display_value' => sprintf('%+.1f', $score),
    ];

    return $build;
  }

  /**
   * Helper to get the rendered entity content.
   */
  private function getHtml(EntityInterface $entity): string {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'default', $langcode);
    $rendered = $this->renderer->render($view);
    
    $content = is_object($rendered) && method_exists($rendered, '__toString')
      ? $rendered->__toString()
      : (string) $rendered;
      
    $content = strip_tags($content);
    $content = str_replace('&nbsp;', ' ', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    return $content;
  }

  /**
   * Analyze the brand voice of entity content.
   */
  protected function analyzeBrandVoice(EntityInterface $entity): ?float {
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

      // Example brand voice guidelines
      $brand_voice = <<<EOT
Our brand voice is:
- Friendly but professional
- Clear and concise
- Empowering and solution-focused
- Knowledgeable without being condescending
- Inclusive and welcoming

We avoid:
- Overly technical jargon
- Passive voice
- Negative or critical tone
- Informal slang
- Excessive exclamation marks
EOT;

      $prompt = <<<EOT
<task>Analyze how well the following text aligns with our brand voice guidelines.</task>

<brand_voice>
$brand_voice
</brand_voice>

<text>
$content
</text>

<instructions>Provide a precise score between -1.0 and +1.0 that represents how well the text aligns with our brand voice guidelines, where:
-1.0 = Completely off-brand
 0.0 = Neutral/mixed alignment
+1.0 = Perfect brand voice alignment</instructions>

<output_format>Respond with a simple JSON object:
{
  "score": number  // Score from -1.0 to +1.0
}</output_format>
EOT;

      $chat_array = [
        new ChatMessage('user', $prompt),
      ];

      $messages = new ChatInput($chat_array);
      $message = $ai_provider->chat($messages, $defaults['model_id'])->getNormalized();
      
      $decoded = $this->promptJsonDecoder->decode($message);
      
      if (!is_array($decoded) || !isset($decoded['score'])) {
        return NULL;
      }
      
      return max(-1.0, min(1.0, (float) $decoded['score']));
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    return $this->currentUser->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return parent::getFullReportUrl($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [];
  }

  private function getAiProvider() {
    if (!$this->aiProvider->hasProvidersForOperationType('chat', TRUE)) {
      return NULL;
    }

    $defaults = $this->getDefaultModel();
    if (empty($defaults['provider_id'])) {
      return NULL;
    }

    $ai_provider = $this->aiProvider->createInstance($defaults['provider_id']);
    $ai_provider->setConfiguration(['temperature' => 0.2]);
    
    return $ai_provider;
  }

  private function getDefaultModel() {
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if (empty($defaults['provider_id']) || empty($defaults['model_id'])) {
      return NULL;
    }
    return $defaults;
  }
} 