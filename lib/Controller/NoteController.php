<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Controller;

use OCA\SchwarzesBrett\Db\Note;
use OCA\SchwarzesBrett\Service\ImageService;
use OCA\SchwarzesBrett\Service\ModerationService;
use OCA\SchwarzesBrett\Service\NoteNotFoundException;
use OCA\SchwarzesBrett\Service\NoteService;
use OCA\SchwarzesBrett\Service\PermissionException;
use OCA\SchwarzesBrett\Service\ValidationException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

final class NoteController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly NoteService $noteService,
		private readonly ImageService $imageService,
		private readonly IGroupManager $groupManager,
		private readonly IUserManager $userManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly ModerationService $moderationService,
		private readonly string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function approve(int $id): JSONResponse {
		try {
			$note = $this->noteService->approve($id, $this->userId);

			return new JSONResponse(['note' => $this->serialize($note)]);
		} catch (ValidationException $exception) {
			return $this->error($exception->getMessage(), 422, $exception->getField());
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	#[NoAdminRequired]
	public function archive(int $id): JSONResponse {
		try {
			$note = $this->noteService->archive($id, $this->userId);

			return new JSONResponse(['note' => $this->serialize($note)]);
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	#[NoAdminRequired]
	public function unarchive(int $id): JSONResponse {
		try {
			$note = $this->noteService->unarchive($id, $this->userId);

			return new JSONResponse(['note' => $this->serialize($note)]);
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	/**
	 * @param int|null $limit Return only the newest notes; omit for the full board.
	 */
	#[NoAdminRequired]
	public function index(?int $limit = null): JSONResponse {
		$notes = array_map(
			fn (Note $note): array => $this->serialize($note),
			$limit !== null && $limit > 0
				? $this->noteService->findLatest(min($limit, 100))
				: $this->noteService->findAll($this->userId),
		);

		return new JSONResponse(['notes' => $notes]);
	}

	/**
	 * @param list<mixed> $categories
	 */
	#[NoAdminRequired]
	public function create(
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
	): JSONResponse {
		try {
			$note = $this->noteService->create(
				$this->userId,
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

			return new JSONResponse(['note' => $this->serialize($note)], 201);
		} catch (ValidationException $exception) {
			return $this->error($exception->getMessage(), 422, $exception->getField());
		}
	}

	/**
	 * @param list<mixed> $categories
	 */
	#[NoAdminRequired]
	public function update(
		int $id,
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
	): JSONResponse {
		try {
			$note = $this->noteService->update(
				$id,
				$this->userId,
				$this->isAdmin(),
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

			return new JSONResponse(['note' => $this->serialize($note)]);
		} catch (ValidationException $exception) {
			return $this->error($exception->getMessage(), 422, $exception->getField());
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	#[NoAdminRequired]
	public function destroy(int $id): JSONResponse {
		try {
			$note = $this->noteService->delete($id, $this->userId, $this->isAdmin());
			$this->imageService->delete($note);

			return new JSONResponse([], 204);
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serialize(Note $note): array {
		$data = $note->jsonSerialize();
		$author = $this->userManager->get($note->getUserId());
		$isInArchive = $this->noteService->isInArchive($note);
		$canChangeArchiveState = $note->getUserId() === $this->userId
			|| $this->moderationService->canModerate($this->userId);
		$data['authorName'] = $author?->getDisplayName() ?? $note->getUserId();
		$data['canEdit'] = $note->getUserId() === $this->userId || $this->isAdmin();
		$data['canApprove'] = !$isInArchive
			&& !$note->getIsDraft()
			&& !$note->getIsApproved()
			&& $this->moderationService->canModerate($this->userId);
		$data['canArchive'] = !$isInArchive && $canChangeArchiveState;
		$data['canUnarchive'] = $isInArchive && $canChangeArchiveState;
		$data['imageUrl'] = $note->getImageName() !== null
			? $this->urlGenerator->linkToRoute(
				'schwarzes_brett.image.show',
				['id' => $note->getId(), 'v' => $note->getUpdatedAt()],
			)
			: null;

		return $data;
	}

	private function isAdmin(): bool {
		return $this->groupManager->isAdmin($this->userId);
	}

	private function error(string $message, int $status, string $field = ''): JSONResponse {
		return new JSONResponse([
			'error' => [
				'message' => $message,
				'field' => $field,
			],
		], $status);
	}
}
