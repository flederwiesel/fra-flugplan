#!/bin/sh

#mysql --host=localhost --user=root --password= --default-character-set=utf8

tests=$(readlink -f $(dirname "$0"))/tests
outdir=results/$(date +"%Y-%m-%d %H~%M~%S")

check() {

	$(mkdir -p "$1")

	if [ $? -ne 0 ]; then
		echo "Could not create '$1'." >&2
	else

		pushd "$1" >/dev/null

		if [ $? -ne 0 ]; then
			echo "Could not cd to '$1'." >&2
		else
			$("$tests/$1.sh")
			popd  >/dev/null
		fi
	fi
}

# start test
$(mkdir -p "$outdir")

if [ $? -ne 0 ]; then
	echo "Could not create '$outdir'." >&2
else

	pushd "$outdir" >/dev/null

	if [ $? -ne 0 ]; then
		echo "Could not cd to '$outdir'." >&2
	else
		check base
		check register

		popd >/dev/null
	fi
fi
