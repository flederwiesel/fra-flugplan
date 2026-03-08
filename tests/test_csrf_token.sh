CSRFTOKEN=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1-0" browse -X POST "$url/?req=register" \
	--data-urlencode "email=uid-1@example.com" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz" \
	--data-urlencode "passwd-confirm=elvizzz" \
	--data-urlencode "timezone=UTC+1"

csrftoken="$CSRFTOKEN" \
check "1-1" browse -X POST "$url/?req=register" \
	--data-urlencode "email=uid-1@example.com" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz" \
	--data-urlencode "passwd-confirm=elvizzz" \
	--data-urlencode "timezone=UTC+1"

token=$(
	query fra-flugplan --skip-column-names \
	<<< 'SELECT `token` FROM `users` WHERE `name`="uid-1"'
)

check "2-0" browse "$url/?req=activate" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "token='$token'"

csrftoken="$CSRFTOKEN" \
check "2-1" browse "$url/?req=activate" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "token='$token'"

check "3-0" browse "$url/?req=reqtok" \
	--data-urlencode "user=uid-1"

csrftoken="$CSRFTOKEN" \
check "3-1" browse "$url/?req=reqtok" \
	--data-urlencode "user=uid-1"

check "4-0" browse -X POST "$url/?req=login" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz"

csrftoken="$CSRFTOKEN" \
check "4-1" browse -X POST "$url/?req=login" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz"

check "5-0" browse -X POST "$url/?req=login" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz"

csrftoken="$CSRFTOKEN" \
check "5-1" browse -X POST "$url/?req=login" \
	--data-urlencode "user=uid-1" \
	--data-urlencode "passwd=elvizzz"

check "6-0" browse "$url/?arrival" \
	--data-urlencode "add='C-GFAH	Air Canada - Star Alliance	1'"

csrftoken="$CSRFTOKEN" \
check "6-1" browse "$url/?arrival" \
	--data-urlencode "add='C-GFAH	Air Canada - Star Alliance	1'"

check "7-0" browse "$url/?req=profile\&dispinterval" \
	--data-urlencode "tm-=0" \
	--data-urlencode "tm%2b=86400" \
	--data-urlencode "tt-=0" \
	--data-urlencode "tt%2b=86400" \
	--data-urlencode "submit=interval"

csrftoken="$CSRFTOKEN" \
check "7-1" browse "$url/?req=profile\&dispinterval" \
	--data-urlencode "tm-=0" \
	--data-urlencode "tm%2b=86400" \
	--data-urlencode "tt-=0" \
	--data-urlencode "tt%2b=86400" \
	--data-urlencode "submit=interval"

check "8-0" browse "$url/?req=profile\&notifinterval" \
	--data-urlencode "from=06:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "timefmt='%+ %H:%M'" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"

csrftoken="$CSRFTOKEN" \
check "8-1" browse "$url/?req=profile\&notifinterval" \
	--data-urlencode "from=06:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "timefmt='%+ %H:%M'" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"

check "9-0" browse "$url/?req=profile\&photodb" \
	--data-urlencode "submit=photodb" \
	--data-urlencode "photodb=jetphotos.com"

csrftoken="$CSRFTOKEN" \
check "9-1" browse "$url/?req=profile\&photodb" \
	--data-urlencode "submit=photodb" \
	--data-urlencode "photodb=jetphotos.com"

# `csrftoken` not required for POST, but filtering in `browse()` ...
csrftoken="$CSRFTOKEN" \
check "10" browse "$url/?req=profile\&changepw"

check "11-0" browse "$url/?req=changepw" \
	--data-urlencode "passwd=zwiebel" \
	--data-urlencode "passwd-confirm=zwiebel" \
	--data-urlencode "submit=changepw"

csrftoken="$CSRFTOKEN" \
check "11-1" browse "$url/?req=changepw" \
	--data-urlencode "passwd=zwiebel" \
	--data-urlencode "passwd-confirm=zwiebel" \
	--data-urlencode "submit=changepw"
