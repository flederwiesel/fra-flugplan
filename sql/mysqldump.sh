#!/bin/bash

# bypass windows find.exe  (located in Windows/system32)
uname -o | grep -iq cygwin

if [ $? -eq 0 ]; then
	export PATH=$(echo $PATH | sed "s|$(cygpath --folder 37)[^:]*||ig; s|:::*|:|g")
fi

IFS=$'\n'

curl[1]="Unsupported protocol. This build of curl has no support for this protocol."
curl[2]="Failed to initialize."
curl[3]="URL malformed. The syntax was not correct."
curl[4]="A feature or option that was needed to perform the desired request was not enabled or was explicitly disabled at build-time. To make curl able to do this, you probably need another build of libcurl!"
curl[5]="Couldn't resolve proxy. The given proxy host could not be resolved."
curl[6]="Couldn't resolve host. The given remote host was not resolved."
curl[7]="Failed to connect to host."
curl[8]="FTP weird server reply. The server sent data curl couldn't parse."
curl[9]="FTP access denied. The server denied login or denied access to the particular resource or directory you wanted to reach. Most often you tried to change to a directory that doesn't exist on the server."
curl[11]="FTP weird PASS reply. Curl couldn't parse the reply sent to the PASS request."
curl[13]="FTP weird PASV reply, Curl couldn't parse the reply sent to the PASV request."
curl[14]="FTP weird 227 format. Curl couldn't parse the 227-line the server sent."
curl[15]="FTP can't get host. Couldn't resolve the host IP we got in the 227-line."
curl[17]="FTP couldn't set binary. Couldn't change transfer method to binary."
curl[18]="Partial file. Only a part of the file was transferred."
curl[19]="FTP couldn't download/access the given file, the RETR (or similar) command failed."
curl[21]="FTP quote error. A quote command returned error from the server."
curl[22]="HTTP page not retrieved. The requested url was not found or returned another error with the HTTP error code being 400 or above. This return code only appears if -f, --fail is used."
curl[23]="Write error. Curl couldn't write data to a local filesystem or similar."
curl[25]="FTP couldn't STOR file. The server denied the STOR operation, used for FTP uploading."
curl[26]="Read error. Various reading problems."
curl[27]="Out of memory. A memory allocation request failed."
curl[28]="Operation timeout. The specified time-out period was reached according to the conditions."
curl[30]="FTP PORT failed. The PORT command failed. Not all FTP servers support the PORT command, try doing a transfer using PASV instead!"
curl[31]="FTP couldn't use REST. The REST command failed. This command is used for resumed FTP transfers."
curl[33]="HTTP range error. The range \"command\" didn't work."
curl[34]="HTTP post error. Internal post-request generation error."
curl[35]="SSL connect error. The SSL handshaking failed."
curl[36]="FTP bad download resume. Couldn't continue an earlier aborted download."
curl[37]="FILE couldn't read file. Failed to open the file. Permissions?"
curl[38]="LDAP cannot bind. LDAP bind operation failed."
curl[39]="LDAP search failed."
curl[41]="Function not found. A required LDAP function was not found."
curl[42]="Aborted by callback. An application told curl to abort the operation."
curl[43]="Internal error. A function was called with a bad parameter."
curl[45]="Interface error. A specified outgoing interface could not be used."
curl[47]="Too many redirects. When following redirects, curl hit the maximum amount."
curl[48]="Unknown option specified to libcurl. This indicates that you passed a weird option to curl that was passed on to libcurl and rejected. Read up in the manual!"
curl[49]="Malformed telnet option."
curl[51]="The peer's SSL certificate or SSH MD5 fingerprint was not OK."
curl[52]="The server didn't reply anything, which here is considered an error."
curl[53]="SSL crypto engine not found."
curl[54]="Cannot set SSL crypto engine as default."
curl[55]="Failed sending network data."
curl[56]="Failure in receiving network data."
curl[58]="Problem with the local certificate."
curl[59]="Couldn't use specified SSL cipher."
curl[60]="Peer certificate cannot be authenticated with known CA certificates."
curl[61]="Unrecognized transfer encoding."
curl[62]="Invalid LDAP URL."
curl[63]="Maximum file size exceeded."
curl[64]="Requested FTP SSL level failed."
curl[65]="Sending the data requires a rewind that failed."
curl[66]="Failed to initialise SSL Engine."
curl[67]="The user name, password, or similar was not accepted and curl failed to log in."
curl[68]="File not found on TFTP server."
curl[69]="Permission problem on TFTP server."
curl[70]="Out of disk space on TFTP server."
curl[71]="Illegal TFTP operation."
curl[72]="Unknown TFTP transfer ID."
curl[73]="File already exists (TFTP)."
curl[74]="No such user (TFTP)."
curl[75]="Character conversion failed."
curl[76]="Character conversion functions required."
curl[77]="Problem with reading the SSL CA cert (path? access rights?)."
curl[78]="The resource referenced in the URL does not exist."
curl[79]="An unspecified error occurred during the SSH session."
curl[80]="Failed to shut down the SSL connection."
curl[82]="Could not load CRL file, missing or wrong format (added in 7.19.0)."
curl[83]="Issuer check failed (added in 7.19.0)."
curl[84]="The FTP PRET command failed"
curl[85]="RTSP: mismatch of CSeq numbers"
curl[86]="RTSP: mismatch of Session Identifiers"
curl[87]="unable to parse FTP file list"
curl[88]="FTP chunk callback reported error"

