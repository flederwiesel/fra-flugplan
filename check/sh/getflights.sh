#!/bin/bash

###############################################################################
#
#       project: FRA-flights Live Schedule
#                Auomatic test script
#
#       $Author: flederwiesel $
#         $Date: 2013-07-04 18:16:27 +0200 (Do, 04 Jul 2013) $
#          $Rev: 341 $
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

baseurl=$(rawurlencode localhost/fra-schedule/check)

###############################################################################

cat > flugplan/airportcity/arrival.csv <<EOF
06:15;06:06;SA;260;South African Airways;A346;ZSSNH;JNB;FAOR;Johannesburg; Südafrika;Gepäckausgabe beendet
06:20;06:18;CX;289;Cathay Pacific Airways;B77W;BKPE; HKG;VHHH;Hongkong; Hong Kong;Gepäckausgabe
06:25;06:25;EY;001;Etihad Airways;A332;;AUH;OMAA;Abu Dhabi; Ver.Arab.Emirate;gelandet
07:00;07:00;AC;872;Air Canada;B77W;CFITW;YYZ;CYYZ;Toronto; Kanada;im Anflug
13:30;15:34;DE;5249;Condor;B763;DABUD;POP;MDPP;Puerto Plata; Dominikan. Rep.;im Anflug
14:50;14:50;LH;3617;Lufthansa;TRN;;QKL;;Köln Hbf; Deutschland;Zug
15:20;;JP;112;Adria Airways;;;LJU;LJLJ;Ljubljana; Slowenien ;
15:25;05:30;DE;6369;Condor;B763;DABUM;HKT;VTSP;Phuket; Thailand;verspätet auf 31.12.
20:15;20:15;LH;031;Lufthansa;A321;DAISQ;HAM;EDDH;Hamburg; Deutschland;
22:30;21:58;TP;578;TAP Portugal;A320;CSTNP;LIS;LPPT;Lissabon; Portugal;
EOF

cat > flugplan/airportcity/departure.csv <<EOF
20:15;21:41;SK;4758;SAS Scandinavian Airlines ;B738;LNRRE;OSL;ENGM;Oslo-Gardermoen;Norwegen ;gestartet
21:09;;LH;3626;Lufthansa;TRN;;QKL;;Köln Hbf; Deutschland;Zug
21:30;;AI;120;Air India;B788;VTANH;DEL;VIDP;Delhi;Indien;geschlossen
21:55;;SQ;325;Singapore Airlines ;B77W;9VSWK;SIN;WSSS;Singapur;Singapur;Gate offen
04:45;;X3;2138;TUIfly;B738;DAHFZ;FUE;GCFV;Fuerteventura;Spanien;
06:55;;KL;1762;KLM Royal Dutch Airlines ;E190;PHEZS;AMS;EHAM;Amsterdam;Niederlande;
07:55;;I2;3635;Iberia Express;A320;ECJSK;MAD;LEMD;Madrid;Spanien;
09:40;;EK;44;Emirates;B77W;A6ECI;DXB;OMDB;Dubai-International;Ver.Arab.Emirate;
10:30;;AC;845;Air Canada;A333;CGFAF;YYC;CYYC;Calgary;Kanada;
10:55;;EY;002;Etihad Airways;A346;A6EHE;AUH;OMAA;Abu Dhabi;Ver.Arab.Emirate;
11:45;;TK;1588;Turkish Airlines;A343;;IST;LTBA;Istanbul-Atatuerk;Türkei;
EOF

now=$(date +'%Y-%m-%d %H:%M:%S' --date='05:00')
now=$(rawurlencode $now)

check "1" curl "$url/getflights.php?baseurl=$baseurl\&now=$now\&debug=url\&fmt=html"\
	"| sed -r '"\
	"s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g;"\
	"s/(fi[ad]=[A-Z0-9]+)$(date --date='06:30' +%Y%m%d)$/\100000000/g;"\
	"s/[0-9]{4}-[0-9]{2}-[0-9]{2} ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-00 \1/g"\
	"'"

check "2" curl "$url/?arrival\&now=$now"\
	"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"

