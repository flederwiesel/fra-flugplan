#!/bin/bash

set -euo pipefail

readonly FONTFILE=SchibstedGrotesk-Medium.ttf
readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")

if ! [ -f "$SCRIPTDIR/$FONTFILE" ]; then
	unzip -p "$SCRIPTDIR/Schibsted_Grotesk.zip" \
		"static/$FONTFILE" > "$SCRIPTDIR/$FONTFILE"
fi

for ERR in \
	400 401 402 403 404 405 406 407 408 409 \
	410 411 412 413 414 415 416 417 418 419 \
	    421 422 423 424 425 426     428 429 \
	    431 \
	    451 \
	500 501 502 503 504 505 506 507 508 \
	510 511
do
	echo python -m splitflap \
		--digit-width 92 \
		--height 96 \
		--hinge-width 2 \
		--hinge-gravity -3 \
		--font-file "$SCRIPTDIR/$FONTFILE" \
		ERR "$ERR" "$SCRIPTDIR/../errors/$ERR.gif"
done
