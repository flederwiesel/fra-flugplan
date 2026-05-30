# @@assetdir=$ASSETDIR

readonly MOBILE='Opera/9.80 (Android 2.3.7; Linux; Opera Mobi/46154) Presto/2.11.355 Version/12.10'

query fra-flugplan < <(
	cat "$PRJDIR/sql/data/countries.sql" \
	    "$PRJDIR/sql/data/airports.sql" \
	    "$PRJDIR/sql/data/airlines.sql" \
	    "$PRJDIR/sql/data/models.sql" \
)

query fra-flugplan <<-"SQL"
	SELECT `id` INTO @LH FROM `airlines` WHERE `code`='LH';

	SELECT `id` INTO @EGLL FROM `airports` WHERE `icao`='EGLL';
	SELECT `id` INTO @SEQM FROM `airports` WHERE `icao`='SEQM';

	SELECT `id` INTO @A321 FROM `models` WHERE `icao`='A321';
	SELECT `id` INTO @MD11 FROM `models` WHERE `icao`='MD11';

	INSERT INTO `aircrafts` (`reg`, `model`) VALUES ('D-AIRY', @A321);
	INSERT INTO `aircrafts` (`reg`, `model`) VALUES ('D-ALCC', @MD11);

	SELECT `id` INTO @DAIRY FROM `aircrafts` WHERE `reg`='D-AIRY';
	SELECT `id` INTO @DALCC FROM `aircrafts` WHERE `reg`='D-ALCC';

	INSERT INTO flights(
		`type`, `direction`, `airline`, `code`, `scheduled`,
		`airport`, `model`, `aircraft`
	)
	VALUES(
		'P', 'arrival', @LH, '666', DATE_ADD(CURRENT_DATE(), INTERVAL '1 10' DAY_HOUR),
		@EGLL, @A321, @DAIRY
	),(
		'C', 'arrival', @LH, '9999', DATE_ADD(CURRENT_DATE(), INTERVAL '1 11' DAY_HOUR),
		@SEQM, @MD11, @DALCC
	);

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `timezone`, `language`, `salt`, `passwd`
	)
	VALUES
	(
		1, 'uid-1', 'uid-1@example.com', 3600, 'en',
		'ad879fa6950455c6bbe11b96d2038b6bd2e91a3c95f9624500d16c2bf3759e2c',
		'3209cdc842a87229023e3f1a01f0051f87710dafa417960e8436469a41343e30'
	);

	SELECT `id` INTO @uid FROM `users` WHERE `name`='uid-1';

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT 1, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('users', 'addflights')
	);
	SQL

###############################################################################

now=$(date -d 'today 12:00' +'%Y-%m-%dT%H:%M:%S%z')
now=$(rawurlencode "$now")

test_1_arrival() {
	browse "$url/?arrival&time=$now"
}

test_2_departure() {
	browse "$url/?departure"
}

# Still using session dir=departure ...
test_3_departure_logged_in() {
	browse "$url/?req=login" \
		--store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_4_arrival_watchlist() {
	query fra-flugplan <<-"SQL"
		SELECT `id` INTO @uid FROM `users` WHERE `name`='uid-1';

		INSERT INTO `watchlist`(`user`, `reg`, `comment`)
		VALUES(@uid, 'D-AIRY', 'Die Maus');
		SQL

	browse "$url/?arrival&time=$now"
}

# The only profile page containing assets is photodb...
test_7_profile_photodb() {
	browse "$url/?req=profile&photodb"
}

# Still logged in -> "settings"/"logout" icons
test_11_mobile_arrival() {
	browse --user-agent "$MOBILE" "$url/?arrival&time=$now"
}

test_12_mobile_departure() {
	browse --user-agent "$MOBILE" "$url/?departure"
}

# For mobile UA, test all profile tabs, as highlighted tab icon has different colour
test_15_profile_dispinterval() {
	browse --user-agent "$MOBILE" "$url/?req=profile&dispinterval"
}

test_16_profile_notifinterval() {
	browse --user-agent "$MOBILE" "$url/?req=profile&notifinterval"
}

test_17_profile_photodb() {
	browse --user-agent "$MOBILE" "$url/?req=profile&photodb"
}

test_18_profile_changepw() {
	browse --user-agent "$MOBILE" "$url/?req=profile&changepw"
}

# Logged out "register"/"login" icons
test_19_mobile_logged_out() {
	browse --user-agent "$MOBILE" "$url/?req=logout"
}
