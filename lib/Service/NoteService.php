<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Service;

use OCA\SchwarzesBrett\Db\Note;
use OCA\SchwarzesBrett\Db\NoteMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IL10N;

final class NoteService {
	private const MAX_CATEGORIES = 12;

	public function __construct(
		private readonly NoteMapper $mapper,
		private readonly IL10N $l10n,
		private readonly ModerationService $moderationService,
	) {
	}

	/**
	 * @return list<Note>
	 */
	public function findAll(string $userId): array {
		return $this->mapper->findAll(
			$userId,
			$this->moderationService->isAdmin($userId),
			$this->moderationService->isModerator($userId),
		);
	}

	/**
	 * @return list<Note>
	 */
	public function findLatest(int $limit = 5, ?int $beforeCreatedAt = null): array {
		return $this->mapper->findLatest(max(1, min($limit, 100)), $beforeCreatedAt);
	}

	public function find(int $id): Note {
		try {
			return $this->mapper->find($id);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			throw new NoteNotFoundException($this->l10n->t('The note could not be found.'));
		}
	}

	/**
	 * @param list<mixed> $categories
	 */
	public function create(
		string $userId,
		string $title,
		string $content = '',
		array $categories = [],
		?int $eventStart = null,
		?int $eventEnd = null,
		bool $isAllDay = false,
		string $location = '',
		string $linkUrl = '',
		string $linkLabel = '',
		bool $isDraft = false,
	): Note {
		$values = $this->validate(
			$title,
			$content,
			$categories,
			$eventStart,
			$eventEnd,
			$isAllDay,
			$location,
			$linkUrl,
			$linkLabel,
			$isDraft,
		);

		$now = time();
		$note = new Note();
		$note->setUserId($userId);
		$this->applyValues($note, $values);
		$note->setIsApproved($isDraft || !$this->moderationService->isEnabled());
		$note->setCreatedAt($now);
		$note->setUpdatedAt($now);

		return $this->mapper->insert($note);
	}

	/**
	 * @param list<mixed> $categories
	 */
	public function update(
		int $id,
		string $userId,
		bool $isAdmin,
		string $title,
		string $content = '',
		array $categories = [],
		?int $eventStart = null,
		?int $eventEnd = null,
		bool $isAllDay = false,
		string $location = '',
		string $linkUrl = '',
		string $linkLabel = '',
		bool $isDraft = false,
	): Note {
		$note = $this->find($id);
		$this->assertCanManage($note, $userId, $isAdmin);
		$values = $this->validate(
			$title,
			$content,
			$categories,
			$eventStart,
			$eventEnd,
			$isAllDay,
			$location,
			$linkUrl,
			$linkLabel,
			$isDraft,
		);

		$this->applyValues($note, $values);
		// Publishing new or changed content goes back through moderation. Drafts
		// remain outside that workflow until their author submits them.
		$note->setIsApproved($isDraft || !$this->moderationService->isEnabled());
		// An edit re-posts the note: it returns to the top of the board and the
		// date it shows is the date of that edit, so both stamps move together.
		$now = time();
		$note->setCreatedAt($now);
		$note->setUpdatedAt($now);

		return $this->mapper->update($note);
	}

	public function delete(int $id, string $userId, bool $isAdmin): Note {
		$note = $this->find($id);
		$this->assertCanManage($note, $userId, $isAdmin);
		$this->mapper->delete($note);

		return $note;
	}

	public function approve(int $id, string $userId): Note {
		$note = $this->find($id);
		$this->assertCanRead($note, $userId);
		if (!$this->moderationService->canModerate($userId)) {
			throw new PermissionException($this->l10n->t('Only a moderator or administrator can approve notes.'));
		}
		if ($note->getIsDraft()) {
			throw new ValidationException($this->l10n->t('Drafts cannot be approved.'));
		}

		if (!$note->getIsApproved()) {
			$note->setIsApproved(true);
			// Approval is the moment the note reaches the board.
			$now = time();
			$note->setCreatedAt($now);
			$note->setUpdatedAt($now);
			$note = $this->mapper->update($note);
		}

		return $note;
	}

	public function approveAllPending(): void {
		$this->mapper->approveAllPending();
	}

	public function saveImageMetadata(
		Note $note,
		string $imageName,
		string $imageMime,
	): Note {
		$note->setImageName($imageName);
		$note->setImageMime($imageMime);
		$note->setUpdatedAt(time());

		return $this->mapper->update($note);
	}

	public function clearImageMetadata(Note $note): Note {
		$note->setImageName(null);
		$note->setImageMime(null);
		$note->setUpdatedAt(time());

		return $this->mapper->update($note);
	}

