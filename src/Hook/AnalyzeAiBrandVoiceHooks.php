<?php

namespace Drupal\analyze_ai_brand_voice\Hook;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for analyze_ai_brand_voice.
 */
class AnalyzeAiBrandVoiceHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_views_color_scale_popover_alter().
   */
  #[Hook('views_color_scale_popover_alter')]
  public function viewsColorScalePopoverAlter(array &$content, array $context): void {
    if ($context['view']->storage->get('base_table') !== 'analyze_ai_brand_voice_results') {
      return;
    }
    $content = [
      '#theme' => 'analyze_gauge',
      '#caption' => $context['field_options']['label'] ?? '',
      '#range_min_label' => $this->t('Off-brand'),
      '#range_mid_label' => $this->t('Neutral'),
      '#range_max_label' => $this->t('On-brand'),
      '#range_min' => $context['min'],
      '#value' => $context['position'],
      '#display_value' => $context['display_value'],
      '#range_max' => $context['max'],
      '#attached' => [
        'library' => [
          'analyze/gauge-popover',
        ],
      ],
    ];
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity) {
    if (_analyze_ai_brand_voice_is_supported_entity($entity)) {
      \Drupal::service('analyze_ai_brand_voice.storage')->deleteScores($entity);
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    if (_analyze_ai_brand_voice_is_supported_entity($entity)) {
      \Drupal::service('analyze_ai_brand_voice.storage')->deleteScores($entity);
    }
  }

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, $display_id, array &$args) {
    // Add settings link to the brand voice analysis view header.
    if ($view->id() === 'ai_brand_voice_analysis' && $display_id === 'page_1') {
      // Build the settings link if user has permission.
      $current_user = \Drupal::currentUser();
      if ($current_user->hasPermission('administer analyze')) {
        $settings_url = Url::fromRoute('analyze_ai_brand_voice.settings');
        if ($settings_url->access()) {
          $link = Link::fromTextAndUrl($this->t('Configure settings'), $settings_url);
          $link = $link->toRenderable();
          $link['#attributes']['class'][] = 'button';
          $link['#attributes']['class'][] = 'button--small';
          $link['#attributes']['class'][] = 'button--primary';
          // Add content before the view using attachment_before as render array.
          $view->attachment_before = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'form-actions',
                'views-configure-actions',
              ],
            ],
            '#weight' => -10,
            'configure_link' => $link,
            '#attached' => [
              'library' => [
                'system/base',
              ],
            ],
          ];
        }
      }
    }
  }

}
