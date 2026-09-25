/*
 * SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Run without a browser or dependencies: node --test tests/scheduling.cjs
const assert = require('node:assert/strict')
const {readFileSync} = require('node:fs')
const {test} = require('node:test')
const vm = require('node:vm')

const now = 1800000000
const source = readFileSync(`${__dirname}/../js/main.js`, 'utf8')
const context = {
	Date: class extends Date { static now() { return now * 1000 } },
	Intl,
	window: {},
	document: {getElementById: () => ({})},
}
// Expose the private app without booting its network/DOM event handlers.
vm.runInNewContext(source.replace('new BoardApp(root).start()',
	'globalThis.app = {viewOf, BoardApp}'), context)
const {viewOf, BoardApp} = context.app
const note = {isApproved: true, eventStart: now + 100, eventEnd: now + 200}

function element() {
	return {children: [], append(...children) { this.children.push(...children) }}
}

test('only scheduling dates determine the tab, including exact boundaries', () => {
	assert.equal(viewOf(note), 'board')
	assert.equal(viewOf({...note, eventStart: 0, eventEnd: now - 1}), 'board')
	assert.equal(viewOf({...note, publishAt: now + 1}), 'pending')
	assert.equal(viewOf({...note, publishAt: now}), 'board')
	assert.equal(viewOf({...note, archiveAt: now + 1}), 'board')
	assert.equal(viewOf({...note, archiveAt: now}), 'archive')
	assert.equal(viewOf({...note, archiveAt: 0}), 'archive')
	assert.equal(viewOf({...note, archiveAt: now, isDraft: true}), 'pending')
	assert.equal(viewOf({...note, archiveAt: now, isApproved: false}), 'pending')
	assert.equal(viewOf({...note, isDraft: true, isArchived: true}), 'archive')
})

test('form payload round-trips both date pairs independently', () => {
	const app = Object.create(BoardApp.prototype)
	app.elements = Object.fromEntries([
		'title', 'content', 'categories', 'eventStart', 'eventEnd',
		'publishAt', 'archiveAt', 'location', 'linkUrl', 'linkLabel',
	].map(name => [name, {value: ''}]))
	app.elements.allDay = {checked: false}
	const dates = {eventStart: now + 600, eventEnd: now + 1200,
		publishAt: now - 600, archiveAt: now + 1800}
	for (const [field, value] of Object.entries(dates)) {
		app.elements[field].value = app.toDateTimeInput(value)
	}
	const payload = app.formPayload(false)
	for (const [field, value] of Object.entries(dates)) {
		assert.equal(payload[field], value)
		app.elements[field].value = ''
	}
	const empty = app.formPayload(true)
	for (const field of Object.keys(dates)) assert.equal(empty[field], null)
	assert.equal(empty.isDraft, true)
	assert.notEqual(app.toDateTimeInput(0), '')
})

test('cards and details show event dates, never scheduling dates', () => {
	const app = Object.create(BoardApp.prototype)
	app.createElement = element
	app.createMetaRow = (icon, text) => ({icon, text})
	assert.equal(app.createMeta({publishAt: now, archiveAt: now + 100}).children.length, 0)
	for (const isAllDay of [false, true]) {
		const format = timestamp => new Intl.DateTimeFormat(undefined, {
			dateStyle: 'medium', ...(isAllDay ? {} : {timeStyle: 'short'}),
		}).format(new Date(timestamp * 1000))
		assert.equal(app.createMeta({...note, isAllDay}).children[0].text,
			`${format(note.eventStart)} – ${format(note.eventEnd)}`)
		assert.equal(app.createMeta({eventStart: 0, isAllDay}).children[0].text,
			`Starts: ${format(0)}`)
		assert.equal(app.createMeta({eventEnd: now, isAllDay}).children[0].text,
			`Ends: ${format(now)}`)
	}
})

test('German server and browser translations agree', () => {
	for (const locale of ['de', 'de_DE']) {
		let browser
		vm.runInNewContext(readFileSync(`${__dirname}/../l10n/${locale}.js`, 'utf8'), {
			OC: {L10N: {register: (app, translations) => { browser = translations }}},
		})
		const server = JSON.parse(readFileSync(`${__dirname}/../l10n/${locale}.json`, 'utf8'))
		assert.deepEqual(JSON.parse(JSON.stringify(browser)), server.translations)
	}
})
