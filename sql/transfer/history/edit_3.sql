UPDATE aircrafts SET type=(SELECT id FROM `aircraft-types` WHERE icao='﻿B737') WHERE reg='UR-GBD';