declare -A http

http[0]="Unknown or invalid HTTP error code"
http[﻿100]="Continue"
http[101]="Switching Protocols"
http[200]="OK"
http[201]="Created"
http[202]="Accepted"
http[203]="Non-Authoritative Information"
http[204]="No Content"
http[205]="Reset Content"
http[206]="Partial Content"
http[300]="Multiple Choices"
http[301]="Moved Permanently"
http[302]="Found"
http[303]="See Other"
http[304]="Not Modified"
http[305]="Use Proxy"
http[307]="Temporary Redirect"
http[400]="Bad Request"
http[401]="Unauthorized"
http[402]="Payment Required"
http[403]="Forbidden"
http[404]="Not Found"
http[405]="Method Not Allowed"
http[406]="Not Acceptable"
http[407]="Proxy Authentication Required"
http[408]="Request Timeout"
http[409]="Conflict"
http[410]="Gone"
http[411]="Length Required"
http[412]="Precondition Failed"
http[413]="Request Entity Too Large"
http[414]="Request-URI Too Long"
http[415]="Unsupported Media Type"
http[416]="Requested Range Not Satisfiable"
http[417]="Expectation Failed"
http[500]="Internal Server Error"
http[501]="Not Implemented"
http[502]="Bad Gateway"
http[503]="Service Unavailable"
http[504]="Gateway Timeout"
http[505]="HTTP Version Not Supported"

rotate() {

	fmt_hourly='00~[0-9]{2}~[0-9]{2}'	# Keep all files from hour 00
	fmt_daily='[0-9]{4}-[0-9]{2}-01'	# Keep all files from day 01

	###########################################################################
	# -> hourly
	###########################################################################

	mv *."${filename##*.}" hourly

	###########################################################################
	# hourly -> daily
	###########################################################################

	hourly=$(find hourly -mindepth 1 -maxdepth 1 | sort -r)

	# Remove all files not matching $fmt_hourly, but keep the 24 newest
	rm=$(echo "$hourly" | sed -r "/${fmt_hourly}/d; 1,24d")
	[ -n "$rm" ] && rm -r $rm

	# Move all files matching $fmt_hourly but the first (today's)
	mv=$(echo "$hourly" | sed -r "/${fmt_hourly}/!d; 1,1d")
	[ -n "$mv" ] && mv -t daily $mv

	###########################################################################
	# daily -> monthly
	###########################################################################

	daily=$(find daily -mindepth 1 -maxdepth 1 | sort -r)

	# We're looking back 1 month...
	case $(($(date -d @$now +%_m) % 12 + 1)) in
	2)
		y=$(date -d @$now +%Y)

		[[ 0 == $(($y % 4)) ]] &&
		[[ 0 != $(($y % 100)) ]] &&
		[[ 0 == $(($y % 400)) ]] &&
			days=29 ||
			days=28

		;;
	1|3|5|7|8|10|12)
		days=31
		;;
	*)
		days=30
		;;
	esac

	# Remove all files not matching $fmt_daily, but keep the $days newest
	rm=$(echo "$daily" | sed -r "/${fmt_daily}/d; 1,${days}d")
	[ -n "$rm" ] && rm -r $rm

	# Move all files matching $fmt_daily but the first (this month's)
	mv=$(echo "$daily" | sed -r "/${fmt_daily}/!d; 1,1d")
	[ -n "$mv" ] && mv -t monthly $mv
}

cd $(dirname "$0")

filename="fra-flugplan-$(date --utc +'%Y-%m-%d %H~%M~00').sql.bz2"

for target in mysql
do
	if [ ! -d "$target" ]; then
		mkdir -p "$target"

		for dir in hourly daily monthly
		do
			mkdir -p "$target/$dir"
		done
	fi

	pushd "$target" > /dev/null

	result=$(curl \
		--silent \
		--fail \
		--write-out '%{http_code}' \
		--location "http://www.fra-flugplan.de/mysqldump-php" \
		--output "$filename"
	)

	status=$?

	if [ $status -eq 0 ]; then
		if [ ! "$result" = "200" ]; then
			if [ -z "${http[$result]}" ]; then
				result=0
			fi

			result="[$result] ${http[$result]}"
			status=1
		fi
	else
		if [ -z "$result" ]; then
			result="[$status] ${curl[$status]}"
		else
			if [ -z "${http[$result]}" ]; then
				result="[$status] ${curl[$status]}"
			else
				result="[$status] ${curl[$status]} -- [$result] ${http[$result]}"
			fi
		fi
	fi

	if [ $status -ne 0 ]; then
		echo "$(date +'%F %X') $result" | tee -a "$target/error.log"
		latest=$(find "hourly" -mindepth 1 -maxdepth 1 | sort -r | head -n 1)
		cp -fl "$latest" "$filename"
	fi

	now=$(date +%s)
	rotate

	popd > /dev/null
done
