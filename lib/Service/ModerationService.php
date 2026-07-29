<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Service;

use OCA\SchwarzesBrett\AppInfo\Application;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

final class ModerationService {
	private const ENABLED_KEY = 'approval_enabled';
	private const MODERATORS_KEY = 'moderator_user_ids';

	public function __construct(
		private readonly IConfig $config,
		private readonly IGroupManager $groupManager,
		private readonly IUserManager $userManager,
	) {
	}

	public function isEnabled(): bool {
		return $this->config->getAppValue(Application::APP_ID, self::ENABLED_KEY, '0') === '1';
	}

	/**
	 * @return list<string>
	 */
	public function getModeratorIds(): array {
		$decoded = json_decode(
			$this->config->getAppValue(Application::APP_ID, self::MODERATORS_KEY, '[]'),
			true,
		);
		if (!is_array($decoded)) {
			return [];
		}

		return array_values(array_filter(
			$decoded,
			static fn (mixed $userId): bool => is_string($userId) && $userId !== '',
		));
	}

	public function isAdmin(string $userId): bool {
		return $this->groupManager->isAdmin($userId);
	}

	public function isModerator(string $userId): bool {
		return in_array($userId, $this->getModeratorIds(), true);
	}

	public function canModerate(string $userId): bool {
		return $this->isAdmin($userId) || $this->isModerator($userId);
	}

	/**
	 * @param list<mixed> $moderatorIds
	 */
	public function save(bool $enabled, array $moderatorIds): void {
		$validIds = [];
		foreach ($moderatorIds as $userId) {
			if (!is_string($userId) || $userId === '' || !$this->userManager->userExists($userId)) {
				continue;
			}
			$validIds[$userId] = $userId;
		}

		$this->config->setAppValue(
			Application::APP_ID,
			self::MODERATORS_KEY,
			json_encode(array_values($validIds), JSON_THROW_ON_ERROR),
		);
		$this->config->setAppValue(
			Application::APP_ID,
			self::ENABLED_KEY,
			$enabled ? '1' : '0',
		);
	}
}
