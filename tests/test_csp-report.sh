:> "$LOCALSTATEDIR/log/csp.reports"

# From the header, only regard HTTP status and Content-Type;
# make the latter lower case. In the body, remove unique error ID.
responseFilter() {
	sed -rn '
		1,/^$/ {
				/^(HTTP|Content-Type)/ {
					s/^(.*):/\L\1:/g
					p
				}
		}
		/^$/,$ {
			s/"(ref)":\s*"[^"]*"/"\1":""/g
			p
		}
	'
}

csp_report() {
	browse -i https://fra-flugplan.local/csp-report.php \
		--header 'Content-Type: application/csp-report' \
		--data @- |
	responseFilter

	jq < "$LOCALSTATEDIR/log/csp.reports" |
	sed -r 's/("updated":\s*)"[^"]*"/\1""/g'
}

sandbox_eval_code() {
	csp_report <<"EOF"
{
  "csp-report": {
    "blocked-uri": "inline",
    "column-number": 33,
    "disposition": "enforce",
    "document-uri": "https://fra-flugplan.local/",
    "effective-directive": "script-src-elem",
    "line-number": 17,
    "original-policy": "default-src 'self';",
    "referrer": "https://fra-flugplan.local/",
    "source-file": "sandbox eval code",
    "status-code": 200,
    "violated-directive": "script-src-elem"
  }
}
EOF
}

moz_extension() {
	csp_report <<"EOF"
{
  "csp-report": {
    "blocked-uri": "inline",
    "column-number": 29,
    "disposition": "enforce",
    "document-uri": "https://fra-flugplan.local/",
    "effective-directive": "script-src-elem",
    "line-number": 950,
    "original-policy": "default-src 'self';",
    "referrer": "https://fra-flugplan.local/",
    "source-file": "moz-extension",
    "status-code": 200,
    "violated-directive": "script-src-elem"
  }
}
EOF
}

# @@fileext=txt
test_10_get() {
	browse -i https://fra-flugplan.local/csp-report.php |
	responseFilter
}

test_20_post_no_content_type() {
	browse -i https://fra-flugplan.local/csp-report.php \
		--data @- <<"EOF" | \
	responseFilter
{
	"csp-report": {}
}
EOF
}

test_30_post_empty() {
	csp_report <<< ''
}

test_40_empty_object() {
	csp_report <<< '{}'
}

test_50_sandbox_eval_code_1() {
	sandbox_eval_code
}

test_60_sandbox_eval_code_2() {
	sandbox_eval_code
}

test_70_moz_extension_1() {
	moz_extension
}

test_80_sandbox_eval_code_3() {
	sandbox_eval_code
}

test_90_moz_extension_2() {
	moz_extension
}
