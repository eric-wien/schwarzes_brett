<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array{
 *     enabled: bool,
 *     moderatorIds: list<string>,
 *     users: list<array{id: string, name: string}>,
 *     saveUrl: string
 * } $_
 */

script('schwarzes_brett', 'admin');
style('schwarzes_brett', 'admin');
?>

<section id="schwarzes-brett-admin"
		 class="sb-admin section"
		 data-save-url="<?php p($_['saveUrl']); ?>">
	<h2><?php p($l->t('Schwarzes Brett')); ?></h2>

	<p class="sb-admin__intro">
		<?php p($l->t('Control whether notes require moderation before they appear on the board.')); ?>
	</p>

	<label class="sb-admin__toggle">
		<input id="sb-approval-enabled"
			   type="checkbox"
			   <?php if ($_['enabled']) { ?>checked<?php } ?>>
		<span>
			<strong><?php p($l->t('Require approval')); ?></strong>
			<small><?php p($l->t('New and edited notes stay pending until a moderator or administrator approves them.')); ?></small>
		</span>
	</label>

	<div class="sb-admin__field">
		<label for="sb-moderators"><?php p($l->t('Moderators')); ?></label>
		<select id="sb-moderators" multiple size="8">
			<?php foreach ($_['users'] as $user) { ?>
				<option value="<?php p($user['id']); ?>"
					<?php if (in_array($user['id'], $_['moderatorIds'], true)) { ?>selected<?php } ?>>
					<?php p($user['name']); ?> (<?php p($user['id']); ?>)
				</option>
			<?php } ?>
		</select>
		<small>
			<?php p($l->t('Moderators can see and approve pending submissions. Administrators always have full access.')); ?>
		</small>
	</div>

	<div class="sb-admin__actions">
		<button id="sb-settings-save" class="primary" type="button">
			<?php p($l->t('Save')); ?>
		</button>
		<span id="sb-settings-status" role="status" aria-live="polite"></span>
	</div>
</section>
