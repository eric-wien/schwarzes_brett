<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Service;

use RuntimeException;

final class ValidationException extends RuntimeException {
	public function __construct(
		string $message,
		private readonly string $field = '',
	) {
		parent::__construct($message);
	}

	public function getField(): string {
		return $this->field;
	}
}
