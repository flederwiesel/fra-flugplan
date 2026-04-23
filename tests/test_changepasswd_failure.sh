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

test_5_1() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "email=uid-2@example.com" \
		"$url/?req=reqtok"
}

test_5_2() {
	browse --with-csrf-token \
		--data-urlencode "user=unkown" \
		"$url/?req=reqtok"
}

test_5_3() {
	browse --with-csrf-token \
		--data-urlencode "email=unknown@example.com" \
		"$url/?req=reqtok"
}

test_5_4() {
	browse --with-csrf-token \
		--data-urlencode "user= " \
		"$url/?req=reqtok"
}

test_5_5() {
	browse --with-csrf-token \
		--data-urlencode "email= " \
		"$url/?req=reqtok"
}

test_5_6() {
	browse --with-csrf-token \
		--data-urlencode "user= " \
		--data-urlencode "email= " \
		"$url/?req=reqtok"
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
		--data-urlencode "user=erwin" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_8() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_9() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_10() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebl" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_11() {
	query fra-flugplan <<-"SQL"
		UPDATE users SET token_expires=
		FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - 3600)
		WHERE name='uid-1'
	SQL

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

test_12() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}
