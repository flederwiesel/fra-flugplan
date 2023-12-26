#!/bin/bash

SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
TOPDIR=$(git -C "$SCRIPTDIR" rev-parse --show-toplevel)

queries=$(
	grep -ERho '/\* *\[ *Q[0-9]+ *\] *\*/' "$TOPDIR"/*.php "$TOPDIR/content/"*.php |
	sed 's/[^0-9]//g' |
	sort -nu
)

cat <<EOF

=== Gaps: ===

$(
	for q in $(awk '$1!=p+1{print p+1}{p=$1}' <<< "$queries")
	do
		echo "/*[Q$q]*/"
	done
)

=== Next available: ===

$(
	last=$(tail -n 1 <<< "$queries")
	echo "/*[Q$((last + 1))]*/"
)
EOF
