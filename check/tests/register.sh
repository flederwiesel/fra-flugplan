#!/bin/sh

curl "http://localhost/fra-schedule/?req=register" > register.0.htm
curl "http://localhost/fra-schedule/?req=register&lang=" > register.email.none.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=" > register.email.null.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=invalid" > register.email.invalid.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=crapmail@flederwiesel.com" \
	--data-urlencode "user=" > register.user.null.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=crapmail@flederwiesel.com" \
	--data-urlencode "user=hmm" > register.user.short.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=crapmail@flederwiesel.com" \
	--data-urlencode "user=123456789012345678901234567890123" > register.user.long.htm

curl "http://localhost/fra-schedule/?req=register" \
	--data-urlencode "email=crapmail@flederwiesel.com" \
	--data-urlencode "user=erwin" > register.passwd.none.htm
