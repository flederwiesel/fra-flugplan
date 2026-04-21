test_1_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		"$url/?req=register"
}

test_1_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		"$url/?req=register"
}

test_2_0() {
	token=$(
		query fra-flugplan --skip-column-names \
		<<< 'SELECT `token` FROM `users` WHERE `name`="uid-1"'
	)

	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		"$url/?req=activate"
}

test_2_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		"$url/?req=activate"
}

test_3_0() {
	browse -X POST \
		--clear-csrf-token --data-urlencode "user=uid-1" "$url/?req=reqtok"
}

test_3_1() {
	browse -X POST \
		--store-csrf-token --data-urlencode "user=uid-1" "$url/?req=reqtok"
}

test_4_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

test_4_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

test_5_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

test_5_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

test_6_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "add=C-GFAH	Air Canada - Star Alliance	1" \
		"$url/?arrival"
}

test_6_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "add=C-GFAH	Air Canada - Star Alliance	1" \
		"$url/?arrival"
}

test_7_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "tm-=0" \
		--data-urlencode "tm%2b=86400" \
		--data-urlencode "tt-=0" \
		--data-urlencode "tt%2b=86400" \
		--data-urlencode "submit=interval" \
		"$url/?req=profile&dispinterval"
}

test_7_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "tm-=0" \
		--data-urlencode "tm%2b=86400" \
		--data-urlencode "tt-=0" \
		--data-urlencode "tt%2b=86400" \
		--data-urlencode "submit=interval" \
		"$url/?req=profile&dispinterval"
}

test_8_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "from=06:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=%+ %H:%M" \
		--data-urlencode "submit=notifications" \
		"$url/?req=profile&notifinterval" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_8_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "from=06:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=%+ %H:%M" \
		--data-urlencode "submit=notifications" \
		"$url/?req=profile&notifinterval" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_9_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com" \
		"$url/?req=profile&photodb"
}

test_9_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com" \
		"$url/?req=profile&photodb"
}

test_10() {
	browse "$url/?req=profile&changepw"
}

test_11_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_11_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}
