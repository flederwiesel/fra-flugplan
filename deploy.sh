#!/bin/bash

set -euo pipefail

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
readonly root=httpdocs/vault/fra-flugplan

git -C "$SCRIPTDIR" diff-index --quiet HEAD -- ||
{
	echo "Working copy is dirty. Aborting." >&2
	exit 1
}

if ! tag=$(git -C "$SCRIPTDIR" describe --tags --exact-match); then
	echo "Cannot determine tag." >&2
	exit 1
fi

rev=$(git -C "$SCRIPTDIR" log -1 --no-show-signature --pretty="Version $tag@%h %cd")

# If checked out under Windows, cygwin permissions are wrong, which
# may lead to permissions being wrong on the server.

ssh fra-flugplan.de "mkdir -p $root httpdocs/var/log"
ssh fra-flugplan.de "rm -rf $root/$tag"
rsync -av \
--exclude="~*" \
--exclude="*~" \
--exclude="*.pdn" \
--filter="+ .config" \
--filter="+ .htaccess" \
--filter="+ apple-touch-icon.png" \
--filter="+ classes/" \
--filter="+ classes/*" \
--filter="+ content/" \
--filter="+ content/*" \
--filter="+ content/img/de" \
--filter="+ content/img/de/*" \
--filter="+ content/img/en" \
--filter="+ content/img/en/*" \
--filter="+ content/language/*" \
--filter="+ css/" \
--filter="+ css/*" \
--filter="+ css/ie/*" \
--filter="+ error.css" \
--filter="+ error.php" \
--filter="+ favicon.*" \
--filter="+ forms/" \
--filter="+ forms/*" \
--filter="+ getflights.*" \
--filter="+ git-rev" \
--filter="+ img/" \
--filter="- img/src" \
--filter="+ img/**" \
--filter="+ index.php" \
--filter="+ lib/" \
--filter="+ lib/Mobile-Detect/" \
--filter="+ lib/Mobile-Detect/src/" \
--filter="+ lib/Mobile-Detect/src/MobileDetect.php" \
--filter="- lib/*" \
--filter="+ nav.php" \
--filter="+ photodb.php" \
--filter="+ robots.txt" \
--filter="+ script/" \
--filter="+ script/*" \
--filter="+ script/jquery*/***" \
--filter="+ user.php" \
--filter="- *" \
"$SCRIPTDIR/" fra-flugplan.de:"$root/$tag"

ssh fra-flugplan.de <<EOF
echo "$tag" > "$root/target"
echo "$rev" > "$root/$tag/git-rev"
cp "\$HOME/.config/fra-flugplan/.config" "$root/$tag/.config"
EOF

echo -e "\033[32mSUCCESS.\033[m"
