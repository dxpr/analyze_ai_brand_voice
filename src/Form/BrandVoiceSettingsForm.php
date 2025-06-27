<?php

declare(strict_types=1);

namespace Drupal\analyze_ai_brand_voice\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure brand voice analysis settings.
 */
final class BrandVoiceSettingsForm extends ConfigFormBase {

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
      '#markup' => $this->t('<p>Configure the brand voice guidelines used for AI analysis. These guidelines will be used to evaluate how well content aligns with your brand voice.</p>'),
    ];

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

    $form['examples'] = [
      '#type' => 'details',
      '#title' => $this->t('Brand Voice Examples'),
      '#open' => FALSE,
      'content' => [
        '#theme' => 'item_list',
        '#title' => $this->t('Good brand voice descriptions:'),
        '#items' => [
          $this->t('<strong>Technology Company:</strong> "Clear, concise, innovative yet accessible. Avoid jargon while maintaining technical accuracy. Confident but not arrogant."'),
          $this->t('<strong>Healthcare Provider:</strong> "Compassionate, trustworthy, professional. Use plain language to explain complex topics. Reassuring and supportive tone."'),
          $this->t('<strong>Financial Services:</strong> "Authoritative, transparent, reliable. Professional language with clear explanations. Builds confidence and trust."'),
          $this->t('<strong>Creative Agency:</strong> "Bold, creative, energetic. Conversational and inspiring. Uses vivid language and storytelling elements."'),
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('analyze_ai_brand_voice.settings')
      ->set('brand_voice', trim($form_state->getValue('brand_voice')))
      ->save();

    // Invalidate all cached brand voice analysis results since configuration changed.
    \Drupal::service('analyze_ai_brand_voice.storage')->invalidateConfigCache();

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
    if (\Drupal::moduleHandler()->moduleExists('ckeditor_ai_agent')) {
      $config = \Drupal::config('ckeditor_ai_agent.settings');
      $brand_voice = $config->get('brand_voice');
      if (!empty($brand_voice)) {
        return $brand_voice;
      }
    }

    // Fallback to default.
    return 'Clear, approachable, professional, respectful';
  }

}