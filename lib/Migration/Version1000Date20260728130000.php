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

final class Version1000Date20260728130000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('sb_notes')) {
			return null;
		}

		$table = $schema->createTable('sb_notes');
		$table->addColumn('id', 'bigint', [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('title', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('content', 'text', [
			'notnull' => false,
		]);
		$table->addColumn('categories', 'text', [
			'notnull' => false,
		]);
		$table->addColumn('event_start', 'bigint', [
			'notnull' => false,
		]);
		$table->addColumn('event_end', 'bigint', [
			'notnull' => false,
		]);
		$table->addColumn('is_all_day', 'boolean', [
			'notnull' => true,
			'default' => false,
		]);
		$table->addColumn('location', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('link_url', 'string', [
			'notnull' => false,
			'length' => 2048,
		]);
		$table->addColumn('link_label', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('image_name', 'string', [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('image_mime', 'string', [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('created_at', 'bigint', [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', 'bigint', [
			'notnull' => true,
		]);
		$table->setPrimaryKey(['id']);
		$table->addIndex(['created_at'], 'sb_notes_created_idx');
		$table->addIndex(['event_start'], 'sb_notes_event_idx');
		$table->addIndex(['user_id'], 'sb_notes_user_idx');

		return $schema;
	}
}
