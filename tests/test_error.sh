test_403() {
	browse "$url/img"
}

test_404() {
	browse "$url/foo/bar"
}

test_500() {
	browse "$url/error.php?status=500"
}
