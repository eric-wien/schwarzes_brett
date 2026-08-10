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

<svg class="sb-sprite" aria-hidden="true" focusable="false" width="0" height="0">
	<defs>
		<symbol id="sb-i-board" viewBox="0 0 24 24">
			<path fill-rule="evenodd" d="M4.5 3h15A2.5 2.5 0 0 1 22 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 18.5v-13A2.5 2.5 0 0 1 4.5 3Zm.4 1.8A1.1 1.1 0 0 0 3.8 5.9v12.2c0 .61.49 1.1 1.1 1.1h14.2c.61 0 1.1-.49 1.1-1.1V5.9c0-.61-.49-1.1-1.1-1.1Z"/>
			<path d="m5.37 7.18 5.86-.72.6 4.86-5.86.72z"/>
			<path d="m12.8 12.07 5.47.58-.47 4.48-5.47-.58z"/>
		</symbol>
		<symbol id="sb-i-plus" viewBox="0 0 24 24">
			<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/>
		</symbol>
		<symbol id="sb-i-search" viewBox="0 0 24 24">
			<path d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.61-.59 3.09-1.56 4.23l.27.27h.79l5 5-1.5 1.5-5-5v-.79l-.27-.27A6.52 6.52 0 0 1 9.5 16 6.5 6.5 0 0 1 3 9.5 6.5 6.5 0 0 1 9.5 3Zm0 2C7 5 5 7 5 9.5S7 14 9.5 14 14 12 14 9.5S12 5 9.5 5Z"/>
		</symbol>
		<symbol id="sb-i-close" viewBox="0 0 24 24">
			<path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
		</symbol>
		<symbol id="sb-i-pencil" viewBox="0 0 24 24">
			<path d="M20.71 7.04c.39-.39.39-1.04 0-1.41l-2.34-2.34c-.37-.39-1.02-.39-1.41 0l-1.84 1.83 3.75 3.75zM3 17.25V21h3.75L17.81 9.93l-3.75-3.75z"/>
		</symbol>
		<symbol id="sb-i-clock" viewBox="0 0 24 24">
			<path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16m0-18a10 10 0 0 1 0 20C6.47 22 2 17.5 2 12A10 10 0 0 1 12 2m.5 5v5.25l4.5 2.67-.75 1.23L11 13V7z"/>
		</symbol>
		<symbol id="sb-i-marker" viewBox="0 0 24 24">
			<path d="M12 11.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7"/>
		</symbol>
		<symbol id="sb-i-link" viewBox="0 0 24 24">
			<path d="M14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3zm5 16H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2z"/>
		</symbol>
		<symbol id="sb-i-account" viewBox="0 0 24 24">
			<path d="M12 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4"/>
		</symbol>
		<symbol id="sb-i-delete" viewBox="0 0 24 24">
			<path d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14zM6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6z"/>
		</symbol>
		<symbol id="sb-i-archive" viewBox="0 0 24 24">
			<path d="M20.54 5.23 19.15 3.55A1.5 1.5 0 0 0 18 3H6a1.5 1.5 0 0 0-1.15.55L3.46 5.23A1.98 1.98 0 0 0 3 6.5V19a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.5c0-.46-.16-.9-.46-1.27M6.24 5h11.52l.83 1H5.41zM5 19V8h14v11zm4-9h6v2H9z"/>
		</symbol>
		<symbol id="sb-i-restore" viewBox="0 0 24 24">
			<path d="M13 3a9 9 0 0 0-9 9H1l4 4 4-4H6a7 7 0 1 1 2.05 4.95l-1.42 1.42A9 9 0 1 0 13 3"/>
		</symbol>
		<symbol id="sb-i-image" viewBox="0 0 24 24">
			<path d="M8.5 13.5 11 16.5l3.5-4.5 4.5 6H5zM21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2"/>
		</symbol>
		<symbol id="sb-i-alert" viewBox="0 0 24 24">
			<path d="M13 13h-2V7h2m0 10h-2v-2h2M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2"/>
		</symbol>
		<symbol id="sb-i-check" viewBox="0 0 24 24">
			<path d="m9 16.17-4.17-4.17-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
		</symbol>
	</defs>
