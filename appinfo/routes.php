<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'note#index', 'url' => '/api/notes', 'verb' => 'GET'],
		['name' => 'note#create', 'url' => '/api/notes', 'verb' => 'POST'],
		['name' => 'note#update', 'url' => '/api/notes/{id}', 'verb' => 'PUT'],
		['name' => 'note#destroy', 'url' => '/api/notes/{id}', 'verb' => 'DELETE'],
		['name' => 'image#upload', 'url' => '/api/notes/{id}/image', 'verb' => 'POST'],
		['name' => 'image#destroy', 'url' => '/api/notes/{id}/image', 'verb' => 'DELETE'],
		['name' => 'image#show', 'url' => '/notes/{id}/image', 'verb' => 'GET'],
	],
];
