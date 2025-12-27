#!/bin/bash

# Download and unpack customised jquery-ui archive or provide preview link

set -euo pipefail

readonly VERSION=1.14.1
readonly zThemeParams_json=jquery-ui-ThemeParams.json

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
	cat <<-EOF
		$0 [-h|--help] [--preview-url]

		    Download a customized jquery-ui archive, if no parameters a given
		    Otherwise:

		    -h --help      Show this text
		    --preview-url  Show URL to the jquery-ui download page, initialised
		                   with the parameters being in use by this script

		EOF
elif [ "${1:-}" = "--preview-url" ]; then
	URL=https://jqueryui.com/download/
	components=110001110001000000000010010010010000000000000000

	# Get theme params from https://jqueryui.com/themeroller/#! url query string
	# after initial download.
	# `components` can be copied directly, whilezThemeParams need to be unpacked:
	# ```sh
	# zThemeParams=5d00008000ffffffffffffffff003d9f5cfffffffff0000000
	# xxd -r -p <<< "$zThemeParams" | unlzma | jq
	# ```

	themeParams=$(
		jq --compact-output --join-output < "$SCRIPTDIR/$zThemeParams_json" |
		lzma |
		xxd -p -c 0
	)

	echo "${URL}#!version=$VERSION&components=$components&zThemeParams=$themeParams"
else
	URL=https://download.jqueryui.com/download

	components=$(sed ':n N; s/\n/\&/g; tn' <<-EOF
		version=$VERSION
		widget=1
		position=1
		form-reset-mixin=1
		jquery-patch=1
		keycode=1
		unique-id=1
		widgets/datepicker=1
		widgets/mouse=1
		widgets/slider=1
		widgets/tooltip=1
		EOF
	)

	themeParams=$(
		jq -r 'to_entries | map(.key+"="+.value) | join("%26")' \
			< "$SCRIPTDIR/$zThemeParams_json"
	)

	if [ -e "$SCRIPTDIR/jquery-ui-$VERSION" ]; then
		now=$(date +'%F_%T' | tr : -)
		echo "Moved existing installation to 'jquery-ui-${VERSION}_$now'." >&2
		mv "$SCRIPTDIR/jquery-ui-$VERSION" "$SCRIPTDIR/jquery-ui-${VERSION}-$now"
	fi

	wget --output-document="$SCRIPTDIR/jquery-ui-$VERSION.zip" \
		--post-data="$components&theme=$themeParams" "$URL"

	unzip -o -d "$SCRIPTDIR" "$SCRIPTDIR/jquery-ui-$VERSION.zip"
	mv "$SCRIPTDIR/jquery-ui-$VERSION.custom" "$SCRIPTDIR/jquery-ui-$VERSION"
	rm "$SCRIPTDIR/jquery-ui-$VERSION.zip"

	mkdir -p "$SCRIPTDIR/jquery-ui-$VERSION/i18n"
	wget --directory-prefix "$SCRIPTDIR/jquery-ui-$VERSION/i18n" \
		"https://raw.githubusercontent.com/jquery/jquery-ui/refs/tags/$VERSION/ui/i18n/datepicker-de.js"
fi
