<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Controller;

use OCA\SchwarzesBrett\Db\Note;
use OCA\SchwarzesBrett\Service\ImageService;
use OCA\SchwarzesBrett\Service\NoteNotFoundException;
use OCA\SchwarzesBrett\Service\NoteService;
use OCA\SchwarzesBrett\Service\PermissionException;
use OCA\SchwarzesBrett\Service\ValidationException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;

final class ImageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly NoteService $noteService,
		private readonly ImageService $imageService,
		private readonly IGroupManager $groupManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function upload(int $id): JSONResponse {
		$storedName = null;
		try {
			$note = $this->noteService->find($id);
			$this->noteService->assertCanManage($note, $this->userId, $this->isAdmin());
			$oldName = $note->getImageName();
			$stored = $this->imageService->store($this->request->getUploadedFile('image'));
			$storedName = $stored['name'];
			$note = $this->noteService->saveImageMetadata($note, $stored['name'], $stored['mime']);
			$this->imageService->deleteByName($oldName);

			return new JSONResponse([
				'imageUrl' => $this->urlGenerator->linkToRoute(
					'schwarzes_brett.image.show',
					['id' => $note->getId(), 'v' => $note->getUpdatedAt()],
				),
			]);
		} catch (ValidationException $exception) {
			$this->imageService->deleteByName($storedName);
			return $this->error($exception->getMessage(), 422, $exception->getField());
		} catch (NoteNotFoundException $exception) {
			$this->imageService->deleteByName($storedName);
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			$this->imageService->deleteByName($storedName);
			return $this->error($exception->getMessage(), 403);
		} catch (\Throwable $exception) {
			$this->imageService->deleteByName($storedName);
			throw $exception;
		}
	}

	#[NoAdminRequired]
	public function destroy(int $id): JSONResponse {
		try {
			$note = $this->noteService->find($id);
			$this->noteService->assertCanManage($note, $this->userId, $this->isAdmin());
			$this->imageService->delete($note);
			$this->noteService->clearImageMetadata($note);

			return new JSONResponse([], 204);
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		} catch (PermissionException $exception) {
			return $this->error($exception->getMessage(), 403);
		}
	}

	#[NoAdminRequired]
	public function show(int $id): DataDisplayResponse|JSONResponse {
		try {
			$image = $this->imageService->read($this->noteService->find($id));

			return new DataDisplayResponse($image['content'], 200, [
				'Content-Type' => $image['mime'],
				'Content-Disposition' => 'inline; filename="notice-image"',
				'Cache-Control' => 'private, max-age=3600',
				'X-Content-Type-Options' => 'nosniff',
			]);
		} catch (NoteNotFoundException $exception) {
			return $this->error($exception->getMessage(), 404);
		}
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
