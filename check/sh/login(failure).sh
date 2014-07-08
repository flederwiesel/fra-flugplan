#!/bin/sh

###############################################################################
#
#  Copyright © Tobias Kühne
#
#  You may use and distribute this software free of charge for non-commercial
#  purposes. The software must be distributed in its entirety, i.e. containing
#  ALL binary and source files without modification.
#  Publication of modified versions of the source code provided herein,
#  is permitted only with the author's written consent. In this case the
#  copyright notice must not be removed or altered, all modifications to the
#  source code must be clearly marked as such.
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

###############################################################################

check "1" curl "$url/?req=register" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"

token=$(query "USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "2" curl "$url/?req=activate" --data-urlencode "user=flederwiesel" --data-urlencode "token=$token"

###############################################################################

check "3" curl "$url/?req=login"
check "4" curl "$url/?req=login" --data-urlencode "user="
check "5" curl "$url/?req=login" --data-urlencode "passwd="
check "6" curl "$url/?req=login" --data-urlencode "user=" --data-urlencode "passwd=elvizzz"
check "7" curl "$url/?req=login" --data-urlencode "user=flederwiesel" --data-urlencode "passwd="

check "8" curl "$url/?req=login" \
		--data-urlencode "user=afb923eb88df155f28f68cb054502f7885b5e0423a9b95c9fe35ccc809c2e4db1878fb72ccf5948dcaac98366798d4a80fe20c118ed28628ff7646e860887bcde7423acd12d3ffd7ff59d45fa32a1b206b3c4c2519053b0466461f7cc5e70ea6dc3e9369ba6a40c9859caefa2b71adb77468caa3a866802e6ebd7b841b9718eef802197d9b2884f190b5a4e9714d23058b1280ca8c2d9fe256137388460db68e76507f5d652d2faaf45a8a94844ae80a39171c4dd86a6b9e52830e056d0c4ca70108300bf34060bd53f4af9fe1a5860e60810d35bdabfe271728e2d82edeaa3e0dff07c1d20e5355af1bdd8de914faf5a13267d1be4fd000ea03c9cc74f468278c7ac1dda3edadca2c8b39140eb01ce5326f8af300e714b3c3e08b35eef6a450b83e25981b9b45a9d37c6115d99a36857bb87ca0bc314f54c268360a35d82d062527b1dc9dedadc91abae9a6074c42844b8685e00755a566be4b82d39dc7c225f279fd05a537c39e8766c30cf544d2830612e66d6e081e136d578ebabe10d7d957286d3134bedd397d60ca9af9070a82e0151e6fadcbdf7fa747a92111bb72497479b63b00b6b8f0ea33c2340f6a4efa93d0ab9b306e1ef1f513ff6b971432b944625c5ed353c8e06e8f1dbe7c2c057b0941705a6c1b598a9454416b093944d90d2a0611eae74ab0851085206e982203991d8049d88f2ece465e6a9f05323c539380c23f8e63c2aa755b3358437c6e81e82de611fcc0b36144b259fc4d4609c9570cff62a7d48977915b057a04e1f7302de4438ba014a386f8d4e003c1bec7f5336550fa983382aca0782d88e89c634c4a77633887fbd499d81b9021adb30f76189c60cc8a2965127658de64fab0a87ccbb567be7fbda6f9765c7f1470f0e403efb500c2928c881000a5ade24773c8bb3d31fd612e6334f3b02723589a1838eb1ebb5279bbbea162eb285125269f408868ed301a95515925dcf87d7ba284dd66dbe25bfd634dd6c900847d2b7db8d92cd47e50e113de4b66800f7bc252b659b366009f6c545d32d1ab7cb02f53cb028ea95650723b5150c0c170d85aa01a290eeba14f52f0049d880064ecd3c6205602c776f9dce346d3457937c97a8219e93372622d564daa5a2102cd5f4deec23ab89b0da53873b05e8ab705a75c1b8560b64981001885a05b95190cfb02b0c81585aad13a145bab422dc08f9f5f456d398b7cf9469b95ab876f428244452e862796fbfffc99a6420c101aa36e54db6ca95cd2459cd921376091af05725904987f2ea689324f940103ea7d0445498fdcf5888100c95420a89b036deaffd09769323bd1fe4ea574635ea39f8e76af20bd57136526addefff30a7c99086e92df58b60afca64657daa4e0d5439945ee7a9755225188069603c5334e509f2fe361abda8582126ecfddbd3a54b80c4ad96ff61af9" \
		--data-urlencode "passwd=elvizzz"

check "9" curl "$url/?req=login" \
		--data-urlencode "user=elvizzz" \
		--data-urlencode "passwd=afb923eb88df155f28f68cb054502f7885b5e0423a9b95c9fe35ccc809c2e4db1878fb72ccf5948dcaac98366798d4a80fe20c118ed28628ff7646e860887bcde7423acd12d3ffd7ff59d45fa32a1b206b3c4c2519053b0466461f7cc5e70ea6dc3e9369ba6a40c9859caefa2b71adb77468caa3a866802e6ebd7b841b9718eef802197d9b2884f190b5a4e9714d23058b1280ca8c2d9fe256137388460db68e76507f5d652d2faaf45a8a94844ae80a39171c4dd86a6b9e52830e056d0c4ca70108300bf34060bd53f4af9fe1a5860e60810d35bdabfe271728e2d82edeaa3e0dff07c1d20e5355af1bdd8de914faf5a13267d1be4fd000ea03c9cc74f468278c7ac1dda3edadca2c8b39140eb01ce5326f8af300e714b3c3e08b35eef6a450b83e25981b9b45a9d37c6115d99a36857bb87ca0bc314f54c268360a35d82d062527b1dc9dedadc91abae9a6074c42844b8685e00755a566be4b82d39dc7c225f279fd05a537c39e8766c30cf544d2830612e66d6e081e136d578ebabe10d7d957286d3134bedd397d60ca9af9070a82e0151e6fadcbdf7fa747a92111bb72497479b63b00b6b8f0ea33c2340f6a4efa93d0ab9b306e1ef1f513ff6b971432b944625c5ed353c8e06e8f1dbe7c2c057b0941705a6c1b598a9454416b093944d90d2a0611eae74ab0851085206e982203991d8049d88f2ece465e6a9f05323c539380c23f8e63c2aa755b3358437c6e81e82de611fcc0b36144b259fc4d4609c9570cff62a7d48977915b057a04e1f7302de4438ba014a386f8d4e003c1bec7f5336550fa983382aca0782d88e89c634c4a77633887fbd499d81b9021adb30f76189c60cc8a2965127658de64fab0a87ccbb567be7fbda6f9765c7f1470f0e403efb500c2928c881000a5ade24773c8bb3d31fd612e6334f3b02723589a1838eb1ebb5279bbbea162eb285125269f408868ed301a95515925dcf87d7ba284dd66dbe25bfd634dd6c900847d2b7db8d92cd47e50e113de4b66800f7bc252b659b366009f6c545d32d1ab7cb02f53cb028ea95650723b5150c0c170d85aa01a290eeba14f52f0049d880064ecd3c6205602c776f9dce346d3457937c97a8219e93372622d564daa5a2102cd5f4deec23ab89b0da53873b05e8ab705a75c1b8560b64981001885a05b95190cfb02b0c81585aad13a145bab422dc08f9f5f456d398b7cf9469b95ab876f428244452e862796fbfffc99a6420c101aa36e54db6ca95cd2459cd921376091af05725904987f2ea689324f940103ea7d0445498fdcf5888100c95420a89b036deaffd09769323bd1fe4ea574635ea39f8e76af20bd57136526addefff30a7c99086e92df58b60afca64657daa4e0d5439945ee7a9755225188069603c5334e509f2fe361abda8582126ecfddbd3a54b80c4ad96ff61af9"
