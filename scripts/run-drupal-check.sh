#!/bin/bash
set -vo pipefail

# Support both environment variable names (local vs GitHub Actions)
if [[ -n "${TARGET_DRUPAL_CORE_VERSION:-}" ]]; then
  DRUPAL_RECOMMENDED_PROJECT="${TARGET_DRUPAL_CORE_VERSION}.x-dev"
else
  DRUPAL_RECOMMENDED_PROJECT=${DRUPAL_RECOMMENDED_PROJECT:-11.x-dev}
fi
PHP_EXTENSIONS="gd"

# Install required PHP extensions
for ext in $PHP_EXTENSIONS; do
  if ! php -m | grep -q $ext; then
    apk update && apk add --no-cache ${ext}-dev
    docker-php-ext-install $ext
  fi
done

# Create Drupal project if it doesn't exist
if [ ! -d "/drupal" ]; then
  composer create-project drupal/recommended-project=$DRUPAL_RECOMMENDED_PROJECT drupal --no-interaction --stability=dev
fi

cd drupal
mkdir -p web/modules/contrib/

# Symlink analyze_ai_brand_voice if not already linked
if [ ! -L "web/modules/contrib/analyze_ai_brand_voice" ]; then
  ln -s /src web/modules/contrib/analyze_ai_brand_voice
fi

# Install the statistic modules if D11 (removed from core).
if [[ $DRUPAL_RECOMMENDED_PROJECT == 11.* ]]; then
  composer require drupal/statistics
fi

# Install module dependencies required for static analysis
composer require drupal/analyze drupal/ai drupal/views_color_scales --dev

# Install PHPStan with Drupal extensions (alternative to drupal-check)
composer require --dev phpstan/phpstan phpstan/extension-installer mglaman/phpstan-drupal phpstan/phpstan-deprecation-rules

# Copy PHPStan configuration to the Drupal root
cp /src/phpstan.neon phpstan.neon

# Create symlink to module for analysis (in web/modules/contrib)
# (already done above in the symlink section)

# Run PHPStan analysis (equivalent to drupal-check)
./vendor/bin/phpstan analyse web/modules/contrib/analyze_ai_brand_voice --no-progress