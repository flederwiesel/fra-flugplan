sed='s/(ip=)[0-9]+(,email=)[0-9]+(,username=)[0-9]+/\1*\2*\3*/g'

###############################################################################

check "0" browse -X POST "$url/?req=register"

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "2" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "3" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "4" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}\&ip=46.118.155.73" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "5" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}\&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "6" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}\&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "7" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}\&ip=46.118.155.73" \
		--data-urlencode "email=nospam@example.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "8" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}\&ip=::1" \
		--data-urlencode "email=notsure@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"
