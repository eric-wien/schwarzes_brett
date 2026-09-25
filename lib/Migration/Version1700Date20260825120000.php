<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Separates event information from the existing board display period. */
final class Version1700Date20260825120000 extends SimpleMigrationStep {
	public function __construct(private readonly IDBConnection $db) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('sb_notes');
		foreach (['publish_at', 'archive_at'] as $column) {
			if (!$table->hasColumn($column)) {
				$table->addColumn($column, 'bigint', ['notnull' => false]);
			}
		}

		return $schema;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// These dates previously controlled visibility. Move them, rather than
		// changing when existing notes appear or inventing event information.
		$query = $this->db->getQueryBuilder();
		$query->update('sb_notes')
			->set('publish_at', $query->createFunction('event_start'))
			->set('archive_at', $query->createFunction('event_end'))
			->set('event_start', $query->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('event_end', $query->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->where($query->expr()->isNull('publish_at'))
			->andWhere($query->expr()->isNull('archive_at'));
		$query->executeStatement();
	}
}
