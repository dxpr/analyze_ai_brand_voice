<?php

declare(strict_types=1);

namespace Drupal\analyze_ai_brand_voice\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Configure brand voice analysis settings.
 */
final class BrandVoiceSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The brand voice storage service.
   *
   * @var \Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService
   */
  protected BrandVoiceStorageService $brandVoiceStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new BrandVoiceSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\analyze_ai_brand_voice\Service\BrandVoiceStorageService $brand_voice_storage
   *   The brand voice storage service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    ModuleHandlerInterface $module_handler,
    BrandVoiceStorageService $brand_voice_storage,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->moduleHandler = $module_handler;
    $this->brandVoiceStorage = $brand_voice_storage;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('analyze_ai_brand_voice.storage'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'analyze_ai_brand_voice_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['analyze_ai_brand_voice.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('analyze_ai_brand_voice.settings');

    $form['description'] = [
      '#markup' => $this->t('<p>Configure the brand voice guidelines used for AI analysis. These guidelines will be used to evaluate how well content aligns with your brand voice. Need help defining your brand voice? Try the <a href=":url" target="_blank">DXPR Tone of Voice Wizard</a>.</p>', [':url' => 'https://dxpr.com/tools/tone-of-voice']),
    ];

    // Add link to reports page if user has permission.
    if ($this->currentUser->hasPermission('access site reports')) {
      $reports_url = Url::fromRoute('view.ai_brand_voice_analysis.page_1');
      if ($reports_url->access()) {
        $form['actions_top'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['form-actions']],
          '#weight' => -10,
          'report_link' => [
            '#type' => 'link',
            '#title' => $this->t('View reports'),
            '#url' => $reports_url,
            '#attributes' => [
              'class' => ['button', 'button--small', 'button--primary'],
            ],
          ],
        ];
      }
    }

    $default_brand_voice = $this->getDefaultBrandVoice();

    $form['brand_voice'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Brand Voice Guidelines'),
      '#description' => $this->t('Describe your brand voice characteristics. Be specific about tone, style, and communication approach. Examples: "Professional yet approachable", "Technical but accessible", "Friendly and conversational".'),
      '#default_value' => $config->get('brand_voice') ?: $default_brand_voice,
      '#rows' => 6,
      '#required' => TRUE,
      '#placeholder' => $this->t('Clear, approachable, professional, respectful'),
    ];

    if ($default_brand_voice && !$config->get('brand_voice')) {
      $form['default_notice'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        'message' => [
          '#markup' => $this->t('Default brand voice loaded from CKEditor AI Agent configuration.'),
        ],
        '#weight' => -1,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('analyze_ai_brand_voice.settings')
      ->set('brand_voice', trim($form_state->getValue('brand_voice')))
      ->save();

    // Invalidate all cached brand voice analysis results since configuration
    // changed.
    $this->brandVoiceStorage->invalidateConfigCache();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the default brand voice from CKEditor AI Agent if available.
   *
   * @return string
   *   The default brand voice string.
   */
  private function getDefaultBrandVoice(): string {
    // Try to get brand voice from CKEditor AI Agent if module exists.
    if ($this->moduleHandler->moduleExists('ckeditor_ai_agent')) {
      $config = $this->configFactory->get('ckeditor_ai_agent.settings');
      $brand_voice = $config->get('brand_voice');
      if (!empty($brand_voice)) {
        return $brand_voice;
      }
    }

    // Fallback to default.
    return 'Clear, approachable, professional, respectful';
  }

}
