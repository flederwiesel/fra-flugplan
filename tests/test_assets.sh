# Use real asset path and replace with constant "/assets/".
# Go the complete register/login path, so each image on each page
# will be referenced at least once.
# @@assets=/assets/

test_1_login_form() {
	browse "$url/?req=login"
}

test_2_register_form() {
	browse --store-csrf-token "$url/?req=register"
}

test_3_register_submit() {
	browse --with-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_4_activate_form() {
	browse "$url/?req=activate"
}

test_5_activate_submit() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		"$url/?req=activate"
}

test_6_reqtok_form() {
	browse "$url/?req=reqtok"
}

test_7_reqtok_submit() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		"$url/?req=reqtok"
}

test_8_changepw_submit() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw" \
		"$url/?req=changepw"
}

test_9_login() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=zwiebel" \
		"$url/?req=login"
}

# Add to watchlist to get the photodb icon
test_10_watchlist_add() {
	add=$(
		cat <<-"EOF"
			C-GHLM	Air Canada - 938	1
			/10+0[123]/	GAF	1
		EOF
	)

	browse --with-csrf-token --data-urlencode add="$add" "$url"
}

test_11_profile_dispinterval() {
	browse "$url/?req=profile&dispinterval"
}

test_12_profile_notifinterval() {
	browse "$url/?req=profile&notifinterval"
}

test_13_profile_photodb() {
	browse "$url/?req=profile&photodb"
}

test_14_profile_changepw() {
	browse "$url/?req=profile&changepw"
}
