# Changelog

## 1.3.2 - 2026-07-31

### Fixed

- Declare MySQL/MariaDB, PostgreSQL, and SQLite as the supported databases so
  Nextcloud does not apply Oracle-only schema restrictions to the app's
  non-null boolean columns during installation.

## 1.3.1 - 2026-07-29

### Fixed

- Revision the Dashboard widget's black icon asset so a previously cached white
  copy is not shown white in light mode and inverted to black in dark mode. The
  app-menu icon is unchanged.

## 1.3.0 - 2026-07-29

### Added

- Add an optional approval workflow under the app's administration settings.
  Administrators can choose moderating users; new and edited notes then stay
  pending until a moderator or administrator approves them.
- Add a pending-approval state and approval controls to cards and note details.
  Pending submissions are visible to their author, moderators, and administrators
  but stay out of the board, archive, and Dashboard widget.

### Changed

- Give administrators full access to drafts and their images as well as
  published and pending notes, so they can edit or remove any content.
- Disabling approval publishes submissions that are still waiting. Editing an
  approved note while the workflow is enabled sends it through approval again.

## 1.2.0 - 2026-07-29

### Added

- Add a draft state with a **Save as draft** button. Drafts stay out of the board,
  the archive and the Dashboard widget until they are published, and the primary
  button of a draft publishes it. Drafts and their images are private to their
  creator except for administrators, who retain moderation access. Adds the
  `is_draft` column.
- Add a third tab, **Drafts & scheduled**, holding drafts and notes whose start
  date still lies ahead. Cards carry a badge saying which of the two they are.
- Add category suggestions to the editor. Typing completes the token after the
  last comma from the categories already in use, which keeps near-duplicates out.
- Suggest existing categories with keyboard support (arrows, Enter, Escape).

### Changed

- Opening a note from the Dashboard widget now opens that note in the app instead
  of only navigating to the board; the app follows a `#note-<id>` link.
- Make a note's own link clickable inside the widget. Items are no longer wrapped
  in a single anchor, since anchors cannot be nested - the title and thumbnail
  link to the note, and a click anywhere else on the row follows them.
- Hide the drafts and archive tabs while they are empty; the board tab always
  stays. Leaving the last note of a tab returns to the board.

## 1.1.0 - 2026-07-29

### Added

- Add an archive tab. The event dates now form the period in which a note is on
  the board: it appears once the start date is reached and moves to the archive
  after the end date, instead of being deleted.
- Add a read-only detail view, opened from the card title or the card body, so a
  clamped description can be read in full without opening the editor.

- Add German translations (`de` informal, `de_DE` formal) covering the interface,
  the Dashboard widget, and all server-side validation messages.
- Add a redesigned Dashboard widget that renders its own items with three
  clamped lines of the description and the note's link.
- Add an optional `limit` parameter to `GET /api/notes`.

### Changed

- Order notes by their last change, falling back to their creation time, so an
  edited note returns to the top of the board and of the Dashboard widget. An
  edit also resets the creation date, so the date a note shows matches its place
  in the list.
- Stop displaying the event dates on notes; they only decide board visibility.
  The sort control went with them, because ordering is now fixed.
- Allow an end date without a start date - both bounds are independently
  optional now that they describe a display period.
- Redesign the board on Nextcloud's design tokens, so the default, light, dark,
  and both high-contrast themes are all covered.
- Lay the notes out as a masonry grid and colour-code them by their first
  category instead of by position.
- Redraw the app icon as a monochrome silhouette and use the same artwork for
  the app menu, the board header, and the Dashboard widget. Following the core
  convention, `img/app.svg` is the white variant the app menu needs on the dark
  header, and `img/app-dark.svg` is the black variant for normal surfaces.
- Replace the placeholder glyphs with a proper icon set.

### Fixed

- Fix the board collapsing to a narrow column: `#content` is a flex row, and the
  app root did not claim the remaining width.
- Fix the editor dialog rendering inline at the bottom of the page and the
  `hidden` attribute being ignored - Nextcloud's CSS reset contains
  `dialog{display:block}`, which defeats both.
- Fix the modal dialog not being centred, because that same reset zeroes the
  margin a modal needs.
- Fix buttons, inputs, and the search icon picking up Nextcloud's global element
  styles, whose `:not()` selectors outrank plain classes.
- Fix note images never loading. Browsers request them through an `<img>` tag,
  which carries no request token, so the read-only route needs `NoCSRFRequired`;
  without it every image answered `412 CSRF check failed`.
- Fix dialogs stretching to the full viewport height. A modal dialog is
  `position: fixed; inset: 0`, where `block-size: auto` fills the viewport, so
  they now use `fit-content` and still clamp at their maximum.
- Fix the focus ring on buttons. Nextcloud repaints focused buttons through
  `button:not(…):focus` and draws a 4px ring in the page background, which turned
  a focused primary button into a flat tinted box behind a muddy double ring.
- Fix the loading indicator staying visible next to the empty state.
- Fix the Dashboard widget overflowing its panel: `.panel--content` is a fixed
  424 px with `overflow: visible`, so the widget now caps itself at the panel
  height and scrolls its own list.

## 1.0.0 - 2026-07-28

- Add the shared notice board with search, category filtering, and sorting.
- Add title, description, categories, calendar details, location, link, and
  image fields.
- Add author/admin edit and delete permissions.
- Add the native Dashboard widget with the five newest notes.
- Add responsive light/dark theme styling and accessible editor interactions.
