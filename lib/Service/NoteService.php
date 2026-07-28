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

final class NoteService {
	private const MAX_CATEGORIES = 12;

	public function __construct(
		private readonly NoteMapper $mapper,
	) {
	}

	/**
	 * @return list<Note>
	 */
	public function findAll(): array {
		return $this->mapper->findAll();
	}

	/**
	 * @return list<Note>
	 */
	public function findLatest(int $limit = 5, ?int $beforeCreatedAt = null): array {
		return $this->mapper->findLatest(max(1, min($limit, 5)), $beforeCreatedAt);
	}

	public function find(int $id): Note {
		try {
			return $this->mapper->find($id);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			throw new NoteNotFoundException('The note could not be found.');
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
		);

		$now = time();
		$note = new Note();
		$note->setUserId($userId);
		$this->applyValues($note, $values);
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
		);

		$this->applyValues($note, $values);
		$note->setUpdatedAt(time());

		return $this->mapper->update($note);
	}

	public function delete(int $id, string $userId, bool $isAdmin): Note {
		$note = $this->find($id);
		$this->assertCanManage($note, $userId, $isAdmin);
		$this->mapper->delete($note);

		return $note;
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

	public function assertCanManage(Note $note, string $userId, bool $isAdmin): void {
		if ($note->getUserId() !== $userId && !$isAdmin) {
			throw new PermissionException('Only the author or an administrator can change this note.');
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
	): array {
		$title = trim($title);
		$content = trim($content);
		$location = trim($location);
		$linkUrl = trim($linkUrl);
		$linkLabel = trim($linkLabel);

		$this->assertLength($title, 1, 255, 'title', 'Please enter a title.');
		$this->assertLength($content, 0, 10_000, 'content', 'The description is too long.');
		$this->assertLength($location, 0, 255, 'location', 'The location is too long.');
		$this->assertLength($linkLabel, 0, 255, 'linkLabel', 'The link label is too long.');

		if (strlen($linkUrl) > 2048) {
			throw new ValidationException('The link is too long.', 'linkUrl');
		}
		if ($linkUrl !== '') {
			$scheme = strtolower((string)parse_url($linkUrl, PHP_URL_SCHEME));
			if (!filter_var($linkUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
				throw new ValidationException('Enter a valid http or https link.', 'linkUrl');
			}
		}

		if ($eventStart !== null && $eventStart < 0) {
			throw new ValidationException('The start date is invalid.', 'eventStart');
		}
		if ($eventEnd !== null && $eventStart === null) {
			throw new ValidationException('Choose a start date before an end date.', 'eventStart');
		}
		if ($eventStart !== null && $eventEnd !== null && $eventEnd < $eventStart) {
			throw new ValidationException('The end date must be after the start date.', 'eventEnd');
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
			$this->assertLength($category, 1, 40, 'categories', 'A category is too long.');
			$cleanCategories[mb_strtolower($category)] = $category;
		}
		$cleanCategories = array_values($cleanCategories);
		if (count($cleanCategories) > self::MAX_CATEGORIES) {
			throw new ValidationException('Use no more than 12 categories.', 'categories');
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
