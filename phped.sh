#!/bin/bash

urlencode() {
	local string="${1}"
	local strlen=${#string}
	local encoded=""
	local retain="${2}"

	for (( pos=0 ; pos<strlen ; pos++ ))
	do
		c=${string:$pos:1}
		case "$c" in
			[-_.~a-zA-Z0-9"$retain"] )
				o="${c}"
				;;
			* )
				printf -v o '%%%02x' "'$c"
		esac

		encoded+="${o}"
	done
	echo "${encoded}"    # You can either set a return variable (FASTER)
	REPLY="${encoded}"   #+or echo the result (EASIER)... or both... :p
}

projects=(
	"index:index.php?arrival\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"index-spam:index.php?req=register\&stopforumspam=localhost/\$prjroot\&user=spam\&email=spam@gmail.com"
	"betriebsrichtung:apps.fraport.de/betriebsrichtung/betriebsrichtung.html"
	"getflights:getflights.php?debug=url,json,sql\&fmt=html"
	"getflights-local:getflights.php?debug=url,json,sql\&fmt=html\&prefix=localhost/\$prjroot/\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"download:index.php?page=download"
	"specials:index.php?page=specials"
	"stopforumspam:api.stopforumspam.org/index.php?username=spam\&email=spam@gmail.com\&ip=46.118.155.73"
	"www.frankfurt-airport.com:www.frankfurt-airport.com/index.php?type=arrival\&lang=de\&perpage=3\&page=1\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"www.frankfurt-airport.com-airlines:www.frankfurt-airport.com/de/_jcr_content.airlines.json"
	"www.frankfurt-airport.com-airports:www.frankfurt-airport.com/de/_jcr_content.airports.json"
)

filename=$(readlink -f "${BASH_SOURCE[0]}")
cygpath=$(dirname "$filename")
winpath=$(cygpath --windows "$cygpath")
prjdir=$(dirname "$filename")
prjroot=${prjdir##*htdocs/}
workspace=${prjdir##*/}

mkdir -p "$cygpath/.phped"

# PROJECT
# Create *.ppj for each project

i=0

for p in ${projects[@]}
do
	if grep -q '\?' <<<"$p"; then
		fin='\&'
	else
		fin='?'
	fi

	sed -r "s|(DefaultFile=).*\$|\\1http://localhost/$prjroot/${p##*:}$fin|g;
			s|\\\$prjroot|$prjroot|g" > "$cygpath/.phped/${p%%:*}.ppj" <<-EOF
		[Debugger]
		stopbeginning=0
		dbgsessions=1
		excset=Exception
		profwithdbg=0
		excign=
		breakexc=1
		cp=System default encoding
		tunnel=
		readonlyed=0
		custom=0
		host=localhost
		errset=E_ERROR,E_WARNING,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING,E_USER_ERROR,E_USER_WARNING,E_STRICT,E_RECOVERABLE_ERROR
		sesstimeout=15
		blkout=0
		showmaperrors=1
		Custom.Debug.Settings=0
		breakerr=1

		[PHPEdProject.Filters.Allow]
		\=

		[Test.Suite]
		/=>

		[PHPEdProject.Filters]
		\=

		[Encoder]
		excl=0
		asptags=0
		obfusclevel=0
		stoponerr=0
		extinfo=0
		licensing=0
		phpdoc=1
		compatibilitylevel=1
		nofreeinc=0
		copyall=1
		suppresssucc=0
		headertype=0
		shorttags=1
		lineinfo=0
		destinationtype=0

		[PHPEdProject.Encodings]
		\=UTF-8

		[Wizard]
		runmode=2
		projectroot=$winpath
		webrooturl=http://localhost/
		localwebroot=E:\home\common\prj\HTML\htdocs

		[PHPEdProject.JSLibraries]
		Count=0

		[Testing]
		TestingCustomLoader=
		TestingCustomLoaderExtraArg=
		TestingConfig=phpunit.xml
		FindTestMode=0
		TestingRoot=
		EnableTesting=0

		[PHPEdProject]
		HideDirs=CVS;.svn;.git
		CvsRoot=
		MappingLocal0=$winpath
		ParserProp_CSS_SubLang=2
		UrlMappingCount=0
		CvsModule=
		EncoderEnabled=0
		DefaultEncodingCount=1
		CvsPassw=xGkz19Uq0yChJr+8rWLrmDnmp6WFmgMQUZbvrF3SG4hJJ3e1NQozAAEGH5wNQkt4aZZZh+V6Y/Cxdk+MvbJ7aA==
		DriverID=
		IncPath_count=0
		RelativeRootDir=..\\..\\$workspace
		UsedPHPFrameworkPath=
		MappingMainIdx=0
		ParserProp_CSS_ParsePHP=0
		MappingURL0=http://localhost/$prjroot
		MappingPublishingRoot=
		ParserProp_PHP_AspTags=0
		MappingRemote0=$winpath
		CvsHost=
		RunMode=2
		CvsMethod=pserver
		CvsUser=
		URL=http://localhost/$prjroot
		CvsCVSROOT=:pserver:@
		DefaultFile=http://localhost/$prjroot/index.php
		ChangeTime=
		ParserProp_JS_ParsePHP=0
		ParserProp_Override=0
		RemoteCliAccount=
		GUID={8FD21476-5E2E-483B-970B-58875E6E0727}
		RemoteCliPhp=
		CvsUseUtf8=0
		MappingCount=1
		ParserProp_AllowSingleAsteriskXDoc=0
		UsedPHPFrameworkId=
		PublishingAllowFilterCount=1
		MappingRemoteDir=$winpath
		PhpUnitPackage=
		DontPublishDirs=CVS;.svn;.git
		SourceControl=0
		MappingPublishing0=
		ScriptRunTarget=2
		ParserProp_HTML_SubLang=8
		UsedPHPFrameworkChecked=0
		ParserPropPHPShortTags=1
		ParserProp_PHP_SubLang=4
		PublishingFilterCount=1
		CustomPropCount=0
		HideFiles=.gitignore;.cvsignore

		[Publishing]
		DebuggerPublishingIdx=-1
		count=0
		DfltPublishingIdx=-1
EOF

	# workspace entries
	Projects="${Projects}
Project$i=$winpath\.phped\\${p%%:*}.ppj
Unloaded$i=0"
	((i++))
done

# WORKSPACE

cat > "$cygpath/$workspace.ppw" <<EOF
[PHPEdWorkspace]
ProjectCount=$i
$Projects
ActiveProject=$winpath\.phped\index.ppj
EOF
