#!/bin/sh

htdocs=/html
prj=fra-schedule

if [ ! -h ${htdocs}/${prj} ]; then
	if [ -d ${htdocs}/${prj} ]; then
		echo "$LINENO: Expected ${htdocs}/${prj} as symlink, found directory!" >&2
	else
		if [ -e ${htdocs}/vault/${prj}/target ]; then
			ln -s ${htdocs}/vault/${prj}/$(cat ${htdocs}/vault/${prj}/target) ${htdocs}/${prj}

			if [ 0 == $? ]; then
				mv -f ${htdocs}/vault/${prj}/target ${htdocs}/vault/${prj}/recent
			fi
		else
			echo "$LINENO: No symlink and no target info!" >&2
			ln -s ${htdocs} ${htdocs}/${prj}
		fi
	fi
else
	if [ -e ${htdocs}/vault/${prj}/target ]; then
		target=$(cat ${htdocs}/vault/${prj}/target)

		if [ ! -d ${htdocs}/vault/${prj}/${target} ]; then
			echo "$LINENO: Target '${target}' does not exist!" >&2
		else
			mv ${htdocs}/${prj} ${htdocs}/${prj}~
			ln -s ${htdocs}/vault/${prj}/${target} ${htdocs}/${prj}

			if [ 0 == $? ]; then
				rm ${htdocs}/${prj}~
				mv -f ${htdocs}/vault/${prj}/target ${htdocs}/vault/${prj}/recent
			else
				echo "$LINENO: Unable to create symlink, restoring previous..." >&2
				mv /html/${prj}~ /html/${prj}
			fi
		fi
	fi
fi
