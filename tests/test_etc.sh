# @fileext=txt
test_1_email_subject() {
	browse --head "$url/content/email.php?subject=foo" |
	dos2unix |
	sed -r 's/^[^:]+:/\L&/g; /^(date|server|x-powered)/d'
}

test_2_email_body() {
	browse --head "$url/content/email.php?body=äöü" |
	dos2unix |
	sed -r 's/^[^:]+:/\L&/g; /^(date|server|x-powered)/d'
}
