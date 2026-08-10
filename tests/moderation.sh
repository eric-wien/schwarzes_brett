#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2026 Schwarzes Brett contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

: "${SB_BASE_URL:?Set SB_BASE_URL to the Nextcloud origin}"
: "${SB_ADMIN_USER:?Set SB_ADMIN_USER to an administrator}"
: "${SB_ADMIN_PASSWORD:?Set SB_ADMIN_PASSWORD to the administrator password}"
: "${SB_AUTHOR_USER:?Set SB_AUTHOR_USER to a non-admin test account}"
: "${SB_AUTHOR_PASSWORD:?Set SB_AUTHOR_PASSWORD to the author password}"
: "${SB_MODERATOR_USER:?Set SB_MODERATOR_USER to a different non-admin test account}"
: "${SB_MODERATOR_PASSWORD:?Set SB_MODERATOR_PASSWORD to the moderator password}"

test "${SB_ADMIN_USER}" != "${SB_AUTHOR_USER}"
test "${SB_ADMIN_USER}" != "${SB_MODERATOR_USER}"
test "${SB_AUTHOR_USER}" != "${SB_MODERATOR_USER}"

APP="${SB_BASE_URL%/}/index.php/apps/schwarzes_brett"
API="${APP}/api/notes"
SETTINGS_API="${APP}/api/settings"
HEADERS=(
	--header "OCS-APIRequest: true"
	--header "Accept: application/json"
)

admin_request() {
	curl --silent --show-error --user "${SB_ADMIN_USER}:${SB_ADMIN_PASSWORD}" "${HEADERS[@]}" "$@"
}

author_request() {
	curl --silent --show-error --user "${SB_AUTHOR_USER}:${SB_AUTHOR_PASSWORD}" "${HEADERS[@]}" "$@"
}

moderator_request() {
	curl --silent --show-error --user "${SB_MODERATOR_USER}:${SB_MODERATOR_PASSWORD}" "${HEADERS[@]}" "$@"
}

original_settings="$(admin_request --fail-with-body "${SETTINGS_API}")"
original_enabled="$(jq -er '.enabled' <<<"${original_settings}")"
original_moderators="$(jq -cer '.moderators' <<<"${original_settings}")"
draft_id=''
pending_id=''
plain_id=''
pixel_file=''

restore_settings() {
	local payload
	payload="$(jq -cn \
		--argjson enabled "${original_enabled}" \
		--argjson moderators "${original_moderators}" \
		'{enabled:$enabled, moderators:$moderators}')"
	admin_request \
		--output /dev/null \
		--header 'Content-Type: application/json' \
		--request PUT \
		--data "${payload}" \
		"${SETTINGS_API}" || true
}

cleanup() {
	for id in "${draft_id}" "${pending_id}" "${plain_id}"; do
		if test -n "${id}"; then
			admin_request --output /dev/null --request DELETE "${API}/${id}" || true
		fi
	done
	test -z "${pixel_file}" || rm -f "${pixel_file}"
	restore_settings
}
trap cleanup EXIT

settings_payload="$(jq -cn \
	--arg moderator "${SB_MODERATOR_USER}" \
	'{enabled:true, moderators:[$moderator]}')"
admin_request \
	--fail-with-body \
	--output /dev/null \
	--header 'Content-Type: application/json' \
	--request PUT \
	--data "${settings_payload}" \
	"${SETTINGS_API}"

# Only administrators may change or inspect the workflow configuration.
test "$(
	moderator_request \
		--output /dev/null \
		--write-out '%{http_code}' \
		"${SETTINGS_API}"
)" = "403"

# Administrators have full access to another user's draft, including its image,
# and may take it down for moderation.
draft="$(
	author_request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{"title":"Moderation test draft","isDraft":true}' \
		"${API}"
)"
draft_id="$(jq -er '.note.id' <<<"${draft}")"
jq -e '.note.isDraft == true and .note.isApproved == true' <<<"${draft}" >/dev/null
jq -e --argjson id "${draft_id}" \
	'.notes[] | select(.id == $id) | .canEdit == true' \
	<<<"$(admin_request --fail-with-body "${API}")" >/dev/null
jq -e --argjson id "${draft_id}" '.notes | any(.id == $id) | not' \
	<<<"$(moderator_request --fail-with-body "${API}")" >/dev/null

pixel_file="$(mktemp "${TMPDIR:-/tmp}/schwarzes-brett-pixel.XXXXXX.png")"
base64 --decode < tests/fixtures/pixel.png.base64 > "${pixel_file}"
author_request \
	--fail-with-body \
	--output /dev/null \
	--request POST \
	--form "image=@${pixel_file};type=image/png" \
	"${API}/${draft_id}/image"
test "$(
	admin_request --output /dev/null --write-out '%{http_code}' "${APP}/notes/${draft_id}/image"
)" = "200"

