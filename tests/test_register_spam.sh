sed='s/(ip=)[0-9]+(,email=)[0-9]+(,username=)[0-9]+/\1*\2*\3*/g'

###############################################################################

test_1_spam_user() {
	browse --store-csrf-token \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_2_spam_email() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_3_spam_email_user() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_4_spam_ip() {
	browse --with-csrf-token \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_5_email_ip() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_6_spam_email_user_ip() {
	browse --with-csrf-token \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=46.118.155.73"
}

test_7_spam_ip() {
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
test_8_nospam() {
	browse --with-csrf-token \
		--data-urlencode "email=notsure@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		"$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}&ip=::1"
}
