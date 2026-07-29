/*
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Custom rendering for the Dashboard widget. Nextcloud's built-in widget
 * rendering only offers a title and one subtitle line per item, which is not
 * enough for three clamped lines of description plus the note's link.
 */

(function () {
	'use strict'

	const APP_ID = 'schwarzes_brett'
	const SVG_NS = 'http://www.w3.org/2000/svg'
	const ITEM_LIMIT = 5
	const DESCRIPTION_LINES = 3
	const RELOAD_INTERVAL = 60000
	const ACCENT_COUNT = 6

	/** Mirrors accentFor() in js/main.js so both views colour-code identically. */
	function accentFor(note) {
		const key = (note.categories[0] || '').trim().toLocaleLowerCase()
		if (key === '') {
			return 0
		}
		let hash = 0
		for (let index = 0; index < key.length; index += 1) {
			hash = (hash * 31 + key.charCodeAt(index)) % 100000007
		}
		return (hash % ACCENT_COUNT) + 1
	}

	function translate(text, vars) {
		if (typeof window.t === 'function') {
			return window.t(APP_ID, text, vars)
		}
		return String(text).replace(/\{(\w+)\}/g, (match, name) => (
			vars && name in vars ? vars[name] : match
		))
	}

	function url(path) {
		if (window.OC && typeof window.OC.generateUrl === 'function') {
			return window.OC.generateUrl(path)
		}
		return '/index.php' + path
	}

	function element(tagName, className, text) {
		const node = document.createElement(tagName)
		if (className) {
			node.className = className
		}
		if (text !== undefined && text !== '') {
			node.textContent = text
		}
		return node
	}

	function logo(className) {
		const svg = document.createElementNS(SVG_NS, 'svg')
		svg.setAttribute('viewBox', '0 0 24 24')
		svg.setAttribute('aria-hidden', 'true')
		svg.setAttribute('focusable', 'false')
		svg.setAttribute('class', className)
		const frame = document.createElementNS(SVG_NS, 'path')
		frame.setAttribute('fill-rule', 'evenodd')
		frame.setAttribute('d', 'M4.5 3h15A2.5 2.5 0 0 1 22 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 18.5v-13A2.5 2.5 0 0 1 4.5 3Zm.4 1.8A1.1 1.1 0 0 0 3.8 5.9v12.2c0 .61.49 1.1 1.1 1.1h14.2c.61 0 1.1-.49 1.1-1.1V5.9c0-.61-.49-1.1-1.1-1.1Z')
		const first = document.createElementNS(SVG_NS, 'path')
		first.setAttribute('d', 'm5.37 7.18 5.86-.72.6 4.86-5.86.72z')
		const second = document.createElementNS(SVG_NS, 'path')
		second.setAttribute('d', 'm12.8 12.07 5.47.58-.47 4.48-5.47-.58z')
		svg.append(frame, first, second)
		return svg
	}

	function linkIcon() {
		const svg = document.createElementNS(SVG_NS, 'svg')
		svg.setAttribute('viewBox', '0 0 24 24')
		svg.setAttribute('aria-hidden', 'true')
		svg.setAttribute('focusable', 'false')
		svg.setAttribute('class', 'sbw-item__link-icon')
		const path = document.createElementNS(SVG_NS, 'path')
		path.setAttribute('d', 'M14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3zm5 16H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2z')
		svg.append(path)
		return svg
	}

	function linkText(note) {
		if (note.linkLabel) {
			return note.linkLabel
		}
		try {
			return new URL(note.linkUrl).hostname
		} catch (error) {
			return translate('Open link')
		}
	}

	/** Mirrors lastChange() in js/main.js. */
	function lastChange(note) {
		return note.updatedAt || note.createdAt
	}

	function relativeDate(timestamp) {
		const seconds = timestamp - Math.floor(Date.now() / 1000)
		const relative = new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'})
		if (Math.abs(seconds) < 3600) {
			return relative.format(Math.round(seconds / 60), 'minute')
		}
		if (Math.abs(seconds) < 86400) {
			return relative.format(Math.round(seconds / 3600), 'hour')
		}
		if (Math.abs(seconds) < 2592000) {
			return relative.format(Math.round(seconds / 86400), 'day')
		}
		return new Intl.DateTimeFormat(undefined, {dateStyle: 'medium'})
			.format(new Date(timestamp * 1000))
	}


	/**
	 * The note is not wrapped in a single anchor: a note's own link has to stay
	 * separately clickable, and anchors cannot be nested. The thumbnail and the
	 * title link to the note in the app, and a click anywhere else on the item
	 * follows them through the handler installed in render().
	 */
	function createItem(note, noteUrl) {
		const item = element('div', 'sbw-item')
		item.dataset.accent = String(accentFor(note))
		item.dataset.noteUrl = noteUrl

		const thumb = element('a', 'sbw-item__thumb')
		thumb.href = noteUrl
		thumb.tabIndex = -1
		thumb.setAttribute('aria-hidden', 'true')
		if (note.imageUrl) {
			const image = element('img', 'sbw-item__image')
			image.src = note.imageUrl
			image.alt = ''
			image.loading = 'lazy'
			thumb.classList.add('sbw-item__thumb--image')
			thumb.append(image)
		} else {
			thumb.append(logo('sbw-item__logo'))
		}
		item.append(thumb)

		const body = element('div', 'sbw-item__body')
		const title = element('a', 'sbw-item__title', note.title)
		title.href = noteUrl
		body.append(title)

		if (note.content) {
			body.append(element('span', 'sbw-item__text', note.content))
		}

		if (note.linkUrl) {
			const link = element('a', 'sbw-item__link')
			link.href = note.linkUrl
			link.target = '_blank'
			link.rel = 'noopener noreferrer'
			link.append(linkIcon(), element('span', '', linkText(note)))
			body.append(link)
		}

		body.append(element('span', 'sbw-item__meta',
			`${note.authorName} · ${relativeDate(lastChange(note))}`))

		item.append(body)
		return item
	}

	function renderEmpty(root) {
		const empty = element('div', 'sbw-empty')
		empty.append(
			logo('sbw-empty__logo'),
			element('p', 'sbw-empty__title', translate('No notes have been posted yet.')),
		)
		root.append(empty)
	}

	function render(root, notes, boardUrl) {
		root.replaceChildren()
		root.classList.add('sbw')
		root.style.setProperty('--sbw-lines', String(DESCRIPTION_LINES))

		if (notes.length === 0) {
			renderEmpty(root)
		} else {
			const list = element('div', 'sbw-list')
			notes.slice(0, ITEM_LIMIT).forEach((note) => {
				list.append(createItem(note, `${boardUrl}#note-${note.id}`))
			})
			// The whole row is clickable for convenience, but a click on the
			// note's own link has to reach that link instead.
			list.addEventListener('click', (event) => {
				if (event.target.closest('a')) {
					return
				}
				const item = event.target.closest('[data-note-url]')
				if (item) {
					window.location.href = item.dataset.noteUrl
				}
			})
			root.append(list)
		}

		const footer = element('a', 'sbw-more', translate('Open the notice board'))
		footer.href = boardUrl
		root.append(footer)
	}

	function renderError(root, message) {
		root.replaceChildren()
		root.classList.add('sbw')
		root.append(element('p', 'sbw-error', message))
	}

	async function loadNotes() {
		const requestToken = window.OC?.requestToken
			|| document.querySelector('meta[name="requesttoken"]')?.content
			|| document.head.dataset.requesttoken
		const headers = new Headers({
			Accept: 'application/json',
			'X-Requested-With': 'XMLHttpRequest',
		})
		if (requestToken) {
			headers.set('requesttoken', requestToken)
		}

		const response = await fetch(
			url(`/apps/${APP_ID}/api/notes?limit=${ITEM_LIMIT}`),
			{headers, credentials: 'same-origin'},
		)
		if (!response.ok) {
			throw new Error(translate('The notes could not be loaded.'))
		}
		const data = await response.json()
		return Array.isArray(data.notes) ? data.notes : []
	}

	function register() {
		if (!window.OCA || !window.OCA.Dashboard) {
			return false
		}
		window.OCA.Dashboard.register(APP_ID, (root) => {
			const boardUrl = url(`/apps/${APP_ID}/`)
			const refresh = async () => {
				try {
					render(root, await loadNotes(), boardUrl)
				} catch (error) {
					renderError(root, error.message || translate('The notes could not be loaded.'))
				}
			}
			refresh()
			window.setInterval(refresh, RELOAD_INTERVAL)
		})
		return true
	}

	// The script is emitted after the Dashboard bundle, so OCA.Dashboard is
	// normally there already; the retry only covers a changed script order.
	function bootstrap(attempt) {
		if (register() || attempt >= 20) {
			return
		}
		window.setTimeout(() => bootstrap(attempt + 1), 100)
	}

	bootstrap(0)
})()
