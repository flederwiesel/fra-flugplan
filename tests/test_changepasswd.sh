test_1() {
	browse "$url/"
}

test_2() {
	browse --store-csrf-token "$url/?req=register"
}

# @mailto=uid-1@example.com
test_3() {
	browse --with-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_4() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		"$url/?req=activate"
}

###############################################################################
# registered and activated, not logged in
###############################################################################

test_5() {
	browse "$url/?req=reqtok"
}

# @mailto=uid-1@example.com
test_6() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		"$url/?req=reqtok"
}

test_7() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

###############################################################################
# try login with new passwd
###############################################################################

test_8() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=zwiebel" \
		"$url/?req=login"
}

###############################################################################
# change password whilst logged in
###############################################################################

test_9() {
	browse "$url/?req=profile&changepw"
}

test_10() {
	browse --with-csrf-token \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}
