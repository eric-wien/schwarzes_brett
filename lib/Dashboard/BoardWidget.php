<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Dashboard;

use OCA\SchwarzesBrett\AppInfo\Application;
use OCA\SchwarzesBrett\Db\Note;
use OCA\SchwarzesBrett\Service\NoteService;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IReloadableWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;

final class BoardWidget implements IButtonWidget, IIconWidget, IReloadableWidget {
	private const ITEM_LIMIT = 5;

	public function __construct(
		private readonly NoteService $noteService,
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
		private readonly IUserManager $userManager,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('Schwarzes Brett');
	}

	#[\Override]
	public function getOrder(): int {
		return 30;
	}

	#[\Override]
	public function getIconClass(): string {
		return '';
	}

	#[\Override]
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg'),
		);
	}

	#[\Override]
	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('schwarzes_brett.page.index');
	}

	#[\Override]
	public function load(): void {
		// IAPIWidgetV2 is rendered by Nextcloud and needs no custom JavaScript.
	}

	#[\Override]
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
		$notes = $this->noteService->findLatest(self::ITEM_LIMIT);
		$items = array_map(
			fn (Note $note): WidgetItem => $this->toWidgetItem($note),
			$notes,
		);

		return new WidgetItems(
			$items,
			$items === [] ? $this->l10n->t('No notes have been posted yet.') : '',
		);
	}

	#[\Override]
	public function getWidgetButtons(string $userId): array {
		return [
			new WidgetButton(
				WidgetButton::TYPE_MORE,
				$this->urlGenerator->linkToRouteAbsolute('schwarzes_brett.page.index'),
				$this->l10n->t('Open the notice board'),
			),
		];
	}

	#[\Override]
	public function getReloadInterval(): int {
		return 60;
	}

	private function toWidgetItem(Note $note): WidgetItem {
		$author = $this->userManager->get($note->getUserId())?->getDisplayName() ?? $note->getUserId();
		$subtitleParts = [$author];
		if ($note->getEventStart() !== null) {
			$subtitleParts[] = $this->formatDate($note->getEventStart(), $note->getIsAllDay());
		}
		if ($note->getLocation() !== null) {
			$subtitleParts[] = $note->getLocation();
		}

		$iconUrl = $note->getImageName() !== null
			? $this->urlGenerator->linkToRouteAbsolute(
				'schwarzes_brett.image.show',
				['id' => $note->getId(), 'v' => $note->getUpdatedAt()],
			)
			: $this->getIconUrl();

		return new WidgetItem(
			$note->getTitle(),
			implode(' · ', $subtitleParts),
			$this->getUrl() . '#note-' . $note->getId(),
			$iconUrl,
			(string)$note->getCreatedAt(),
		);
	}

	private function formatDate(int $timestamp, bool $allDay): string {
		return $allDay
			? date('Y-m-d', $timestamp)
			: date('Y-m-d H:i', $timestamp);
	}
}
