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
 * Adds the draft flag. A draft is kept out of the board, the archive and the
 * Dashboard widget until it is published.
 */
final class Version1100Date20260729110000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('sb_notes')) {
			return null;
		}

		$table = $schema->getTable('sb_notes');
		if ($table->hasColumn('is_draft')) {
			return null;
		}

		$table->addColumn('is_draft', 'boolean', [
			'notnull' => true,
			'default' => false,
		]);

		return $schema;
	}
}
