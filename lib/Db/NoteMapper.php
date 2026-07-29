<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class NoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'sb_notes', Note::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function find(int $id): Note {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($query);
	}

	/**
	 * Notes are ordered by their last change, falling back to their creation
	 * time, so an edited note returns to the top of the board.
	 *
	 * The column names need no quoting - they are lower case and not reserved -
	 * which keeps the expression portable across MySQL, PostgreSQL and SQLite.
	 */
	private function orderByLastChange(IQueryBuilder $query): void {
		$query->orderBy($query->createFunction('COALESCE(updated_at, created_at)'), 'DESC')
			->addOrderBy('id', 'DESC');
	}

	/**
	 * Administrators see every note. Moderators see submitted notes and their
	 * own drafts. Everyone else sees approved notes plus their own drafts and
	 * submissions. Filtering in SQL keeps private content out of controller
	 * memory altogether.
	 *
	 * @return list<Note>
	 */
	public function findAll(string $userId, bool $isAdmin, bool $isModerator): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName());

		if ($isAdmin) {
			$this->orderByLastChange($query);
			return $this->findEntities($query);
		}

		if ($isModerator) {
			$query->where(
				$query->expr()->orX(
					$query->expr()->eq(
						'is_draft',
						$query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL),
					),
					$query->expr()->eq(
						'user_id',
						$query->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
					),
				),
			);
		} else {
			$query->where(
				$query->expr()->orX(
					$query->expr()->eq(
						'user_id',
						$query->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
					),
					$query->expr()->andX(
						$query->expr()->eq(
							'is_draft',
							$query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL),
						),
						$query->expr()->eq(
							'is_approved',
							$query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
						),
					),
				),
			);
		}
		$this->orderByLastChange($query);

		return $this->findEntities($query);
	}

	/**
	 * Returns the newest notes that are currently on the board, so callers such
	 * as the Dashboard widget never surface drafts, scheduled or archived ones.
	 *
	 * @return list<Note>
	 */
	public function findLatest(int $limit = 5, ?int $beforeCreatedAt = null): array {
		$query = $this->db->getQueryBuilder();
		$now = time();
		$query->select('*')
			->from($this->getTableName())
			->where(
				$query->expr()->eq(
					'is_draft',
					$query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL),
				),
			)
			->andWhere(
				$query->expr()->eq(
					'is_approved',
					$query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				),
			)
			// A note is on the board between its start and end date; both are
			// optional, and either one outside the window archives the note.
			->andWhere(
				$query->expr()->orX(
					$query->expr()->isNull('event_start'),
					$query->expr()->lte(
						'event_start',
						$query->createNamedParameter($now, IQueryBuilder::PARAM_INT),
					),
				),
			)
			->andWhere(
				$query->expr()->orX(
					$query->expr()->isNull('event_end'),
					$query->expr()->gt(
						'event_end',
						$query->createNamedParameter($now, IQueryBuilder::PARAM_INT),
					),
				),
			)
			->setMaxResults($limit);
		$this->orderByLastChange($query);

		if ($beforeCreatedAt !== null) {
			$query->andWhere(
				$query->expr()->lt(
					'created_at',
					$query->createNamedParameter($beforeCreatedAt, IQueryBuilder::PARAM_INT),
				),
			);
		}

		return $this->findEntities($query);
	}

	/**
	 * Disabling moderation publishes anything that was still waiting. This
	 * avoids old submissions becoming hidden again if the workflow is later
	 * re-enabled.
	 */
	public function approveAllPending(): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set(
				'is_approved',
				$query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
			)
			->where(
				$query->expr()->eq(
					'is_approved',
					$query->createNamedParameter(false, IQueryBuilder::PARAM_BOOL),
				),
			);
		$query->executeStatement();
	}
}
