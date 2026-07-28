# Schwarzes Brett for Nextcloud

Schwarzes Brett is a shared notice board for Nextcloud. Authenticated users can
post notes for everyone on the server, including:

- a required title and an optional description;
- multiple categories;
- start/end dates, all-day events, and a location;
- an external HTTP(S) link and label;
- one JPEG, PNG, GIF, or WebP image up to 5 MB.

The app also registers a native Nextcloud Dashboard widget. It shows exactly the
five newest notes and links to the complete board.

## Permissions

Every authenticated user can read and create notes. A note can only be changed
or deleted by its author or a Nextcloud administrator. Images are private app
data and are only served through an authenticated app route.

## Requirements

- Nextcloud 32, 33, or 34
- PHP 8.2 or newer
- PHP `fileinfo` extension (part of a standard Nextcloud installation)

There are no runtime Composer packages and no frontend build dependencies.
The committed JavaScript and CSS files are the assets served by Nextcloud.

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
lib/Dashboard/     native IAPIWidgetV2 dashboard integration
lib/Migration/     portable database schema
templates/         server-rendered application shell
js/ and css/       dependency-free frontend
```

Useful checks inside a Nextcloud installation:

```sh
find custom_apps/schwarzes_brett -name '*.php' -print0 | xargs -0 -n1 php -l
php occ app:list
```

Nextcloud 34 no longer ships the former `app:check-code` command. The
`tests/integration.sh` script exercises create, validation, update, list, and
delete behavior against a running instance:

```sh
SB_BASE_URL=http://127.0.0.1:8080 \
SB_USER=admin \
SB_PASSWORD=secret \
./tests/integration.sh
```

For a production release, package the directory without development-only files
such as `.git`, and keep the built `js/` and `css/` assets in the archive.

## Data model and limits

Notes are server-wide rather than personal. The database stores note metadata;
uploaded images are stored in the app's private `appdata` directory.
Descriptions are capped at 10,000 characters and notes can have up to 12
categories of 40 characters each.

External URLs are restricted to HTTP and HTTPS. SVG uploads are intentionally
not accepted because active SVG content is difficult to serve safely.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
