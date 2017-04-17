USE `flederwiesel_fra-schedule`;

INSERT INTO `airlines`(`id`, `code`, `name`)
VALUES
(  1, 'A3',  'Aegean Airlines'),
(  2, 'AA',  'American Airlines'),
(  3, 'AB',  'Airberlin'),
(  4, 'AC',  'Air Canada'),
(  5, 'AEE', 'Aegean Airlines'),
(  6, 'AF',  'Air France'),
(  7, 'AH',  'Air Algerie'),
(  8, 'AI',  'Air India'),
(  9, 'AP',  'Air One'),
( 10, 'AT',  'Royal Air Maroc'),
( 11, 'AY',  'Finnair'),
( 12, 'AZ',  'Alitalia'),
( 13, 'BA',  'British Airways'),
( 14, 'BD',  'British Midland International'),
( 15, 'BE',  'Flybe'),
( 16, 'BPA', 'Blue Panorama Airlines'),
( 17, 'BRU', 'Belavia'),
( 18, 'BT',  'Air Baltic'),
( 19, 'BUC', 'Bulgarian Air Charter'),
( 20, 'CA',  'Air China'),
( 21, 'CAI', 'Corendon Airlines'),
( 22, 'CFE', 'BA CityFlyer'),
( 23, 'CI',  'China Airlines'),
( 24, 'CX',  'Cathay Pacific Airways'),
( 25, 'CY',  'Cyprus Airways'),
( 26, 'DE',  'Condor'),
( 27, 'DL',  'Delta Air Lines'),
( 28, 'DLN', 'Dalmatian'),
( 29, 'EI',  'Aer Lingus'),
( 30, 'EK',  'Emirates'),
( 31, 'EN',  'Air Dolomiti'),
( 32, 'ERT', 'Eritrean Airlines'),
( 33, 'ET',  'Ethiopian Airlines'),
( 34, 'EY',  'Etihad Airways'),
( 35, 'FB',  'Bulgaria Air'),
( 36, 'FHY', 'Freebird Airlines'),
( 37, 'FI',  'Icelandair'),
( 38, 'FV',  'Rossiya'),
( 39, 'GF',  'Gulf Air'),
( 40, 'GH',  'Globus'),
( 41, 'GHY', 'German Sky Airlines '),
( 42, 'GWI', 'Germanwings'),
( 43, 'GXL', 'XL Airways Germany'),
( 44, 'HCC', 'Holidays Czech Airlines'),
( 45, 'HG',  'NIKI'),
( 46, 'HY',  'Uzbekistan Airways'),
( 47, 'IB',  'Iberia'),
( 48, 'IR',  'Iran Air'),
( 49, 'IY',  'Yemenia Yemen Airways'),
( 50, 'IZ',  'Arkia Israel Airlines'),
( 51, 'JA',  'B&H Airlines'),
( 52, 'JFU', 'Jet4you'),
( 53, 'JJ',  'TAM Linhas Aéreas'),
( 54, 'JL',  'JAL Japan Airlines'),
( 55, 'JU',  'Air Serbia'),
( 56, 'KC',  'Air Astana'),
( 57, 'KE',  'Korean Air'),
( 58, 'KIL', 'Kuban Airlines'),
( 59, 'KK',  'Atlasjet Airlines'),
( 60, 'KL',  'KLM Royal Dutch Airlines'),
( 61, 'KM',  'Air Malta'),
( 62, 'KRP', 'Carpatair'),
( 63, 'KU',  'Kuwait Airways'),
( 64, 'LA',  'LAN Airlines'),
( 65, 'LBT', 'Nouvelair Tunisie'),
( 66, 'LG',  'Luxair'),
( 67, 'LH',  'Lufthansa'),
( 68, 'LO',  'LOT Polish Airlines'),
( 69, 'LV',  'Albanian Airlines'),
( 70, 'LX',  'Swiss International Air Lines'),
( 71, 'LY',  'El Al Israel Airlines'),
( 72, 'ME',  'MEA Middle East Airlines'),
( 73, 'MH',  'Malaysia Airlines'),
( 74, 'MHK', 'Alnaser Airlines'),
( 75, 'MK',  'Air Mauritius'),
( 76, 'MLD', 'Air Moldova'),
( 77, 'MS',  'Egypt Air'),
( 78, 'MSC', 'Air Cairo'),
( 79, 'MU',  'China Eastern Airlines'),
( 80, 'NH',  'ANA'),
( 81, 'OK',  'CSA Czech Airlines'),
( 82, 'OLT', 'OLT Express'),
( 83, 'OM',  'MIAT Mongolian Airlines'),
( 84, 'OS',  'Austrian Airlines Group'),
( 85, 'OU',  'Croatia Airlines'),
( 86, 'OZ',  'Asiana Airlines'),
( 87, 'PGT', 'Pegasus Airlines'),
( 88, 'PHW', 'Ave.com'),
( 89, 'PK',  'PIA Pakistan International Airlines'),
( 90, 'PS',  'Ukraine International Airlines'),
( 91, 'QB',  'Sky Georgia'),
( 92, 'QF',  'Qantas Airways'),
( 93, 'QR',  'Qatar Airways'),
( 94, 'RB',  'Syrianair'),
( 95, 'RJ',  'Royal Jordanian'),
( 96, 'RKM', 'RAK Airways'),
( 97, 'RNV', 'Armavia'),
( 98, 'RO',  'Tarom'),
( 99, 'SA',  'South African Airways'),
(100, 'SBI', 'S7 Airlines'),
(101, 'SK',  'SAS Scandinavian Airlines'),
(102, 'SMR', 'Somon Air'),
(103, 'SOV', 'Saravia-Saratov Airlines'),
(104, 'SP',  'SATA Internacional'),
(105, 'SQ',  'Singapore Airlines'),
(106, 'SU',  'Aeroflot'),
(107, 'SUW', 'Sunny Airways'),
(108, 'SV',  'Saudi Arabian Airlines'),
(109, 'SW',  'Air Namibia'),
(110, 'TG',  'Thai Airways International'),
(111, 'TGZ', 'Georgian Airways'),
(112, 'TK',  'Turkish Airlines'),
(113, 'TP',  'TAP Portugal'),
(114, 'TRK', 'Turkuaz Airlines'),
(115, 'TS',  'Air Transat'),
(116, 'TU',  'Tunis Air'),
(117, 'TUA', 'Turkmenistan Airlines'),
(118, 'TUI', 'TUIfly'),
(119, 'TWI', 'Tailwind Havayollari'),
(120, 'UA',  'United Airlines'),
(121, 'UDN', 'Dniproavia'),
(122, 'UL',  'Srilankan Airlines'),
(123, 'UN',  'Transaero Airlines'),
(124, 'US',  'US Airways'),
(125, 'VIM', 'Air VIA Bulgarian'),
(126, 'VLM', 'VLM Airlines'),
(127, 'VN',  'Vietnam Airlines'),
(128, 'VQ',  'FlyHellas.com'),
(129, 'WY',  'Oman Air'),
(130, 'X3',  'TUIfly'),
(131, 'XG',  'SunExpress Deutschland'),
(132, 'XQ',  'SunExpress'),
(133, 'YM',  'Montenegro Airlines'),
(134, 'ZY',  'Sky Airlines'),
(135, 'ZZ',  'ZZ'),
(136, 'JP',  'Adria Airways'),
(137, 'HU',  'Hainan Airlines'),
(138, 'ST',  'Germania'),
(139, 'EW',  'Eurowings'),
(140, '4U',  'Germanwings'),
(141, 'EZE', 'Eastern Airways'),
(142, 'MON', 'Monarch Airlines'),
(143, 'IFA', 'FAI Airservice'),
(144, 'BV',  'Blue Panorama Airlines'),
(145, 'TFL', 'Arkefly'),
(146, 'AEY', 'Air Italy'),
(147, 'DWT', 'Darwin Airline'),
(148, 'YW',  'Air Nostrum'),
(149, 'OHY', 'Onur Air'),
(150, 'FPO', 'Europe Airpost'),
(151, 'JTG', 'Jet Time'),
(152, 'EXS', 'Jet2.com'),
(153, 'HAY', 'Hamburg Airways'),
(154, 'EZ',  'Evergreen International Airlines'),
(155, 'HV',  'Transavia Airlines'),
(156, 'VPA', 'VIP Wings'),
(157, 'JAF', 'JetAirFly'),
(158, 'BO',  'Aerologic'),
(159, 'BOX', 'Aerologic'),
(160, 'UC',  'LAN Cargo'),
(161, 'ACX', 'ACG Air Cargo Germany'),
(162, 'BR',  'EVA Air'),
(163, 'ABW', 'AirBridgeCargo'),
(164, 'FX',  'FedEx'),
(165, 'RGN', 'Cygnus Air'),
(166, 'CZ',  'China Southern Airlines'),
(167, 'ABR', 'Air Bridge Carriers'),
(168, 'AHO', 'Air Hamburg'),
(169, 'CSA', 'Czech Airlines'),
(170, 'DLH', 'Lufthansa'),
(171, 'ETD', 'Etihad Airways'),
(172, 'EXH', 'Executive AG'),
(173, 'EXT', 'Nightexpress'),
(174, 'FFD', 'Stuttgarter Flugdienst'),
(175, 'FMY', 'Aviation Legere de l\'Armee de Terre'),
(176, 'IAM', 'Aeronautica Militare Italiana'),
(177, 'JEI', 'Jet Executive International Charter'),
(178, 'KAC', 'Kuwait Airways'),
(179, 'KZR', 'Air Astana'),
(180, 'LNX', 'London Executive Aviation - LEA'),
(181, 'LZB', 'Bulgaria Air'),
(182, 'MAS', 'Malaysia Airlines'),
(183, 'MGR', 'Magna Air'),
(184, 'NJE', 'NetJets Transportes Aereos'),
(185, 'QAJ', 'Quick Air Jet Charter'),
(186, 'RZO', 'SATA Internacional'),
(187, 'SHY', 'Sky Airlines'),
(188, 'THY', 'Turkish Airlines'),
(189, 'AFL', 'Aeroflot'),
(190, 'AZG', 'Sakaviaservice'),
(191, 'CMB', 'US Transportation Command'),
(192, 'GTI', 'Atlas Air'),
(193, 'MNB', 'MNG Airlines'),
(194, 'YZR', 'Yangtze River Express Airlines'),
(195, 'AG',  'Affretair'),
(196, 'FRA', 'FR Aviation'),
(197, 'OAW', 'Helvetic'),
(198, 'AEA', 'Air Europa'),
(199, 'QS',  'Travel Servis/Smartwings'),
(200, 'BIE', 'Air Mediterranee'),
(201, 'I2',  'Iberia Express'),
(202, 'HC',  'Aero Tropics'),
(203, 'BM',  'BMI regional'),
(204, 'FQ',  'Thomas Cook Belgium Airlines'),
(205, 'IQ',  'Augsburg Airways'),
(206, 'ZZZ', 'ZZZ'),
(207, 'SWT', 'Swiftair'),
(208, 'XLF', 'XL Airways France'),
(209, 'FC',  'Flybe Nordic'),
(210, 'KZ',  'Nippon Cargo Airlines'),
(211, 'WDL', 'WDL Aviation'),
(212, 'VLG', 'Vueling'),
(213, 'VY',  'Vueling Airlines'),
(214, 'S4',  'SATA International'),
(215, 'IA',  'Iraqi Airways'),
(216, 'AHY', 'Azerbaijan Airlines'),
(217, 'SZ',  'Somon Air'),
(218, 'UT',  'UTAir'),
(219, 'TDR', 'Trade Air'),
(220, 'QY',  'DHL'),
(221, 'TF',  'Malmö Aviation'),
(222, 'RU',  'Air Brigde Cargo'),
(223, 'AWC', 'Titan Airways'),
(224, 'MT',  'Thomas Cook Airlines'),
(225, 'OAE', 'Omni Air International'),
(226, 'RC',  'Atlantic Airways'),
(227, 'CLJ', 'Cello Aviation'),
(228, 'YY',  'General Aviation'),
(229, 'WX',  'CityJet'),
(230, '6G',  'Go2Sky'),
(231, 'AXE', 'AirExplore'),
(232, 'HFY', 'Hi Fly'),
(233, 'LAV', 'Alba Star'),
(234, 'PVG', 'Privilege Style'),
(235, 'PC',  'Pegasus Hava Tasimaciligi'),
(236, 'ISK', 'Intersky'),
(237, 'ENT', 'Enter Air'),
(238, 'A6',  'Air Alps'),
(239, '9Y',  'FlyGeorgia'),
(240, '6W',  'Saratov Airlines'),
(241, 'A9',  'Air Zena'),
(242, 'DTR', 'Danish Air Transport'),
(243, 'NJ',  'Nordic Global Airlines'),
(244, 'ORB', 'Oren Air'),
(245, 'EZY', 'easyJet'),
(246, 'BG',  'Biman Bangladesh Airlines'),
(247, 'UX',  'Air Europa'),
(248, 'CKS', 'Kalitta Air'),
(249, 'HQ',  'Thomas Cook Airlines Belgium'),
(250, 'SRK', 'SkyWork Airlines'),
(251, 'DNM', 'Denim Air'),
(252, 'DK',  'Thomas Cook Airlines Scandinavia'),
(253, 'DY',  'Norwegian Air Shuttle'),
(254, 'WLC', 'Welcome Air'),
(255, 'SN',  'Brussels Airlines'),
(256, 'ISR', 'Israir Airlines'),
(257, 'AIB', 'Airbus Industrie'),
(258, 'NMA', 'Nesma Airlines'),
(259, 'AJD', 'flyVista'),
(260, 'QN',  'Air Armenia'),
(261, 'V7',  'Volotea'),
(262, 'OV',  'Estonian Air'),
(263, 'OR',  'Arkefly'),
(264, '8Q',  'Onur Air'),
(265, 'AAB', 'Abelag Aviation'),
(266, 'AV',  'Avianca'),
(267, 'GA',  'Garuda Indonesia'),
(268, 'HM',  'Air Seychelles'),
(269, 'JAI', 'Jet Airways'),
(270, 'LPV', 'Air Alps Aviation'),
(271, 'NZ',  'Air New Zealand'),
(272, 'TUL', 'Tulpar Air'),
(273, 'HRM', 'Hermes Airlines'),
(274, 'JQ',  'Alba Star'),
(276, '7W',  'Wind Rose Aviation'),
(277, 'B2',  'Belavia'),
(278, 'A5',  'Hop!'),
(279, 'V3',  'Carpatair'),
(280, 'TOM', 'Thomson Airways'),
(281, 'EVE', 'Evelop Airlines'),
(282, 'MSA', 'Mistral Air'),
(283, 'MMZ', 'euroAtlantic Airways'),
(284, 'IG',  'Meridiana Fly'),
(285, 'PF',  'Primera Air Scandinavia'),
(286, 'PRW', 'Primera Air Nordic'),
(287, '6F',  'Primera Air Nordic'),
(288, 'AX',  'Avanti Air'),
(289, 'TXC', 'Transaviaexport'),
(290, 'ZT',  'Titan Airways'),
(291, 'SRN', 'Sprint Air'),
(292, 'NO',  'Neos'),
(293, 'DM',  'Asian Air'),
(294, 'FRF', 'Fleet Air International'),
(295, 'YB',  'Bora Jet'),
(296, 'SWU', 'Swiss European Air Lines'),
(297, 'MLT', 'Maleth Aero'),
(298, 'BRJ', 'Bora Jet'),
(299, 'EZS', 'easyJet Switzerland'),
(300, 'RSY', 'I fly'),
(301, 'BLX', 'TUIfly Nordic'),
(302, 'FEG', 'Fly Egypt'),
(303, '3O',  'Air Arabia Maroc'),
(304, 'PTG', 'PrivatAir'),
(305, 'CC',  'Air Atlanta Icelandic'),
(306, 'BCS', 'European Air Transport'),
(307, 'SUA', 'Silesia Air'),
(308, 'BVR', 'ACM Air Charter'),
(309, 'VJT', 'VistaJet'),
(310, 'AOJ', 'Avcon Jet'),
(311, 'MMD', 'Air Alsie'),
(312, 'LXA', 'Luxaviation'),
(313, 'EFD', 'Eisele Flugdienst'),
(314, 'EJM', 'Executive Jet Management'),
(315, '??', ''),
(316, 'DCS', 'DC Aviation'),
(317, 'PNC', 'Prince Aviation'),
(318, 'TOY', 'Toyo Aviation'),
(319, 'AVB', 'Aviation Beauport'),
(320, 'BZE', 'Zenith Aviation'),
(321, 'QGA', 'Windrose Air'),
(322, 'EDC', 'Air Charter Scotland'),
(323, 'ADN', 'Aero-Dienst'),
(324, 'ATL', 'Atlas Air Service'),
(325, 'QNR', 'Queen Air'),
(326, 'SIO', 'Sirio'),
(327, 'FYG', 'Flying Service'),
(328, 'HTM', 'HTM Jet Service'),
(329, 'FAT', 'ASL Airlines (Switzerland)'),
(330, 'CLU', 'Cargologicair'),
(331, 'XPE', 'Amira Air'),
(332, 'GAC', 'GlobeAir'),
(333, 'XES', 'Europstar Express'),
(334, 'UU',  'Air Austral'),
(335, 'JSY', 'Jung Sky'),
(336, 'WGT', 'Volkswagen Air Service'),
(337, 'SAZ', 'REGA Swiss Air-Ambulance'),
(338, 'PHA', 'Phoenix Air Group'),
(339, 'JSI', 'Jet Air Group'),
(340, 'LLT', 'Classic Jet'),
(341, 'IGA', 'Skytaxi'),
(342, 'CLF', 'Centreline Air Charter'),
(343, 'PTI', 'Privatair'),
(344, 'AYY', 'Air Alliance Express'),
(345, 'SVB', 'SiAvia'),
(346, 'TJS', 'Tyrolean Jet Service'),
(347, 'GMA', 'Gama Aviation'),
(348, 'SCR', 'Silver Cloud Air'),
(349, 'LEU', 'Lions Air'),
(350, 'IJM', 'International Jet Management'),
(351, 'AZI', 'Astra Airlines'),
(352, 'ESQ', 'Europ Star'),
(353, 'FXR', 'K-air'),
(354, 'ECA', 'OHL Air Charterflug GmbH'),
(355, 'FBR', 'Baden Aircraft Operations'),
(356, 'CAZ', 'CAT Aviation'),
(357, 'RBB', 'Rabbit-Air'),
(358, 'NUB', 'Nomad Aviation (Europe)'),
(359, 'DUK', 'Luxembourg Air Ambulance'),
(360, 'TIH', 'Tiriac Air'),
(361, 'BAF', 'Belgian Air Force'),
(362, 'GNJ', 'GainJet'),
(363, 'JKH', 'JK Jetkontor AG'),
(364, 'BBA', 'Bombardier'),
(365, 'BKK', 'Blink'),
(366, 'JDI', 'Jet Story'),
(367, 'SVW', 'Global Jet Luxembourg'),
(368, 'HFM', 'Hi Fly Limited Malta'),
(369, 'NPT', 'Atlantic Airlines'),
(370, 'FPG', 'TAG Aviation (Schweiz)'),
(371, 'FLJ', 'FlairJet'),
(372, 'GOT', 'Walt Air Europe'),
(373, 'FCK', 'FCS Flight Calibration Services'),
(374, 'VLB', 'Air Volta'),
(375, 'RHK', 'Sovereign Business Jets'),
(376, 'ITL', 'Italfly'),
(377, 'LXG', 'Luxaviation Germany'),
(378, 'BOB', 'Backbone'),
(379, 'SXN', 'SaxonAir Charter'),
(380, 'HR',  'Hahn Air Lines'),
(381, 'TYW', 'Tyrol Air Ambulance'),
(382, 'GSW', 'Germania Flug'),
(383, 'VR',  'TACV Cabo Verde Airlines'),
(384, 'BFD', 'Bertelsmann Aviation'),
(385, 'MLM', 'Comlux Malta'),
(386, 'MJC', 'Mandarin Air'),
(387, 'CLS', 'Challenge Air'),
(388, 'KFE', 'Skyfirst'),
(389, 'FYL', 'Flying Group Luxembourg'),
(390, 'JEF', 'Jetflite Oy'),
(391, 'MHV', 'MHS Aviation'),
(392, 'XRO', 'Exxaero'),
(393, 'HRN', 'Heron Luftfahrt'),
(394, 'ESW', 'Silk Way Business Aviation LLC'),
(395, 'HYP', 'Hyperion Aviation'),
(396, 'TJT', 'Twin Jet'),
(397, 'MJF', 'MJet'),
(398, 'VVV', 'Valair'),
(399, 'BFX', 'Fly Alpha'),
(400, 'P7',  'Small Planet Airlines (Poland)'),
(401, 'ADZ', 'Avio Delta'),
(402, 'WW',  'WOW Air'),
(403, 'S5',  'Small Planet Airlines UAB'),
(404, 'SOO', 'Southern Air'),
(405, 'CRL', 'Corse Air International (Corsair)'),
(406, 'TAY', 'TNT Airways'),
(407, 'PEA', 'Pan Europeenne Air Service'),
(408, 'MOZ', 'SalzburgJetAviation'),
(409, 'STC', 'Skytaxi Luftfahrt GmbH'),
(410, 'VMP', 'Execujet Europe'),
(411, 'IBG', 'Springfield Air'),
(412, 'EXJ', 'Executive Jet Charter'),
(413, 'NFA', 'North Flying'),
(414, 'VPC', 'Panaviatic'),
(415, 'EXU', 'Executive Airlines'),
(416, 'NVR', 'Novair'),
(417, 'ASJ', 'Astonjet'),
(418, 'LWG', 'Luxwing'),
(419, 'GDK', 'Goldeck Flug'),
(420, 'AZE', 'Arcus Air-Logistic'),
(421, 'BHP', 'Belair Airlines'),
(422, 'XGO', 'Airgo Flugservice'),
(423, 'EAT', 'Air Transport Europe'),
(424, 'VCG', 'Fly Vectra'),
(425, 'LDM', 'LaudaMotion'),
(426, 'PVJ', 'Privajet'),
(427, 'IMX', 'Zimex Aviation'),
(428, 'PY',  'Surinam Airways'),
(429, 'DFC', 'Aeropartner'),
(430, 'SM',  'Air Cairo'),
(431, 'JTI', 'ImperialJet Europe'),
(432, 'KD',  'Western Global Airlines'),
(433, 'ECC', 'Eclair Aviation'),
(434, 'HSG', 'Hesnes Air'),
(435, 'BMS', 'Blue Air'),
(436, 'AWU', 'Sylt Air'),
(437, 'LMJ', 'Masterjet Aviacao Executiva'),
(438, 'SYG', 'Synergy Aviation'),
(439, 'VND', 'Avionord'),
(440, 'CND', 'Corendon Dutch Airlines'),
(441, 'PJS', 'Jet Aviation'),
(442, 'PAV', 'ProAir'),
(443, 'FTY', 'ABC Bedarfsflug	'),
(444, 'PWF', 'Private Wings Flugcharter'),
(445, 'GSJ', 'G-Jet'),
(446, 'EOL', 'Airailes'),
(447, 'JAR', 'Airlink'),
(448, 'VS',  'Virgin Atlantic'),
(449, 'NWG', 'Airwing'),
(450, 'JFA', 'Jetfly Aviation'),
(451, 'M4',  'Mistral Air'),
(452, 'AEH', 'Aero4M'),
(453, 'AMQ', 'Aircraft Management and Consulting'),
(454, 'EDG', 'Jet Edge'),
(455, 'EOA', 'Elilombarda'),
(456, 'LFO', 'DLR Flugbetriebe'),
(457, 'LUC', 'Albinati Aeronautics'),
(458, 'JME', 'EJME Aircraft Management'),
(459, 'PSK', 'Prescott'),
(460, 'UJ',  'AlMasria Universal Airlines'),
(461, 'GER', 'Eagle Aviation GmbH, Mannheim'),
(462, 'LLX', 'Small Planet Airlines'),
(463, 'MYX', 'SmartLynx Estonia'),
(464, 'TVF', 'Transavia France'),
(465, 'GLJ', 'Global Jet Austria'),
(466, 'ARN', 'Aeronexus'),
(467, 'LSA', 'Leader SRL'),
(468, 'FR',  'Ryanair'),
(469, 'ABP', 'ABS Jets'),
(470, 'EVJ', 'Everjets'),
(471, 'TGM', 'TAG Aviation España'),
(472, 'DHK', 'DHL Air'),
(473, 'LJB', 'Al Jaber Aviation'),
(474, 'TIE', 'Time Air'),
(475, 'POF', 'Police Nationale'),
(476, 'ABF', 'Scanwings Oy'),
(477, 'ED',  'AirExplore'),
(478, 'AXY', 'Air X Charter Limited'),
(479, 'CV',  'Cargolux Airlines International'),
(480, 'EL',  'Ellinair S.A.'),
(481, 'JCB', 'J C Bamford Excavators'),
(482, 'SPG', 'Speedwings Executive'),
(483, 'CAO', 'Air China Cargo');
