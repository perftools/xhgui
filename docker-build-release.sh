#!/bin/sh

set -eu
set -x

prepare() {
	last_tag=$(git describe --tags --abbrev=0)

	# clone to get pristine state (without local files outside vcs)
	rm -rf build
	git clone . build
	cp -al vendor build/vendor
	cd build
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
