CSRFTOKEN=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

test_1_0() {
	browse -X POST "$url/?req=register" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1"
}

test_1_1() {
	csrftoken="$CSRFTOKEN" \
	browse -X POST "$url/?req=register" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1"
}

test_2_0() {
	token=$(
		query fra-flugplan --skip-column-names \
		<<< 'SELECT `token` FROM `users` WHERE `name`="uid-1"'
	)

	browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"
}

test_2_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"
}

test_3_0() {
	browse "$url/?req=reqtok" --data-urlencode "user=uid-1"
}

test_3_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=reqtok" --data-urlencode "user=uid-1"
}

test_4_0() {
	browse -X POST "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_4_1() {
	csrftoken="$CSRFTOKEN" \
	browse -X POST "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_5_0() {
	browse -X POST "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_5_1() {
	csrftoken="$CSRFTOKEN" \
	browse -X POST "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_6_0() {
	browse "$url/?arrival" \
		--data-urlencode "add=C-GFAH	Air Canada - Star Alliance	1"
}

test_6_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?arrival" \
		--data-urlencode "add=C-GFAH	Air Canada - Star Alliance	1"
}

test_7_0() {
	browse "$url/?req=profile&dispinterval" \
		--data-urlencode "tm-=0" \
		--data-urlencode "tm%2b=86400" \
		--data-urlencode "tt-=0" \
		--data-urlencode "tt%2b=86400" \
		--data-urlencode "submit=interval"
}

test_7_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=profile&dispinterval" \
		--data-urlencode "tm-=0" \
		--data-urlencode "tm%2b=86400" \
		--data-urlencode "tt-=0" \
		--data-urlencode "tt%2b=86400" \
		--data-urlencode "submit=interval"
}

test_8_0() {
	browse "$url/?req=profile&notifinterval" \
		--data-urlencode "from=06:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=%+ %H:%M" \
		--data-urlencode "submit=notifications" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_8_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=profile&notifinterval" \
		--data-urlencode "from=06:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=%+ %H:%M" \
		--data-urlencode "submit=notifications" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_9_0() {
	browse "$url/?req=profile&photodb" \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com"
}

test_9_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=profile&photodb" \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com"
}

test_10() {
	# `csrftoken` not required for GET, but filtering in `browse()` ...
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=profile&changepw"
}

test_11_0() {
	browse "$url/?req=changepw" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

test_11_1() {
	csrftoken="$CSRFTOKEN" \
	browse "$url/?req=changepw" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

check "1-0" test_1_0
check "1-1" test_1_1
check "2-0" test_2_0
check "2-1" test_2_1
check "3-0" test_3_0
check "3-1" test_3_1
check "4-0" test_4_0
check "4-1" test_4_1
check "5-0" test_5_0
check "5-1" test_5_1
check "6-0" test_6_0
check "6-1" test_6_1
check "7-0" test_7_0
check "7-1" test_7_1
check "8-0" test_8_0
check "8-1" test_8_1
check "9-0" test_9_0
check "9-1" test_9_1
check "10" test_10
check "11-0" test_11_0
check "11-1" test_11_1
