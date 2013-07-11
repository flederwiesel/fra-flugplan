﻿INSERT INTO `users`(`id`, `name`, `email`, `salt`, `passwd`, `language`, `permissions`)
VALUES
(
	245,
	'flederwiesel',
	'hausmeister@flederwiesel.com',
	'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
	'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
	'en',
	'1'
);

SELECT (@uid:=`id`) FROM `users` WHERE `name`='flederwiesel';

INSERT INTO `watchlist`(`user`, `reg`, `comment`)
VALUES
(@uid, '9A-CTI',	'Croatia Airlines - Star Alliance'),
(@uid, '9H-AEO',	'Air Malta - Valetta 2018'),
(@uid, '9M-MTE',	'Malaysia - One World'),
(@uid, '9V-SPP',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SRE',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SRI',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SWI',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SWJ',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SYE',	'Singapore Airlines - Star Alliance'),
(@uid, '9V-SYL',	'Singapore Airlines - Star Alliance'),
(@uid, 'A40-EJ',	'Gulf Air - Calligraphy'),
(@uid, 'A6-AFA',	'Etihad - Visit Abu Dhabi lila'),
(@uid, 'A6-EIB',	'Etihad - Formula1'),
(@uid, 'A6-EHJ',	'Etihad - Abu Dhabi Grand Prix 2012'),
(@uid, 'A6-ESH',	'United Arab Emirates'),
(@uid, 'A6-EYE',	'Etihad - Manchester City'),
(@uid, 'A6-EGO',	'Emirates - 1000th 777'),
(@uid, 'A6-UAE',	'Abu Dhabi Amiri Flight'),
(@uid, 'A7-HHM',	'Qatar Amiri Flight'),
(@uid, 'A9C-KB',	'Gulf Air - Bahrain Grand Prix'),
(@uid, 'B-16309',	'EVA Air - Hello Kitty'),
(@uid, 'B-16332',	'EVA Air - Hello Kitty'),
(@uid, 'B-18203',	'China Airlines - "Jimmy"'),
(@uid, 'B-18206',	'China Airlines - Sky Team'),
(@uid, 'B-18311',	'China Airlines - Sky Team'),
(@uid, 'B-18312',	'China Airlines - 50 Years'),
(@uid, 'B-18806',	'China Airlines - Climate Monitoring'),
(@uid, 'B-2032',	'Air China - Star Alliance'),
(@uid, 'B-2035',	'Air China - Smiling China'),
(@uid, 'B-2059',	'Air China - Blau'),
(@uid, 'B-2060',	'Air China - ROT'),
(@uid, 'B-2290',	'China Eastern Airlines - Expo 2010 Shanghai*'),
(@uid, 'B-5908',	'China Eastern Airlines - Sky Team'),
(@uid, 'B-6053',	'China Eastern Airlines - Sky Team'),
(@uid, 'B-6055',	'China Eastern Airlines - Expo 2010 Shanghai'),
(@uid, 'B-6057',	'China Southern Airlines - 16th Asian Games Guangzhou 2010'),
(@uid, 'B-6065',	'China Eastern Airlines - Better City, Better Life'),
(@uid, 'B-6075',	'Air China - Red/Gold'),
(@uid, 'B-6076',	'Air China - Blue/Gold'),
(@uid, 'B-6091',	'Air China - Star Alliance'),
(@uid, 'B-6093',	'Air China - Star Alliance'),
(@uid, 'B-6100',	'China Eastern Airlines - EXPO 2010'),
(@uid, 'B-6125',	'China Eastern Airlines - Xinhua News'),
(@uid, 'B-6126',	'China Eastern Airlines - www.people.cn'),
(@uid, 'B-6127',	'China Eastern Airlines - Shanghai EXPO 2010: Better Flight, Better Trip'),
(@uid, 'B-6128',	'China Eastern Airlines - Peacock'),
(@uid, 'B-6361',	'Air China - Beautiful Sichuan Panda*'),
(@uid, 'B-6528',	'China Southern - Sky Team'),
(@uid, 'B-6538',	'China Eastern Airlines - Sky Team'),
(@uid, 'B-HXG' ,	'Cathay Pacific Airways - oneworld'),
(@uid, 'B-HYF' ,	'Dragonair - 25 years'),
(@uid, 'B-LAD' ,	'Cathay Pacific - 100th aircraft (now w/o sticker)'),
(@uid, 'B-LJA' ,	'Cathay Pacific - Hong Kong Trader'),
(@uid, 'B-KPF' ,	'Cathay Pacific - Asias world city GRÜN'),
(@uid, 'B-MAJ' ,	'Air Macau - 4th East Asian Games 2005*'),
(@uid, 'C-FMWY',	'Air Canada - Star Alliance'),
(@uid, 'C-FZUH',	'Air Canada - Trans Canadian Retro'),
(@uid, 'C-GFAH',	'Air Canada'),
(@uid, 'C-GHLM',	'Air Canada - Star Alliance'),
(@uid, 'C-GLAT',	'Air Transat - Welcome white'),
(@uid, 'C-GSAT',	'Air Transat - Welcome'),
(@uid, 'C-GTSF',	'Air Transat  - Welcome'),
(@uid, 'C-GTSJ',	'Air Transat  - Welcome'),
(@uid, 'CC-CXJ',	'LAN - oneworld'),
(@uid, 'CS-TIC',	'Air Portugal - Fly Algarve'),
(@uid, 'CS-TNP',	'TAP - Star Alliance'),
(@uid, 'CS-TOH',	'TAP - Star Alliance'),
(@uid, 'D-ABMC',	'Air Berlin -  oneworld'),
(@uid, 'D-ABME',	'Air Berlin -  oneworld'),
(@uid, 'D-ABON',	'Condor - Willi'),
(@uid, 'D-ABUE',	'Condor - Janosch'),
(@uid, 'D-ABUM',	'Condor - 70s Retro'),
(@uid, 'D-ABUW',	'Lufthansa - Star Alliance /w white tail'),
(@uid, 'D-ABXA',	'Air Berlin -  oneworld'),
(@uid, 'D-ABYA',	'Lufthansa - 747-8'),
(@uid, 'D-ACPQ',	'Lufthansa - Star Alliance'),
(@uid, 'D-ACPS',	'Lufthansa - Star Alliance'),
(@uid, 'D-ACPT',	'Lufthansa - Star Alliance'),
(@uid, 'D-ADIC',	'dba - T-Mobile'),
(@uid, 'D-AFKA',	'Contact Air - Star Alliance'),
(@uid, 'D-AFKB',	'Contact Air - Star Alliance'),
(@uid, 'D-AFKF',	'Lufthansa - Star Alliance'),
(@uid, 'D-AGPH',	'Contact Air - Star Alliance'),
(@uid, 'D-AGPK',	'Contact Air - Star Alliance'),
(@uid, 'D-AHFA',	'TUI - WEISS'),
(@uid, 'D-AHFB',	'TUI - SharanAir'),
(@uid, 'D-AHFM',	'TUI - GoldbAIR'),
(@uid, 'D-AHFO',	'TUI - WEISS'),
(@uid, 'D-AHFR',	'ÜBÄRFlieger'),
(@uid, 'D-AHFY',	'TUI - Kärnten'),
(@uid, 'D-AHFZ',	'TUI - Cewe'),
(@uid, 'D-AHIK',	'Hamburg International - 10 Jahre'),
(@uid, 'D-AICA',	'Condor - Retro "Hans"'),
(@uid, 'D-AIDV',	'Lufthansa - Retro'),
(@uid, 'D-AILU',	'Lufthansa - Jetfriends'),
(@uid, 'D-AIGC',	'Lufthansa - Star Alliance'),
(@uid, 'D-AILF',	'Lufthansa - Star Alliance'),
(@uid, 'D-AIQW',	'100 Jahre Hamburg Airport'),
(@uid, 'D-AIRW',	'Lufthansa - Star Alliance'),
(@uid, 'D-AIRX',	'Lufthansa - Retro'),
(@uid, 'D-AIRY',	'Lufthansa - Die Maus'),
(@uid, 'D-ALCC',	'Lufthansa Cargo - 100 Years Air Cargo'),
(@uid, 'D-ATUC',	'TUI - Deutsche Bahn Regio'),
(@uid, 'D-ATUD',	'TUI - HaribAIR'),
(@uid, 'D-ATUE',	'TUI - Deutsche Bahn ICE'),
(@uid, 'D-ATUF',	'Hapag-Lloyd'),
(@uid, 'D-CURT',	'Sven Väth'),
(@uid, 'EC-HDP',	'Iberia - oneworld'),
(@uid, 'EC-ILH',	'Spanair - Star Alliance'),
(@uid, 'EC-IZR',	'Iberia - oneworld'),
(@uid, 'EC-JHK',	'Air Europa "Skyteam"'),
(@uid, 'EC-JZQ',	'Vueling - ¡gracias!'),
(@uid, 'EC-KKS',	'Iberia - Retro'),
(@uid, 'EC-LNH' ,	'Air Europa "Skyteam"'),
(@uid, 'EC-LVP' ,	'Vueling - Linking Europe'),
(@uid, 'EI-DSA',	'Alitalia - Grau'),
(@uid, 'EI-DVM',	'Aer Lingus - Retro'),
(@uid, 'EI-END',	'Alitalia - Sky Team'),
(@uid, 'EI-EZL',	'Turkish Airlines - grey'),
(@uid, 'EI-IXI',	'Alitalia - Retro'),
(@uid, 'EK74799',	'Saudia weiß'),
(@uid, 'ET-ALO',	'Ethiopian - Star Alliance'),
(@uid, 'F-BUAD',	'Zero G'),
(@uid, 'F-GFKJ',	'Air France - Retro'),
(@uid, 'F-GFKS',	'Air France - Sky Team'),
(@uid, 'F-GFKY',	'Air France - Sky Team'),
(@uid, 'F-GTAE',	'Air France - Sky Team'),
(@uid, 'G-CIVP',	'British Airways - One World'),
(@uid, 'G-DBCB',	'British Airways - The Dove'),
(@uid, 'G-DBCD',	'British Airways - The Dove'),
(@uid, 'G-EJAR',	'easyJet - Unicef'),
(@uid, 'G-EUOH',	'British Airways - The Dove'),
(@uid, 'G-EUPA',	'British Airways - The Dove'),
(@uid, 'G-EUPD',	'British Airways - The Dove'),
(@uid, 'G-EUPG',	'British Airways - The Dove'),
(@uid, 'G-EUPH',	'British Airways - The Dove'),
(@uid, 'G-MIDS',	'BMI - Star Alliance'),
(@uid, 'G-MIDX',	'BMI - Star Alliance'),
(@uid, 'G-OJIB',	'Iron Maiden - Somewhere Back In Time'),
(@uid, 'G-STRX',	'Iron Maiden - The Final Frontier'),
(@uid, 'G-TCBB',	'Thomas Cook Airlines - egypt'),
(@uid, 'G-XLEA',	'BA A380'),
(@uid, 'HB-IYS',	'Swiss - Shopping Paradise Zürich Airport'),
(@uid, 'HB-IYU',	'Swiss - Star Alliance'),
(@uid, 'HB-IYV',	'Swiss - Star Alliance'),
(@uid, 'HC-CLC',	'LAN - oneworld'),
(@uid, 'HL7495',	'Korean Air - "Welcome to Korea"'),
(@uid, 'HL7516',	'Asiana - Star Alliance'),
(@uid, 'HL7733',	'Korean Air -Sky Team'),
(@uid, 'HL7752',	'Korean Air - New Horizons Of Korea'),
(@uid, 'HL8211',	'Korean Air - Children\'s Paintings'),
(@uid, 'HS-MAR',	'Thai Airways - A380 ??'),
(@uid, 'HS-STA',	'Orient Thai'),
(@uid, 'HS-TEL',	'Thai Airways - Star Alliance'),
(@uid, 'HS-TGP',	'Thai Airways - Retro'),
(@uid, 'HS-TGW',	'Thai Airways - Star Alliance'),
(@uid, 'HS-TUA',	'Thai Airways - A380'),
(@uid, 'HZ-AKA',	'Saudia - SkyTeam'),
(@uid, 'HZ-ASF',	'Saudia - SkyTeam'),
(@uid, 'HZ-TGH',	'Saudia - SkyTeam'),
(@uid, 'JA602A',	'ANA - Retro'),
(@uid, 'JA604J',	'JAL - One World'),
(@uid, 'JA606A',	'ANA - Panda'),
(@uid, 'JA614A',	'ANA - Star Alliance'),
(@uid, 'JA704J',	'JAL - one world'),
(@uid, 'JA702J',	'JAL - "Japan Endless Discovery" Blumen'),
(@uid, 'JA707J',	'JAL - contrail'),
(@uid, 'JA708J',	'JAL - One World'),
(@uid, 'JA711A',	'ANA - Star Alliance'),
(@uid, 'JA712A',	'ANA - Star Alliance'),
(@uid, 'JA712J',	'JAL - "Japan Endless Discovery" Blumen'),
(@uid, 'JA714J',	'JAL - "Japan Endless Discovery" Blumen'),
(@uid, 'JA723J',	'JAL - "Japan Endless Discovery" Blumen'),
(@uid, 'JA731A',	'ANA - Star Alliance'),
(@uid, 'JA732J',	'JAL - oneworld'),
(@uid, 'JA734J',	'JAL - Sky Eco'),
(@uid, 'JA752J',	'JAL - oneworld'),
(@uid, 'JA754A',	'ANA - Pokemon Peace Jet'),
(@uid, 'JA771J',	'JAL - oneworld'),
(@uid, 'JA805A',	'ANA - 787'),
(@uid, 'JA821J',	'JAL - 787 ??'),
(@uid, 'JA822A',	'ANA - 787 ??'),
(@uid, 'JA822J',	'JAL - 787'),
(@uid, 'JA825J',	'JAL - 787'),
(@uid, 'JA851J',	'JAL - 787 ??'),
(@uid, 'JA8941',	'JAL - hellosmile'),
(@uid, 'JA8956',	'ANA - Pokemon'),
(@uid, 'JA8957',	'ANA - Pokemon'),
(@uid, 'JA8978',	'JAL - Sky Tree'),
(@uid, 'JA8980',	'JAL - One World'),
(@uid, 'JA8981',	'JAL - Hawaii'),
(@uid, 'JA8984',	'JAL - Eco Jet'),
(@uid, 'JY-AYP',	'Royal Jordanian - One World'),
(@uid, 'LN-BUD',	'SAS weiß'),
(@uid, 'LN-RRL',	'SAS - Star Alliance'),
(@uid, 'N121UA',	'United Airlines - Star Alliance'),
(@uid, 'N14120',	'United Airlines - Star Alliance'),
(@uid, 'N171DZ',	'Delta - Habitat For Humanity'),
(@uid, 'N174AA',	'American Airlines - One World'),
(@uid, 'N175DZ',	'Delta - Sky Team'),
(@uid, 'N194UA',	'United Charter'),
(@uid, 'N218UA',	'United Airlines - Star Alliance'),
(@uid, 'N342AV',	'Avianca - Star Alliance'),
(@uid, 'N395AN',	'American Airlines - One World'),
(@uid, 'N519AV',	'Avianca - Star Alliance'),
(@uid, 'N653UA',	'United Airlines - Star Alliance'),
(@uid, 'N705TW',	'Delta - Sky Team'),
(@uid, 'N717TW',	'Delta - Sky Team'),
(@uid, 'N722TW',	'Delta - Sky Team'),
(@uid, 'N759AN',	'American Airlines - Pink Ribbon'),
(@uid, 'N76021',	'United Airlines - Star Alliance'),
(@uid, 'N76055',	'United Airlines - Star Alliance'),
(@uid, 'N77022',	'United Airlines - Star Alliance'),
(@uid, 'N78017',	'United Airlines - Star Alliance'),
(@uid, 'N791AN',	'American Airlines - One World'),
(@uid, 'N796AN',	'American Airlines - One World'),
(@uid, 'N844MH',	'Delta - Sky Team'),
(@uid, 'N845MH',	'Delta - Pink Lady'),
(@uid, 'OD-MRL',	'5000 A320 Family Sticker'),
(@uid, 'OE-LBP',	'AUA Retro'),
(@uid, 'OE-LNT',	'AUA Star Alliance'),
(@uid, 'OE-LBX',	'AUA - Star Alliance'),
(@uid, 'OE-LVG',	'AUA - Star Alliance'),
(@uid, 'OH-BLN',	'Blue1 - Star Alliance'),
(@uid, 'OH-LQD',	'Finnair - Blumen'),
(@uid, 'OH-LQE',	'Finnair - One World'),
(@uid, 'OH-LVF',	'Finnair - One World'),
(@uid, 'OK-GFR',	'Czech Airlines Sky Team'),
(@uid, 'OO-SSC',	'Brussels Airlines - Star Alliance'),
(@uid, 'OY-KBM',	'SAS - Star Alliance'),
(@uid, 'OY-KBO',	'SAS Retro'),
(@uid, 'OY-KHE',	'SAS - Star Alliance'),
(@uid, 'OY-KHP',	'SAS - Star Alliance'),
(@uid, 'P4-KCB',	'20th anniversary of the independence of Kazakhstan'),
(@uid, 'P4-MES',	'Untitled'),
(@uid, 'PH-BVD',	'KLM - Sky Team'),
(@uid, 'PH-BXA',	'KLM - Retro'),
(@uid, 'PH-BXO',	'KLM - Sky Team'),
(@uid, 'PR-MBO',	'TAM - Star Alliance'),
(@uid, 'PT-MVM',	'TAM - Star Alliance'),
(@uid, 'PT-MVP',	'TAM - Seleção Brasileira (Football)'),
(@uid, 'RA-89005',	'Aeroflot - Sky Team'),
(@uid, 'S5-AAG',	'Adria - Star Alliance'),
(@uid, 'SE-DIB',	'SAS - Star Alliance'),
(@uid, 'SE-DHF',	'SAS - Star Alliance'),
(@uid, 'SE-REF',	'SAS - Star Alliance'),
(@uid, 'SP-LDC',	'LOT - Star Alliance'),
(@uid, 'SP-LDK',	'LOT - Star Alliance'),
(@uid, 'SP-LKE',	'LOT - Star Alliance'),
(@uid, 'SP-LLC',	'LOT - Gold'),
(@uid, 'SP-LNB',	'LOT - Move your Imagination - Sticker'),
(@uid, 'SP-LPE',	'LOT - Star Alliance'),
(@uid, 'SU-GBR',	'Egypt Air - Star Alliance'),
(@uid, 'SU-GCK',	'Egypt Air - Star Alliance'),
(@uid, 'SU-GCS',	'Egypt Air - Star Alliance'),
(@uid, 'SX-DIO',	'Astra Airlines'),
(@uid, 'SX-DVQ',	'Aegean - Star Alliance'),
(@uid, 'TC-JDL',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-JFH',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-JFI',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-JFV',	'Turkish Airlines - Manchester United'),
(@uid, 'TC-JHF',	'Turkish Airlines - World Championship'),
(@uid, 'TC-JHU',	'Turkish Airlines - Borussia Dortmund'),
(@uid, 'TC-JLC',	'Turkish Airlines - Retro'),
(@uid, 'TC-JNI',	'Turkish Airlines - Olympia 2020'),
(@uid, 'TC-JOL',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-JRA',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-JRB',	'Turkish Airlines - Star Alliance'),
(@uid, 'TC-SKM',	'Sky Airlines'),
(@uid, 'TC-SKS',	'Sky Airlines'),
(@uid, 'TC-SUZ',	'Sun Express - Impressions of Istanbul'),
(@uid, 'TS-INI',	'Nouvelair - white body'),
(@uid, 'TF-AMF',	'Saudia weiß'),
(@uid, 'TF-AMX',	'Saudia weiß'),
(@uid, 'TF-AMZ',	'Saudia weiß'),
(@uid, 'VH-OEB',	'Qantas - Australian Grand Prix 2011'),
(@uid, 'VH-OEF',	'Qantas - One World'),
(@uid, 'VH-OJO',	'Qantas - Go Wallabies'),
(@uid, 'VH-OJS',	'Qantas - Socceroos'),
(@uid, 'VH-OJU',	'Qantas - One World'),
(@uid, 'VP-BAT',	'Qatar Amiri Flight 747SP'),
(@uid, 'VP-BMF',	'Aeroflot - Sochi Winter Olympic games 2014'),
(@uid, 'VP-BNT',	'Aeroflot - Retro'),
(@uid, 'VP-BTN',	'S7 Airlines - oneworld'),
(@uid, 'VP-BZP',	'Aeroflot -  Sochi Winter Olympic games 2014'),
(@uid, 'VP-CMS',	'SAAD Group'),
(@uid, 'VP-CVX',	'Volkswagen Air Services'),
(@uid, 'VQ-BCO',	'Aeroflot - One World'),
(@uid, 'VQ-BKW',	'S7 Airlines - oneworld'),
(@uid, 'VT-ANA',	'Air India - 787'),
(@uid, 'YL-LCK',	'Condor (operated by SmartLynx)'),
(@uid, 'YR-BGF',	'Tarom - Sky Team'),
(@uid, 'YR-BGG',	'Tarom - Retro'),
(@uid, 'ZK-OKQ',	'Air New Zealand - All Blacks'),
(@uid, 'ZS-SNC',	'South African Airways - Star Alliance'),
(@uid, 'ZS-SNG',	'South African Airways - Beijing 2012'),
(@uid, 'ZS-SXD',	'South African Airways - Siyanqoba London 2012 Olympic Games')
;
/*
(@uid, 'B-18210',	'China Airlines - Dreamliner c/s'),
(@uid, 'G-EUPC',	'British Airways - London 2012 Olympics - Torch Relay'),
(@uid, 'OE-LVM',	'AUA - Austrian Ski Team'),
(@uid, 'OH-LVE',	'Finnair - Retro'),
(@uid, 'OK-XGB',	'Czech Airlines Retro Blau'),
(@uid, 'OK-XGC',	'Czech Airlines Retro Rot'),
(@uid, 'OK-XGE',	'Czech Airlines Sky Team'),
(@uid, 'SP-LDC',	'LOT - The Dutchess'),
(@uid, 'TC-JHL',	'Turkish Airlines - Globally Yours - Gesichterpuzzle'),
(@uid, 'TC-JJI',	'Turkish Airlines - FC Barcelona - Spieler'),
(@uid, 'TC-JRO',	'Turkish Airlines - Euro League'),
(@uid, 'TC-JYI',	'Turkish Airlines - 200th aircraft'),
(@uid, 'PT-MVG',	'TAM - employees\' signatures'),
*/

/*
(@uid, 'D-AIGR',	'2000th Airbus'),
(@uid, 'A6-EDH',	'6000th Airbus'),
(@uid, 'N552UW',	'7000th Airbus'),

(@uid, 'F-GRHD',	'1000th A320'),
(@uid, 'B-6022',	'2000th A320'),
(@uid, '9M-AFP',	'3000th A320'),
(@uid, 'PT-TMA',	'4000th A320'),
(@uid, 'OD-MRL',	'5000th A320'),
*/
