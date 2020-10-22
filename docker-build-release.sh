#!/bin/sh

set -eu
set -x

cleanup_vendor() {
	rm -rf vendor/twig/twig/ext/twig
	rm -rf vendor/pimple/pimple/src/Pimple/Tests
	rm -rf vendor/twig/twig/src/Test
	find vendor -name doc -print0 | sort -zr | xargs -0 -r rm -r
	find vendor -name docs -print0 | sort -zr | xargs -0 -r rm -r
	find vendor -name tests -print0 | sort -zr | xargs -0 -r rm -r
}

prepare() {
	last_tag=$(git describe --tags --abbrev=0)

	# clone to get pristine state (without local files outside vcs)
	rm -rf build
	git clone . build
	cp -al vendor build/vendor
	cd build

	# docs and tests only increase image size
	cleanup_vendor
}

build() {
	docker pull xhgui/xhgui:$last_tag
	docker build --cache-from=xhgui/xhgui:$last_tag . -t xhgui/xhgui:latest
}

cleanup() {
	cd ..
	rm -rf build
}

prepare
build
cleanup
