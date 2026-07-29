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
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Util;

/**
 * The widget renders its own list through js/dashboard.js, because a note needs
 * more than the title and single subtitle line that Nextcloud's built-in widget
 * rendering offers - it also shows three clamped lines of the description and
 * the note's link.
 *
 * Only the version 1 item API is implemented on purpose: the Dashboard front-end
 * takes over rendering for widgets that announce version 2, which would drop the
 * richer layout. Version 1 keeps the items available to the mobile and desktop
 * clients.
 */
final class BoardWidget implements IAPIWidget, IButtonWidget, IIconWidget {
	public const ITEM_LIMIT = 5;

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
		return 'icon-schwarzes-brett';
	}

	#[\Override]
	public function getIconUrl(): string {
		// The black variant: consumers render it on a normal surface and apply
		// --background-invert-if-dark, which whitens it in the dark themes.
		// app.svg is the white variant the app menu needs on the dark header.
		$iconUrl = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg'),
		);

		// Unlike scripts and styles, this URL does not receive Nextcloud's
		// cachebuster automatically. The revision prevents an older white copy
		// of app-dark.svg from being inverted into black in the dark theme.
		return $iconUrl . '?v=2';
	}

	#[\Override]
	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('schwarzes_brett.page.index');
	}

	#[\Override]
	public function load(): void {
		// The third argument makes Nextcloud emit this script after the
		// Dashboard app's own bundle, so OCA.Dashboard already exists.
		Util::addScript(Application::APP_ID, 'dashboard', 'dashboard');
		Util::addStyle(Application::APP_ID, 'dashboard');
	}

	/**
	 * @return list<WidgetItem>
	 */
	#[\Override]
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$notes = $this->noteService->findLatest(min($limit, self::ITEM_LIMIT));

		return array_map(
			fn (Note $note): WidgetItem => $this->toWidgetItem($note),
			$notes,
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

	private function toWidgetItem(Note $note): WidgetItem {
		$author = $this->userManager->get($note->getUserId())?->getDisplayName() ?? $note->getUserId();
		// The event dates only decide whether a note is on the board or in the
		// archive; they are deliberately not part of what a note displays.
		$subtitleParts = [$author];
		if ($note->getLocation() !== null && $note->getLocation() !== '') {
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
			(string)max($note->getUpdatedAt(), $note->getCreatedAt()),
		);
	}
}
