/*
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function () {
	'use strict'

	const APP_ID = 'schwarzes_brett'
	const root = document.getElementById('schwarzes-brett-admin')
	if (!root) {
		return
	}

	const enabled = document.getElementById('sb-approval-enabled')
	const moderators = document.getElementById('sb-moderators')
	const save = document.getElementById('sb-settings-save')
	const status = document.getElementById('sb-settings-status')

	function translate(text) {
		return typeof window.t === 'function' ? window.t(APP_ID, text) : text
	}

	save.addEventListener('click', async () => {
		save.disabled = true
		status.textContent = translate('Saving…')
		try {
			const headers = new Headers({
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			})
			const requestToken = window.OC?.requestToken
				|| document.querySelector('meta[name="requesttoken"]')?.content
			if (requestToken) {
				headers.set('requesttoken', requestToken)
			}

			const response = await fetch(root.dataset.saveUrl, {
				method: 'PUT',
				headers,
				credentials: 'same-origin',
				body: JSON.stringify({
					enabled: enabled.checked,
					moderators: [...moderators.selectedOptions].map((option) => option.value),
				}),
			})
			if (!response.ok) {
				throw new Error()
			}
			status.textContent = translate('Settings saved.')
		} catch (error) {
			status.textContent = translate('The settings could not be saved.')
		} finally {
			save.disabled = false
		}
	})
})()