# Administrators can archive drafts belonging to other users. Draft privacy is
# unchanged: configured moderators still cannot discover or archive them.
jq -e '.note.isArchived == true and .note.canArchive == false and .note.canUnarchive == true' \
	<<<"$(admin_request --fail-with-body --request POST "${API}/${draft_id}/archive")" >/dev/null
test "$(
	moderator_request \
		--output /dev/null \
		--write-out '%{http_code}' \
		--request POST \
		"${API}/${draft_id}/archive"
)" = "404"
test "$(
	moderator_request \
		--output /dev/null \
		--write-out '%{http_code}' \
		--request POST \
		"${API}/${draft_id}/unarchive"
)" = "404"
jq -e '.note.isDraft == true and .note.isArchived == false' \
	<<<"$(admin_request --fail-with-body --request POST "${API}/${draft_id}/unarchive")" >/dev/null
rm -f "${pixel_file}"
pixel_file=''

test "$(
	admin_request \
		--output /dev/null \
		--write-out '%{http_code}' \
		--request DELETE \
		"${API}/${draft_id}"
)" = "204"
draft_id=''

# Publishing while the workflow is enabled creates a private submission. The
# author, configured moderator, and administrator see it; the Dashboard does not.
pending="$(
	author_request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{"title":"Moderation test submission"}' \
		"${API}"
)"
pending_id="$(jq -er '.note.id' <<<"${pending}")"
jq -e '.note.isDraft == false and .note.isApproved == false' <<<"${pending}" >/dev/null
jq -e --argjson id "${pending_id}" \
	'.notes[] | select(.id == $id) | .canApprove == true and .canEdit == false' \
	<<<"$(moderator_request --fail-with-body "${API}")" >/dev/null
jq -e --argjson id "${pending_id}" \
	'.notes[] | select(.id == $id) | .canApprove == true and .canEdit == true' \
	<<<"$(admin_request --fail-with-body "${API}")" >/dev/null
jq -e --argjson id "${pending_id}" '.notes | any(.id == $id) | not' \
	<<<"$(author_request --fail-with-body "${API}?limit=100")" >/dev/null

# The author cannot self-approve.
test "$(
	author_request \
		--output /dev/null \
		--write-out '%{http_code}' \
		--request POST \
		"${API}/${pending_id}/approve"
)" = "403"

approved="$(
	moderator_request \
		--fail-with-body \
		--request POST \
		"${API}/${pending_id}/approve"
)"
jq -e '.note.isApproved == true and .note.canApprove == false' <<<"${approved}" >/dev/null
jq -e --argjson id "${pending_id}" '.notes | any(.id == $id)' \
	<<<"$(author_request --fail-with-body "${API}?limit=100")" >/dev/null

# Moderators can archive notes they did not create. The note remains available
# in the full listing but disappears from the Dashboard listing.
jq -e '.note.isArchived == true and .note.canArchive == false and .note.canUnarchive == true' \
	<<<"$(moderator_request --fail-with-body --request POST "${API}/${pending_id}/archive")" >/dev/null
jq -e --argjson id "${pending_id}" '.notes | any(.id == $id) | not' \
	<<<"$(author_request --fail-with-body "${API}?limit=100")" >/dev/null

# The same moderator can restore the note without gaining edit access.
jq -e '.note.isArchived == false and .note.canEdit == false and .note.canUnarchive == false' \
	<<<"$(moderator_request --fail-with-body --request POST "${API}/${pending_id}/unarchive")" >/dev/null
jq -e --argjson id "${pending_id}" '.notes | any(.id == $id)' \
	<<<"$(author_request --fail-with-body "${API}?limit=100")" >/dev/null

# Editing archived approved content restores it and sends it through approval
# again.
moderator_request \
	--fail-with-body \
	--output /dev/null \
	--request POST \
	"${API}/${pending_id}/archive"
revised="$(
	author_request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request PUT \
		--data '{"title":"Moderation test submission, revised"}' \
		"${API}/${pending_id}"
)"
jq -e '.note.isApproved == false and .note.isArchived == false' <<<"${revised}" >/dev/null

# Turning the workflow off publishes anything still waiting and restores direct
# publishing for subsequent notes.
admin_request \
	--fail-with-body \
	--output /dev/null \
	--header 'Content-Type: application/json' \
	--request PUT \
	--data '{"enabled":false,"moderators":[]}' \
	"${SETTINGS_API}"
jq -e --argjson id "${pending_id}" \
	'.notes[] | select(.id == $id) | .isApproved == true' \
	<<<"$(author_request --fail-with-body "${API}")" >/dev/null

plain="$(
	author_request \
		--fail-with-body \
		--header 'Content-Type: application/json' \
		--request POST \
		--data '{"title":"Moderation disabled test note"}' \
		"${API}"
)"
plain_id="$(jq -er '.note.id' <<<"${plain}")"
jq -e '.note.isApproved == true' <<<"${plain}" >/dev/null

cleanup
trap - EXIT

echo "Schwarzes Brett moderation checks passed."
