# Schwarzes Brett for Nextcloud

Schwarzes Brett is a shared notice board for Nextcloud. Authenticated users can
post notes for everyone on the server, including:

- a required title and an optional description;
- multiple categories;
- a display period (see below), an all-day flag, and a location;
- an external HTTP(S) link and label;
- one JPEG, PNG, GIF, or WebP image up to 5 MB.

Notes are ordered by their last change, falling back to their creation time, so
an edited note returns to the top. Editing a note also resets its creation date,
which keeps the date it shows in step with its position.

The app also registers a Nextcloud Dashboard widget. It shows the five newest
notes with up to three lines of their description plus their link, and links to
the complete board.

The interface follows Nextcloud's design tokens, so the default, light, dark, and
both high-contrast themes are covered, and it is responsive from narrow phones up
to wide desktops. English and German (`de`, `de_DE`) are shipped.

## Tabs

The board has three tabs. **Drafts & scheduled** and **Archive** are hidden while
they are empty, so a board without dated notes shows a single tab.

| tab | holds |
| --- | --- |
| Board | published notes inside their display period |
| Drafts & scheduled | drafts, and notes whose start date still lies ahead |
| Archive | manually archived notes and notes whose end date has passed |

A note saved with **Save as draft** stays out of the board, the archive and the
Dashboard widget until it is published. The start and end dates form the display
period; both are optional and independent:

| start | end | where the note is |
| --- | --- | --- |
| unset | unset | on the board forever |
| reached | unset or future | on the board |
| in the future | any | drafts & scheduled, until the start date is reached |
| any | in the past | archive |

Nothing is deleted when a note leaves the board. The dates are deliberately never
shown on a note - they control visibility only - so the editor is where you see
and change them. A note can also be moved there immediately with **Archive
note**. Cards outside the board carry a badge naming their state.

Notes open in a read-only detail view, from the card title or the card body, so a
clamped description can be read without opening the editor. A Dashboard widget
item links to `#note-<id>`, which the app resolves by opening that note directly.

## Permissions

Every authenticated user can read published notes and create notes. Drafts,
including their images, are private to their author and administrators. A
published note can only be changed or deleted by its author or a Nextcloud
administrator. Administrators have full access to every note so they can
moderate or remove content. Images are private app data and are only served
through an authenticated app route.

Authors can archive their own notes. Configured moderators and administrators
can archive any note visible to them; this permission is enforced by the API as
well as by the interface.

An administrator can enable approval under **Administration settings →
Additional settings → Schwarzes Brett** and choose moderating users. While the
workflow is enabled, new and edited notes stay out of the board and Dashboard
until a moderator or administrator approves them. Pending submissions are
visible only to their author, configured moderators, and administrators.
Disabling approval publishes anything still waiting.

## Requirements

- Nextcloud 32, 33, or 34
- PHP 8.2 or newer
- PHP `fileinfo` extension (part of a standard Nextcloud installation)

There are no runtime Composer packages and no frontend build dependencies.
The committed JavaScript and CSS files are the assets served by Nextcloud.

The 1.0.0 release has been integration-tested with the official Apache images
for Nextcloud 33.0.7 (PHP 8.4) and Nextcloud 34.0.2 (PHP 8.5), using SQLite.
The 1.1.0 interface work was verified against Nextcloud 33.0.7 in the default,
light, dark, and high-contrast themes, and at viewport widths from 320 px up.

## Install from a checkout

Clone or copy this directory to:

```text
<nextcloud>/custom_apps/schwarzes_brett
```

The directory name must stay `schwarzes_brett`, then enable the app:

```sh
sudo -u www-data php occ app:enable schwarzes_brett
```

Nextcloud applies the database migration automatically. Open **Schwarzes Brett**
from the app navigation. The widget can be enabled and positioned from the
Dashboard's customize view.

## Development

The backend follows the standard Nextcloud AppFramework structure:

```text
appinfo/           metadata and routes
lib/Controller/    page, note, and image HTTP endpoints
lib/Db/            note entity and mapper
lib/Service/       validation, permissions, and image storage
lib/Dashboard/     dashboard widget (item API v1 plus custom rendering)
lib/Migration/     portable database schema
templates/         server-rendered application shell
js/ and css/       dependency-free frontend (main + dashboard bundles)
l10n/              translation bundles
```

The Dashboard widget deliberately implements only version 1 of the widget item
API. The Dashboard front-end takes over rendering for widgets that announce
version 2, which allows a title and one subtitle line per item - not enough for
the clamped description and the link. Version 1 keeps the items available to the
mobile and desktop clients while `js/dashboard.js` renders the web view.

Translations live in `l10n/<locale>.json` (server) and `l10n/<locale>.js`
(browser); both files must list the same keys. Plural keys use Nextcloud's
`_singular_::_plural_` form.

Useful checks inside a Nextcloud installation:

```sh
find custom_apps/schwarzes_brett -name '*.php' -print0 | xargs -0 -n1 php -l
php occ app:list
```

Nextcloud 34 no longer ships the former `app:check-code` command. The
`tests/integration.sh` script exercises create, validation, update, list,
archive, and delete behavior against a running instance:

```sh
SB_BASE_URL=http://127.0.0.1:8080 \
SB_USER=admin \
SB_PASSWORD=secret \
./tests/integration.sh
```

To also exercise draft isolation between ordinary users, provide a different
non-admin second account. The test then verifies listing, update, delete, and
image access as the non-author:

```sh
SB_BASE_URL=http://127.0.0.1:8080 \
SB_USER=author \
SB_PASSWORD=secret \
SB_OTHER_USER=another-user \
SB_OTHER_PASSWORD=other-secret \
./tests/integration.sh
```

`tests/moderation.sh` exercises the administrator, author, and configured
moderator roles, including settings authorization, draft takedown, pending
visibility, approval, manual archiving, reapproval after edits, and disabling
the workflow:

```sh
SB_BASE_URL=http://127.0.0.1:8080 \
SB_ADMIN_USER=admin \
SB_ADMIN_PASSWORD=admin-secret \
SB_AUTHOR_USER=author \
SB_AUTHOR_PASSWORD=author-secret \
SB_MODERATOR_USER=moderator \
SB_MODERATOR_PASSWORD=moderator-secret \
./tests/moderation.sh
```

For a production release, package the directory without development-only files
such as `.git`, and keep the built `js/` and `css/` assets in the archive.
`make appstore` creates the correctly structured archive. Maintainer setup and
release steps are documented in [docs/PUBLISHING.md](docs/PUBLISHING.md).

## Data model and limits

Notes are server-wide rather than personal. The database stores note metadata;
uploaded images are stored in the app's private `appdata` directory.
Descriptions are capped at 10,000 characters and notes can have up to 12
categories of 40 characters each.

External URLs are restricted to HTTP and HTTPS. SVG uploads are intentionally
not accepted because active SVG content is difficult to serve safely.

`GET /api/notes` returns every note the requesting user may see. Passing
`?limit=n` returns only the `n` newest approved board notes instead; the
Dashboard widget uses `?limit=5`.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
