## CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers

## INTRODUCTION

The Analyze AI Brand Voice module provides AI-powered ai brand voice consistency
analysis for Drupal content, measuring how well content aligns with your
organization's ai brand voice guidelines.

The primary use case for this module is to:

- **Analyze** content using AI processing
- **Score** text content against ai brand voice guidelines:
  - Overall alignment with ai brand voice (-1 to 1)
- **Guide** content creators with instant AI feedback
- **Customize** brand voice guidelines through a simple hook

Goals:

- A focused AI brand voice analysis solution for Drupal content
- A stable, maintainable API for AI-powered content evaluation
- Integration with the Analyze framework for consistent reporting
- Simple, zero-configuration setup with built-in ai brand voice guidelines
- Flexible brand voice customization through Drupal's hook system

## REQUIREMENTS

This module requires the following modules:

- Analyze (drupal/analyze)
- AI (drupal/ai)

Optional integration:
- CKEditor AI Agent (drupal/ckeditor_ai_agent) - Can provide default brand 
  voice settings if installed

## INSTALLATION

1. If your site is managed via Composer, use:
   ```composer require "drupal/analyze_ai_brand_voice"```
   Otherwise, copy the module to your Drupal installation's modules directory.

2. Enable the 'Analyze AI Brand Voice' module in 'Extend'.
   (/admin/modules)

3. Configure permissions for content analysis.
   (/admin/people/permissions#module-analyze_ai_brand_voice)

### Brand Voice Configuration
Configure your brand voice guidelines at `/admin/config/analyze/brand-voice`
where you can set:
- Custom brand voice guidelines text
- Tone and style preferences
- Writing guidelines specific to your organization

The module includes sensible defaults, but you can customize them to match
your organization's specific brand voice.

### Advanced Customization
For developers, brand voice guidelines can also be customized using the
`hook_ai_brand_voice_alter()` hook:

```php
function mymodule_ai_brand_voice_alter(string &$brand_voice) {
  // Override the default brand voice guidelines
  $brand_voice = 'Friendly, conversational, expert, inclusive';
}
```

If the CKEditor AI Agent module is installed, it can also provide default
brand voice settings that will be used as fallbacks.
### Docker Commands

This module uses Docker to ensure consistent development and testing
environments. Here are the key Docker commands you can use:

#### Linting Drupal Code

To run the Drupal linter:

```bash
docker compose run --rm drupal-lint
```

This command checks your Drupal code for adherence to coding standards and best
practices.

#### Running Drupal Deprecation and Analysis Checks

To perform Drupal deprecation and analysis checks:

```bash
docker compose run --rm drupal-check
```

This command analyzes your code for usage of deprecated Drupal APIs and other
potential issues.

#### Auto-fixing Drupal Code

To automatically fix some coding standard issues:

```bash
docker compose run --rm drupal-lint-auto-fix
```

This command will attempt to automatically fix coding standard violations in
your Drupal code.

#### Pre-commit Hook

This repository includes a pre-commit hook that automatically runs linting
checks before each commit. The hook will:

1. **Auto-fix issues**: Run `drupal-lint-auto-fix` to automatically resolve
   fixable coding standard violations
2. **Verify compliance**: Run `drupal-lint` to check for any remaining issues
3. **Block commits**: Prevent commits if any coding standard violations remain

The pre-commit hook is automatically installed at `.git/hooks/pre-commit` and
provides colored output to clearly show the linting process and results.

**Benefits:**
- Ensures all committed code follows Drupal coding standards
- Automatically fixes common issues before commit
- Prevents "broken" commits that don't pass linting
- Provides immediate feedback during development

**Manual Installation:**
If you need to manually install or reinstall the pre-commit hook:

```bash
chmod +x .git/hooks/pre-commit
```

**Bypassing the Hook:**
In rare cases where you need to bypass the pre-commit hook:

```bash
git commit --no-verify -m "Your commit message"
```

**Note:** Use `--no-verify` sparingly and ensure you fix any linting issues
in a follow-up commit.

#### Environment Variables

The `DRUPAL_RECOMMENDED_PROJECT` environment variable is already defined in the
process. You don't need to specify it when running the commands.

These Docker commands help maintain code quality and compatibility across
different Drupal versions. Make sure to run these checks before submitting pull
requests or merging changes into the main branch.

## CONFIGURATION

### Basic Setup
- Configure AI provider settings at `/admin/config/analyze/ai`
- Configure brand voice guidelines at
  `/admin/config/analyze/brand-voice`
- Enable/disable the analyzer per content type at
  `/admin/config/system/analyze-settings`
- Access batch analysis tools at `/admin/config/analyze/brand-voice/batch`

### Content Type Configuration
You can enable/disable ai brand voice analysis per content type:

1. Through the analyze settings:
   - Go to `/admin/config/system/analyze-settings`
   - Find the "AI Brand Voice Analysis" section
   - Enable/disable for specific content types

2. Through individual content:
   - View any content piece
   - Look for the Analyze tab or section
   - Find the "AI Brand Voice Analysis" settings

### Analysis Metrics
The module evaluates content against your configured brand voice guidelines.
Default guidelines include:

- Friendly but professional
- Clear and concise
- Empowering and solution-focused
- Knowledgeable without being condescending
- Inclusive and welcoming

The analysis provides a score from -1.0 (completely off-brand) to +1.0
(perfectly aligned with brand voice).

### Batch Processing
For analyzing large amounts of existing content:

1. Go to `/admin/config/analyze/brand-voice/batch`
2. Select content types to analyze
3. Choose whether to force re-analysis of previously analyzed content
4. Set processing limits and start the batch job

Results are cached for performance and only re-analyzed when content or
configuration changes.

### Display and Reporting
- Results are shown as a gauge with clear progression from -1.0 to +1.0
- Simple visual indicator of brand voice consistency
- Integration with Views for creating custom reports and dashboards
- Color-coded results available with the Views Color Scales module
- Historical tracking of brand voice alignment over time

## MAINTAINERS

Current maintainers:
- Jurriaan Roelofs - https://www.drupal.org/u/jurriaanroelofs

This project is sponsored by:
- DXPR - https://www.drupal.org/node/2303425

For bug reports and feature requests, please use the project's issue queue at:
https://www.drupal.org/project/issues/analyze_ai_brand_voice
