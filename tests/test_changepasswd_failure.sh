mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1" browse "$url/"
check "2" browse "$url/?req=register"

check "3" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

check "4" browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"

###############################################################################
# registered and activated, not logged in
###############################################################################

check "5" browse "$url/?req=reqtok"

check "5-1" browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "email=uid-2@example.com"

check "5-2" browse "$url/?req=reqtok" \
		--data-urlencode "user=unkown"

check "5-3" browse "$url/?req=reqtok" \
		--data-urlencode "email=unknown@example.com"

check "5-4" browse "$url/?req=reqtok" \
		--data-urlencode "user=' '"

check "5-5" browse "$url/?req=reqtok" \
		--data-urlencode "email=' '"

check "5-6" browse "$url/?req=reqtok" \
		--data-urlencode "user=' '" \
		--data-urlencode "email=' '"

check "6" browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

check "7" browse "$url/?req=changepw" \
		--data-urlencode "user=erwin" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "8" browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "9" browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "10" browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebl" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

query --execute="USE fra-flugplan;
	UPDATE users SET token_expires=
	FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - 3600)
	WHERE name='uid-1'"

check "11" browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

###############################################################################
# try login with new passwd
###############################################################################

check "12" browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
