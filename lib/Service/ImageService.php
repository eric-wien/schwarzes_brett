<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Service;

use OCA\SchwarzesBrett\Db\Note;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IL10N;

final class ImageService {
	public const MAX_SIZE = 5_242_880;
	private const FOLDER_NAME = 'note-images';
	private const ALLOWED_MIME_TYPES = [
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/webp',
	];

	public function __construct(
		private readonly IAppData $appData,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * @param array<string, mixed>|null $upload
	 * @return array{name: string, mime: string}
	 */
	public function store(?array $upload): array {
		if ($upload === null || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			throw new ValidationException($this->l10n->t('Choose an image to upload.'), 'image');
		}
		if ((int)($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			throw new ValidationException($this->l10n->t('The image upload did not complete.'), 'image');
		}

		$tmpName = $upload['tmp_name'] ?? null;
		$size = (int)($upload['size'] ?? 0);
		if (!is_string($tmpName) || $tmpName === '' || $size <= 0) {
			throw new ValidationException($this->l10n->t('The uploaded image is empty.'), 'image');
		}
		if ($size > self::MAX_SIZE) {
			throw new ValidationException($this->l10n->t('Images must be smaller than 5 MB.'), 'image');
		}

		$mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);
		if (!is_string($mime) || !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
			throw new ValidationException($this->l10n->t('Use a JPEG, PNG, GIF, or WebP image.'), 'image');
		}

		$content = file_get_contents($tmpName);
		if ($content === false || strlen($content) !== $size) {
			throw new ValidationException($this->l10n->t('The uploaded image could not be read.'), 'image');
		}

		$name = bin2hex(random_bytes(20));
		$file = $this->getFolder()->newFile($name);
		$file->putContent($content);

		return ['name' => $name, 'mime' => $mime];
	}

	/**
	 * @return array{content: string, mime: string}
	 */
	public function read(Note $note): array {
		$name = $note->getImageName();
		$mime = $note->getImageMime();
		if ($name === null || $mime === null) {
			throw new NoteNotFoundException($this->l10n->t('This note has no image.'));
		}

		try {
			$file = $this->getFolder()->getFile($name);
		} catch (NotFoundException) {
			throw new NoteNotFoundException($this->l10n->t('The image could not be found.'));
		}

		return ['content' => $file->getContent(), 'mime' => $mime];
	}

	public function delete(Note $note): void {
		$this->deleteByName($note->getImageName());
	}

	public function deleteByName(?string $name): void {
		if ($name === null) {
			return;
		}

		try {
			$this->getFolder()->getFile($name)->delete();
		} catch (NotFoundException) {
			// Missing files are already in the desired state.
		}
	}

	private function getFolder(): ISimpleFolder {
		try {
			return $this->appData->getFolder(self::FOLDER_NAME);
		} catch (NotFoundException) {
			return $this->appData->newFolder(self::FOLDER_NAME);
		}
	}
}