	/**
	 * Administrators can read everything. Drafts are otherwise private to their
	 * author, while submissions awaiting approval are also visible to moderators.
	 * Answering "not found" keeps private content undiscoverable.
	 */
	public function assertCanRead(Note $note, string $userId): void {
		if ($this->moderationService->isAdmin($userId) || $note->getUserId() === $userId) {
			return;
		}
		if (!$note->getIsDraft()
			&& ($note->getIsApproved() || $this->moderationService->isModerator($userId))) {
			return;
		}
		throw new NoteNotFoundException($this->l10n->t('The note could not be found.'));
	}

	public function assertCanManage(Note $note, string $userId, bool $isAdmin): void {
		if ($isAdmin) {
			return;
		}
		$this->assertCanRead($note, $userId);
		if ($note->getUserId() !== $userId) {
			throw new PermissionException($this->l10n->t('Only the author or an administrator can change this note.'));
		}
	}

	/**
	 * @param list<mixed> $categories
	 * @return array<string, mixed>
	 */
	private function validate(
		string $title,
		string $content,
		array $categories,
		?int $eventStart,
		?int $eventEnd,
		bool $isAllDay,
		string $location,
		string $linkUrl,
		string $linkLabel,
		bool $isDraft,
	): array {
		$title = trim($title);
		$content = trim($content);
		$location = trim($location);
		$linkUrl = trim($linkUrl);
		$linkLabel = trim($linkLabel);

		$this->assertLength($title, 1, 255, 'title', $this->l10n->t('Please enter a title.'));
		$this->assertLength($content, 0, 10_000, 'content', $this->l10n->t('The description is too long.'));
		$this->assertLength($location, 0, 255, 'location', $this->l10n->t('The location is too long.'));
		$this->assertLength($linkLabel, 0, 255, 'linkLabel', $this->l10n->t('The link label is too long.'));

		if (strlen($linkUrl) > 2048) {
			throw new ValidationException($this->l10n->t('The link is too long.'), 'linkUrl');
		}
		if ($linkUrl !== '') {
			$scheme = strtolower((string)parse_url($linkUrl, PHP_URL_SCHEME));
			if (!filter_var($linkUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
				throw new ValidationException($this->l10n->t('Enter a valid http or https link.'), 'linkUrl');
			}
		}

		if ($eventStart !== null && $eventStart < 0) {
			throw new ValidationException($this->l10n->t('The start date is invalid.'), 'eventStart');
		}
		// Both dates are independently optional: they bound the period in which
		// the note is on the board, so "hide after this date" is valid on its own.
		if ($eventStart !== null && $eventEnd !== null && $eventEnd < $eventStart) {
			throw new ValidationException($this->l10n->t('The end date must be after the start date.'), 'eventEnd');
		}

		$cleanCategories = [];
		foreach ($categories as $category) {
			if (!is_string($category)) {
				continue;
			}
			$category = trim($category);
			if ($category === '') {
				continue;
			}
			$this->assertLength($category, 1, 40, 'categories', $this->l10n->t('A category is too long.'));
			$cleanCategories[mb_strtolower($category)] = $category;
		}
		$cleanCategories = array_values($cleanCategories);
		if (count($cleanCategories) > self::MAX_CATEGORIES) {
			throw new ValidationException(
				$this->l10n->n(
					'Use no more than %n category.',
					'Use no more than %n categories.',
					self::MAX_CATEGORIES,
				),
				'categories',
			);
		}

		return [
			'title' => $title,
			'content' => $content,
			'categories' => $cleanCategories,
			'eventStart' => $eventStart,
			'eventEnd' => $eventEnd,
			'isAllDay' => $isAllDay,
			'location' => $location,
			'linkUrl' => $linkUrl,
			'linkLabel' => $linkLabel,
			'isDraft' => $isDraft,
		];
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function applyValues(Note $note, array $values): void {
		$encodedCategories = json_encode($values['categories'], JSON_THROW_ON_ERROR);
		$note->setTitle($values['title']);
		$note->setContent($values['content'] !== '' ? $values['content'] : null);
		$note->setCategories($encodedCategories !== '[]' ? $encodedCategories : null);
		$note->setEventStart($values['eventStart']);
		$note->setEventEnd($values['eventEnd']);
		$note->setIsAllDay($values['isAllDay']);
		$note->setLocation($values['location'] !== '' ? $values['location'] : null);
		$note->setLinkUrl($values['linkUrl'] !== '' ? $values['linkUrl'] : null);
		$note->setLinkLabel($values['linkLabel'] !== '' ? $values['linkLabel'] : null);
		$note->setIsDraft($values['isDraft']);
	}

	private function assertLength(
		string $value,
		int $min,
		int $max,
		string $field,
		string $message,
	): void {
		$length = mb_strlen($value);
		if ($length < $min || $length > $max) {
			throw new ValidationException($message, $field);
		}
	}
}
