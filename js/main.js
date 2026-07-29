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

	const APP_ID = 'schwarzes_brett'
	const SVG_NS = 'http://www.w3.org/2000/svg'
	const MAX_IMAGE_SIZE = 5 * 1024 * 1024
	const ACCENT_COUNT = 6

	/**
	 * Nextcloud injects the translation bundle together with this script, but
	 * the fallbacks keep the board usable if it ever fails to load.
	 */
	function translate(text, vars) {
		if (typeof window.t === 'function') {
			return window.t(APP_ID, text, vars)
		}
		return String(text).replace(/\{(\w+)\}/g, (match, name) => (
			vars && name in vars ? vars[name] : match
		))
	}

	function translatePlural(singular, plural, count) {
		if (typeof window.n === 'function') {
			return window.n(APP_ID, singular, plural, count)
		}
		return (count === 1 ? singular : plural).replace('%n', String(count))
	}

	/**
	 * A note returns to the top of the board when it is edited, so ordering uses
	 * the last change and falls back to the creation time.
	 */
	function lastChange(note) {
		return note.updatedAt || note.createdAt
	}

	/**
	 * Which tab a note belongs to. The event dates form the period in which it is
	 * on the board: it appears once the start date is reached and leaves again
	 * after the end date. They decide visibility only - neither date affects
	 * ordering or is displayed. A draft stays out of the board until published.
	 *
	 * Validation guarantees end >= start, so the cases cannot overlap.
	 */
	function viewOf(note) {
		const now = Date.now() / 1000
		if (note.isDraft) {
			return 'pending'
		}
		if (!note.isApproved) {
			return 'pending'
		}
		if (note.eventEnd && note.eventEnd <= now) {
			return 'archive'
		}
		if (note.eventStart && note.eventStart > now) {
			return 'pending'
		}
		return 'board'
	}

	function badgeFor(note) {
		if (note.isDraft) {
			return translate('Draft')
		}
		if (!note.isApproved) {
			return translate('Pending approval')
		}
		const view = viewOf(note)
		if (view === 'pending') {
			return translate('Scheduled')
		}
		return view === 'archive' ? translate('Archived') : ''
	}

	/**
	 * Notes with the same first category get the same accent colour, so the
	 * board colour-codes itself instead of looking randomly striped.
	 * Uncategorised notes fall back to the neutral accent 0.
	 */
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

	function icon(name, className) {
		const svg = document.createElementNS(SVG_NS, 'svg')
		svg.setAttribute('viewBox', '0 0 24 24')
		svg.setAttribute('aria-hidden', 'true')
		svg.setAttribute('focusable', 'false')
		if (className) {
			svg.setAttribute('class', className)
		}
		const use = document.createElementNS(SVG_NS, 'use')
		use.setAttribute('href', '#' + name)
		svg.append(use)
		return svg
	}

	class BoardApp {
		constructor(element) {
			this.root = element
			this.apiUrl = element.dataset.apiUrl
			this.notes = []
			this.activeNote = null
			this.view = 'board'
			this.linkHandled = false
			this.categoryIndex = -1
			this.toastTimer = null
			this.imageHintDefault = ''
			this.masonryFrame = 0
			this.gridWidth = 0
			this.elements = {
				add: document.getElementById('board-add-note'),
				emptyAdd: document.getElementById('board-empty-add'),
				tabs: [
					document.getElementById('board-tab-board'),
					document.getElementById('board-tab-pending'),
					document.getElementById('board-tab-archive'),
				],
				tabCounts: {
					board: document.getElementById('board-tab-board-count'),
					pending: document.getElementById('board-tab-pending-count'),
					archive: document.getElementById('board-tab-archive-count'),
				},
				viewBadge: document.getElementById('board-view-badge'),
				categoryOptions: document.getElementById('board-category-options'),
				saveDraft: document.getElementById('board-save-draft'),
				viewer: document.getElementById('board-viewer'),
				viewTitle: document.getElementById('board-view-title'),
				viewClose: document.getElementById('board-view-close'),
				viewDismiss: document.getElementById('board-view-dismiss'),
				viewApprove: document.getElementById('board-view-approve'),
				viewEdit: document.getElementById('board-view-edit'),
				viewFigure: document.getElementById('board-view-figure'),
				viewImage: document.getElementById('board-view-image'),
				viewTags: document.getElementById('board-view-tags'),
				viewText: document.getElementById('board-view-text'),
				viewMeta: document.getElementById('board-view-meta'),
				viewAuthor: document.getElementById('board-view-author'),
				search: document.getElementById('board-search'),
				category: document.getElementById('board-category'),
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
				formErrorText: document.getElementById('board-form-error-text'),
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
				imageName: document.getElementById('board-image-name'),
				removeImageWrap: document.getElementById('board-remove-image-wrap'),
				removeImage: document.getElementById('board-remove-image'),
				deleteNote: document.getElementById('board-delete-note'),
				save: document.getElementById('board-save'),
			}
			this.imageHintDefault = this.elements.imageName.textContent
		}

		start() {
			this.bindEvents()
			this.observeWidth()
			this.loadNotes()
		}

		observeWidth() {
			if (typeof ResizeObserver !== 'function') {
				window.addEventListener('resize', () => this.scheduleMasonry())
				return
			}
			// Only a width change can alter the column count; reacting to the
			// height would loop, because the spans change the grid height.
			new ResizeObserver((entries) => {
				const width = Math.round(entries[0].contentRect.width)
				if (width !== this.gridWidth) {
					this.gridWidth = width
					this.scheduleMasonry()
				}
			}).observe(this.elements.grid)
		}

		scheduleMasonry() {
			window.cancelAnimationFrame(this.masonryFrame)
			this.masonryFrame = window.requestAnimationFrame(() => this.layoutMasonry())
		}

		layoutMasonry() {
			const grid = this.elements.grid
			if (grid.hidden || grid.childElementCount === 0) {
				return
			}
			// A single column already stacks perfectly; spanning row tracks
			// there would only add rounding gaps between the cards.
			if (window.getComputedStyle(grid).gridTemplateColumns.split(' ').length < 2) {
				grid.classList.remove('sb-grid--masonry')
				for (const card of grid.children) {
					card.style.gridRowEnd = ''
				}
				return
			}

			grid.classList.add('sb-grid--masonry')
			const styles = window.getComputedStyle(grid)
			const rowHeight = parseFloat(styles.gridAutoRows)
			const gap = parseFloat(styles.rowGap) || 0
			if (!rowHeight) {
				return
			}
			// Cards are start-aligned, so their height stays content-driven and
			// can be measured even while an outdated span is still applied.
			for (const card of grid.children) {
				const height = card.getBoundingClientRect().height
				const span = Math.max(1, Math.ceil((height + gap) / (rowHeight + gap)))
				card.style.gridRowEnd = `span ${span}`
			}
		}

		bindEvents() {
			this.elements.add.addEventListener('click', () => this.openEditor())
			this.elements.emptyAdd.addEventListener('click', () => this.openEditor())
			this.elements.close.addEventListener('click', () => this.closeEditor())
			this.elements.cancel.addEventListener('click', () => this.closeEditor())
			this.elements.form.addEventListener('submit', (event) => this.saveNote(event, false))
			this.elements.saveDraft.addEventListener('click', (event) => this.saveNote(event, true))
			this.bindCategorySuggestions()
			this.elements.deleteNote.addEventListener('click', () => this.deleteActiveNote())
			this.elements.search.addEventListener('input', () => this.render())
			this.elements.category.addEventListener('change', () => this.render())
			this.elements.tabs.forEach((tab) => {
				tab.addEventListener('click', () => this.setView(tab.dataset.view))
			})

			this.elements.grid.addEventListener('click', (event) => {
				const approve = event.target.closest('[data-approve-note]')
				if (approve) {
					this.withNote(approve.dataset.approveNote, (note) => this.approveNote(note))
					return
				}
				const edit = event.target.closest('[data-edit-note]')
				if (edit) {
					this.withNote(edit.dataset.editNote, (note) => this.openEditor(note))
					return
				}
				// Anything else inside a card opens the note, except the links,
				// which have to keep working as links.
				if (event.target.closest('a')) {
					return
				}
				const card = event.target.closest('[data-note-id]')
				if (card) {
					this.withNote(card.dataset.noteId, (note) => this.openViewer(note))
				}
			})

			this.elements.viewClose.addEventListener('click', () => this.closeViewer())
			this.elements.viewDismiss.addEventListener('click', () => this.closeViewer())
			this.elements.viewApprove.addEventListener('click', () => {
				if (this.activeNote) {
					this.approveNote(this.activeNote, true)
				}
			})
			this.elements.viewEdit.addEventListener('click', () => {
				const note = this.activeNote
				this.closeViewer()
				if (note) {
					this.openEditor(note)
				}
			})
			this.elements.viewer.addEventListener('click', (event) => {
				if (event.target === this.elements.viewer) {
					this.closeViewer()
				}
			})
			this.elements.viewer.addEventListener('cancel', (event) => {
				event.preventDefault()
				this.closeViewer()
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
			this.elements.image.addEventListener('change', () => this.onImageSelected())
		}

		onImageSelected() {
			const file = this.elements.image.files[0]
			if (file && file.size > MAX_IMAGE_SIZE) {
				this.showFormError(translate('Images must be smaller than 5 MB.'), 'image')
				this.elements.image.value = ''
			}
			const selected = this.elements.image.files[0]
			this.elements.imageName.textContent = selected ? selected.name : this.imageHintDefault
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
				this.elements.status.replaceChildren(
					icon('sb-i-alert'),
					this.createElement('span', '', error.message || translate('The notes could not be loaded.')),
				)
			}
		}

		withNote(id, callback) {
			const note = this.notes.find((item) => item.id === Number(id))
			if (note) {
				callback(note)
			}
		}

		setView(view) {
			if (!(view in this.countByView())) {
				return
			}
			this.view = view
			this.render()
		}

		countByView() {
			const counts = {board: 0, pending: 0, archive: 0}
			this.notes.forEach((note) => {
				counts[viewOf(note)] += 1
			})
			return counts
		}

		render() {
			const counts = this.countByView()
			// Only the board tab stays when empty, so never leave the user on a
			// tab that just disappeared.
			if (this.view !== 'board' && counts[this.view] === 0) {
				this.view = 'board'
			}
			this.elements.tabs.forEach((tab) => {
				const view = tab.dataset.view
				this.elements.tabCounts[view].textContent = String(counts[view])
				tab.hidden = view !== 'board' && counts[view] === 0
				tab.setAttribute('aria-selected', String(view === this.view))
			})

			const visibleNotes = this.getVisibleNotes()
			this.elements.status.hidden = true
			this.elements.grid.replaceChildren(...visibleNotes.map((note) => this.createCard(note)))
			this.elements.grid.hidden = visibleNotes.length === 0
			this.elements.count.textContent = this.formatCount(visibleNotes.length)

			const hasFilters = this.elements.search.value.trim() !== '' || this.elements.category.value !== ''
			this.elements.empty.hidden = visibleNotes.length !== 0
			this.elements.emptyAdd.hidden = this.view !== 'board' || this.notes.length !== 0
			if (hasFilters && counts[this.view] > 0) {
				this.elements.emptyTitle.textContent = translate('No matching notes')
				this.elements.emptyText.textContent = translate('Try a different search term or category.')
			} else if (this.view === 'archive') {
				this.elements.emptyTitle.textContent = translate('The archive is empty')
				this.elements.emptyText.textContent = translate('Notes outside their display period are kept here instead of being deleted.')
			} else if (this.view === 'pending') {
				this.elements.emptyTitle.textContent = translate('Nothing waiting')
				this.elements.emptyText.textContent = translate('Drafts, submissions awaiting approval, and scheduled notes appear here.')
			} else {
				this.elements.emptyTitle.textContent = translate('The board is still empty')
				this.elements.emptyText.textContent = translate('Share an announcement, an event, an offer, or a useful link.')
			}

			this.scheduleMasonry()
			this.openLinkedNote()
		}

		getVisibleNotes() {
			const query = this.elements.search.value.trim().toLocaleLowerCase()
			const category = this.elements.category.value
			const notes = this.notes.filter((note) => {
				if (viewOf(note) !== this.view) {
					return false
				}
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

			// Newest change first; the event dates never influence the order.
			return notes.sort((left, right) => lastChange(right) - lastChange(left) || right.id - left.id)
		}

		knownCategories() {
			return [...new Set(this.notes.flatMap((note) => note.categories))]
				.sort((left, right) => left.localeCompare(right))
		}

		/**
		 * The categories field is a comma-separated list, so a plain datalist
		 * would try to match the whole value. This completes the token after the
		 * last comma instead, which is what keeps duplicates from creeping in.
		 */
		bindCategorySuggestions() {
			const input = this.elements.categories
			input.addEventListener('input', () => this.showCategorySuggestions())
			input.addEventListener('focus', () => this.showCategorySuggestions())
			input.addEventListener('keydown', (event) => this.onCategoryKeydown(event))
			// A blur that lands on an option must not close the list first.
			input.addEventListener('blur', () => {
				window.setTimeout(() => this.hideCategorySuggestions(), 150)
			})
			this.elements.categoryOptions.addEventListener('mousedown', (event) => {
				const option = event.target.closest('[data-category]')
				if (option) {
					event.preventDefault()
					this.applyCategorySuggestion(option.dataset.category)
				}
			})
		}

		categoryTokens() {
			return this.elements.categories.value.split(',')
		}

		categorySuggestions() {
			const tokens = this.categoryTokens()
			const token = (tokens[tokens.length - 1] || '').trim().toLocaleLowerCase()
			const taken = new Set(tokens.slice(0, -1)
				.map((entry) => entry.trim().toLocaleLowerCase())
				.filter(Boolean))
			return this.knownCategories()
				.filter((category) => !taken.has(category.toLocaleLowerCase()))
				.filter((category) => token === '' || category.toLocaleLowerCase().includes(token))
				.slice(0, 8)
		}

		showCategorySuggestions() {
			const matches = this.categorySuggestions()
			const list = this.elements.categoryOptions
			if (matches.length === 0) {
				this.hideCategorySuggestions()
				return
			}
			this.categoryIndex = -1
			list.replaceChildren(...matches.map((category, index) => {
				const option = this.createElement('li', 'sb-combo__option', category)
				option.id = `board-category-option-${index}`
				option.setAttribute('role', 'option')
				option.setAttribute('aria-selected', 'false')
				option.dataset.category = category
				return option
			}))
			list.hidden = false
			this.elements.categories.setAttribute('aria-expanded', 'true')
			this.elements.categories.removeAttribute('aria-activedescendant')
		}

		hideCategorySuggestions() {
			this.elements.categoryOptions.hidden = true
			this.elements.categoryOptions.replaceChildren()
			this.categoryIndex = -1
			this.elements.categories.setAttribute('aria-expanded', 'false')
			this.elements.categories.removeAttribute('aria-activedescendant')
		}

		highlightCategory(index) {
			const options = [...this.elements.categoryOptions.children]
			if (options.length === 0) {
				return
			}
			this.categoryIndex = (index + options.length) % options.length
			options.forEach((option, position) => {
				option.setAttribute('aria-selected', String(position === this.categoryIndex))
			})
			const active = options[this.categoryIndex]
			active.scrollIntoView({block: 'nearest'})
			this.elements.categories.setAttribute('aria-activedescendant', active.id)
		}

		onCategoryKeydown(event) {
			const open = !this.elements.categoryOptions.hidden
			if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
				event.preventDefault()
				if (!open) {
					this.showCategorySuggestions()
					return
				}
				this.highlightCategory(this.categoryIndex + (event.key === 'ArrowDown' ? 1 : -1))
				return
			}
			if (event.key === 'Enter' && open && this.categoryIndex >= 0) {
				// Only swallow Enter when it picks a suggestion, so the form can
				// still be submitted from this field otherwise.
				event.preventDefault()
				this.applyCategorySuggestion(
					this.elements.categoryOptions.children[this.categoryIndex].dataset.category,
				)
				return
			}
			if (event.key === 'Escape' && open) {
				event.preventDefault()
				this.hideCategorySuggestions()
			}
		}

		applyCategorySuggestion(category) {
			const tokens = this.categoryTokens()
			tokens[tokens.length - 1] = category
			const unique = []
			const seen = new Set()
			tokens.map((entry) => entry.trim()).filter(Boolean).forEach((entry) => {
				const key = entry.toLocaleLowerCase()
				if (!seen.has(key)) {
					seen.add(key)
					unique.push(entry)
				}
			})
			this.elements.categories.value = unique.join(', ') + ', '
			this.hideCategorySuggestions()
			this.elements.categories.focus()
		}

		updateCategoryOptions() {
			const selected = this.elements.category.value
			const categories = this.knownCategories()
			const options = [this.createOption('', translate('All categories'))]
			options.push(...categories.map((category) => this.createOption(category, category)))
			this.elements.category.replaceChildren(...options)
			if (categories.includes(selected)) {
				this.elements.category.value = selected
			}
		}

		createCard(note) {
			const card = this.createElement('article', 'sb-card')
			card.id = `note-${note.id}`
			card.dataset.noteId = String(note.id)
			card.dataset.accent = String(accentFor(note))

			if (note.imageUrl) {
				const figure = this.createElement('figure', 'sb-card__figure')
				const image = this.createElement('img', 'sb-card__image')
				image.src = note.imageUrl
				image.alt = translate('Image of “{title}”', {title: note.title})
				image.loading = 'lazy'
				image.decoding = 'async'
				// A late-loading image changes the card height, so re-measure.
				image.addEventListener('load', () => this.scheduleMasonry())
				figure.append(image)
				card.append(figure)
			}

			const body = this.createElement('div', 'sb-card__body')

			const head = this.createElement('div', 'sb-card__head')
			const heading = this.createElement('h3', 'sb-card__title')
			const open = this.createElement('button', 'sb-card__open', note.title)
			open.type = 'button'
			open.setAttribute('aria-label', translate('Open “{title}”', {title: note.title}))
			heading.append(open)
			head.append(heading)
			if (note.canApprove) {
				const approve = this.createElement('button', 'sb-icon-button sb-card__approve')
				approve.type = 'button'
				approve.dataset.approveNote = String(note.id)
				approve.setAttribute('aria-label', translate('Approve “{title}”', {title: note.title}))
				approve.append(icon('sb-i-check'))
				head.append(approve)
			}
			if (note.canEdit) {
				const edit = this.createElement('button', 'sb-icon-button sb-card__edit')
				edit.type = 'button'
				edit.dataset.editNote = String(note.id)
				edit.setAttribute('aria-label', translate('Edit “{title}”', {title: note.title}))
				edit.append(icon('sb-i-pencil'))
				head.append(edit)
			}
			body.append(head)

			// Tells a draft from a scheduled note inside the same tab.
			const badge = badgeFor(note)
			if (badge !== '') {
				body.append(this.createElement('span', 'sb-badge', badge))
			}

			if (note.categories.length > 0) {
				const tags = this.createElement('div', 'sb-card__tags')
				note.categories.forEach((category) => {
					tags.append(this.createElement('span', 'sb-tag', category))
				})
				body.append(tags)
			}

			if (note.content) {
				body.append(this.createElement('p', 'sb-card__text', note.content))
			}

			const meta = this.createMeta(note)
			if (meta.childElementCount > 0) {
				body.append(meta)
			}

			const foot = this.createElement('footer', 'sb-card__foot')
			const author = this.createElement('span', 'sb-card__author')
			author.append(icon('sb-i-account'), this.createElement('span', '', note.authorName))
			const date = this.createElement('time', 'sb-card__date', this.formatRelativeDate(note.createdAt))
			date.dateTime = new Date(note.createdAt * 1000).toISOString()
			foot.append(author, date)
			body.append(foot)

			card.append(body)
			return card
		}

		/**
		 * The event dates are intentionally absent: they only decide whether a
		 * note sits on the board or in the archive.
		 */
		createMeta(note) {
			const meta = this.createElement('div', 'sb-card__meta')
			if (note.location) {
				meta.append(this.createMetaRow('sb-i-marker', note.location))
			}
			if (note.linkUrl) {
				const row = this.createElement('div', 'sb-meta')
				const link = this.createElement('a', 'sb-card__link', note.linkLabel || this.linkHost(note.linkUrl))
				link.href = note.linkUrl
				link.target = '_blank'
				link.rel = 'noopener noreferrer'
				row.append(icon('sb-i-link'), link)
				meta.append(row)
			}
			return meta
		}

		createMetaRow(iconName, text) {
			const row = this.createElement('div', 'sb-meta')
			row.append(icon(iconName), this.createElement('span', '', text))
			return row
		}

		openViewer(note) {
			this.activeNote = note
			this.elements.viewTitle.textContent = note.title

			this.elements.viewFigure.hidden = !note.imageUrl
			if (note.imageUrl) {
				this.elements.viewImage.src = note.imageUrl
				this.elements.viewImage.alt = translate('Image of “{title}”', {title: note.title})
			} else {
				this.elements.viewImage.removeAttribute('src')
			}

			const badge = badgeFor(note)
			this.elements.viewBadge.hidden = badge === ''
			this.elements.viewBadge.textContent = badge

			this.elements.viewTags.hidden = note.categories.length === 0
			this.elements.viewTags.replaceChildren(...note.categories.map(
				(category) => this.createElement('span', 'sb-tag', category),
			))

			this.elements.viewText.hidden = !note.content
			this.elements.viewText.textContent = note.content

			// Same rows as the card, so the two views stay recognisably related.
			this.elements.viewMeta.replaceChildren(...this.createMeta(note).children)

			this.elements.viewAuthor.textContent = translate('Posted by {author} · {date}', {
				author: note.authorName,
				date: this.formatRelativeDate(lastChange(note)),
			})
			this.elements.viewApprove.hidden = !note.canApprove
			this.elements.viewEdit.hidden = !note.canEdit

			this.elements.viewer.showModal()
			requestAnimationFrame(() => this.elements.viewDismiss.focus())
		}

		closeViewer() {
			if (this.elements.viewer.open) {
				this.elements.viewer.close()
			}
			this.activeNote = null
		}

		/** Publishing is the primary action unless the note is already published. */
		saveLabel() {
			return this.activeNote && !this.activeNote.isDraft
				? translate('Save')
				: translate('Publish')
		}

		openEditor(note = null) {
			this.activeNote = note
			this.elements.form.reset()
			this.hideFormError()
			this.elements.imageName.textContent = this.imageHintDefault
			this.elements.dialogTitle.textContent = note ? translate('Edit note') : translate('New note')
			this.elements.save.textContent = this.saveLabel()
			this.hideCategorySuggestions()
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

		async saveNote(event, asDraft) {
			event.preventDefault()
			if (!this.elements.form.reportValidity()) {
				return
			}

			const image = this.elements.image.files[0]
			if (image && image.size > MAX_IMAGE_SIZE) {
				this.showFormError(translate('Images must be smaller than 5 MB.'), 'image')
				return
			}

			this.setSaving(true)
			this.hideFormError()
			try {
				const payload = this.formPayload(asDraft)
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
					this.elements.dialogTitle.textContent = translate('Edit note')
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

				await this.refreshNotes(false)
				// Saving as a draft, or with dates outside the display period,
				// moves the note to another tab - follow it instead of letting it
				// silently disappear from the current one.
				const saved = this.notes.find((note) => note.id === savedId)
				this.setView(saved ? viewOf(saved) : 'board')
				this.closeEditor()
				this.showToast(asDraft
					? translate('Draft saved.')
					: (!response.note.isApproved
						? translate('Submitted for approval.')
						: (id ? translate('Note updated.') : translate('Note published.'))))
			} catch (error) {
				await this.refreshNotes(false)
				this.showFormError(error.message || translate('The note could not be saved.'), error.field)
			} finally {
				this.setSaving(false)
			}
		}

		async deleteActiveNote() {
			const question = translate('Delete “{title}”?', {title: this.activeNote?.title || ''})
			if (!this.activeNote || !window.confirm(question)) {
				return
			}
			this.setSaving(true)
			this.hideFormError()
			try {
				await this.request(`${this.apiUrl}/${this.activeNote.id}`, {method: 'DELETE'})
				await this.refreshNotes()
				this.closeEditor()
				this.showToast(translate('Note deleted.'))
			} catch (error) {
				this.showFormError(error.message || translate('The note could not be deleted.'))
			} finally {
				this.setSaving(false)
			}
		}

		async approveNote(note, closeViewer = false) {
			try {
				await this.request(`${this.apiUrl}/${note.id}/approve`, {method: 'POST'})
				await this.refreshNotes()
				if (closeViewer) {
					this.closeViewer()
				}
				this.showToast(translate('Note approved.'))
			} catch (error) {
				this.showToast(error.message || translate('The note could not be approved.'))
			}
		}

		formPayload(asDraft) {
			return {
				isDraft: asDraft,
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
				const fallback = translate('The request failed ({status}).', {status: response.status})
				const error = new Error(data?.error?.message || fallback)
				error.field = data?.error?.field || ''
				throw error
			}
			return data || {}
		}

		showFormError(message, field = '') {
			this.elements.formErrorText.textContent = message
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
			this.elements.formErrorText.textContent = ''
		}

		setSaving(saving) {
			this.elements.save.disabled = saving
			this.elements.saveDraft.disabled = saving
			this.elements.deleteNote.disabled = saving
			this.elements.cancel.disabled = saving
			this.elements.save.textContent = saving ? translate('Saving…') : this.saveLabel()
		}

		showToast(message) {
			window.clearTimeout(this.toastTimer)
			this.elements.toast.textContent = message
			this.elements.toast.hidden = false
			this.toastTimer = window.setTimeout(() => {
				this.elements.toast.hidden = true
			}, 3200)
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
				return translate('Open link')
			}
		}

		/**
		 * The Dashboard widget links to #note-<id>, which should open that note
		 * rather than just land on the board. Runs once per page load, otherwise
		 * the dialog would reopen on every filter change.
		 */
		openLinkedNote() {
			if (this.linkHandled) {
				return
			}
			const match = /^#note-(\d+)$/.exec(window.location.hash)
			if (!match) {
				return
			}
			this.linkHandled = true
			const note = this.notes.find((item) => item.id === Number(match[1]))
			if (!note) {
				return
			}
			// The note may live in another tab than the one currently shown.
			if (viewOf(note) !== this.view) {
				this.view = viewOf(note)
				this.render()
			}
			const card = document.getElementById(`note-${note.id}`)
			if (card) {
				requestAnimationFrame(() => card.scrollIntoView({block: 'center'}))
			}
			this.openViewer(note)
		}

		formatCount(count) {
			return translatePlural('%n note', '%n notes', count)
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
