<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Controller;

use OCA\SchwarzesBrett\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

final class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'main', [
			'apiUrl' => $this->urlGenerator->linkToRoute('schwarzes_brett.note.index'),
			'imageRouteTemplate' => $this->urlGenerator->linkToRoute(
				'schwarzes_brett.image.show',
				['id' => '__NOTE_ID__'],
			),
		]);
	}
}
