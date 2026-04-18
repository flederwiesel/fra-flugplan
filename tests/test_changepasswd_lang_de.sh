mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

test_1() {
	browse "$url/?lang=de"
}

test_2() {
	browse "$url/?req=register"
}

test_3() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=de"
}

test_4() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"
}

###############################################################################
# registered and activated, not logged in
###############################################################################

test_5() {
	browse "$url/?req=reqtok"
}

test_6() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1"
}

test_7() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

###############################################################################
# try login with new passwd
###############################################################################

test_8() {
	browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=zwiebel"
}

###############################################################################
# change password whilst logged in
###############################################################################

test_9() {
	browse "$url/?req=profile&changepw"
}

test_10() {
	browse "$url/?req=changepw" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "submit=changepw"
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
