#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

: "${SB_BASE_URL:?Set SB_BASE_URL to the Nextcloud origin}"
: "${SB_USER:?Set SB_USER to a test account}"
: "${SB_PASSWORD:?Set SB_PASSWORD to the test account password}"

API="${SB_BASE_URL%/}/index.php/apps/schwarzes_brett/api/notes"
AUTH=("${SB_USER}:${SB_PASSWORD}")
HEADERS=(
	--header "OCS-APIRequest: true"
	--header "Accept: application/json"
)

request() {
	curl --silent --show-error --user "${AUTH[0]}" "${HEADERS[@]}" "$@"
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

request --fail-with-body --output /dev/null --request DELETE "${API}/${note_id}"
trap - EXIT

echo "Schwarzes Brett integration checks passed."
