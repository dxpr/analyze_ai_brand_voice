## CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers

## INTRODUCTION

The Analyze Brand Voice module provides AI-powered brand voice consistency analysis for Drupal content, measuring how well content aligns with your organization's brand voice guidelines.

The primary use case for this module is to:

- **Analyze** content using AI processing
- **Score** text content against brand voice guidelines:
  - Overall alignment with brand voice (-1 to 1)
- **Guide** content creators with instant AI feedback

Goals:

- A focused AI brand voice analysis solution for Drupal content
- A stable, maintainable API for AI-powered content evaluation
- Integration with the Analyze framework for consistent reporting
- Simple, zero-configuration setup with built-in brand voice guidelines

## REQUIREMENTS

This module requires the following modules:

- Analyze (drupal/analyze)
- AI (drupal/ai)

## INSTALLATION

1. If your site is managed via Composer, use:
   ```composer require "drupal/analyze_brand_voice"```
   Otherwise, copy the module to your Drupal installation's modules directory.

2. Enable the 'Analyze Brand Voice' module in 'Extend'.
   (/admin/modules)

3. Configure permissions for content analysis.
   (/admin/people/permissions#module-analyze_brand_voice)

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

#### Environment Variables

The `DRUPAL_RECOMMENDED_PROJECT` environment variable is already defined in the
process. You don't need to specify it when running the commands.

These Docker commands help maintain code quality and compatibility across
different Drupal versions. Make sure to run these checks before submitting pull
requests or merging changes into the main branch.

## CONFIGURATION

### Basic Setup
- Configure AI provider settings at `/admin/config/analyze/ai`
- Enable/disable the analyzer per content type at `/admin/config/system/analyze-settings`

### Content Type Configuration
You can enable/disable brand voice analysis per content type:

1. Through the analyze settings:
   - Go to `/admin/config/system/analyze-settings`
   - Find the "Brand Voice Analysis" section
   - Enable/disable for specific content types

2. Through individual content:
   - View any content piece
   - Look for the Analyze tab or section
   - Find the "Brand Voice Analysis" settings

### Analysis Metrics
The module evaluates content against predefined brand voice guidelines:

- Friendly but professional
- Clear and concise
- Empowering and solution-focused
- Knowledgeable without being condescending
- Inclusive and welcoming

The analysis provides a score from -1.0 (completely off-brand) to +1.0 (perfectly aligned with brand voice).

### Display
- Results are shown as a gauge with clear progression
- Score is normalized to show alignment from -1.0 to +1.0
- Simple visual indicator of brand voice consistency

## MAINTAINERS

Current maintainers:
- Jurriaan Roelofs - https://www.drupal.org/u/jurriaanroelofs

This project is sponsored by:
- DXPR - https://www.drupal.org/node/2303425

For bug reports and feature requests, please use the project's issue queue at:
https://www.drupal.org/project/issues/analyze_brand_voice 