</svg>

<main
	id="schwarzes-brett"
	class="sb"
	data-api-url="<?php p($_['apiUrl']); ?>"
	data-image-route-template="<?php p($_['imageRouteTemplate']); ?>">

	<div class="sb__header">
		<div class="sb__header-inner">
			<div class="sb-title">
				<span class="sb-title__mark" aria-hidden="true">
					<svg viewBox="0 0 24 24"><use href="#sb-i-board"/></svg>
				</span>
				<span class="sb-title__text">
					<h1 class="sb-title__name"><?php p($l->t('Schwarzes Brett')); ?></h1>
					<span class="sb-title__claim"><?php p($l->t('News, events, offers, and everything worth sharing.')); ?></span>
				</span>
			</div>
			<button id="board-add-note" class="sb-button sb-button--primary" type="button">
				<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-plus"/></svg>
				<span><?php p($l->t('New note')); ?></span>
			</button>
		</div>

		<div class="sb-tabs" role="tablist" aria-label="<?php p($l->t('Notes')); ?>">
			<button id="board-tab-board" class="sb-tab" type="button" role="tab"
					aria-selected="true" data-view="board">
				<span><?php p($l->t('Board')); ?></span>
				<span id="board-tab-board-count" class="sb-tab__count"></span>
			</button>
			<button id="board-tab-pending" class="sb-tab" type="button" role="tab"
					aria-selected="false" data-view="pending" hidden>
				<span><?php p($l->t('Drafts & pending')); ?></span>
				<span id="board-tab-pending-count" class="sb-tab__count"></span>
			</button>
			<button id="board-tab-archive" class="sb-tab" type="button" role="tab"
					aria-selected="false" data-view="archive" hidden>
				<span><?php p($l->t('Archive')); ?></span>
				<span id="board-tab-archive-count" class="sb-tab__count"></span>
			</button>
		</div>

		<div class="sb-toolbar" role="search">
			<div class="sb-field sb-field--search">
				<svg class="sb-field__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-search"/></svg>
				<label class="sb-visually-hidden" for="board-search"><?php p($l->t('Search notes')); ?></label>
				<input id="board-search" type="search" autocomplete="off"
					   placeholder="<?php p($l->t('Search notes…')); ?>">
			</div>
			<div class="sb-field sb-field--select">
				<label class="sb-visually-hidden" for="board-category"><?php p($l->t('Category')); ?></label>
				<select id="board-category">
					<option value=""><?php p($l->t('All categories')); ?></option>
				</select>
			</div>
			<p id="board-result-count" class="sb-toolbar__count" aria-live="polite"></p>
		</div>
	</div>

	<div class="sb__body">
		<div id="board-status" class="sb-status" role="status" aria-live="polite">
			<span class="sb-spinner" aria-hidden="true"></span>
			<span><?php p($l->t('Loading notes…')); ?></span>
		</div>

		<section id="board-grid" class="sb-grid" aria-label="<?php p($l->t('Notes')); ?>"></section>

		<section id="board-empty" class="sb-empty" hidden>
			<span class="sb-empty__mark" aria-hidden="true">
				<svg viewBox="0 0 24 24"><use href="#sb-i-board"/></svg>
			</span>
			<h2 id="board-empty-title"><?php p($l->t('The board is still empty')); ?></h2>
			<p id="board-empty-text"><?php p($l->t('Share an announcement, an event, an offer, or a useful link.')); ?></p>
			<button id="board-empty-add" class="sb-button sb-button--secondary" type="button">
				<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-plus"/></svg>
				<span><?php p($l->t('Post the first note')); ?></span>
			</button>
		</section>
	</div>

	<div id="board-toast" class="sb-toast" role="status" aria-live="polite" hidden></div>

	<dialog id="board-viewer" class="sb-dialog sb-dialog--view" aria-labelledby="board-view-title">
		<div class="sb-dialog__form">
			<header class="sb-dialog__header">
				<h2 id="board-view-title" class="sb-dialog__title sb-view__title"></h2>
				<button id="board-view-close" class="sb-icon-button" type="button"
						aria-label="<?php p($l->t('Close')); ?>">
					<svg aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-close"/></svg>
				</button>
			</header>

			<div class="sb-dialog__scroll">
				<figure id="board-view-figure" class="sb-view__figure" hidden>
					<img id="board-view-image" class="sb-view__image" alt="">
				</figure>
				<span id="board-view-badge" class="sb-badge sb-view__badge" hidden></span>
				<div id="board-view-tags" class="sb-card__tags sb-view__tags" hidden></div>
				<p id="board-view-text" class="sb-view__text" hidden></p>
				<div id="board-view-meta" class="sb-view__meta"></div>
			</div>

			<footer class="sb-dialog__footer">
				<span id="board-view-author" class="sb-view__author"></span>
				<span class="sb-dialog__spacer"></span>
				<button id="board-view-approve" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-check"/></svg>
					<span><?php p($l->t('Approve')); ?></span>
				</button>
				<button id="board-view-edit" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-pencil"/></svg>
					<span><?php p($l->t('Edit note')); ?></span>
				</button>
				<button id="board-view-archive" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-archive"/></svg>
					<span><?php p($l->t('Archive note')); ?></span>
				</button>
				<button id="board-view-unarchive" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-restore"/></svg>
					<span><?php p($l->t('Restore note')); ?></span>
				</button>
				<button id="board-view-dismiss" class="sb-button sb-button--primary" type="button">
					<?php p($l->t('Close')); ?>
				</button>
			</footer>
		</div>
	</dialog>

	<dialog id="board-editor" class="sb-dialog" aria-labelledby="board-dialog-title">
		<form id="board-form" class="sb-dialog__form">
			<header class="sb-dialog__header">
				<h2 id="board-dialog-title" class="sb-dialog__title"><?php p($l->t('New note')); ?></h2>
				<button id="board-dialog-close" class="sb-icon-button" type="button"
						aria-label="<?php p($l->t('Close')); ?>">
					<svg aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-close"/></svg>
				</button>
			</header>

			<div class="sb-dialog__scroll">
				<div id="board-form-error" class="sb-alert" role="alert" hidden>
					<svg class="sb-alert__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-alert"/></svg>
					<span id="board-form-error-text"></span>
				</div>

				<div class="sb-form">
					<div class="sb-input sb-input--full">
						<label for="board-title"><?php p($l->t('Title')); ?> <abbr class="sb-required" title="<?php p($l->t('Required')); ?>">*</abbr></label>
						<input id="board-title" name="title" type="text" maxlength="255" required
							   placeholder="<?php p($l->t('What would you like to share?')); ?>">
					</div>

					<div class="sb-input sb-input--full">
						<label for="board-content"><?php p($l->t('Description')); ?></label>
						<textarea id="board-content" name="content" rows="5" maxlength="10000"
								  placeholder="<?php p($l->t('Add the details people need to know…')); ?>"></textarea>
					</div>

					<div class="sb-input sb-input--full">
						<label for="board-categories"><?php p($l->t('Categories')); ?></label>
						<div class="sb-combo">
							<input id="board-categories" name="categories" type="text"
								   role="combobox" aria-expanded="false" aria-autocomplete="list"
								   aria-controls="board-category-options" autocomplete="off"
								   placeholder="<?php p($l->t('Event, For sale, Neighbourhood')); ?>">
							<ul id="board-category-options" class="sb-combo__list" role="listbox"
								aria-label="<?php p($l->t('Existing categories')); ?>" hidden></ul>
						</div>
						<small><?php p($l->t('Separate categories with commas. Existing categories are suggested while you type.')); ?></small>
					</div>

					<fieldset class="sb-group sb-input--full">
						<legend>
							<svg aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-clock"/></svg>
							<?php p($l->t('When and where')); ?>
						</legend>
						<div class="sb-form">
							<div class="sb-input">
								<label for="board-event-start"><?php p($l->t('Starts')); ?></label>
								<input id="board-event-start" name="eventStart" type="datetime-local">
							</div>
							<div class="sb-input">
								<label for="board-event-end"><?php p($l->t('Ends')); ?></label>
								<input id="board-event-end" name="eventEnd" type="datetime-local">
							</div>
							<div class="sb-input sb-input--full">
								<label class="sb-checkbox">
									<input id="board-all-day" name="isAllDay" type="checkbox">
									<span><?php p($l->t('All-day event')); ?></span>
								</label>
							</div>
							<div class="sb-input sb-input--full">
								<label for="board-location"><?php p($l->t('Location')); ?></label>
								<input id="board-location" name="location" type="text" maxlength="255"
									   placeholder="<?php p($l->t('Room, address, or online')); ?>">
							</div>
						</div>
					</fieldset>

					<fieldset class="sb-group sb-input--full">
						<legend>
							<svg aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-link"/></svg>
							<?php p($l->t('Link')); ?>
						</legend>
						<div class="sb-form">
							<div class="sb-input">
								<label for="board-link-url"><?php p($l->t('Web address')); ?></label>
								<input id="board-link-url" name="linkUrl" type="url" maxlength="2048" inputmode="url"
									   placeholder="https://…">
							</div>
							<div class="sb-input">
								<label for="board-link-label"><?php p($l->t('Link label')); ?></label>
								<input id="board-link-label" name="linkLabel" type="text" maxlength="255"
									   placeholder="<?php p($l->t('Learn more')); ?>">
							</div>
						</div>
					</fieldset>

					<fieldset class="sb-group sb-input--full">
						<legend>
							<svg aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-image"/></svg>
							<?php p($l->t('Image')); ?>
						</legend>
						<div class="sb-upload">
							<input id="board-image" name="image" type="file" class="sb-upload__input"
								   accept="image/jpeg,image/png,image/gif,image/webp">
							<label for="board-image" class="sb-button sb-button--secondary sb-upload__trigger">
								<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-image"/></svg>
								<span><?php p($l->t('Choose an image')); ?></span>
							</label>
							<span id="board-image-name" class="sb-upload__hint"><?php p($l->t('JPEG, PNG, GIF, or WebP · up to 5 MB')); ?></span>
						</div>
						<label id="board-remove-image-wrap" class="sb-checkbox" hidden>
							<input id="board-remove-image" type="checkbox">
							<span><?php p($l->t('Remove the current image')); ?></span>
						</label>
					</fieldset>
				</div>
			</div>

			<footer class="sb-dialog__footer">
				<button id="board-delete-note" class="sb-button sb-button--danger" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-delete"/></svg>
					<span><?php p($l->t('Delete')); ?></span>
				</button>
				<button id="board-archive-note" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-archive"/></svg>
					<span><?php p($l->t('Archive note')); ?></span>
				</button>
				<button id="board-unarchive-note" class="sb-button sb-button--secondary" type="button" hidden>
					<svg class="sb-button__icon" aria-hidden="true" viewBox="0 0 24 24"><use href="#sb-i-restore"/></svg>
					<span><?php p($l->t('Restore note')); ?></span>
				</button>
				<span class="sb-dialog__spacer"></span>
				<button id="board-cancel" class="sb-button sb-button--tertiary" type="button">
					<?php p($l->t('Cancel')); ?>
				</button>
				<button id="board-save-draft" class="sb-button sb-button--secondary" type="button">
					<?php p($l->t('Save as draft')); ?>
				</button>
				<button id="board-save" class="sb-button sb-button--primary" type="submit">
					<?php p($l->t('Publish')); ?>
				</button>
			</footer>
		</form>
	</dialog>
</main>
