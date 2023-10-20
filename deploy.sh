#!/bin/bash

###############################################################################
#
#  Copyright © Tobias Kühne
#
#  You may use and distribute this software free of charge for non-commercial
#  purposes. The software must be distributed in its entirety, i.e. containing
#  ALL binary and source files without modification.
#  Publication of modified versions of the source code provided herein,
#  is permitted only with the author's written consent. In this case the
#  copyright notice must not be removed or altered, all modifications to the
#  source code must be clearly marked as such.
#
###############################################################################

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

# If checked out under Windows, cygwin permissions are wrong, which
# may lead to permissions being wrong on the server.

ssh fra-flugplan.de "mkdir -p $root httpdocs/var/log"
ssh fra-flugplan.de "rm -rf $root/$tag"
rsync -av  \
--exclude=*.dsk \
--exclude=*.ppw \
--exclude=.phped \
--exclude=.git \
--exclude=api.stopforumspam.org \
--exclude=check \
--exclude=etc \
--exclude=flagcounter.txt \
--exclude=ftp-flederwiesel.sh \
--exclude=METAR \
--exclude=sql \
--exclude=www.frankfurt-airport.com \
"$SCRIPTDIR/" fra-flugplan.de:"$root/$tag"

ssh fra-flugplan.de "echo $tag > $root/target"

echo -e "\033[32mSUCCESS.\033[m"
