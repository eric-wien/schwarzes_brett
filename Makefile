app_name := schwarzes_brett
version := $(shell sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' appinfo/info.xml)

build_dir := build
stage_dir := $(build_dir)/staging/$(app_name)
artifact_dir := $(build_dir)/artifacts/appstore
archive := $(artifact_dir)/$(app_name)-v$(version).tar.gz

production_dirs := appinfo css img js l10n lib templates
production_files := CHANGELOG.md LICENSE README.md

.PHONY: appstore archive-path clean verify-release

appstore: verify-release
	rm -rf "$(stage_dir)"
	mkdir -p "$(stage_dir)" "$(artifact_dir)"
	cp -R $(production_dirs) "$(stage_dir)/"
	cp $(production_files) "$(stage_dir)/"
	tar -C "$(build_dir)/staging" -czf "$(archive)" "$(app_name)"
	@echo "Created $(archive)"

archive-path:
	@echo "$(archive)"

verify-release:
	@test "$(app_name)" = "$$(sed -n 's:.*<id>\([^<]*\)</id>.*:\1:p' appinfo/info.xml)" || \
		{ echo "App id in appinfo/info.xml must be $(app_name)" >&2; exit 1; }
	@test -n "$(version)" || \
		{ echo "No version found in appinfo/info.xml" >&2; exit 1; }
	@grep -Eq '^## $(version)( |$$)' CHANGELOG.md || \
		{ echo "CHANGELOG.md has no $(version) release entry" >&2; exit 1; }
	@grep -q '<licence>AGPL-3.0-or-later</licence>' appinfo/info.xml || \
		{ echo "appinfo/info.xml must use an allowed SPDX licence identifier" >&2; exit 1; }
	@grep -q '<bugs>https://' appinfo/info.xml || \
		{ echo "appinfo/info.xml must contain an HTTPS bug tracker URL" >&2; exit 1; }

clean:
	rm -rf "$(build_dir)"
