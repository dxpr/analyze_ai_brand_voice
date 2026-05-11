<?php

declare(strict_types=1);

namespace Drupal\analyze_ai_brand_voice\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * Service for storing and retrieving brand voice analysis results.
 */
final class BrandVoiceStorageService {

  use DependencySerializationTrait;

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Gets the cached brand voice score for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the score for.
   *
   * @return float|null
   *   The cached score if available and valid, NULL otherwise.
   */
  public function getScore(EntityInterface $entity): ?float {
    $content_hash = $this->generateContentHash($entity);
    $config_hash = $this->generateConfigHash();

    $result = $this->database->select('analyze_ai_brand_voice_results', 'r')
      ->fields('r', ['score'])
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('langcode', $entity->language()->getId())
      ->condition('content_hash', $content_hash)
      ->condition('config_hash', $config_hash)
      ->execute()
      ->fetchField();

    return $result !== FALSE ? (float) $result : NULL;
  }

  /**
   * Saves a brand voice score for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the score is for.
   * @param float $score
   *   The brand voice score (-1.0 to +1.0).
   */
  public function saveScore(EntityInterface $entity, float $score): void {
    // Ensure score is within valid range.
    $score = max(-1.0, min(1.0, $score));

    $this->database->merge('analyze_ai_brand_voice_results')
      ->keys([
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'langcode' => $entity->language()->getId(),
      ])
      ->fields([
        'entity_revision_id' => $entity instanceof RevisionableInterface ? $entity->getRevisionId() : 0,
        'score' => $score,
        'content_hash' => $this->generateContentHash($entity),
        'config_hash' => $this->generateConfigHash(),
        'analyzed_timestamp' => $this->time->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Deletes all stored scores for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to delete scores for.
   */
  public function deleteScores(EntityInterface $entity): void {
    $this->database->delete('analyze_ai_brand_voice_results')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();
  }

  /**
   * Invalidates all cached results due to configuration changes.
   */
  public function invalidateConfigCache(): void {
    // Delete all records with old config hash.
    $current_hash = $this->generateConfigHash();
    $this->database->delete('analyze_ai_brand_voice_results')
      ->condition('config_hash', $current_hash, '!=')
      ->execute();
  }

  /**
   * Gets statistics about stored analysis results.
   *
   * @return array{total_results: int, unique_entities: int, average_score: float, oldest_analysis: int, newest_analysis: int}
   *   Array with count statistics.
   */
  public function getStatistics(): array {
    $query = $this->database->select('analyze_ai_brand_voice_results', 'r');
    $query->addExpression('COUNT(*)', 'total_results');
    $query->addExpression('COUNT(DISTINCT entity_id)', 'unique_entities');
    $query->addExpression('AVG(score)', 'average_score');
    $query->addExpression('MIN(analyzed_timestamp)', 'oldest_analysis');
    $query->addExpression('MAX(analyzed_timestamp)', 'newest_analysis');

    $result = $query->execute()->fetchAssoc();

    return [
      'total_results' => (int) $result['total_results'],
      'unique_entities' => (int) $result['unique_entities'],
      'average_score' => $result['average_score'] ? (float) $result['average_score'] : 0.0,
      'oldest_analysis' => $result['oldest_analysis'] ? (int) $result['oldest_analysis'] : 0,
      'newest_analysis' => $result['newest_analysis'] ? (int) $result['newest_analysis'] : 0,
    ];
  }

  /**
   * Generates a content hash for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate a hash for.
   *
   * @return string
   *   The SHA256 hash of the entity content.
   */
  private function generateContentHash(EntityInterface $entity): string {
    $content = $this->getEntityContent($entity);
    return hash('sha256', $content);
  }

  /**
   * Generates a configuration hash for brand voice settings.
   *
   * @return string
   *   The MD5 hash of the brand voice configuration.
   */
  private function generateConfigHash(): string {
    // Get the brand voice configuration from settings.
    $config = $this->configFactory->get('analyze_ai_brand_voice.settings');
    $brand_voice = $config->get('brand_voice') ?: 'Clear, approachable, professional, respectful';

    return hash('md5', $brand_voice);
  }

  /**
   * Extracts clean text content from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to extract content from.
   *
   * @return string
   *   The cleaned text content.
   */
  private function getEntityContent(EntityInterface $entity): string {
    // Use the entity's own language, not the current UI language.
    $langcode = $entity->language()->getId();

    // Render the entity in default view mode.
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $view = $view_builder->view($entity, 'default', $langcode);
    $rendered = $this->renderer->renderInIsolation($view);

    // Convert to string and clean up.
    $content = (string) $rendered;

    // Strip HTML tags and normalize whitespace.
    $content = strip_tags($content);
    $content = str_replace('&nbsp;', ' ', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);

    return $content;
  }

  /**
   * Counts the number of analyzed entities for a given type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   *
   * @return int
   *   The count of analyzed entities.
   */
  public function countAnalyzedEntities(string $entity_type_id, string $bundle): int {
    $query = $this->database->select('analyze_ai_brand_voice_results', 'r');
    $query->condition('r.entity_type', $entity_type_id);
    if ($entity_type_id === 'node') {
      $query->join('node_field_data', 'n', 'r.entity_id = n.nid AND r.entity_type = :type', [':type' => 'node']);
      $query->condition('n.type', $bundle);
    }
    $query->addExpression('COUNT(DISTINCT r.entity_id)');
    return (int) $query->execute()->fetchField();
  }

  /**
   * Gets entity IDs that have stored results for a given type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   *
   * @return array<string|int>
   *   Array of entity IDs with existing results.
   */
  public function getAnalyzedEntityIds(string $entity_type_id, string $bundle): array {
    $query = $this->database->select('analyze_ai_brand_voice_results', 'r');
    $query->addField('r', 'entity_id');
    $query->condition('r.entity_type', $entity_type_id);
    if ($entity_type_id === 'node') {
      $query->join('node_field_data', 'n', 'r.entity_id = n.nid AND r.entity_type = :type', [':type' => 'node']);
      $query->condition('n.type', $bundle);
    }
    $query->distinct();
    return $query->execute()->fetchCol();
  }

}
