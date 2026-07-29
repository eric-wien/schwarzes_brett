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
 * Adds the moderation state. Existing notes stay approved so enabling the
 * workflow never unexpectedly removes existing content from the board.
 */
final class Version1300Date20260729130000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('sb_notes')) {
			return null;
		}

		$table = $schema->getTable('sb_notes');
		if ($table->hasColumn('is_approved')) {
			return null;
		}

		$table->addColumn('is_approved', 'boolean', [
			'notnull' => true,
			'default' => true,
		]);

		return $schema;
	}
}
