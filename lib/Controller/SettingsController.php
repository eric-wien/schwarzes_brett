<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Controller;

use OCA\SchwarzesBrett\Service\ModerationService;
use OCA\SchwarzesBrett\Service\NoteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Deliberately has no NoAdminRequired attribute: Nextcloud restricts this route
 * to administrators before the action runs.
 */
final class SettingsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ModerationService $moderationService,
		private readonly NoteService $noteService,
	) {
		parent::__construct($appName, $request);
	}

	public function index(): JSONResponse {
		return new JSONResponse([
			'enabled' => $this->moderationService->isEnabled(),
			'moderators' => $this->moderationService->getModeratorIds(),
		]);
	}

	/**
	 * @param list<mixed> $moderators
	 */
	public function update(bool $enabled = false, array $moderators = []): JSONResponse {
		if (!$enabled) {
			$this->noteService->approveAllPending();
		}
		$this->moderationService->save($enabled, $moderators);

		return new JSONResponse([
			'enabled' => $enabled,
			'moderators' => $this->moderationService->getModeratorIds(),
		]);
	}
}
