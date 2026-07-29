#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

: "${SB_BASE_URL:?Set SB_BASE_URL to the Nextcloud origin}"
: "${SB_USER:?Set SB_USER to a test account}"
: "${SB_PASSWORD:?Set SB_PASSWORD to the test account password}"

API="${SB_BASE_URL%/}/index.php/apps/schwarzes_brett/api/notes"
AUTH=("${SB_USER}:${SB_PASSWORD}")
OTHER_AUTH=("${SB_OTHER_USER:-}:${SB_OTHER_PASSWORD:-}")
HEADERS=(
	--header "OCS-APIRequest: true"
	--header "Accept: application/json"
)

if test -n "${SB_OTHER_USER:-}" || test -n "${SB_OTHER_PASSWORD:-}"; then
	: "${SB_OTHER_USER:?Set both SB_OTHER_USER and SB_OTHER_PASSWORD}"
	: "${SB_OTHER_PASSWORD:?Set both SB_OTHER_USER and SB_OTHER_PASSWORD}"
fi

request() {
	curl --silent --show-error --user "${AUTH[0]}" "${HEADERS[@]}" "$@"
}

request_as_other() {
	curl --silent --show-error --user "${OTHER_AUTH[0]}" "${HEADERS[@]}" "$@"
}

status="$(
	request \
		--output /dev/null \
		--write-out '%{http_code}' \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{"title":"  "}' \
		"${API}"
)"
test "${status}" = "422"

created="$(
	request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{
			"title": "Integration test note",
			"content": "Created by tests/integration.sh",
			"categories": ["Test", "Automated"],
			"location": "CI",
			"linkUrl": "https://nextcloud.com/",
			"linkLabel": "Nextcloud"
		}' \
		"${API}"
)"
note_id="$(jq -er '.note.id' <<<"${created}")"
trap 'request --output /dev/null --request DELETE "${API}/${note_id}" || true' EXIT

jq -e '
	.note.title == "Integration test note"
	and .note.categories == ["Test", "Automated"]
	and .note.canEdit == true
' <<<"${created}" >/dev/null

updated="$(
	request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request PUT \
		--data '{
			"title": "Updated integration note",
			"content": "The update endpoint works.",
			"categories": ["Test"]
		}' \
		"${API}/${note_id}"
)"
jq -e '.note.title == "Updated integration note"' <<<"${updated}" >/dev/null

listed="$(request --fail-with-body "${API}")"
jq -e --argjson id "${note_id}" '.notes | any(.id == $id)' <<<"${listed}" >/dev/null

# Notes are ordered by their last change, so the note just updated comes first.
jq -e --argjson id "${note_id}" '.notes[0].id == $id' <<<"${listed}" >/dev/null

# An end date in the past takes a note off the board and into the archive, which
# the limited listing used by the Dashboard widget must not return.
request \
	--fail-with-body \
	--output /dev/null \
	--header 'Content-Type: application/json' \
	--request PUT \
	--data '{"title":"Updated integration note","eventEnd":1000000000}' \
	"${API}/${note_id}"
jq -e --argjson id "${note_id}" '.notes | any(.id == $id) | not' \
	<<<"$(request --fail-with-body "${API}?limit=5")" >/dev/null
jq -e --argjson id "${note_id}" '.notes | any(.id == $id)' \
	<<<"$(request --fail-with-body "${API}")" >/dev/null

# A draft is likewise kept out of the widget listing until it is published.
draft="$(
	request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{"title":"Integration test draft","isDraft":true}' \
		"${API}"
)"
draft_id="$(jq -er '.note.id' <<<"${draft}")"
trap 'request --output /dev/null --request DELETE "${API}/${note_id}" || true
      request --output /dev/null --request DELETE "${API}/${draft_id}" || true
      test -z "${pixel_file:-}" || rm -f "${pixel_file}"' EXIT
jq -e '.note.isDraft == true' <<<"${draft}" >/dev/null
jq -e --argjson id "${draft_id}" '.notes | any(.id == $id)' \
	<<<"$(request --fail-with-body "${API}")" >/dev/null
jq -e --argjson id "${draft_id}" '.notes | any(.id == $id) | not' \
	<<<"$(request --fail-with-body "${API}?limit=5")" >/dev/null

if test -n "${SB_OTHER_USER:-}"; then
	# Draft privacy is enforced server-side. A non-author cannot discover the
	# draft through the full listing or through mutation and image routes.
	jq -e --argjson id "${draft_id}" '.notes | any(.id == $id) | not' \
		<<<"$(request_as_other --fail-with-body "${API}")" >/dev/null

	status="$(
		request_as_other \
			--output /dev/null \
			--write-out '%{http_code}' \
			--header 'Content-Type: application/json' \
			--request PUT \
			--data '{"title":"A draft belonging to someone else"}' \
			"${API}/${draft_id}"
	)"
	test "${status}" = "404"

	status="$(
		request_as_other \
			--output /dev/null \
			--write-out '%{http_code}' \
			--request DELETE \
			"${API}/${draft_id}"
	)"
	test "${status}" = "404"

	pixel_file="$(mktemp "${TMPDIR:-/tmp}/schwarzes-brett-pixel.XXXXXX.png")"
	base64 --decode < tests/fixtures/pixel.png.base64 > "${pixel_file}"
	request \
		--fail-with-body \
		--output /dev/null \
		--request POST \
		--form "image=@${pixel_file};type=image/png" \
		"${API}/${draft_id}/image"

	image_url="${SB_BASE_URL%/}/index.php/apps/schwarzes_brett/notes/${draft_id}/image"
	test "$(
		request --output /dev/null --write-out '%{http_code}' "${image_url}"
	)" = "200"
	test "$(
		request_as_other --output /dev/null --write-out '%{http_code}' "${image_url}"
	)" = "404"

	rm -f "${pixel_file}"
	pixel_file=''
fi

published="$(
	request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request PUT \
		--data '{"title":"Integration test draft","isDraft":false}' \
		"${API}/${draft_id}"
)"
jq -e '.note.isDraft == false' <<<"${published}" >/dev/null
jq -e --argjson id "${draft_id}" '.notes | any(.id == $id)' \
	<<<"$(request --fail-with-body "${API}?limit=5")" >/dev/null
if test -n "${SB_OTHER_USER:-}"; then
	jq -e --argjson id "${draft_id}" '.notes | any(.id == $id)' \
		<<<"$(request_as_other --fail-with-body "${API}")" >/dev/null
fi

request --fail-with-body --output /dev/null --request DELETE "${API}/${draft_id}"
trap 'request --output /dev/null --request DELETE "${API}/${note_id}" || true' EXIT

# The Dashboard widget relies on ?limit=n returning at most n newest notes.
limited="$(request --fail-with-body "${API}?limit=1")"
jq -e '(.notes | length) <= 1' <<<"${limited}" >/dev/null

request --fail-with-body --output /dev/null --request DELETE "${API}/${note_id}"
trap - EXIT

echo "Schwarzes Brett integration checks passed."
