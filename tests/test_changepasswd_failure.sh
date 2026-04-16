mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

test_1() {
	browse "$url/"
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
		--data-urlencode "lang=en"
}

test_4() {
	token=$(query --execute="USE fra-flugplan;
		SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

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

test_5_1() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "email=uid-2@example.com"
}

test_5_2() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user=unkown"
}

test_5_3() {
	browse "$url/?req=reqtok" \
		--data-urlencode "email=unknown@example.com"
}

test_5_4() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user= "
}

test_5_5() {
	browse "$url/?req=reqtok" \
		--data-urlencode "email= "
}

test_5_6() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user= " \
		--data-urlencode "email= "
}

test_6() {
	browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1"
}

test_7() {
	token=$(query --execute="USE fra-flugplan;
		SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

	browse "$url/?req=changepw" \
		--data-urlencode "user=erwin" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

test_8() {
	browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

test_9() {
	browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

test_10() {
	browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebl" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"
}

test_11() {
	query --execute="USE fra-flugplan;
		UPDATE users SET token_expires=
		FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - 3600)
		WHERE name='uid-1'"

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

test_12() {
	browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

check "1" test_1
check "2" test_2
check "3" test_3
check "4" test_4
check "5" test_5
check "5-1" test_5_1
check "5-2" test_5_2
check "5-3" test_5_3
check "5-4" test_5_4
check "5-5" test_5_5
check "5-6" test_5_6
check "6" test_6
check "7" test_7
check "8" test_8
check "9" test_9
check "10" test_10
check "11" test_11
check "12" test_12
