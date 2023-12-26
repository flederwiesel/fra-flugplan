#!/bin/bash

set -euo pipefail

SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
TOPDIR=$(git -C "$SCRIPTDIR" rev-parse --show-toplevel)

queries=$(
	grep -ERho '/\* *\[ *Q[0-9]+ *\] *\*/' "$TOPDIR"/*.php "$TOPDIR/content/"*.php "$TOPDIR/forms/"*.php |
	sed 's/[^0-9]//g' |
	sort -n
)

dups=$(uniq -d <<< "$queries" | sed 's?.*?/*[Q&]*/?g')
gaps=$(uniq <<< "$queries" | awk 'BEGIN { last = 0 } { while (++last < $1) print "/*[Q" last "]*/" }')
next=$((${queries##*$'\n'} + 1))

if [[ ${dups:-} ]]; then
cat <<EOF
=== Duplicates: ===
$dups
EOF
fi

if [[ ${gaps:-} ]]; then
cat <<EOF
=== Gaps: ===
$gaps
EOF
fi

cat <<EOF
=== Next available: ===
$(echo "/*[Q$next]*/")
EOF
