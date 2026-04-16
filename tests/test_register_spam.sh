sed='s/(ip=)[0-9]+(,email=)[0-9]+(,username=)[0-9]+/\1*\2*\3*/g'

###############################################################################

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

test_1() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_2() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_3() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_4() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_5() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_6() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_7() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

test_8() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=::1" \
		--data-urlencode "email=notsure@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"
}

check "1" test_1
check "2" test_2
check "3" test_3
check "4" test_4
check "5" test_5
check "6" test_6
check "7" test_7
check "8" test_8
