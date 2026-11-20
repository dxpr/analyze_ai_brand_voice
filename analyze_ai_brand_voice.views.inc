<?php

/**
 * @file
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\analyze_ai_brand_voice\Hook\AnalyzeAiBrandVoiceViewsHooks;

/**
 * @file
 * Provide views data for analyze_ai_brand_voice.module.
 */

/**
 * Implements hook_views_data().
 */
#[LegacyHook]
function analyze_ai_brand_voice_views_data() {
  return \Drupal::service(AnalyzeAiBrandVoiceViewsHooks::class)->viewsData();
}
