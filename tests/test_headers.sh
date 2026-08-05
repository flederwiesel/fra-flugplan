# Filter for CSP, make header name all lower case, break lines at ';'
csp() {
	sed -rn '/content-security-policy/I {
		s/^(.*):/\L\1:/g
		s/\r$//g
		s/;/;\n    /g
		p
	}'
}

test_10_csp_index() {
	browse -i https://fra-flugplan.local/ | csp
}

test_20_csp_error() {
	browse -i https://fra-flugplan.local/404 | csp
}
