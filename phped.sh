#!/bin/bash

projects=(
	'getflights:getflights.php?debug=url,query\&fmt=html'
	'download:index.php?page=download'
	'specials:index.php?page=specials'
)

filename=$(readlink -f "${BASH_SOURCE[0]}")
cygpath=$(dirname "$filename")
winpath=$(cygpath --windows "$cygpath")
workspace=$(dirname "$filename")
workspace=$(basename "$workspace")

mkdir -p "$cygpath/.phped"

# PROJECT index.ppj

cat > "$cygpath/.phped/index.ppj" <<EOF
[Debugger]
dbgsessions=1
stopbeginning=1
excset=Exception
profwithdbg=0
excign=
breakexc=1
cp=System default encoding
tunnel=
readonlyed=0
host=localhost
custom=0
errset=E_ERROR,E_WARNING,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING,E_USER_ERROR,E_USER_WARNING,E_STRICT,E_RECOVERABLE_ERROR
sesstimeout=15
blkout=0
Custom.Debug.Settings=0
showmaperrors=1
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
srunmode=2
sprojectroot=$winpath
slocalwebroot=E:\Documents and Settings\common\prj\HTML\htdocs
swebrooturl=http://localhost/

[Testing]
TestingCustomLoader=
TestingCustomLoaderExtraArg=
TestingConfig=
FindTestMode=0
TestingRoot=
EnableTesting=0

[PHPEdProject]
HideDirs=CVS;.svn;.git
CvsRoot=
MappingLocal0=$winpath
ParserProp_CSS_SubLang=1
CvsModule=
EncoderEnabled=0
DefaultEncodingCount=1
CvsPassw=
DriverID=
IncPath_count=0
RelativeRootDir=..\\..\\$workspace
MappingMainIdx=0
ParserProp_CSS_ParsePHP=0
MappingURL0=http://localhost/$workspace
MappingPublishingRoot=
ParserProp_PHP_AspTags=0
MappingRemote0=$winpath
CvsHost=
RunMode=2
CvsMethod=pserver
CvsUser=
URL=http://localhost/$workspace
CvsCVSROOT=:pserver:@
DefaultFile=http://localhost/$workspace/index.php
ParserProp_JS_ParsePHP=0
ParserProp_Override=0
GUID={8FD21476-5E2E-483B-970B-58875E6E0727}
CvsUseUtf8=0
MappingCount=1
ParserProp_AllowSingleAsteriskXDoc=0
PublishingAllowFilterCount=1
MappingRemoteDir=$winpath
DontPublishDirs=CVS;.svn;.git
MappingPublishing0=
SourceControl=0
ParserProp_HTML_SubLang=2
ParserPropPHPShortTags=1
ParserProp_PHP_SubLang=3
HideFiles=.gitignore;.cvsignore
CustomPropCount=0
PublishingFilterCount=1
EOF

# PROJECTS $projects=

# workspace entry for index.ppj
Projects="Project0=$winpath\.phped\index.ppj
Unloaded0=0"

i=1

for p in ${projects[@]}
do
	sed -r "s|(DefaultFile=).*\$|\\1http://localhost/$workspace/${p##*:}|g" "$cygpath/.phped/index.ppj" > "$cygpath/.phped/${p%%:*}.ppj"
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
