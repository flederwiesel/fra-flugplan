sed='s/(ip=)[0-9]+(,email=)[0-9]+(,username=)[0-9]+/\1*\2*\3*/g'

###############################################################################

test_1() {
	browse --store-csrf-token \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_2() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_3() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_4() {
	browse --with-csrf-token \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_5() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_6() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_7() {
	browse --with-csrf-token \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

# @mailto=notsure@gmail.com
test_8() {
	browse --with-csrf-token \
		--data-urlencode "email=notsure@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=::1"
}
