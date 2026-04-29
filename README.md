> **Analyze AI Brand Voice** detects brand voice drift across your Drupal site
> using AI tone analysis. An [Analyze](https://www.drupal.org/project/analyze)
> ecosystem plugin by [DXPR](https://dxpr.com).
>
> [Getting Started](https://dxpr.com/c/getting-started) |
> [Pricing](https://dxpr.com/pricing) |
> [Try Free Demo](https://dxpr.com/try)

# AI Brand Voice Analysis: AI-Powered Brand Voice Consistency Scoring for Drupal

AI-powered brand voice consistency analysis measuring content alignment against
your brand guidelines.

## Features

- **Brand Voice Analysis**: AI evaluation against customizable brand guidelines
- **Visual Feedback**: Gauge displays with alignment scores (-1.0 to +1.0)
- **Analyze Framework Integration**: Consistent reporting across analysis tools
- **Batch Processing**: Analyze large content volumes efficiently
- **Custom Guidelines**: Hook-based customization for organization-specific
  requirements

## Requirements

- [Analyze](https://www.drupal.org/project/analyze) framework
- [AI](https://www.drupal.org/project/ai) module with configured provider

Optional:
- [CKEditor AI Agent](https://www.drupal.org/project/ckeditor_ai_agent) - Provides default brand voice settings

## Installation

```bash
composer require drupal/analyze_ai_brand_voice
drush en analyze_ai_brand_voice
```

## Configuration

### Basic Setup
1. Configure AI provider at `/admin/config/ai/providers`
2. Set brand voice guidelines at `/admin/config/analyze/brand-voice`
3. Enable per content type at `/admin/config/content/analyze-settings`
4. Configure permissions at `/admin/people/permissions#module-analyze_ai_brand_voice`

### Brand Voice Guidelines
Configure at `/admin/config/analyze/brand-voice`:
- Custom brand voice guidelines text
- Tone and style preferences
- Organization-specific writing standards

Default guidelines include:
- Friendly but professional
- Clear and concise
- Empowering and solution-focused
- Knowledgeable without condescension
- Inclusive and welcoming

### Advanced Customization
Use `hook_ai_brand_voice_alter()` for programmatic customization:

```php
function mymodule_ai_brand_voice_alter(string &$brand_voice) {
  $brand_voice = 'Friendly, conversational, expert, inclusive';
}
```

## AI Coding Assistant Integration

The Brand Voice module includes a built-in
[Agent Skills](https://agentskills.io) file (via the base
Analyze module) that teaches AI coding assistants how to run
brand voice analysis through natural language. Run
`drush analyze:setup-ai` to enable, then ask naturally:

```
"Analyze brand voice consistency on all articles"
"Check if the about page aligns with our brand guidelines"
"Re-analyze brand voice for all published content"
"Run brand voice and sentiment analysis together on blog posts"
```

Batch processing is available via the centralized Analyze
batch system:

```bash
# Check analysis coverage
drush analyze:batch --status

# Run this analyzer on all enabled content types
drush analyze:batch \
  --analyzers=analyze_ai_brand_voice_analyzer

# Run on specific content types with limit
drush analyze:batch \
  --analyzers=analyze_ai_brand_voice_analyzer \
  --types=node:article --limit=50

# Force re-analysis of already analyzed content
drush analyze:batch \
  --analyzers=analyze_ai_brand_voice_analyzer --force
```

Compatible with Claude Code, Codex CLI, Gemini CLI, GitHub
Copilot, Cursor, and other tools supporting the
[Agent Skills standard](https://agentskills.io/specification).

## Analysis

Results show brand voice alignment scores with visual gauge progression.
Cache invalidation is automatic - only re-analyzes when content or
configuration changes.

### Display Options
- Gauge visualization with clear progression
- Historical tracking of alignment over time
- Views integration for custom reports
- Color-coded results with Views Color Scales module

## Development

### Docker Commands
```bash
# Lint code
docker compose run --rm drupal-lint

# Check deprecations
docker compose run --rm drupal-check

# Auto-fix issues
docker compose run --rm drupal-lint-auto-fix
```

### Pre-commit Hook
Automatically runs linting checks before commits:
- Auto-fixes coding standard violations
- Blocks commits with remaining issues
- Provides colored output for feedback

## Related Modules

- [Analyze](https://www.drupal.org/project/analyze) - Content analysis framework with unified Analyze tab
- [AI Sentiments Analysis](https://www.drupal.org/project/analyze_ai_sentiments) - Multi-dimensional content tone analysis
- [AI Content Strategy](https://www.drupal.org/project/ai_content_strategy) - AI-powered content strategy recommendations
- [AI](https://www.drupal.org/project/ai) - Drupal AI integration layer for connecting to LLM providers
- [Metatag](https://www.drupal.org/project/metatag) - Manage meta tags for improved SEO and social sharing
