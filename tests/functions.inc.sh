# Regexes to be replaced in email files
readonly RE_EMAIL="
	/(Activation|Password) token is:/ { N; s/\n.+\$/\n***/g }
	/Das (Aktivierungs-)?Token( dafür)? ist:/ { N; s/\n.+\$/\n***/g }
	s/((Mon|Diens|Donners|Frei|Sams|Sonn)tag|Mittwoch), [0-9]+\. (Januar|Februar|März|April|Mai|Ju[nl]i|August|(Sept|Nov|Dez)ember|Oktober) [0-9]+/Tag, 00. Monat 0000/g
	s/[0-9]{4}-[0-9]{2}-[0-9]{2}([ T])[0-9]{2}:[0-9]{2}:[0-9]{2}/0000-00-00\100:00:00/g
	s/\(code [0-9]+\)/(code ***)/g
	s/^.*\.php\([0-9]+\): *//g
	s/^(Date:[ \t]+).+\$/\1Day, 0 Month 0000 00:00:00 +0000/g
	s/00\+[0-9]{4}/00+0000/g
	s/token='[0-9a-f.]+'/token='***'/g
	s/token=[0-9a-f]+/token=***/g
	s#::1#<localhost>#g
	s#((Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day), [0-9]+/[0-9]+/[0-9]+#Day, 00/00/00#g
	s#(https?://[^/]+/).*/([^/?]+\?.*)#\1/\2#g
	s#(X-Mailer: PHP/).*\$#\1*#g
	s#127.0.0.1#<localhost>#g
"

minversion() {
	# <comment> <expect> <result>
	awk 'BEGIN {
		if (ARGC < 3)
			exit 1

		len["expect"] = split(ARGV[1], expect, ".")
		len["result"] = split(ARGV[2], result, ".")
		len["min"] = len["expect"] < len["result"] ? len["expect"] : len["result"]

		for (i = 1; i <= len["min"]; i++) {
			if (expect[i] < result[i]) {
				exit 0
			}
			if (expect[i] > result[i]) {
				exit 1
			}
		}

		# more elements means suffix -> greater
		if (len["expect"] > len["result"])
				exit 1
	}
' "$2" "$3"
}

chkdep() {

	if ! "$@" &>/dev/null; then
		if [ -z "$3" -o -z "$4" ]; then
			echo -e "\033[1;31m$1 $2 failed.\033[m" >&2
		else
			echo -e "\033[1;31m$1 $2 failed: $3 <-> $4\033[m" >&2
		fi

		exit 1
	fi
}

initdb() {
	query < "$PRJDIR/sql/fra-flugplan.sql" > /dev/null
}

query() {

	[ 1 == $debug ] && echo -e "\033[1;33m$@\033[m" >&2

	mysql --silent --default-character-set=utf8 --skip-column-names "$@"
}

# cURL wrpapper using cookies and ca-cert bundle
#
# --clear-csrf-token
#     Unset CSRF token before request
# --nullify-csrf-token
#     Replace (unknown/unset) token in reponse with empty string --
#     response format must be 'name="CSRFToken" value=""...'
# --store-csrf-token
#     Prepend a GET request storing the form's CSRF token - implies `--with-csrf-token`
# --with-csrf-token
#     Send a CSRFToken=... with the request.
browse() {
	local arg=
	local args=()
	local store=
	local request_uri=
	# Always replace CSRF token with empty string
	local nullify='s/(name="CSRFToken" value)="[^"]+"/\1=""/g'

	for arg
	do
		case "$arg" in
		--clear-csrf-token)
			unset csrftoken
			;;
		--store-csrf-token)
			store=csrf-token
			;;
		--with-csrf-token)
			args+=(--data-urlencode "CSRFToken=$csrftoken")
			;;
		https://*)
			request_uri="$arg"
			;&
		*)
			args+=("$arg")
			;;
		esac
	done

	if [[ $store == csrf-token ]]; then
		if [[ ! ${csrftoken:-} ]]; then
			csrftoken=$(
				curl --silent --location --noproxy localhost \
					--cacert "$PRJDIR/etc/ssl/ca-certificates.crt" \
					--cookie "$COOKIES" \
					--cookie-jar "$COOKIES" \
					"$request_uri" |
				sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
			)
		fi

		args+=(--data-urlencode "CSRFToken=$csrftoken")
	fi

	if [[ ${csrftoken:-} ]]; then
		# Escape "+" for `sed -r`
		nullify="${csrftoken+s:${csrftoken//+/\\+}::g;}"
	fi

	curl --silent --location --noproxy localhost \
		--cacert "$PRJDIR/etc/ssl/ca-certificates.crt" \
		--cookie "$COOKIES" \
		--cookie-jar "$COOKIES" \
		"${data_csrftoken[@]}" "${args[@]}" |
	sed -r "$nullify"
}

rawurlencode() {
	local string="${1}"
	local strlen=${#string}
	local encoded=""
	local retain="${2:-}"

	for (( pos=0 ; pos<strlen ; pos++ ))
	do
		c=${string:$pos:1}
		case "$c" in
			[-_.~a-zA-Z0-9"$retain"] )
				o="${c}"
				;;
			* )
				printf -v o '%%%02x' "'$c"
		esac

		encoded+="${o}"
	done
	echo "${encoded}"    # You can either set a return variable (FASTER)
	REPLY="${encoded}"   #+or echo the result (EASIER)... or both... :p
}

# Wrapper for `sed -r` using a variable name, pointing  to
# a string variable containing the actual script.
strsubst() {
	local re=${!1?}
	shift
	sed -r "$re" "$@"
}

# Get server's DOCUMENT_ROOT be printing it from a temporary file.
getDocumentRoot() {
	local prjdir=$1
	local php=$(mktemp --tmpdir="$prjdir" --suffix=".php")

	# Assuming this is done is a subshell: `root=$(getDocumentRoot)`,
	# this trap is valid only temporarily.
	trap "rm -f "$php" >&2" EXIT

	echo '<?= $_SERVER["DOCUMENT_ROOT"] ?>' > "$php"

	# Depending on Windows permissions and whether $CYGWIN is set,
	# we may have to set permissions explicitly :(
	[[ $(uname -o) == Cygwin ]] &&
	chmod 0644 "$php"

	document_root=$(curl -sSL "https://${FRA_FLUGPLAN_HOST}${php//$prjdir/}")

	[[ $(uname -o) == Cygwin ]] &&
	document_root=$(cygpath "$document_root")

	echo "$document_root"
}
