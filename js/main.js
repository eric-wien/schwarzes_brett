/*
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function () {
	'use strict'

	const root = document.getElementById('schwarzes-brett')
	if (!root) {
		return
	}

	const MAX_IMAGE_SIZE = 5 * 1024 * 1024

	class BoardApp {
		constructor(element) {
			this.root = element
			this.apiUrl = element.dataset.apiUrl
			this.notes = []
			this.activeNote = null
			this.toastTimer = null
			this.elements = {
				add: document.getElementById('board-add-note'),
				emptyAdd: document.getElementById('board-empty-add'),
				search: document.getElementById('board-search'),
				category: document.getElementById('board-category'),
				sort: document.getElementById('board-sort'),
				count: document.getElementById('board-result-count'),
				status: document.getElementById('board-status'),
				grid: document.getElementById('board-grid'),
				empty: document.getElementById('board-empty'),
				emptyTitle: document.getElementById('board-empty-title'),
				emptyText: document.getElementById('board-empty-text'),
				toast: document.getElementById('board-toast'),
				dialog: document.getElementById('board-editor'),
				dialogTitle: document.getElementById('board-dialog-title'),
				close: document.getElementById('board-dialog-close'),
				cancel: document.getElementById('board-cancel'),
				form: document.getElementById('board-form'),
				formError: document.getElementById('board-form-error'),
				noteId: document.getElementById('board-note-id'),
				title: document.getElementById('board-title'),
				content: document.getElementById('board-content'),
				categories: document.getElementById('board-categories'),
				eventStart: document.getElementById('board-event-start'),
				eventEnd: document.getElementById('board-event-end'),
				allDay: document.getElementById('board-all-day'),
				location: document.getElementById('board-location'),
				linkUrl: document.getElementById('board-link-url'),
				linkLabel: document.getElementById('board-link-label'),
				image: document.getElementById('board-image'),
				removeImageWrap: document.getElementById('board-remove-image-wrap'),
				removeImage: document.getElementById('board-remove-image'),
				deleteNote: document.getElementById('board-delete-note'),
				save: document.getElementById('board-save'),
			}
		}

		start() {
			this.bindEvents()
			this.loadNotes()
		}

		bindEvents() {
			this.elements.add.addEventListener('click', () => this.openEditor())
			this.elements.emptyAdd.addEventListener('click', () => this.openEditor())
			this.elements.close.addEventListener('click', () => this.closeEditor())
			this.elements.cancel.addEventListener('click', () => this.closeEditor())
			this.elements.form.addEventListener('submit', (event) => this.saveNote(event))
			this.elements.deleteNote.addEventListener('click', () => this.deleteActiveNote())
			this.elements.search.addEventListener('input', () => this.render())
			this.elements.category.addEventListener('change', () => this.render())
			this.elements.sort.addEventListener('change', () => this.render())
			this.elements.grid.addEventListener('click', (event) => {
				const button = event.target.closest('[data-edit-note]')
				if (!button) {
					return
				}
				const note = this.notes.find((item) => item.id === Number(button.dataset.editNote))
				if (note) {
					this.openEditor(note)
				}
			})
			this.elements.dialog.addEventListener('click', (event) => {
				if (event.target === this.elements.dialog) {
					this.closeEditor()
				}
			})
			this.elements.dialog.addEventListener('cancel', (event) => {
				event.preventDefault()
				this.closeEditor()
			})
			this.elements.image.addEventListener('change', () => {
				const file = this.elements.image.files[0]
				if (file && file.size > MAX_IMAGE_SIZE) {
					this.showFormError('Images must be smaller than 5 MB.', 'image')
					this.elements.image.value = ''
				}
			})
		}

		async loadNotes() {
			this.elements.status.hidden = false
			this.elements.grid.hidden = true
			this.elements.empty.hidden = true

			try {
				const data = await this.request(this.apiUrl)
				this.notes = Array.isArray(data.notes) ? data.notes : []
				this.updateCategoryOptions()
				this.render()
			} catch (error) {
				this.elements.status.replaceChildren()
				const message = this.createElement('p', '', error.message || 'The notes could not be loaded.')
				this.elements.status.append(message)
			}
		}

		render() {
			const visibleNotes = this.getVisibleNotes()
			this.elements.status.hidden = true
			this.elements.grid.replaceChildren(...visibleNotes.map((note) => this.createCard(note)))
			this.elements.grid.hidden = visibleNotes.length === 0
			this.elements.count.textContent = this.formatCount(visibleNotes.length)

			const hasFilters = this.elements.search.value.trim() !== '' || this.elements.category.value !== ''
			this.elements.empty.hidden = visibleNotes.length !== 0
			this.elements.emptyAdd.hidden = this.notes.length !== 0
			if (this.notes.length === 0) {
				this.elements.emptyTitle.textContent = 'The board is waiting for its first note'
				this.elements.emptyText.textContent = 'Share an announcement, event, offer, or useful link.'
			} else if (hasFilters) {
				this.elements.emptyTitle.textContent = 'No matching notes'
				this.elements.emptyText.textContent = 'Try a different search or category.'
			} else {
				this.elements.emptyTitle.textContent = 'Nothing to show'
				this.elements.emptyText.textContent = 'Change the sorting to see other notes.'
			}

			this.focusLinkedNote()
		}

		getVisibleNotes() {
			const query = this.elements.search.value.trim().toLocaleLowerCase()
			const category = this.elements.category.value
			const notes = this.notes.filter((note) => {
				const matchesCategory = category === '' || note.categories.includes(category)
				const haystack = [
					note.title,
					note.content,
					note.location,
					note.authorName,
					...note.categories,
				].join(' ').toLocaleLowerCase()
				return matchesCategory && (query === '' || haystack.includes(query))
			})

			if (this.elements.sort.value === 'upcoming') {
				const now = Date.now() / 1000
				return notes.sort((left, right) => {
					const leftDate = left.eventStart && left.eventStart >= now ? left.eventStart : Number.MAX_SAFE_INTEGER
					const rightDate = right.eventStart && right.eventStart >= now ? right.eventStart : Number.MAX_SAFE_INTEGER
					return leftDate - rightDate || right.createdAt - left.createdAt
				})
			}

			return notes.sort((left, right) => right.createdAt - left.createdAt || right.id - left.id)
		}

		updateCategoryOptions() {
			const selected = this.elements.category.value
			const categories = [...new Set(this.notes.flatMap((note) => note.categories))]
				.sort((left, right) => left.localeCompare(right))
			const options = [this.createOption('', 'All categories')]
			options.push(...categories.map((category) => this.createOption(category, category)))
			this.elements.category.replaceChildren(...options)
			if (categories.includes(selected)) {
				this.elements.category.value = selected
			}
		}

		createCard(note) {
			const card = this.createElement('article', 'board-card')
			card.id = `note-${note.id}`
			card.dataset.noteId = String(note.id)
			card.append(this.createElement('span', 'board-card__pin'))

			if (note.imageUrl) {
				const imageWrap = this.createElement('div', 'board-card__image-wrap')
				const image = this.createElement('img', 'board-card__image')
				image.src = note.imageUrl
				image.alt = `Image for ${note.title}`
				image.loading = 'lazy'
				image.decoding = 'async'
				imageWrap.append(image)
				card.append(imageWrap)
			}

			const body = this.createElement('div', 'board-card__body')
			if (note.categories.length > 0) {
				const categories = this.createElement('div', 'board-card__categories')
				note.categories.forEach((category) => {
					categories.append(this.createElement('span', 'board-chip', category))
				})
				body.append(categories)
			}

			const titleRow = this.createElement('div', 'board-card__title-row')
			titleRow.append(this.createElement('h2', 'board-card__title', note.title))
			if (note.canEdit) {
				const edit = this.createElement('button', 'board-icon-button', '✎')
				edit.type = 'button'
				edit.dataset.editNote = String(note.id)
				edit.setAttribute('aria-label', `Edit ${note.title}`)
				titleRow.append(edit)
			}
			body.append(titleRow)

			if (note.content) {
				body.append(this.createElement('p', 'board-card__content', note.content))
			}

			const details = this.createDetails(note)
			if (details.childElementCount > 0) {
				body.append(details)
			}

			const footer = this.createElement('footer', 'board-card__footer')
			footer.append(
				this.createElement('span', 'board-card__author', `By ${note.authorName}`),
				this.createElement('time', '', this.formatRelativeDate(note.createdAt)),
			)
			footer.lastElementChild.dateTime = new Date(note.createdAt * 1000).toISOString()
			body.append(footer)
			card.append(body)
			return card
		}

		createDetails(note) {
			const details = this.createElement('div', 'board-card__details')
			if (note.eventStart) {
				details.append(this.createDetail('◷', this.formatEvent(note)))
			}
			if (note.location) {
				details.append(this.createDetail('⌖', note.location))
			}
			if (note.linkUrl) {
				const row = this.createElement('div', 'board-detail')
				row.append(this.createElement('span', 'board-detail__icon', '↗'))
				const link = this.createElement('a', 'board-card__link', note.linkLabel || this.linkHost(note.linkUrl))
				link.href = note.linkUrl
				link.target = '_blank'
				link.rel = 'noopener noreferrer'
				row.append(link)
				details.append(row)
			}
			return details
		}

		createDetail(icon, text) {
			const row = this.createElement('div', 'board-detail')
			row.append(
				this.createElement('span', 'board-detail__icon', icon),
				this.createElement('span', '', text),
			)
			return row
		}

		openEditor(note = null) {
			this.activeNote = note
			this.elements.form.reset()
			this.hideFormError()
			this.elements.noteId.value = note ? note.id : ''
			this.elements.dialogTitle.textContent = note ? 'Edit note' : 'Post a note'
			this.elements.save.textContent = note ? 'Save changes' : 'Publish note'
			this.elements.deleteNote.hidden = !note
			this.elements.removeImageWrap.hidden = !note?.imageUrl

			if (note) {
				this.elements.title.value = note.title
				this.elements.content.value = note.content
				this.elements.categories.value = note.categories.join(', ')
				this.elements.eventStart.value = this.toDateTimeInput(note.eventStart)
				this.elements.eventEnd.value = this.toDateTimeInput(note.eventEnd)
				this.elements.allDay.checked = note.isAllDay
				this.elements.location.value = note.location
				this.elements.linkUrl.value = note.linkUrl
				this.elements.linkLabel.value = note.linkLabel
			}

			this.elements.dialog.showModal()
			requestAnimationFrame(() => this.elements.title.focus())
		}

		closeEditor() {
			if (this.elements.dialog.open) {
				this.elements.dialog.close()
			}
			this.activeNote = null
		}

		async saveNote(event) {
			event.preventDefault()
			if (!this.elements.form.reportValidity()) {
				return
			}

			const image = this.elements.image.files[0]
			if (image && image.size > MAX_IMAGE_SIZE) {
				this.showFormError('Images must be smaller than 5 MB.', 'image')
				return
			}

			this.setSaving(true)
			this.hideFormError()
			try {
				const payload = this.formPayload()
				const id = this.activeNote?.id
				const response = await this.request(
					id ? `${this.apiUrl}/${id}` : this.apiUrl,
					{
						method: id ? 'PUT' : 'POST',
						headers: {'Content-Type': 'application/json'},
						body: JSON.stringify(payload),
					},
				)
				const savedId = response.note.id
				// Keep the persisted note in edit mode if a later image request fails,
				// so retrying cannot accidentally create a duplicate note.
				if (!id) {
					this.activeNote = response.note
					this.elements.noteId.value = String(savedId)
					this.elements.dialogTitle.textContent = 'Edit note'
					this.elements.deleteNote.hidden = false
				}

				if (image) {
					const formData = new FormData()
					formData.append('image', image)
					await this.request(`${this.apiUrl}/${savedId}/image`, {
						method: 'POST',
						body: formData,
					})
				} else if (id && this.elements.removeImage.checked) {
					await this.request(`${this.apiUrl}/${savedId}/image`, {method: 'DELETE'})
				}

				await this.refreshNotes()
				this.closeEditor()
				this.showToast(id ? 'Note updated.' : 'Note published.')
			} catch (error) {
				await this.refreshNotes(false)
				this.showFormError(error.message || 'The note could not be saved.', error.field)
			} finally {
				this.setSaving(false)
			}
		}

		async deleteActiveNote() {
			if (!this.activeNote || !window.confirm(`Delete “${this.activeNote.title}”?`)) {
				return
			}
			this.setSaving(true)
			this.hideFormError()
			try {
				await this.request(`${this.apiUrl}/${this.activeNote.id}`, {method: 'DELETE'})
				await this.refreshNotes()
				this.closeEditor()
				this.showToast('Note deleted.')
			} catch (error) {
				this.showFormError(error.message || 'The note could not be deleted.')
			} finally {
				this.setSaving(false)
			}
		}

		formPayload() {
			return {
				title: this.elements.title.value,
				content: this.elements.content.value,
				categories: this.elements.categories.value
					.split(',')
					.map((category) => category.trim())
					.filter(Boolean),
				eventStart: this.fromDateTimeInput(this.elements.eventStart.value),
				eventEnd: this.fromDateTimeInput(this.elements.eventEnd.value),
				isAllDay: this.elements.allDay.checked,
				location: this.elements.location.value,
				linkUrl: this.elements.linkUrl.value,
				linkLabel: this.elements.linkLabel.value,
			}
		}

		async refreshNotes(render = true) {
			const data = await this.request(this.apiUrl)
			this.notes = Array.isArray(data.notes) ? data.notes : []
			this.updateCategoryOptions()
			if (render) {
				this.render()
			}
		}

		async request(url, options = {}) {
			const requestToken = window.OC?.requestToken
				|| document.querySelector('meta[name="requesttoken"]')?.content
				|| document.head.dataset.requesttoken
			const headers = new Headers(options.headers || {})
			headers.set('Accept', 'application/json')
			headers.set('X-Requested-With', 'XMLHttpRequest')
			if (requestToken) {
				headers.set('requesttoken', requestToken)
			}

			const response = await fetch(url, {
				...options,
				headers,
				credentials: 'same-origin',
			})
			if (response.status === 204) {
				return {}
			}

			const contentType = response.headers.get('content-type') || ''
			const data = contentType.includes('application/json') ? await response.json() : null
			if (!response.ok) {
				const error = new Error(data?.error?.message || `Request failed (${response.status}).`)
				error.field = data?.error?.field || ''
				throw error
			}
			return data || {}
		}

		showFormError(message, field = '') {
			this.elements.formError.textContent = message
			this.elements.formError.hidden = false
			const fields = {
				title: this.elements.title,
				content: this.elements.content,
				categories: this.elements.categories,
				eventStart: this.elements.eventStart,
				eventEnd: this.elements.eventEnd,
				location: this.elements.location,
				linkUrl: this.elements.linkUrl,
				linkLabel: this.elements.linkLabel,
				image: this.elements.image,
			}
			if (fields[field]) {
				fields[field].focus()
			}
		}

		hideFormError() {
			this.elements.formError.hidden = true
			this.elements.formError.textContent = ''
		}

		setSaving(saving) {
			this.elements.save.disabled = saving
			this.elements.deleteNote.disabled = saving
			this.elements.cancel.disabled = saving
			this.elements.save.textContent = saving
				? 'Saving…'
				: (this.activeNote ? 'Save changes' : 'Publish note')
		}

		showToast(message) {
			window.clearTimeout(this.toastTimer)
			this.elements.toast.textContent = message
			this.elements.toast.hidden = false
			this.toastTimer = window.setTimeout(() => {
				this.elements.toast.hidden = true
			}, 3200)
		}

		formatEvent(note) {
			const dateOptions = note.isAllDay
				? {dateStyle: 'medium'}
				: {dateStyle: 'medium', timeStyle: 'short'}
			const formatter = new Intl.DateTimeFormat(undefined, dateOptions)
			const start = formatter.format(new Date(note.eventStart * 1000))
			if (!note.eventEnd) {
				return start
			}
			const end = formatter.format(new Date(note.eventEnd * 1000))
			return `${start} – ${end}`
		}

		formatRelativeDate(timestamp) {
			const seconds = timestamp - Math.floor(Date.now() / 1000)
			const relative = new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'})
			if (Math.abs(seconds) < 60) {
				return relative.format(Math.round(seconds), 'second')
			}
			if (Math.abs(seconds) < 3600) {
				return relative.format(Math.round(seconds / 60), 'minute')
			}
			if (Math.abs(seconds) < 86400) {
				return relative.format(Math.round(seconds / 3600), 'hour')
			}
			if (Math.abs(seconds) < 2_592_000) {
				return relative.format(Math.round(seconds / 86400), 'day')
			}
			return new Intl.DateTimeFormat(undefined, {dateStyle: 'medium'})
				.format(new Date(timestamp * 1000))
		}

		toDateTimeInput(timestamp) {
			if (!timestamp) {
				return ''
			}
			const date = new Date(timestamp * 1000)
			const pad = (value) => String(value).padStart(2, '0')
			return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
		}

		fromDateTimeInput(value) {
			if (!value) {
				return null
			}
			const timestamp = new Date(value).getTime()
			return Number.isNaN(timestamp) ? null : Math.floor(timestamp / 1000)
		}

		linkHost(url) {
			try {
				return new URL(url).hostname
			} catch (error) {
				return 'Open link'
			}
		}

		focusLinkedNote() {
			if (!window.location.hash.startsWith('#note-')) {
				return
			}
			const card = document.getElementById(window.location.hash.slice(1))
			if (card) {
				requestAnimationFrame(() => card.scrollIntoView({block: 'center'}))
			}
		}

		formatCount(count) {
			return count === 1 ? '1 note' : `${count} notes`
		}

		createOption(value, label) {
			const option = document.createElement('option')
			option.value = value
			option.textContent = label
			return option
		}

		createElement(tagName, className = '', text = '') {
			const element = document.createElement(tagName)
			if (className) {
				element.className = className
			}
			if (text !== '') {
				element.textContent = text
			}
			return element
		}
	}

	new BoardApp(root).start()
})()
