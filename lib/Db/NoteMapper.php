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
	 * @return list<Note>
	 */
	public function findAll(): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('created_at', 'DESC')
			->addOrderBy('id', 'DESC');

		return $this->findEntities($query);
	}

	/**
	 * @return list<Note>
	 */
	public function findLatest(int $limit = 5, ?int $beforeCreatedAt = null): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('created_at', 'DESC')
			->addOrderBy('id', 'DESC')
			->setMaxResults($limit);

		if ($beforeCreatedAt !== null) {
			$query->where(
				$query->expr()->lt(
					'created_at',
					$query->createNamedParameter($beforeCreatedAt, IQueryBuilder::PARAM_INT),
				),
			);
		}

		return $this->findEntities($query);
	}
}
