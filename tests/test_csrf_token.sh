# Register user
# * No CSRF token -> FAIL
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

# Register user
# * CSRF token -> SUCCESS
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

# Activate user
# * No CSRF token -> FAIL
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

# Activate user
# * CSRF token -> SUCCESS
test_2_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		"$url/?req=activate"
}

# Request password change
# * No CSRF token -> FAIL
test_3_0() {
	browse -X POST \
		--clear-csrf-token --data-urlencode "user=uid-1" "$url/?req=reqtok"
}

# Request password change
# * CSRF token -> SUCCESS
test_3_1() {
	browse -X POST \
		--store-csrf-token --data-urlencode "user=uid-1" "$url/?req=reqtok"
}

# Login
# * No CSRF token -> FAIL
test_4_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

# Login
# * CSRF token -> SUCCESS
test_4_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

# Login - DUPLICATE!
# * No CSRF token -> FAIL
test_5_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

# Login - DUPLICATE!
# * CSRF token -> SUCCESS
test_5_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

# Add to watchlist
# * No CSRF token -> FAIL
test_6_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode 'watchlist={"add":[{"reg":"C-GFAH","comment":"Air Canada - Star Alliance","notify":1}]}' \
		"$url/?arrival"
}

# Add to watchlist -- notification interval is still at 00:00...00:00!
# * CSRF token -> SUCCESS
test_6_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode 'watchlist={"add":[{"reg":"C-GFAH","comment":"Air Canada - Star Alliance","notify":1}]}' \
		"$url/?arrival"
}

# Set display interval
# * No CSRF token -> FAIL
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

# Set display interval
# * CSRF token -> SUCCESS
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

# Set notification interval
# * No CSRF token -> FAIL
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

# Set notification interval
# * CSRF token -> SUCCESS
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

# Set photo db
# * No CSRF token -> FAIL
test_9_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com" \
		"$url/?req=profile&photodb"
}

# Set photo db
# * CSRF token -> SUCCESS
test_9_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com" \
		"$url/?req=profile&photodb"
}

# Navigate to "Change password" page
test_10() {
	browse "$url/?req=profile&changepw"
}

# Change password
# * No CSRF token -> FAIL
test_11_0() {
	browse -X POST \
		--clear-csrf-token \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

# Change password
# * CSRF token -> SUCCESS
test_11_1() {
	browse -X POST \
		--store-csrf-token \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}
