<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SchwarzesBrett\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string|null getContent()
 * @method void setContent(?string $content)
 * @method string|null getCategories()
 * @method void setCategories(?string $categories)
 * @method int|null getEventStart()
 * @method void setEventStart(?int $eventStart)
 * @method int|null getEventEnd()
 * @method void setEventEnd(?int $eventEnd)
 * @method bool getIsAllDay()
 * @method void setIsAllDay(bool $isAllDay)
 * @method string|null getLocation()
 * @method void setLocation(?string $location)
 * @method string|null getLinkUrl()
 * @method void setLinkUrl(?string $linkUrl)
 * @method string|null getLinkLabel()
 * @method void setLinkLabel(?string $linkLabel)
 * @method string|null getImageName()
 * @method void setImageName(?string $imageName)
 * @method string|null getImageMime()
 * @method void setImageMime(?string $imageMime)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
final class Note extends Entity implements JsonSerializable {
	protected string $userId = '';
	protected string $title = '';
	protected ?string $content = null;
	protected ?string $categories = null;
	protected ?int $eventStart = null;
	protected ?int $eventEnd = null;
	protected bool $isAllDay = false;
	protected ?string $location = null;
	protected ?string $linkUrl = null;
	protected ?string $linkLabel = null;
	protected ?string $imageName = null;
	protected ?string $imageMime = null;
	protected int $createdAt = 0;
	protected int $updatedAt = 0;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('eventStart', 'integer');
		$this->addType('eventEnd', 'integer');
		$this->addType('isAllDay', 'boolean');
		$this->addType('createdAt', 'integer');
		$this->addType('updatedAt', 'integer');
	}

	/**
	 * The controller adds request-specific fields such as image URLs and permissions.
	 *
	 * @return array<string, mixed>
	 */
	#[\Override]
	public function jsonSerialize(): array {
		$decodedCategories = json_decode($this->categories ?? '[]', true);

		return [
			'id' => $this->getId(),
			'userId' => $this->userId,
			'title' => $this->title,
			'content' => $this->content ?? '',
			'categories' => is_array($decodedCategories) ? $decodedCategories : [],
			'eventStart' => $this->eventStart,
			'eventEnd' => $this->eventEnd,
			'isAllDay' => $this->isAllDay,
			'location' => $this->location ?? '',
			'linkUrl' => $this->linkUrl ?? '',
			'linkLabel' => $this->linkLabel ?? '',
			'hasImage' => $this->imageName !== null,
			'imageMime' => $this->imageMime,
			'createdAt' => $this->createdAt,
			'updatedAt' => $this->updatedAt,
		];
	}
}
