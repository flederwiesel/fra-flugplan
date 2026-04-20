mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

test_1() {
	browse "$url/?lang=de"
}

test_2() {
	browse --store-csrf-token "$url/?req=register"
}

test_3() {
	browse --with-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=de" \
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

check "1" test_1
check "2" test_2
check "3" test_3
check "4" test_4
check "5" test_5
check "6" test_6
check "7" test_7
check "8" test_8
check "9" test_9
check "10" test_10
