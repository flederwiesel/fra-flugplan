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
	# name:query_string
	"index:index.php?arrival\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"index-spam:index.php?req=register\&stopforumspam=localhost/\${projectpath}\&user=spam\&email=spam@gmail.com"
	"betriebsrichtung:applics.fraport.de/betriebsrichtung/betriebsrichtungsvg.js?rwy=25"
	"getflights:getflights.php?debug=url,json,sql\&fmt=html"
	"getflights-local:getflights.php?debug=url,json,sql\&fmt=html\&prefix=localhost/\${projectpath}/\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"download:index.php?page=download"
	"specials:index.php?page=specials"
	"stopforumspam:api.stopforumspam.org/index.php?username=spam\&email=spam@gmail.com\&ip=46.118.155.73"
	"www.frankfurt-airport.com:www.frankfurt-airport.com/index.php?flighttype=arrivals\&lang=de\&perpage=3\&page=1\&time=$(urlencode $(date +'%Y-%m-%dT05:00:00%z'))"
	"www.frankfurt-airport.com-airlines:www.frankfurt-airport.com/de/_jcr_content.airlines.json"
	"www.frankfurt-airport.com-airports:www.frankfurt-airport.com/de/_jcr_content.airports.json"
	"mkpng:content/mkpng.php?res=ADMIN_SNAILMAIL"
)

filename=$(realpath "${BASH_SOURCE[0]}")
scriptdir=$(dirname "$filename")
webroot=${scriptdir%%/htdocs/*}/htdocs
projectpath=${scriptdir##*/htdocs/}
workspace=${scriptdir##*/}

if [[ $(uname -o) == Cygwin ]]; then
	scriptdir=$(cygpath --windows "$scriptdir")
	webroot=$(cygpath --windows "$webroot")
fi

mkdir -p "$scriptdir/.phped"

# PROJECT
# Create *.ppj for each project

i=0

for p in ${projects[@]}
do
	if grep -q '?' <<<"${p}"; then
		fin='&'
	else
		fin='?'
	fi

	# Use eval for variables embedded in query string
	index="$(eval echo ${p##*:})${fin}"
	project="${p%%:*}"

	cat > "${scriptdir}/.phped/${project}.ppj" <<-EOF
		[Debugger]
		blkout=0
		breakerr=1
		breakexc=1
		cp=System default encoding
		Custom.Debug.Settings=0
		custom=0
		dbgsessions=1
		errset=E_ERROR,E_WARNING,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING,E_USER_ERROR,E_USER_WARNING,E_STRICT,E_RECOVERABLE_ERROR
		excign=
		excset=Exception
		host=localhost
		profwithdbg=0
		readonlyed=0
		sesstimeout=15
		showmaperrors=1
		stopbeginning=0
		tunnel=

		[PHPEdProject.Filters.Allow]
		\=

		[Test.Suite]
		/=>

		[PHPEdProject.Filters]
		\=

		[Encoder]
		asptags=0
		compatibilitylevel=1
		copyall=1
		destinationtype=0
		excl=0
		extinfo=0
		headertype=0
		licensing=0
		lineinfo=0
		nofreeinc=0
		obfusclevel=0
		phpdoc=1
		shorttags=1
		stoponerr=0
		suppresssucc=0

		[PHPEdProject.Encodings]
		\=UTF-8

		[Wizard]
		localwebroot=${webroot}
		projectroot=${scriptdir}
		runmode=2
		webrooturl=http://localhost/

		[PHPEdProject.JSLibraries]
		Count=0

		[Testing]
		EnableTesting=0
		FindTestMode=0
		TestingConfig=phpunit.xml
		TestingCustomLoader=
		TestingCustomLoaderExtraArg=
		TestingRoot=

		[PHPEdProject]
		ChangeTime=
		CustomPropCount=0
		CvsCVSROOT=:pserver:@
		CvsHost=
		CvsMethod=pserver
		CvsModule=
		CvsPassw=
		CvsRoot=
		CvsUser=
		CvsUseUtf8=0
		DefaultEncodingCount=1
		DefaultFile=http://localhost/${projectpath}/${index}
		DontPublishDirs=CVS;.svn;.git
		DriverID=
		EncoderEnabled=0
		GUID={8FD21476-5E2E-483B-970B-58875E6E0727}
		HideDirs=CVS;.svn;.git;.phped
		HideFiles=.gitignore;.cvsignore;*.dsk;*.idx;*.ppx;*.ppw
		IncPath_count=0
		MappingCount=1
		MappingLocal0=${webroot}
		MappingMainIdx=0
		MappingPublishing0=
		MappingPublishingRoot=
		MappingRemote0=${webroot}
		MappingRemoteDir=${webroot}
		MappingURL0=http://localhost/${projectpath}
		ParserProp_AllowSingleAsteriskXDoc=0
		ParserProp_CSS_ParsePHP=0
		ParserProp_CSS_SubLang=2
		ParserProp_HTML_SubLang=8
		ParserProp_JS_ParsePHP=0
		ParserProp_Override=0
		ParserProp_PHP_AspTags=0
		ParserProp_PHP_SubLang=4
		ParserPropPHPShortTags=1
		PhpUnitPackage=
		PublishingAllowFilterCount=1
		PublishingFilterCount=1
		RelativeRootDir=..
		RemoteCliAccount=
		RemoteCliPhp=
		RunMode=2
		ScriptRunTarget=2
		SourceControl=0
		URL=http://localhost/${projectpath}
		UrlMappingCount=0
		UsedPHPFrameworkChecked=0
		UsedPHPFrameworkId=
		UsedPHPFrameworkPath=

		[Publishing]
		count=0
		DebuggerPublishingIdx=-1
		DfltPublishingIdx=-1
EOF

	# workspace entries
	Projects="${Projects}
Project${i}=${scriptdir}\.phped\\${project}.ppj
Unloaded${i}=0"
	((i++))
done

# WORKSPACE

cat > "${scriptdir}/${workspace}.ppw" <<EOF
[PHPEdWorkspace]
ProjectCount=${i}
${Projects}
ActiveProject=${scriptdir}\.phped\${projects[0]%%:*}.ppj
EOF
