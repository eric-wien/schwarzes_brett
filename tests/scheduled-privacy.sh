#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

# Use three distinct test accounts on a disposable instance: this changes
# moderation settings temporarily and restores them on exit.
set -euo pipefail

: "${SB_BASE_URL:?Set SB_BASE_URL to the Nextcloud origin}"
: "${SB_ADMIN_USER:?Set SB_ADMIN_USER to an administrator}"
: "${SB_ADMIN_PASSWORD:?Set SB_ADMIN_PASSWORD}"
: "${SB_AUTHOR_USER:?Set SB_AUTHOR_USER to a non-admin test account}"
: "${SB_AUTHOR_PASSWORD:?Set SB_AUTHOR_PASSWORD}"
: "${SB_MODERATOR_USER:?Set SB_MODERATOR_USER to another non-admin test account}"
: "${SB_MODERATOR_PASSWORD:?Set SB_MODERATOR_PASSWORD}"

test "${SB_ADMIN_USER}" != "${SB_AUTHOR_USER}"
test "${SB_ADMIN_USER}" != "${SB_MODERATOR_USER}"
test "${SB_AUTHOR_USER}" != "${SB_MODERATOR_USER}"

APP="${SB_BASE_URL%/}/index.php/apps/schwarzes_brett"
API="${APP}/api/notes"
SETTINGS_API="${APP}/api/settings"
HEADERS=(--header 'OCS-APIRequest: true' --header 'Accept: application/json')

admin_request() {
	curl --silent --show-error --user "${SB_ADMIN_USER}:${SB_ADMIN_PASSWORD}" "${HEADERS[@]}" "$@"
}
author_request() {
	curl --silent --show-error --user "${SB_AUTHOR_USER}:${SB_AUTHOR_PASSWORD}" "${HEADERS[@]}" "$@"
}
other_request() {
	curl --silent --show-error --user "${SB_MODERATOR_USER}:${SB_MODERATOR_PASSWORD}" "${HEADERS[@]}" "$@"
}
settings() {
	admin_request --fail-with-body --output /dev/null \
		--header 'Content-Type: application/json' --request PUT --data "$1" "${SETTINGS_API}"
}

original_settings="$(admin_request --fail-with-body "${SETTINGS_API}" | jq -c '{enabled,moderators}')"
note_id=''
pixel_file=''
cleanup() {
	if test -n "${note_id}"; then
		admin_request --output /dev/null --request DELETE "${API}/${note_id}" || true
	fi
	test -z "${pixel_file}" || rm -f "${pixel_file}"
	settings "${original_settings}" || true
}
trap cleanup EXIT

settings '{"enabled":true,"moderators":[]}'
created="$(author_request --fail-with-body --header 'Content-Type: application/json' \
	--request POST --data '{"title":"Scheduled privacy test","publishAt":4102444800}' "${API}")"
note_id="$(jq -er '.note.id' <<<"${created}")"
jq -e '.note.isApproved == false and .note.isDraft == false' <<<"${created}" >/dev/null
pixel_file="$(mktemp "${TMPDIR:-/tmp}/schwarzes-brett-pixel.XXXXXX.png")"
base64 --decode < tests/fixtures/pixel.png.base64 > "${pixel_file}"
author_request --fail-with-body --output /dev/null --request POST \
	--form "image=@${pixel_file};type=image/png" "${API}/${note_id}/image"
image_url="${APP}/notes/${note_id}/image"

# A future publication time remains private regardless of approval or manual
# archiving. Moderators must not gain more access than ordinary non-authors.
for state in pending approved archived; do
	if test "${state}" = approved; then
		admin_request --fail-with-body --output /dev/null --request POST "${API}/${note_id}/approve"
	elif test "${state}" = archived; then
		author_request --fail-with-body --output /dev/null --request POST "${API}/${note_id}/archive"
	fi
	for role in ordinary moderator; do
		moderators='[]'
		if test "${role}" = moderator; then
			moderators="$(jq -cn --arg user "${SB_MODERATOR_USER}" '[$user]')"
		fi
		settings "$(jq -cn --argjson moderators "${moderators}" '{enabled:true,moderators:$moderators}')"
		for request in author_request admin_request; do
			jq -e --argjson id "${note_id}" '.notes | any(.id == $id)' \
				<<<"$("${request}" --fail-with-body "${API}")" >/dev/null
			test "$("${request}" --output /dev/null --write-out '%{http_code}' "${image_url}")" = 200
		done
		for suffix in '' '?limit=100'; do
			jq -e --argjson id "${note_id}" '.notes | any(.id == $id) | not' \
				<<<"$(other_request --fail-with-body "${API}${suffix}")" >/dev/null
		done
		test "$(other_request --output /dev/null --write-out '%{http_code}' "${image_url}")" = 404
		for action in approve archive unarchive; do
			test "$(other_request --output /dev/null --write-out '%{http_code}' \
				--request POST "${API}/${note_id}/${action}")" = 404
		done
		test "$(other_request --output /dev/null --write-out '%{http_code}' \
			--header 'Content-Type: application/json' --request PUT \
			--data '{"title":"Unauthorized edit"}' "${API}/${note_id}")" = 404
		test "$(other_request --output /dev/null --write-out '%{http_code}' \
			--request DELETE "${API}/${note_id}")" = 404
		test "$(other_request --output /dev/null --write-out '%{http_code}' \
			--request POST --form "image=@${pixel_file};type=image/png" "${API}/${note_id}/image")" = 404
		test "$(other_request --output /dev/null --write-out '%{http_code}' \
			--request DELETE "${API}/${note_id}/image")" = 404
	done
done

# Once publication time is reached, normal moderation rules resume. Future
# event dates and a future archive date must not make a published note private.
author_request --fail-with-body --output /dev/null --header 'Content-Type: application/json' \
	--request PUT --data '{"title":"Publication reached","publishAt":1000000000,"eventStart":4102444800,"archiveAt":4102531200}' \
	"${API}/${note_id}"
jq -e --argjson id "${note_id}" '.notes | any(.id == $id and .canApprove == true)' \
	<<<"$(other_request --fail-with-body "${API}")" >/dev/null
other_request --fail-with-body --output /dev/null --request POST "${API}/${note_id}/approve"
settings '{"enabled":true,"moderators":[]}'
for suffix in '' '?limit=100'; do
	jq -e --argjson id "${note_id}" '.notes | any(.id == $id)' \
		<<<"$(other_request --fail-with-body "${API}${suffix}")" >/dev/null
done
test "$(other_request --output /dev/null --write-out '%{http_code}' "${image_url}")" = 200

echo 'Schwarzes Brett scheduled-note privacy checks passed.'
