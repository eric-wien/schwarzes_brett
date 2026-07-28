<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array{apiUrl: string, imageRouteTemplate: string} $_
 */

script('schwarzes_brett', 'main');
style('schwarzes_brett', 'main');
?>

<main
	id="schwarzes-brett"
	class="board"
	data-api-url="<?php p($_['apiUrl']); ?>"
	data-image-route-template="<?php p($_['imageRouteTemplate']); ?>">
	<header class="board__hero">
		<div class="board__hero-copy">
			<p class="board__eyebrow"><?php p($l->t('Shared with everyone')); ?></p>
			<h1><?php p($l->t('Schwarzes Brett')); ?></h1>
			<p class="board__intro"><?php p($l->t('News, events, offers, and everything worth sharing.')); ?></p>
		</div>
		<button id="board-add-note" class="board-button board-button--primary" type="button">
			<span aria-hidden="true">＋</span>
			<?php p($l->t('Post a note')); ?>
		</button>
	</header>

	<section class="board__toolbar" aria-label="<?php p($l->t('Filter notes')); ?>">
		<label class="board-search">
			<span class="visually-hidden"><?php p($l->t('Search notes')); ?></span>
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
			<input id="board-search" type="search" autocomplete="off" placeholder="<?php p($l->t('Search notes…')); ?>">
		</label>
		<label class="board-select">
			<span class="visually-hidden"><?php p($l->t('Category')); ?></span>
			<select id="board-category">
				<option value=""><?php p($l->t('All categories')); ?></option>
			</select>
		</label>
		<label class="board-select">
			<span class="visually-hidden"><?php p($l->t('Sort notes')); ?></span>
			<select id="board-sort">
				<option value="latest"><?php p($l->t('Newest first')); ?></option>
				<option value="upcoming"><?php p($l->t('Upcoming first')); ?></option>
			</select>
		</label>
		<p id="board-result-count" class="board__result-count" aria-live="polite"></p>
	</section>

	<div id="board-status" class="board-status" role="status" aria-live="polite">
		<span class="board-spinner" aria-hidden="true"></span>
		<?php p($l->t('Loading notes…')); ?>
	</div>

	<section id="board-grid" class="board-grid" aria-label="<?php p($l->t('Notes')); ?>"></section>

	<section id="board-empty" class="board-empty" hidden>
		<div class="board-empty__icon" aria-hidden="true">✦</div>
		<h2 id="board-empty-title"><?php p($l->t('The board is waiting for its first note')); ?></h2>
		<p id="board-empty-text"><?php p($l->t('Share an announcement, event, offer, or useful link.')); ?></p>
		<button id="board-empty-add" class="board-button board-button--secondary" type="button">
			<?php p($l->t('Post the first note')); ?>
		</button>
	</section>

	<div id="board-toast" class="board-toast" role="status" aria-live="polite" hidden></div>

	<dialog id="board-editor" class="board-dialog" aria-labelledby="board-dialog-title">
		<form id="board-form">
			<header class="board-dialog__header">
				<div>
					<p class="board__eyebrow"><?php p($l->t('For everyone to see')); ?></p>
					<h2 id="board-dialog-title"><?php p($l->t('Post a note')); ?></h2>
				</div>
				<button id="board-dialog-close" class="board-icon-button" type="button" aria-label="<?php p($l->t('Close')); ?>">×</button>
			</header>

			<div id="board-form-error" class="board-form-error" role="alert" hidden></div>
			<input id="board-note-id" type="hidden">

			<div class="board-field board-field--wide">
				<label for="board-title"><?php p($l->t('Title')); ?> <span aria-hidden="true">*</span></label>
				<input id="board-title" name="title" type="text" maxlength="255" required
					   placeholder="<?php p($l->t('What would you like to share?')); ?>">
			</div>

			<div class="board-field board-field--wide">
				<label for="board-content"><?php p($l->t('Description')); ?></label>
				<textarea id="board-content" name="content" rows="5" maxlength="10000"
						  placeholder="<?php p($l->t('Add the details people need to know…')); ?>"></textarea>
			</div>

			<div class="board-field board-field--wide">
				<label for="board-categories"><?php p($l->t('Categories')); ?></label>
				<input id="board-categories" name="categories" type="text"
					   placeholder="<?php p($l->t('Event, For sale, Neighbourhood')); ?>">
				<small><?php p($l->t('Separate categories with commas.')); ?></small>
			</div>

			<fieldset class="board-fieldset board-field--wide">
				<legend><?php p($l->t('When and where')); ?></legend>
				<div class="board-form-grid">
					<div class="board-field">
						<label for="board-event-start"><?php p($l->t('Starts')); ?></label>
						<input id="board-event-start" name="eventStart" type="datetime-local">
					</div>
					<div class="board-field">
						<label for="board-event-end"><?php p($l->t('Ends')); ?></label>
						<input id="board-event-end" name="eventEnd" type="datetime-local">
					</div>
					<div class="board-field board-field--wide">
						<label class="board-checkbox">
							<input id="board-all-day" name="isAllDay" type="checkbox">
							<span><?php p($l->t('All-day event')); ?></span>
						</label>
					</div>
					<div class="board-field board-field--wide">
						<label for="board-location"><?php p($l->t('Location')); ?></label>
						<input id="board-location" name="location" type="text" maxlength="255"
							   placeholder="<?php p($l->t('Room, address, or online')); ?>">
					</div>
				</div>
			</fieldset>

			<fieldset class="board-fieldset board-field--wide">
				<legend><?php p($l->t('Link')); ?></legend>
				<div class="board-form-grid">
					<div class="board-field">
						<label for="board-link-url"><?php p($l->t('Web address')); ?></label>
						<input id="board-link-url" name="linkUrl" type="url" maxlength="2048" inputmode="url"
							   placeholder="https://…">
					</div>
					<div class="board-field">
						<label for="board-link-label"><?php p($l->t('Link label')); ?></label>
						<input id="board-link-label" name="linkLabel" type="text" maxlength="255"
							   placeholder="<?php p($l->t('Learn more')); ?>">
					</div>
				</div>
			</fieldset>

			<div class="board-field board-field--wide">
				<label for="board-image"><?php p($l->t('Image')); ?></label>
				<div class="board-upload">
					<input id="board-image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
					<span><?php p($l->t('JPEG, PNG, GIF, or WebP · up to 5 MB')); ?></span>
				</div>
				<label id="board-remove-image-wrap" class="board-checkbox" hidden>
					<input id="board-remove-image" type="checkbox">
					<span><?php p($l->t('Remove the current image')); ?></span>
				</label>
			</div>

			<footer class="board-dialog__footer">
				<button id="board-delete-note" class="board-button board-button--danger" type="button" hidden>
					<?php p($l->t('Delete note')); ?>
				</button>
				<span class="board-dialog__spacer"></span>
				<button id="board-cancel" class="board-button board-button--quiet" type="button">
					<?php p($l->t('Cancel')); ?>
				</button>
				<button id="board-save" class="board-button board-button--primary" type="submit">
					<?php p($l->t('Publish note')); ?>
				</button>
			</footer>
		</form>
	</dialog>
</main>
