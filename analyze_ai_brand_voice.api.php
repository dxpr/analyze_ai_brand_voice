<?php

/**
 * @file
 * Hooks specific to the analyze_ai_brand_voice module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the AI brand voice guidelines used for content analysis.
 *
 * This hook allows modules to modify the brand voice guidelines that are used
 * when analyzing content for brand voice consistency. The guidelines should be
 * a string describing the desired tone, style, and characteristics of the
 * organization's content.
 *
 * @param string $brand_voice
 *   The brand voice guidelines string that will be used in the AI analysis.
 *   Modify this parameter to change the guidelines.
 *
 * @see \Drupal\analyze_ai_brand_voice\Plugin\Analyze\AIBrandVoiceAnalyzer::getBrandVoice()
 *
 * @ingroup analyze_ai_brand_voice
 */
function hook_ai_brand_voice_alter(string &$brand_voice) {
  // Example: Override the default brand voice guidelines.
  $brand_voice = 'Friendly, conversational, expert, inclusive';
}

/**
 * @} End of "addtogroup hooks".
 */