# Note, that RARE A/C will not be marked as such,
# since we use different a/c in arrival/departure.csv,
# and only arrivals will evaluate visits
check "3" curl "$url/?departure\&now=$now"\
	"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"

cat > flugplan/airportcity/arrival.csv <<EOF
06:15;06:06;SA;260;South African Airways;A346;ZSSNH;JNB;FAOR;Johannesburg; Südafrika;Gepäckausgabe beendet
06:20;06:18;CX;289;Cathay Pacific Airways;B77W;BKPE; HKG;VHHH;Hongkong; Hong Kong;Gepäckausgabe
06:25;06:25;EY;001;Etihad Airways;A332;;AUH;OMAA;Abu Dhabi; Ver.Arab.Emirate;gelandet
07:00;07:00;AC;872;Air Canada;B77W;CFITW;YYZ;CYYZ;Toronto; Kanada;im Anflug
13:30;15:34;DE;5249;Condor;B763;DABUD;POP;MDPP;Puerto Plata; Dominikan. Rep.;im Anflug
14:50;14:50;LH;3617;Lufthansa;TRN;;QKL;;Köln Hbf; Deutschland;Zug
15:20;;JP;112;Adria Airways;;;LJU;LJLJ;Ljubljana; Slowenien ;annulliert
15:25;05:30;DE;6369;Condor;B763;DABUM;HKT;VTSP;Phuket; Thailand;verspätet auf 31.12.
20:15;20:15;LH;031;Lufthansa;A321;DAISQ;HAM;EDDH;Hamburg; Deutschland;
22:30;21:58;TP;578;TAP Portugal;A320;CSTNP;LIS;LPPT;Lissabon; Portugal;
EOF

cat > flugplan/airportcity/departure.csv <<EOF
20:15;21:41;SK;4758;SAS Scandinavian Airlines ;B738;LNRRE;OSL;ENGM;Oslo-Gardermoen;Norwegen ;gestartet
21:09;;LH;3626;Lufthansa;TRN;;QKL;;Köln Hbf; Deutschland;Zug
21:30;;AI;120;Air India;B788;VTANH;DEL;VIDP;Delhi;Indien;geschlossen
21:55;;SQ;325;Singapore Airlines ;B77W;9VSWK;SIN;WSSS;Singapur;Singapur;Gate offen
04:45;;X3;2138;TUIfly;B738;DAHFZ;FUE;GCFV;Fuerteventura;Spanien;
06:55;;KL;1762;KLM Royal Dutch Airlines ;E190;PHEZS;AMS;EHAM;Amsterdam;Niederlande;
07:55;;I2;3635;Iberia Express;A320;ECJSK;MAD;LEMD;Madrid;Spanien;
09:40;;EK;44;Emirates;B77W;A6ECI;DXB;OMDB;Dubai-International;Ver.Arab.Emirate;
10:30;;AC;845;Air Canada;A333;CGFAF;YYC;CYYC;Calgary;Kanada;
10:55;;EY;002;Etihad Airways;A346;A6EHE;AUH;OMAA;Abu Dhabi;Ver.Arab.Emirate;
11:45;;TK;1588;Turkish Airlines;A343;;IST;LTBA;Istanbul-Atatuerk;Türkei;
EOF

now=$(date +'%Y-%m-%d %H:%M:%S' --date='06:00')
now=$(rawurlencode $now)

check "4" curl "$url/getflights.php?baseurl=$baseurl\&now=$now\&debug=url,query\&fmt=html"\
	"| sed -r '"\
	"s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g;"\
	"s/(fi[ad]=[A-Z0-9]+)$(date --date='06:30' +%Y%m%d)$/\100000000/g;"\
	"s/[0-9]{4}-[0-9]{2}-[0-9]{2} ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-00 \1/g"\
	"'"

check "5" curl "$url/?arrival\&now=$now"\
	"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"

# Note, that rare a/c will not be marked as such,
# since we use different a/c in arrival/departure.csv,
# and only arrivals will evaluate visits
check "6" curl "$url/?departure\&now=$now"\
	"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"
