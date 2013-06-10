/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
 *
 * Copyright © Tobias Kühne
 *
 * You may use and distribute this software free of charge for non-commercial
 * purposes. The software must be distributed in its entirety, i.e. containing
 * ALL binary and source files without modification.
 * Publication of modified versions of the source code provided herein,
 * is permitted only with the author's written consent. In this case the
 * copyright notice must not be removed or altered, all modifications to the
 * source code must be clearly marked as such.
 *
 ******************************************************************************/

DROP DATABASE IF EXISTS `fra-schedule`;

CREATE DATABASE IF NOT EXISTS `fra-schedule` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci';
USE `fra-schedule`;

/******************************************************************************
 * Tables
 ******************************************************************************/

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL,
  `salt` varchar(64) NOT NULL,
  `passwd` varchar(64) NOT NULL,
  `token` varchar(64) NULL DEFAULT NULL,
  `token_type` enum('none', 'activation', 'password') NOT NULL,
  `token_expires` timestamp NULL DEFAULT NULL,
  `timezone` smallint DEFAULT 3600,
  `language` varchar(2) NOT NULL DEFAULT 'en',
  `permissions` varchar(1) NOT NULL DEFAULT '0' COMMENT '[0] - addflight',
  `ip` varchar(16) NOT NULL,	/* at the time of registration */
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `airlines`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`uid` integer NOT NULL,
	`code` varchar(3),
	`name` varchar(128),
	PRIMARY KEY (`id`),
	CONSTRAINT `unique: code` UNIQUE (`code`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `models`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`uid` integer NOT NULL,
	`icao` varchar(4) NOT NULL,
	`name` varchar(96) NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:icao` UNIQUE (`icao`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`id`)
) COMMENT = 'http://www.airlinecodes.co.uk'
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aircrafts`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`uid` integer NOT NULL,
	`reg` varchar(8) NOT NULL,
	`model` integer NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:reg` UNIQUE (`reg`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`id`),
	FOREIGN KEY(`model`) REFERENCES `models`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `airports`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`uid` integer NOT NULL,
	`iata` varchar(3) NULL,
	`icao` varchar(4) NOT NULL,
	`name` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:icao` UNIQUE(`icao`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `flights`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`uid` integer NOT NULL,
	`type` enum('pax-regular', 'cargo', 'ferry') NOT NULL DEFAULT 'pax-regular',
	`direction` enum('arrival', 'departure') NOT NULL,
	`airline` integer NOT NULL,
	`code` varchar(6) NOT NULL,
	`scheduled` datetime NOT NULL,
	`expected` timestamp NULL DEFAULT NULL,
	`airport` integer DEFAULT NULL,
	`model` integer DEFAULT NULL,
	`aircraft` integer DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:direction, airline, code, scheduled` UNIQUE (`direction`, `airline`, `code`, `scheduled`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`id`),
	FOREIGN KEY(`airline`) REFERENCES `airlines`(`id`),
	FOREIGN KEY(`airport`) REFERENCES `airports`(`id`),
	FOREIGN KEY(`model`) REFERENCES `models`(`id`),
	FOREIGN KEY(`aircraft`) REFERENCES `aircrafts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `watchlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `reg` varchar(8) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user, reg` (`user`, `reg`),
  FOREIGN KEY(`user`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/******************************************************************************
 * Triggers
 ******************************************************************************/

DELIMITER $$

CREATE TRIGGER `users:length`
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
	IF LENGTH(NEW.`name`) < 4 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '400: `users`.`name` must be at least 4 characters long.';
	END IF;
	IF LENGTH(NEW.`name`) > 64 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '401: `users`.`name` must be no longer than 4 characters.';
	END IF;
	IF LENGTH(NEW.`salt`) != 64 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '402: Salt is invalid.';
	END IF;
	IF LENGTH(NEW.`passwd`) != 64 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '403: Password hash is invalid.';
	END IF;
END$$

/*
	Check:
	======

	INSERT INTO `users` (`name`, `email`, `passwd`, `salt`)
	VALUES(
		'zzz',
		'email.vaild.com',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae');

	INSERT INTO `users` (`name`, `email`, `passwd`, `salt`)
	VALUES(
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae0',
		'email.vaild.com',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae');
	-- fails!

	INSERT INTO `users` (`name`, `email`, `passwd`, `salt`)
	VALUES(
		'erwin',
		'email.vaild.com',
		'',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae');

	INSERT INTO `users` (`name`, `email`, `passwd`, `salt`)
	VALUES(
		'erwin',
		'email.vaild.com',
		'80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae',
		'');
*/

CREATE TRIGGER `users:token`
BEFORE UPDATE ON `users`
FOR EACH ROW
BEGIN
	IF (NOT NEW.`token` IS NULL)
		AND NEW.`token_expires` IS NULL THEN
		SET NEW.`token_expires` = FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + 1 * 3600);
	END IF;
END$$

/*
	Check:
	======

	UPDATE `users` SET `token`='80c93eaddcb3de5728c1fe86c62ac1bf5306134709dcf3770311548968926fae'
		WHERE `id`=245;
*/

DELIMITER ;

/******************************************************************************
 * Indices
 ******************************************************************************/

CREATE INDEX `airlines:uid` ON `airlines`(`uid` ASC);
CREATE INDEX `models:uid` ON `models`(`uid` ASC);
CREATE INDEX `aircrafts:uid` ON `aircrafts`(`uid` ASC);
CREATE INDEX `airports:uid` ON `airports`(`uid` ASC);
CREATE INDEX `flights:uid` ON `flights`(`uid` ASC);
CREATE INDEX `flights:scheduled` ON `flights`(`scheduled` ASC);
CREATE INDEX `flights:code` ON `flights`(`code` ASC);
CREATE INDEX `flights:direction` ON `flights`(`direction` ASC);
CREATE INDEX `watchlist:user` ON `watchlist`(`user` ASC);
CREATE INDEX `watchlist:reg` ON `watchlist`(`reg` ASC);

/******************************************************************************
 * Data
 ******************************************************************************/

INSERT INTO `users`(`id`, `name`, `email`, `salt`, `passwd`, `language`, `permissions`)
VALUES
(
	1,
	'root',
	'fra-schedule@flederwiesel.com',
	'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
	'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
	'en',
	'1'
);

SELECT (@uid:=`id`) FROM `users` WHERE `name`='root';

INSERT INTO `airlines`(`uid`, `code`, `name`)
VALUES
(@uid, 'A3',  'Aegean Airlines'),
(@uid, 'AA',  'American Airlines'),
(@uid, 'AB',  'Airberlin'),
(@uid, 'AC',  'Air Canada'),
(@uid, 'AEE', 'Aegean Airlines'),
(@uid, 'AF',  'Air France'),
(@uid, 'AH',  'Air Algerie'),
(@uid, 'AI',  'Air India'),
(@uid, 'AP',  'Air One'),
(@uid, 'AT',  'Royal Air Maroc'),
(@uid, 'AY',  'Finnair'),
(@uid, 'AZ',  'Alitalia'),
(@uid, 'BA',  'British Airways'),
(@uid, 'BD',  'British Midland International'),
(@uid, 'BE',  'Flybe'),
(@uid, 'BPA', 'Blue Panorama Airlines'),
(@uid, 'BRU', 'Belavia'),
(@uid, 'BT',  'Air Baltic'),
(@uid, 'BUC', 'Bulgarian Air Charter'),
(@uid, 'CA',  'Air China'),
(@uid, 'CAI', 'Corendon Airlines'),
(@uid, 'CFE', 'BA CityFlyer'),
(@uid, 'CI',  'China Airlines'),
(@uid, 'CX',  'Cathay Pacific Airways'),
(@uid, 'CY',  'Cyprus Airways'),
(@uid, 'DE',  'Condor'),
(@uid, 'DL',  'Delta Air Lines'),
(@uid, 'DLN', 'Dalmatian'),
(@uid, 'EI',  'Aer Lingus'),
(@uid, 'EK',  'Emirates'),
(@uid, 'EN',  'Air Dolomiti'),
(@uid, 'ERT', 'Eritrean Airlines'),
(@uid, 'ET',  'Ethiopian Airlines'),
(@uid, 'EY',  'Etihad Airways'),
(@uid, 'FB',  'Bulgaria Air'),
(@uid, 'FHY', 'Freebird Airlines'),
(@uid, 'FI',  'Icelandair'),
(@uid, 'FV',  'Rossiya'),
(@uid, 'GF',  'Gulf Air'),
(@uid, 'GH',  'Globus'),
(@uid, 'GHY', 'German Sky Airlines '),
(@uid, 'GWI', 'Germanwings'),
(@uid, 'GXL', 'XL Airways Germany'),
(@uid, 'HCC', 'Holidays Czech Airlines'),
(@uid, 'HG',  'NIKI'),
(@uid, 'HY',  'Uzbekistan Airways'),
(@uid, 'IB',  'Iberia'),
(@uid, 'IR',  'Iran Air'),
(@uid, 'IY',  'Yemenia Yemen Airways'),
(@uid, 'IZ',  'Arkia Israel Airlines'),
(@uid, 'JA',  'B&H Airlines'),
(@uid, 'JFU', 'Jet4you'),
(@uid, 'JJ',  'TAM Linhas Aéreas'),
(@uid, 'JL',  'JAL Japan Airlines'),
(@uid, 'JU',  'JAT Airways'),
(@uid, 'KC',  'Air Astana'),
(@uid, 'KE',  'Korean Air'),
(@uid, 'KIL', 'Kuban Airlines'),
(@uid, 'KK',  'Atlasjet Airlines'),
(@uid, 'KL',  'KLM Royal Dutch Airlines'),
(@uid, 'KM',  'Air Malta'),
(@uid, 'KRP', 'Carpatair'),
(@uid, 'KU',  'Kuwait Airways'),
(@uid, 'LA',  'LAN Airlines'),
(@uid, 'LBT', 'Nouvelair Tunisie'),
(@uid, 'LG',  'Luxair'),
(@uid, 'LH',  'Lufthansa'),
(@uid, 'LO',  'LOT Polish Airlines'),
(@uid, 'LV',  'Albanian Airlines'),
(@uid, 'LX',  'Swiss International Air Lines'),
(@uid, 'LY',  'El Al Israel Airlines'),
(@uid, 'ME',  'MEA Middle East Airlines'),
(@uid, 'MH',  'Malaysia Airlines'),
(@uid, 'MHK', 'Alnaser Airlines'),
(@uid, 'MK',  'Air Mauritius'),
(@uid, 'MLD', 'Air Moldova'),
(@uid, 'MS',  'Egypt Air'),
(@uid, 'MSC', 'Air Cairo'),
(@uid, 'MU',  'China Eastern Airlines'),
(@uid, 'NH',  'ANA'),
(@uid, 'OK',  'CSA Czech Airlines'),
(@uid, 'OLT', 'OLT Express'),
(@uid, 'OM',  'MIAT Mongolian Airlines'),
(@uid, 'OS',  'Austrian Airlines Group'),
(@uid, 'OU',  'Croatia Airlines'),
(@uid, 'OZ',  'Asiana Airlines'),
(@uid, 'PGT', 'Pegasus Airlines'),
(@uid, 'PHW', 'Ave.com'),
(@uid, 'PK',  'PIA Pakistan International Airlines'),
(@uid, 'PS',  'Ukraine International Airlines'),
(@uid, 'QB',  'Sky Georgia'),
(@uid, 'QF',  'Qantas Airways'),
(@uid, 'QR',  'Qatar Airways'),
(@uid, 'RB',  'Syrianair'),
(@uid, 'RJ',  'Royal Jordanian'),
(@uid, 'RKM', 'RAK Airways'),
(@uid, 'RNV', 'Armavia'),
(@uid, 'RO',  'Tarom'),
(@uid, 'SA',  'South African Airways'),
(@uid, 'SBI', 'S7 Airlines'),
(@uid, 'SK',  'SAS Scandinavian Airlines'),
(@uid, 'SMR', 'Somon Air'),
(@uid, 'SOV', 'Saravia-Saratov Airlines'),
(@uid, 'SP',  'SATA Internacional'),
(@uid, 'SQ',  'Singapore Airlines'),
(@uid, 'SU',  'Aeroflot'),
(@uid, 'SUW', 'Sunny Airways'),
(@uid, 'SV',  'Saudi Arabian Airlines'),
(@uid, 'SW',  'Air Namibia'),
(@uid, 'TG',  'Thai Airways International'),
(@uid, 'TGZ', 'Georgian Airways'),
(@uid, 'TK',  'Turkish Airlines'),
(@uid, 'TP',  'TAP Portugal'),
(@uid, 'TRK', 'Turkuaz Airlines'),
(@uid, 'TS',  'Air Transat'),
(@uid, 'TU',  'Tunis Air'),
(@uid, 'TUA', 'Turkmenistan Airlines'),
(@uid, 'TUI', 'TUIfly'),
(@uid, 'TWI', 'Tailwind Havayollari'),
(@uid, 'UA',  'United Airlines'),
(@uid, 'UDN', 'Dniproavia'),
(@uid, 'UL',  'Srilankan Airlines'),
(@uid, 'UN',  'Transaero Airlines'),
(@uid, 'US',  'US Airways'),
(@uid, 'VIM', 'Air VIA Bulgarian'),
(@uid, 'VLM', 'VLM Airlines'),
(@uid, 'VN',  'Vietnam Airlines'),
(@uid, 'VQ',  'FlyHellas.com'),
(@uid, 'WY',  'Oman Air'),
(@uid, 'X3',  'TUIfly'),
(@uid, 'XG',  'SunExpress Deutschland'),
(@uid, 'XQ',  'SunExpress'),
(@uid, 'YM',  'Montenegro Airlines'),
(@uid, 'ZY',  'Sky Airlines'),
(@uid, 'ZZ',  'ZZ'),
(@uid, 'JP',  'Adria Airways'),
(@uid, 'HU',  'Hainan Airlines'),
(@uid, 'ST',  'Germania'),
(@uid, 'EW',  'Eurowings'),
(@uid, '4U',  'Germanwings'),
(@uid, 'EZE', 'Eastern Airways'),
(@uid, 'MON', 'Monarch Airlines'),
(@uid, 'IFA', 'FAI Airservice'),
(@uid, 'BV',  'Blue Panorama Airlines'),
(@uid, 'TFL', 'Arkefly'),
(@uid, 'AEY', 'Air Italy'),
(@uid, 'DWT', 'Darwin Airline'),
(@uid, 'YW',  'Air Nostrum'),
(@uid, 'OHY', 'Onur Air'),
(@uid, 'FPO', 'Europe Airpost'),
(@uid, 'JTG', 'Jet Time'),
(@uid, 'EXS', 'Jet2.com'),
(@uid, 'HAY', 'Hamburg Airways'),
(@uid, 'EZ',  'Evergreen International Airlines'),
(@uid, 'HV',  'Transavia Airlines'),
(@uid, 'VPA', 'VIP Wings'),
(@uid, 'JAF', 'JetAirFly'),
(@uid, 'BOX', 'Aerologic'),
(@uid, 'UC',  'LAN Cargo'),
(@uid, 'ACX', 'ACG Air Cargo Germany'),
(@uid, 'BR',  'EVA Air'),
(@uid, 'ABW', 'AirBridgeCargo'),
(@uid, 'FX',  'FedEx'),
(@uid, 'RGN', 'Cygnus Air'),
(@uid, 'CZ',  'China Southern Airlines'),
(@uid, 'ABR', 'Air Bridge Carriers'),
(@uid, 'AHO', 'Air Hamburg'),
(@uid, 'CSA', 'Czech Airlines'),
(@uid, 'DLH', 'Lufthansa'),
(@uid, 'ETD', 'Etihad Airways'),
(@uid, 'EXH', 'Executive AG'),
(@uid, 'EXT', 'Nightexpress'),
(@uid, 'FFD', 'Stuttgarter Flugdienst'),
(@uid, 'FMY', 'Aviation Legere de l''Armee de Terre'),
(@uid, 'IAM', 'Aeronautica Militare Italiana'),
(@uid, 'JEI', 'Jet Executive International Charter'),
(@uid, 'KAC', 'Kuwait Airways'),
(@uid, 'KZR', 'Air Astana'),
(@uid, 'LNX', 'London Executive Aviation - LEA'),
(@uid, 'LZB', 'Bulgaria Air'),
(@uid, 'MAS', 'Malaysia Airlines'),
(@uid, 'MGR', 'Magna Air'),
(@uid, 'NJE', 'NetJets Transportes Aereos'),
(@uid, 'QAJ', 'Quick Air Jet Charter'),
(@uid, 'RZO', 'SATA Internacional'),
(@uid, 'SHY', 'Sky Airlines'),
(@uid, 'THY', 'Turkish Airlines'),
(@uid, 'AFL', 'Aeroflot'),
(@uid, 'AZG', 'Sakaviaservice'),
(@uid, 'CMB', 'US Transportation Command'),
(@uid, 'GTI', 'Atlas Air'),
(@uid, 'MNB', 'MNG Airlines'),
(@uid, 'YZR', 'Yangtze River Express Airlines'),
(@uid, 'AG',  'Affretair'),
(@uid, 'FRA', 'FR Aviation'),
(@uid, 'OAW', 'Helvetic'),
(@uid, 'AEA', 'Air Europa'),
(@uid, 'QS',  'Travel Servis/Smartwings'),
(@uid, 'BIE', 'Air Mediterranee'),
(@uid, 'I2',  'MunichAirlines'),
(@uid, 'HC',  'Aero Tropics'),
(@uid, 'BM',  'BM'),
(@uid, 'FQ',  'Thomas Cook Belgium Airlines'),
(@uid, 'IQ',  'Augsburg Airways'),
(@uid, 'ZZZ', 'ZZZ'),
(@uid, 'SWT', 'Swiftair'),
(@uid, 'XLF', 'XL Airways France'),
(@uid, 'FC',  'Flybe Nordic'),
(@uid, 'KZ',  'Nippon Cargo Airlines'),
(@uid, 'WDL', 'WDL Aviation'),
(@uid, 'VLG', 'Vueling'),
(@uid, 'VY',  'Vueling Airlines'),
(@uid, 'S4',  'SATA International'),
(@uid, 'IA',  'Iraqi Airways'),
(@uid, 'AHY', 'Azerbaijan Airlines')
;

INSERT INTO `models`(`uid`, `icao`, `name`)
VALUES
(@uid, 'A124', 'Antonov AN-124 Ruslan'),
(@uid, 'A140', 'Antonov AN-140'),
(@uid, 'A148', 'Antonov AN-148'),
(@uid, 'A306', 'Airbus Industrie A300-600'),
(@uid, 'A30B', 'Airbus Industrie A300B2/B4/C4/F4'),
(@uid, 'A310', 'Airbus A310'),
(@uid, 'A318', 'Airbus A318'),
(@uid, 'A319', 'Airbus A319'),
(@uid, 'A320', 'Airbus A320-100/200'),
(@uid, 'A321', 'Airbus A321-100/200'),
(@uid, 'A330', 'Airbus A330 all models'),
(@uid, 'A332', 'Airbus A330-200'),
(@uid, 'A333', 'Airbus A330-300'),
(@uid, 'A340', 'Airbus A340 all models'),
(@uid, 'A342', 'Airbus A340-200'),
(@uid, 'A343', 'Airbus A340-300'),
(@uid, 'A345', 'Airbus A340-500'),
(@uid, 'A346', 'Airbus A340-600'),
(@uid, 'A388', 'Airbus A380-800'),
(@uid, 'A3ST', 'Airbus Industrie A300-600ST Beluga Freighter'),
(@uid, 'A748', 'Hawker Siddeley HS.748'),
(@uid, 'AC68', 'Gulfstream/Rockwell (Aero) Commander'),
(@uid, 'AC90', 'Gulfstream/Rockwell (Aero) Turbo Commander'),
(@uid, 'AN12', 'Antonov AN-12'),
(@uid, 'AN24', 'Antonov AN-24'),
(@uid, 'AN26', 'Antonov AN-26'),
(@uid, 'AN28', 'Antonov AN-28 / PZL Miele M-28 Skytruck'),
(@uid, 'AN30', 'Antonov AN-30'),
(@uid, 'AN32', 'Antonov AN-32'),
(@uid, 'AN72', 'Antonov AN-72 / AN-74'),
(@uid, 'AT43', 'Aerospatiale/Alenia ATR 42-300 / 320'),
(@uid, 'AT45', 'Aerospatiale/Alenia ATR 42-500'),
(@uid, 'AT72', 'Aerospatiale/Alenia ATR 72-200/210'),
(@uid, 'ATP', 'British Aerospace ATP'),
(@uid, 'B190', 'Beechcraft 1900/1900C/1900D'),
(@uid, 'B461', 'BAe 146-100'),
(@uid, 'B462', 'BAe 146-200'),
(@uid, 'B463', 'BAe 146-300'),
(@uid, 'B703', 'Boeing 707-300'),
(@uid, 'B712', 'Boeing 717'),
(@uid, 'B720', 'Boeing 720B'),
(@uid, 'B721', 'Boeing 727-100'),
(@uid, 'B722', 'Boeing 727-200'),
(@uid, 'B731', 'Boeing 737-100'),
(@uid, 'B732', 'Boeing 737-200'),
(@uid, 'B733', 'Boeing 737-300'),
(@uid, 'B734', 'Boeing 737-400'),
(@uid, 'B735', 'Boeing 737-500'),
(@uid, 'B736', 'Boeing 737-600'),
(@uid, 'B737', 'Boeing 737-700'),
(@uid, 'B738', 'Boeing 737-800'),
(@uid, 'B739', 'Boeing 737-900'),
(@uid, 'B741', 'Boeing 747-100'),
(@uid, 'B742', 'Boeing 747-200'),
(@uid, 'B743', 'Boeing 747-300'),
(@uid, 'B744', 'Boeing 747-400'),
(@uid, 'B748', 'Boeing 747-8I'),
(@uid, 'B74R', 'Boeing 747SR'),
(@uid, 'B752', 'Boeing 757-200'),
(@uid, 'B753', 'Boeing 757-300'),
(@uid, 'B762', 'Boeing 767-200'),
(@uid, 'B763', 'Boeing 767-300'),
(@uid, 'B764', 'Boeing 767-400'),
(@uid, 'B772', 'Boeing 777-200'),
(@uid, 'B773', 'Boeing 777-300'),
(@uid, 'B77L', 'Boeing 777-200LR'),
(@uid, 'B77W', 'Boeing 777-300ER'),
(@uid, 'B788', 'Boeing 787 "Dreamliner"'),
(@uid, 'BA11', 'British Aerospace (BAC) One Eleven'),
(@uid, 'BELF', 'Shorts SC-5 Belfast'),
(@uid, 'BN2P', 'Pilatus Britten-Norman BN-2A/B Islander'),
(@uid, 'C130', 'Lockheed L-182 / 282 / 382 (L-100) Hercules'),
(@uid, 'C212', 'CASA / IPTN 212 Aviocar'),
(@uid, 'C46', 'Curtiss C-46 Commando'),
(@uid, 'CL44', 'Canadair CL-44'),
(@uid, 'CL60', 'Canadair Challenger'),
(@uid, 'CN35', 'CASA / IPTN CN-235'),
(@uid, 'CONC', 'Aerospatiale/BAC Concorde'),
(@uid, 'CONI', 'Lockheed L-1049 Super Constellation'),
(@uid, 'COUC', 'Helio H-250 Courier / H-295 / 385 Super Courier'),
(@uid, 'CRJ1', 'Canadair Regional Jet 100'),
(@uid, 'CRJ2', 'Canadair Regional Jet 200'),
(@uid, 'CRJ7', 'Canadair Regional Jet 700'),
(@uid, 'CRJ9', 'Canadair Regional Jet 900'),
(@uid, 'CVLP', 'Convair CV-240/440'),
(@uid, 'CVLT', 'Convair CV-580/600/640'),
(@uid, 'D228', 'Fairchild Dornier Do.228'),
(@uid, 'D328', 'Fairchild Dornier Do.328'),
(@uid, 'DC10', 'Douglas DC-10'),
(@uid, 'DC3', 'Douglas DC-3'),
(@uid, 'DC6', 'Douglas DC6A/B/C'),
(@uid, 'DC85', 'Douglas DC-8-50 Freighter'),
(@uid, 'DC86', 'Douglas DC-8-62'),
(@uid, 'DC87', 'Douglas DC-8-71/72/73'),
(@uid, 'DC9', 'Douglas DC-9 all models'),
(@uid, 'DC91', 'Douglas DC-9-10'),
(@uid, 'DC92', 'Douglas DC-9-20'),
(@uid, 'DC93', 'Douglas DC-9-30'),
(@uid, 'DC94', 'Douglas DC-9-40'),
(@uid, 'DC95', 'Douglas DC-9-50'),
(@uid, 'DH2T', 'De Havilland Canada DHC-2 Turbo-Beaver'),
(@uid, 'DH8A', 'De Havilland Canada DHC-8-100 Dash 8 / 8Q'),
(@uid, 'DH8B', 'De Havilland Canada DHC-8-200 Dash 8 / 8Q'),
(@uid, 'DH8C', 'De Havilland Canada DHC-8-300 Dash 8 / 8Q'),
(@uid, 'DH8D', 'De Havilland Canada DHC-8-400 Dash 8Q'),
(@uid, 'DHC2', 'De Havilland Canada DHC-2 Beaver'),
(@uid, 'DHC3', 'De Havilland Canada DHC-3 Otter / Turbo Otter'),
(@uid, 'DHC4', 'De Havilland Canada DHC-4 Caribou'),
(@uid, 'DHC6', 'De Havilland Canada DHC-6 Twin Otter'),
(@uid, 'DHC7', 'De Havilland Canada DHC-7 Dash 7'),
(@uid, 'DOVE', 'De Havilland DH.104 Dove'),
(@uid, 'E110', 'Embraer EMB.110 Bandeirnate'),
(@uid, 'E120', 'Embraer EMB.120 Brasilia'),
(@uid, 'E135', 'Embraer RJ135'),
(@uid, 'E145', 'Embraer RJ145 Amazon'),
(@uid, 'E170', 'Embraer 170'),
(@uid, 'E190', 'Embraer 190'),
(@uid, 'F100', 'Fokker 100'),
(@uid, 'F27', 'Fokker F.27 Friendship / Fairchild F.27'),
(@uid, 'F28', 'Fokker F.28 Fellowship'),
(@uid, 'F50', 'Fokker 50'),
(@uid, 'F70', 'Fokker 70'),
(@uid, 'G159', 'Gulfstream Aerospace G-159 Gulfstream I'),
(@uid, 'G21', 'Grumman G.21 Goose'),
(@uid, 'G73T', 'Grumman G.73 Turbo Mallard'),
(@uid, 'GLEX', 'Canadair Global Express'),
(@uid, 'HERN', 'De Havilland DH.114 Heron'),
(@uid, 'I114', 'Ilyushin IL114'),
(@uid, 'IL18', 'Ilyushin IL18'),
(@uid, 'IL62', 'Ilyushin IL62'),
(@uid, 'IL76', 'Ilyushin IL76'),
(@uid, 'IL86', 'Ilyushin IL86'),
(@uid, 'IL96', 'Ilyushin IL96'),
(@uid, 'J328', 'Fairchild Dornier 328JET'),
(@uid, 'JS31', 'British Aerospace Jetstream 31'),
(@uid, 'JS32', 'British Aerospace Jetstream 32'),
(@uid, 'JS41', 'British Aerospace Jetstream 41'),
(@uid, 'JU52', 'Junkers Ju52/3M'),
(@uid, 'L101', 'Lockheed L-1011 1 / 50 / 100 / 150 / 200 / 250 Tristar'),
(@uid, 'L188', 'Lockheed L-188 Electra'),
(@uid, 'L410', 'LET 410'),
(@uid, 'LOAD', 'Ayres LM-200 Loadmaster'),
(@uid, 'MD11', 'McDonnell Douglas MD11'),
(@uid, 'MD80', 'McDonnell Douglas MD80'),
(@uid, 'MD81', 'McDonnell Douglas MD81'),
(@uid, 'MD82', 'McDonnell Douglas MD82'),
(@uid, 'MD83', 'McDonnell Douglas MD83'),
(@uid, 'MD87', 'McDonnell Douglas MD87'),
(@uid, 'MD88', 'McDonnell Douglas MD88'),
(@uid, 'MD90', 'McDonnell Douglas MD90'),
(@uid, 'MU2', 'Mitsubishi Mu-2'),
(@uid, 'N262', 'Aerospatiale (Nord) 262'),
(@uid, 'N74S', 'Boeing 747SP'),
(@uid, 'NOMA', 'Government Aircraft Factories N22B / N24A Nomad'),
(@uid, 'P68', 'Partenavia P.68'),
(@uid, 'PC12', 'Pilatus PC-12'),
(@uid, 'PC6T', 'Pilatus PC-6 Turbo Porter'),
(@uid, 'RJ1H', 'Avro RJ100 Avroliner'),
(@uid, 'RJ70', 'Avro RJ70 Avroliner'),
(@uid, 'RJ85', 'Avro RJ85 Avroliner'),
(@uid, 'RX1H', 'Avro RJX100'),
(@uid, 'RX85', 'Avro RJX85 	M B11 	BA11 	British Aerospace (BAC) One Eleven / RomBAC One Eleven'),
(@uid, 'S210', 'Aerospatiale (Sud Aviation) Se.210 Caravelle'),
(@uid, 'S601', 'Aerospatiale SN.601 Corvette'),
(@uid, 'SB20', 'Saab 2000'),
(@uid, 'SC7', 'Shorts SC-7 Skyvan'),
(@uid, 'SF34', 'Saab SF340A/B'),
(@uid, 'SH33', 'Shorts SD.330'),
(@uid, 'SH36', 'Shorts SD.360'),
(@uid, 'T134', 'Tupolev Tu134'),
(@uid, 'T154', 'Tupolev Tu154'),
(@uid, 'T204', 'Tupolev Tu-204 / Tu-214'),
(@uid, 'TRIS', 'Pilatus Britten-Norman BN-2A Mk III Trislander'),
(@uid, 'VISC', 'Vickers Viscount'),
(@uid, 'WW24', 'Israel Aircraft Industries 1124 Westwind'),
(@uid, 'Y12', 'Harbin Yunshuji Y12'),
(@uid, 'YK40', 'Yakovlev Yak 40'),
(@uid, 'YK42', 'Yakovlev Yak 42'),
(@uid, 'YS11', 'NAMC YS-11'),
(@uid, 'AT75', 'Aerospatiale/Alenia ATR 72-500'),
(@uid, 'AT76', 'Aerospatiale/Alenia ATR 72-600')
;

INSERT INTO `airports`(`uid`, `iata`, `icao`, `name`)
VALUES
(@uid, 'ABV', 'DNAA', 'Abuja, Nigeria'),
(@uid, 'ABZ', 'EGPD', 'Aberdeen, Großbritannien'),
(@uid, 'ACC', 'DGAA', 'Accra, Ghana'),
(@uid, 'ACE', 'GCRR', 'Lanzarote, Spanien'),
(@uid, 'ADA', 'LTAF', 'Adana, Türkei'),
(@uid, 'ADB', 'LTBJ', 'Izmir, Türkei'),
(@uid, 'ADD', 'HAAB', 'Addis Abeba, Äthiopien'),
(@uid, 'AGA', 'GMAD', 'Agadir, Marokko'),
(@uid, 'AGP', 'LEMG', 'Malaga, Spanien'),
(@uid, 'ALA', 'UAAA', 'Almaty, Kasachstan'),
(@uid, 'ALG', 'DAAG', 'Algier, Algerien'),
(@uid, 'AMM', 'OJAI', 'Amman, Jordanien'),
(@uid, 'AMS', 'EHAM', 'Amsterdam, Niederlande'),
(@uid, 'ARN', 'ESSA', 'Stockholm, Schweden'),
(@uid, 'ASB', 'UTAA', 'Ashgabat, Turkmenistan'),
(@uid, 'ASM', 'HHAS', 'Asmara, Eritrea'),
(@uid, 'ASR', 'LTAU', 'Kayseri, Türkei'),
(@uid, 'ATH', 'LGAV', 'Athen, Griechenland'),
(@uid, 'ATL', 'KATL', 'Atlanta, USA'),
(@uid, 'AUH', 'OMAA', 'Abu Dhabi, Ver.Arab.Emirate'),
(@uid, 'AYT', 'LTAI', 'Antalya, Türkei'),
(@uid, 'BAH', 'OBBI', 'Bahrain, Bahrain'),
(@uid, 'BCN', 'LEBL', 'Barcelona, Spanien'),
(@uid, 'BEG', 'LYBE', 'Belgrad, Serbien'),
(@uid, 'BEY', 'OLBA', 'Beirut, Libanon'),
(@uid, 'BGI', 'TBPB', 'Barbados, Barbados'),
(@uid, 'BGO', 'ENBR', 'Bergen, Norwegen'),
(@uid, 'BGY', 'LIME', 'Bergamo-Orio AlSerio, Italien'),
(@uid, 'BHX', 'EGBB', 'Birmingham, Großbritannien'),
(@uid, 'BIO', 'LEBB', 'Bilbao, Spanien'),
(@uid, 'BJL', 'GBYD', 'Banjul, Gambia'),
(@uid, 'BKK', 'VTBS', 'Bangkok, Thailand'),
(@uid, 'BLL', 'EKBI', 'Billund, Dänemark'),
(@uid, 'BLQ', 'LIPE', 'Bologna, Italien'),
(@uid, 'BLR', 'VOBL', 'Bangalore, Indien'),
(@uid, 'BOG', 'SKBO', 'Bogota, Kolumbien'),
(@uid, 'BOM', 'VABB', 'Mumbai, Indien'),
(@uid, 'BOS', 'KBOS', 'Boston, USA'),
(@uid, 'BRE', 'EDDW', 'Bremen, Deutschland'),
(@uid, 'BRU', 'EBBR', 'Brüssel, Belgien'),
(@uid, 'BSL', 'LFSB', 'Basel, Schweiz'),
(@uid, 'BUD', 'LHBP', 'Budapest, Ungarn'),
(@uid, 'CAI', 'HECA', 'Kairo, Ägypten'),
(@uid, 'CAN', 'ZGGG', 'Guangzhou, China'),
(@uid, 'CCS', 'SVMI', 'Caracas, Venezuela'),
(@uid, 'CCU', 'VECC', 'Kolkata, Indien'),
(@uid, 'CDG', 'LFPG', 'Paris-Ch.De Gaulle, Frankreich'),
(@uid, 'CLT', 'KCLT', 'Charlotte, USA'),
(@uid, 'CMB', 'VCBI', 'Colombo, Sri Lanka'),
(@uid, 'CMN', 'GMMN', 'Casablanca, Marokko'),
(@uid, 'CPH', 'EKCH', 'Kopenhagen, Dänemark'),
(@uid, 'CPT', 'FACT', 'Kapstadt, Südafrika'),
(@uid, 'CTA', 'LICC', 'Catania, Italien'),
(@uid, 'CUN', 'MMUN', 'Cancun, Mexiko'),
(@uid, 'DAM', 'OSDI', 'Damaskus, Syrien'),
(@uid, 'DBV', 'LDDU', 'Dubrovnik, Kroatien'),
(@uid, 'DEL', 'VIDP', 'Delhi, Indien'),
(@uid, 'DEN', 'KDEN', 'Denver, USA'),
(@uid, 'DFW', 'KDFW', 'Dallas-Fort Worth, USA'),
(@uid, 'DJE', 'DTTJ', 'Djerba, Tunesien'),
(@uid, 'DME', 'UUDD', 'Moskau-Domodedovo, Russland'),
(@uid, 'DMM', 'OEDF', 'Dammam, Saudi-Arabien'),
(@uid, 'DOH', 'OTBD', 'Doha, Katar'),
(@uid, 'DRS', 'EDDC', 'Dresden, Deutschland'),
(@uid, 'DTW', 'KDTW', 'Detroit, USA'),
(@uid, 'DUB', 'EIDW', 'Dublin, Irland'),
(@uid, 'DUS', 'EDDL', 'Düsseldorf, Deutschland'),
(@uid, 'DXB', 'OMDB', 'Dubai, Ver.Arab.Emirate'),
(@uid, 'DYU', 'UTDD', 'Duschanbe, Tadschikistan'),
(@uid, 'EBL', 'ORER', 'Erbil, Irak'),
(@uid, 'EDI', 'EGPH', 'Edinburgh, Großbritannien'),
(@uid, 'EMA', 'EGNX', 'East Midlands, Großbritannien'),
(@uid, 'ESB', 'LTAC', 'Ankara, Türkei'),
(@uid, 'EWR', 'KEWR', 'New York-Newark, USA'),
(@uid, 'EZE', 'SAEZ', 'Buenos Aires, Argentinien'),
(@uid, 'FAO', 'LPFR', 'Faro, Portugal'),
(@uid, 'FCO', 'LIRF', 'Rom-Fiumicino, Italien'),
(@uid, 'FDH', 'EDNY', 'Friedrichshafen, Deutschland'),
(@uid, 'FLL', 'KFLL', 'Fort Lauderdale, USA'),
(@uid, 'FLR', 'LIRQ', 'Florenz, Italien'),
(@uid, 'FMO', 'EDDG', 'Münster Osnabrück, Deutschland'),
(@uid, 'FNC', 'LPMA', 'Madeira, Portugal'),
(@uid, 'FUE', 'GCFV', 'Fuerteventura, Spanien'),
(@uid, 'GDN', 'EPGD', 'Gdansk/Danzig, Polen'),
(@uid, 'GIG', 'SBGL', 'Rio de Janeiro, Brasilien'),
(@uid, 'GOI', 'VAGO', 'Goa, Indien'),
(@uid, 'GOT', 'ESGG', 'Göteborg, Schweden'),
(@uid, 'GRU', 'SBGR', 'Sao Paulo-Guarulhos, Brasilien'),
(@uid, 'GRZ', 'LOWG', 'Graz, Österreich'),
(@uid, 'GVA', 'LSGG', 'Genf, Schweiz'),
(@uid, 'GZT', 'LTAJ', 'Gaziantep, Türkei'),
(@uid, 'HAJ', 'EDDV', 'Hannover, Deutschland'),
(@uid, 'HAM', 'EDDH', 'Hamburg, Deutschland'),
(@uid, 'HAN', 'VVNB', 'Hanoi, Vietnam'),
(@uid, 'HAV', 'MUHA', 'Havanna, Kuba'),
(@uid, 'HEL', 'EFHK', 'Helsinki, Finnland'),
(@uid, 'HGH', 'ZSHC', 'Hangzhou, China'),
(@uid, 'HKG', 'VHHH', 'Hongkong, Hong Kong'),
(@uid, 'HKT', 'VTSP', 'Phuket'),
(@uid, 'HND', 'RJTT', 'Tokio Haneda, Japan'),
(@uid, 'HOG', 'MUHG', 'Holguin, Kuba'),
(@uid, 'HRG', 'HEGN', 'Hurghada, Ägypten'),
(@uid, 'IAD', 'KIAD', 'Washington, USA'),
(@uid, 'IAH', 'KIAH', 'Houston, USA'),
(@uid, 'ICN', 'RKSI', 'Seoul-Incheon, Korea-Süd'),
(@uid, 'IKA', 'OIIE', 'Teheran ImamKhomeini, Iran'),
(@uid, 'INN', 'LOWI', 'Innsbruck, Österreich'),
(@uid, 'ISB', 'OPRN', 'Islamabad, Pakistan'),
(@uid, 'IST', 'LTBA', 'Istanbul, Türkei'),
(@uid, 'JED', 'OEJN', 'Jeddah, Saudi-Arabien'),
(@uid, 'JFK', 'KJFK', 'New York-J.F.Kennedy, USA'),
(@uid, 'JNB', 'FAJS', 'Johannesburg, Südafrika'),
(@uid, 'JRO', 'HTKJ', 'Kilimanjaro, Tansania'),
(@uid, 'KBP', 'UKBB', 'Kiew-Borispol, Ukraine'),
(@uid, 'KEF', 'BIKF', 'Reykjavik, Island'),
(@uid, 'KIV', 'LUKK', 'Chisinau, Moldawien'),
(@uid, 'KIX', 'RJBB', 'Osaka, Japan'),
(@uid, 'KLU', 'LOWK', 'Klagenfurt, Österreich'),
(@uid, 'KRK', 'EPKK', 'Krakau, Polen'),
(@uid, 'KRT', 'HSSS', 'Khartoum, Sudan'),
(@uid, 'KTW', 'EPKT', 'Kattowitz, Polen'),
(@uid, 'KUL', 'WMKK', 'Kuala Lumpur, Malaysia'),
(@uid, 'KWI', 'OKBK', 'Kuwait, Kuwait'),
(@uid, 'KZN', 'UWKD', 'Kazan, Russland'),
(@uid, 'LAD', 'FNLU', 'Luanda, Angola'),
(@uid, 'LAS', 'KLAS', 'Las Vegas, USA'),
(@uid, 'LAX', 'KLAX', 'Los Angeles, USA'),
(@uid, 'LCA', 'LCLK', 'Larnaca, Zypern'),
(@uid, 'LCY', 'EGLC', 'London-City Airport, Großbritannien'),
(@uid, 'LED', 'ULLI', 'Sankt Petersburg, Russland'),
(@uid, 'LEJ', 'EDDP', 'Leipzig Halle, Deutschland'),
(@uid, 'LGW', 'EGKK', 'London-Gatwick, Großbritannien'),
(@uid, 'LHE', 'OPLA', 'Lahore, Pakistan'),
(@uid, 'LHR', 'EGLL', 'London-Heathrow, Großbritannien'),
(@uid, 'LIN', 'LIML', 'Mailand-Linate, Italien'),
(@uid, 'LIS', 'LPPT', 'Lissabon, Portugal'),
(@uid, 'LJU', 'LJLJ', 'Ljubljana, Slowenien'),
(@uid, 'LNZ', 'LOWL', 'Linz, Österreich'),
(@uid, 'LOS', 'DNMM', 'Lagos, Nigeria'),
(@uid, 'LPA', 'GCLP', 'Las Palmas, Spanien'),
(@uid, 'LRM', 'MDLR', 'La Romana, Dominikan. Rep.'),
(@uid, 'LUX', 'ELLX', 'Luxemburg, Luxemburg'),
(@uid, 'LXR', 'HELX', 'Luxor, Ägypten'),
(@uid, 'LYS', 'LFLL', 'Lyon, Frankreich'),
(@uid, 'MAA', 'VOMM', 'Chennai, Indien'),
(@uid, 'MAD', 'LEMD', 'Madrid, Spanien'),
(@uid, 'MAN', 'EGCC', 'Manchester, Großbritannien'),
(@uid, 'MBA', 'HKMO', 'Mombasa, Kenia'),
(@uid, 'MBJ', 'MKJS', 'Montego Bay, Jamaika'),
(@uid, 'MCO', 'KMCO', 'Orlando, USA'),
(@uid, 'MCT', 'OOMS', 'Muscat, Oman'),
(@uid, 'MEX', 'MMMX', 'Mexico City, Mexiko'),
(@uid, 'MIA', 'KMIA', 'Miami, USA'),
(@uid, 'MLA', 'LMML', 'Malta, Malta'),
(@uid, 'MLE', 'VRMM', 'Male, Malediven'),
(@uid, 'MRS', 'LFML', 'Marseille, Frankreich'),
(@uid, 'MRU', 'FIMP', 'Mauritius, Mauritius'),
(@uid, 'MSQ', 'UMMS', 'Minsk, Weißrussland'),
(@uid, 'MUC', 'EDDM', 'München, Deutschland'),
(@uid, 'MXP', 'LIMC', 'Mailand-Malpensa, Italien'),
(@uid, 'NAP', 'LIRN', 'Neapel, Italien'),
(@uid, 'NBE', 'DTNZ', 'Enfidha, Tunesien'),
(@uid, 'NBO', 'HKJK', 'Nairobi, Kenia'),
(@uid, 'NCE', 'LFMN', 'Nizza, Frankreich'),
(@uid, 'NDR', 'GMFN', 'Nador, Marokko'),
(@uid, 'NGO', 'RJGG', 'Nagoya, Japan'),
(@uid, 'NKG', 'ZSNJ', 'Nanking, China'),
(@uid, 'NRT', 'RJAA', 'Tokio Narita, Japan'),
(@uid, 'NUE', 'EDDN', 'Nürnberg, Deutschland'),
(@uid, 'OPO', 'LPPR', 'Porto, Portugal'),
(@uid, 'ORD', 'KORD', 'Chicago, USA'),
(@uid, 'OSL', 'ENGM', 'Oslo, Norwegen'),
(@uid, 'OTP', 'LROP', 'Bukarest, Rumänien'),
(@uid, 'OVB', 'UNNT', 'Novosibirsk, Russland'),
(@uid, 'PEE', 'USPP', 'Perm, Russland'),
(@uid, 'PEK', 'ZBAA', 'Peking, China'),
(@uid, 'PFO', 'LCPH', 'Paphos, Zypern'),
(@uid, 'PHC', 'DNPO', 'Port Harcourt, Nigeria'),
(@uid, 'PHL', 'KPHL', 'Philadelphia, USA'),
(@uid, 'PMI', 'LEPA', 'Palma de Mallorca, Spanien'),
(@uid, 'PNQ', 'VAPO', 'Pune, Indien'),
(@uid, 'PNR', 'FCPP', 'Pointe Noire, Kongo'),
(@uid, 'POP', 'MDPP', 'Puerto Plata, Dominikan. Rep.'),
(@uid, 'POZ', 'EPPO', 'Poznan/Posen, Polen'),
(@uid, 'PRG', 'LKPR', 'Prag, Tschechien'),
(@uid, 'PRN', 'LYPR', 'Pristina, Serbien'),
(@uid, 'PTY', 'MPTO', 'Panama City, Panama'),
(@uid, 'PUJ', 'MDPC', 'Punta Cana, Dominikan. Rep.'),
(@uid, 'PVG', 'ZSPD', 'Shanghai PuDong, China'),
(@uid, 'REC', 'SBRF', 'Recife, Brasilien'),
(@uid, 'RIX', 'EVRA', 'Riga, Lettland'),
(@uid, 'RMF', 'HEMA', 'Marsa Alam, Ägypten'),
(@uid, 'ROV', 'URRR', 'Rostow, Russland'),
(@uid, 'RUH', 'OERK', 'Riad, Saudi-Arabien'),
(@uid, 'RZE', 'EPRZ', 'Rzeszow, Polen'),
(@uid, 'SAH', 'OYSN', 'Sanaa, Jemen'),
(@uid, 'SAW', 'LTFJ', 'Istanbul S-Gokcen, Türkei'),
(@uid, 'SCL', 'SCEL', 'Santiago de Chile, Chile'),
(@uid, 'SDQ', 'MDSD', 'Santo Domingo, Dominikan. Rep.'),
(@uid, 'SEA', 'KSEA', 'Seattle, USA'),
(@uid, 'SFO', 'KSFO', 'San Francisco, USA'),
(@uid, 'SGN', 'VVTS', 'Ho Chi Minh City, Vietnam'),
(@uid, 'SHE', 'ZYTX', 'Shenyang, China'),
(@uid, 'SIN', 'WSSS', 'Singapur, Singapur'),
(@uid, 'SJO', 'MROC', 'San Jose, Costa Rica'),
(@uid, 'SJU', 'TJSJ', 'San Juan, Puerto Rico'),
(@uid, 'SKG', 'LGTS', 'Thessaloniki, Griechenland'),
(@uid, 'SOF', 'LBSF', 'Sofia, Bulgarien'),
(@uid, 'SOU', 'EGHI', 'Southampton, Großbritannien'),
(@uid, 'SPC', 'GCLA', 'La Palma, Spanien'),
(@uid, 'SPU', 'LDSP', 'Split, Kroatien'),
(@uid, 'SSA', 'SBSV', 'Salvador, Brasilien'),
(@uid, 'SSG', 'FGSL', 'Malabo, Äquator.-Guinea'),
(@uid, 'SSH', 'HESH', 'Sharm El Sheik, Ägypten'),
(@uid, 'STR', 'EDDS', 'Stuttgart, Deutschland'),
(@uid, 'SVG', 'ENZV', 'Stavanger, Norwegen'),
(@uid, 'SVO', 'UUEE', 'Moskau-Sheremetyevo, Russland'),
(@uid, 'SVX', 'USSS', 'Ekaterinburg, Russland'),
(@uid, 'SYD', 'YSSY', 'Sydney, Australien'),
(@uid, 'SZG', 'LOWS', 'Salzburg, Österreich'),
(@uid, 'TAB', 'TTCP', 'Tobago, Trinidad Tobago'),
(@uid, 'TAS', 'UTTT', 'Taschkent, Usbekistan'),
(@uid, 'TFS', 'GCTS', 'Teneriffa-Sur Reina, Spanien'),
(@uid, 'TGD', 'LYPG', 'Podgorica, Montenegro'),
(@uid, 'TIP', 'HLLT', 'Tripolis, Libyen'),
(@uid, 'TLL', 'EETN', 'Tallinn, Estland'),
(@uid, 'TLS', 'LFBO', 'Toulouse, Frankreich'),
(@uid, 'TLV', 'LLBG', 'Tel Aviv, Israel'),
(@uid, 'TPE', 'RCTP', 'Taipeh, Taiwan'),
(@uid, 'TRN', 'LIMF', 'Turin, Italien'),
(@uid, 'TSE', 'UACC', 'Astana, Kasachstan'),
(@uid, 'TUN', 'DTTA', 'Tunis, Tunesien'),
(@uid, 'TXL', 'EDDT', 'Berlin-Tegel, Deutschland'),
(@uid, 'UVF', 'TLPL', 'Saint Lucia, Saint Lucia'),
(@uid, 'VCE', 'LIPZ', 'Venedig, Italien'),
(@uid, 'VIE', 'LOWW', 'Wien, Österreich'),
(@uid, 'VNO', 'EYVI', 'Vilnius, Litauen'),
(@uid, 'VRA', 'MUVR', 'Varadero, Kuba'),
(@uid, 'VRN', 'LIPX', 'Verona, Italien'),
(@uid, 'WAW', 'EPWA', 'Warschau, Polen'),
(@uid, 'WDH', 'FYWH', 'Windhuk, Namibia'),
(@uid, 'WRO', 'EPWR', 'Wroclaw/Breslau, Polen'),
(@uid, 'XRY', 'LEJR', 'Jerez de la Frontera, Spanien'),
(@uid, 'YOW', 'CYOW', 'Ottawa, Kanada'),
(@uid, 'YUL', 'CYUL', 'Montreal-Trudeau, Kanada'),
(@uid, 'YVR', 'CYVR', 'Vancouver, Kanada'),
(@uid, 'YYC', 'CYYC', 'Calgary, Kanada'),
(@uid, 'YYZ', 'CYYZ', 'Toronto, Kanada'),
(@uid, 'ZAD', 'LDZD', 'Zadar, Kroatien'),
(@uid, 'ZAG', 'LDZA', 'Zagreb, Kroatien'),
(@uid, 'ZRH', 'LSZH', 'Zürich, Schweiz'),
(@uid, 'SEZ', 'FSIA', 'Mahe, Seychellen'),
(@uid, 'AJR', 'ESNX', 'Arvidsjaur, Schweden'),
(@uid, 'BIA', 'LFKB', 'Bastia, Frankreich'),
(@uid, 'PDL', 'LPPD', 'Ponta Delgada, Portugal'),
(@uid, 'GLA', 'EGPF', 'Glasgow, Großbritannien'),
(@uid, 'ANU', 'TAPA', 'Antigua, Antigua'),
(@uid, 'RKT', 'OMRK', 'Ras al Khaymah, Ver.Arab.Emirate'),
(@uid, 'COK', 'VOCI', 'Cochin, Indien'),
(@uid, 'ZNZ', 'HTZA', 'Sansibar, Tansania'),
(@uid, 'CGN', 'EDDK', 'Köln Bonn, Deutschland'),
(@uid, 'VKO', 'UUWW', 'Moskau-Vnukovo, Russland'),
(@uid, 'GOA', 'LIMJ', 'Genua, Italien'),
(@uid, 'TRD', 'ENVA', 'Trondheim, Norwegen'),
(@uid, 'PMO', 'LICJ', 'Palermo, Italien'),
(@uid, 'OLB', 'LIEO', 'Olbia, Italien'),
(@uid, 'KUF', 'UWWW', 'Samara, Russland'),
(@uid, 'GOJ', 'UWGG', 'N. Novgorod, Russland'),
(@uid, 'CLE', 'KCLE', 'Cleveland, USA'),
(@uid, 'GWT', 'EDXW', 'Westerland, Deutschland'),
(@uid, 'VAR', 'LBWN', 'Varna, Bulgarien'),
(@uid, 'PHX', 'KPHX', 'Phoenix, USA'),
(@uid, 'ORN', 'DAOO', 'Oran, Algerien'),
(@uid, 'SAN', 'KSAN', 'San Diego-Lindberg, USA'),
(@uid, 'TAO', 'ZSQD', 'Qingdao, China'),
(@uid, 'SZF', 'LTFH', 'Samsun-Carsamba, Türkei'),
(@uid, 'RDU', 'KRDU', 'Raleigh/Durham, USA'),
(@uid, 'TBS', 'UGTB', 'Tbilisi, Georgien'),
(@uid, 'QSH', 'DUMI', 'Seeheim, Deutschland'),
(@uid, 'ALC', 'LEAL', 'Alicante, Spanien'),
(@uid, 'MAO', 'SBEG', 'Manaus, Brasilien'),
(@uid, 'HER', 'LGIR', 'Heraklion, Griechenland'),
(@uid, 'RLG', 'ETNL', 'Rostock Laage, Deutschland'),
(@uid, 'ERF', 'EDDE', 'Erfurt, Deutschland'),
(@uid, 'SOB', 'LHSM', 'Sarmellek, Ungarn'),
(@uid, 'BSB', 'SBBR', 'Brasilia, Brasilien'),
(@uid, 'DLM', 'LTBS', 'Dalaman, Türkei'),
(@uid, 'TCE', 'LRTC', 'Tulcea Cataloi, Rumänien'),
(@uid, 'RHO', 'LGRP', 'Rhodos, Griechenland'),
(@uid, 'KGS', 'LGKO', 'Kos, Griechenland'),
(@uid, 'UGC', 'UTNU', 'Urgench, Usbekistan'),
(@uid, 'CFU', 'LGKR', 'Kerkyra, Griechenland'),
(@uid, 'ANC', 'PANC', 'Anchorage, USA'),
(@uid, 'IBZ', 'LEIB', 'Ibiza, Spanien'),
(@uid, 'YHZ', 'CYHZ', 'Halifax, Kanada'),
(@uid, 'REU', 'LERS', 'Reus, Spanien'),
(@uid, 'PAE', 'KPAE', 'Everett, USA'),
(@uid, 'SID', 'GVAC', 'Sal, Kap Verde'),
(@uid, 'VLC', 'LEVC', 'Valencia, Spanien'),
(@uid, 'BJV', 'LTFE', 'Bodrum, Türkei'),
(@uid, 'BVC', 'GVBA', 'Boa Vista, Kap Verde'),
(@uid, 'GPA', 'LGRX', 'Patras, Griechenland'),
(@uid, 'PVK', 'LGPZ', 'Preveza/Lefkas, Griechenland'),
(@uid, 'SIP', 'UKFF', 'Simferopol, Ukraine'),
(@uid, 'BOJ', 'LBBG', 'Burgas, Bulgarien'),
(@uid, 'HDF', 'EDAH', 'Heringsdorf, Deutschland'),
(@uid, 'RJK', 'LDRI', 'Rijeka, Kroatien'),
(@uid, 'MLX', 'LTAT', 'Malatya, Türkei'),
(@uid, 'JER', 'EGJJ', 'Jersey, Großbritannien'),
(@uid, 'SUF', 'LICA', 'Lamezia Terme, Italien'),
(@uid, 'MAH', 'LEMH', 'Menorca, Spanien'),
(@uid, 'SMI', 'LGSM', 'Samos, Griechenland'),
(@uid, 'CHQ', 'LGSA', 'Chania, Griechenland'),
(@uid, 'FRA', 'EDDF', 'Frankfurt, Deutschland'),
(@uid, 'JTR', 'LGSR', 'Santorini, Griechenland'),
(@uid, 'MIR', 'DTMB', 'Monastir, Tunesien'),
(@uid, 'KVA', 'LGKV', 'Kavala, Griechenland'),
(@uid, 'ZTH', 'LGZA', 'Zakynthos, Griechenland'),
(@uid, 'JMK', 'LGMK', 'Mykonos, Griechenland'),
(@uid, 'LDE', 'LFBT', 'Lourdes, Frankreich'),
(@uid, 'YXY', 'CYXY', 'Whitehorse, Kanada')
;
