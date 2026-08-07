<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adds a persistent archive state for notes archived manually. Notes that were
 * already archived by their end date continue to be classified by that date.
 */
final class Version1400Date20260806120000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('sb_notes')) {
			return null;
		}

		$table = $schema->getTable('sb_notes');
		if ($table->hasColumn('is_archived')) {
			return null;
		}

		$table->addColumn('is_archived', 'boolean', [
			'notnull' => true,
			'default' => false,
		]);

		return $schema;
	}
}
