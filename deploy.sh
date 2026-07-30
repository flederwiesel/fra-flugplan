#!/bin/bash

set -euo pipefail

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
readonly root=httpdocs/vault/fra-flugplan

args=()

for arg
do
	case "$arg" in
	--branch)
		targetdir=$(git branch --show-current)
		;;
	--help)
		echo "Usage:"
		echo
		echo -e " \033[1;37m$(basename "$0")\033[m [--branch] [--help]"
		echo
		echo "  Arguments:"
		echo
		echo "    --branch  Use current branch name as remote dir rather than"
		echo "              the current tag, which is the default."
		echo
		echo "    --help    Show this message."
		echo
		exit
		;;
	*)
		args+=("$arg")
		;;
	esac
done

set -- "${args[@]}"

git -C "$SCRIPTDIR" diff-index --quiet HEAD -- ||
{
	echo "Working copy is dirty. Aborting." >&2
	exit 1
}

if [ -z "${targetdir:-}" ]; then
	if ! targetdir=$(git -C "$SCRIPTDIR" describe --tags --exact-match); then
		echo "Cannot determine tag." >&2
		exit 1
	fi
fi

rev=$(
	git -C "$SCRIPTDIR" log -1 \
		--no-show-signature --pretty="Version $targetdir@%h %cd"
)

# If checked out under Windows, cygwin permissions are wrong, which
# may lead to permissions being wrong on the server.

ssh fra-flugplan.de "rm -rf $root/$targetdir"
ssh fra-flugplan.de "mkdir -p $root/$targetdir httpdocs/var/log"
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
--filter="- script/get-jquery-ui.sh" \
--filter="- script/jquery-ui-ThemeParams.json" \
--filter="+ script/*" \
--filter="+ script/jquery*/***" \
--filter="+ user.php" \
--filter="- *" \
"$SCRIPTDIR/" fra-flugplan.de:"$root/$targetdir/"

ssh fra-flugplan.de <<EOF
echo "$targetdir" > "$root/target"
echo "$rev" > "$root/$targetdir/git-rev"
cp "\$HOME/.config/fra-flugplan/.config" "$root/$targetdir/.config"
EOF

echo -e "\033[32mSUCCESS.\033[m"
