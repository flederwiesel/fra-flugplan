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
	# Cature stdin, we we can print it for debugging
	stdin=$(</dev/stdin)

	# Unconditionally write to debug fd (/dev/null, if --debug is not set)
	echo -e "\033[37m$FUNCNAME $@ <<-SQL $(sed 's/^/\t/g' <<< "$stdin")\n\tSQL\033[m" >&3

	mysql --silent --default-character-set=utf8 --skip-column-names "$@" <<< "$stdin"
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
	# Unconditionally write to debug fd (/dev/null, if --debug is not set)
	echo -e "\033[37m$FUNCNAME $@\033[m" >&3

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
	sed -r -e "$nullify" -e 's/nonce="[^"]+"/nonce=""/g'
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

# $1 name of array, not working for "@"
# $2 value to be checked
inArray()
{
    local IFS="${separator:-,}"
    local __array="$1[*]"
    local __value=$2

    [[ "${IFS}${!__array:-}${IFS}" =~ "${IFS}${__value}${IFS}" ]]
}
