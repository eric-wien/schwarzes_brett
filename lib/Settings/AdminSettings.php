<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Settings;

use OCA\SchwarzesBrett\AppInfo\Application;
use OCA\SchwarzesBrett\Service\ModerationService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Settings\ISettings;

final class AdminSettings implements ISettings {
	public function __construct(
		private readonly ModerationService $moderationService,
		private readonly IUserManager $userManager,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$users = array_values(array_filter(
			$this->userManager->search('', 1000),
			static fn (IUser $user): bool => $user->isEnabled(),
		));
		usort(
			$users,
			static fn (IUser $left, IUser $right): int => strcasecmp(
				$left->getDisplayName(),
				$right->getDisplayName(),
			),
		);

		return new TemplateResponse(Application::APP_ID, 'admin', [
			'enabled' => $this->moderationService->isEnabled(),
			'moderatorIds' => $this->moderationService->getModeratorIds(),
			'users' => array_map(
				static fn (IUser $user): array => [
					'id' => $user->getUID(),
					'name' => $user->getDisplayName(),
				],
				$users,
			),
			'saveUrl' => $this->urlGenerator->linkToRoute('schwarzes_brett.settings.update'),
		], TemplateResponse::RENDER_AS_BLANK);
	}

	#[\Override]
	public function getSection(): string {
		return 'additional';
	}

	#[\Override]
	public function getPriority(): int {
		return 40;
	}
}
