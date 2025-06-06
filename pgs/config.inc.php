<?php
/*
Possible values for IPModus
HideIP
ShowFullIP
ShowLast1ByteOfIP
ShowLast2ByteOfIP
ShowLast3ByteOfIP
*/

$Service     = array();
$CallingHome = array();
$PageOptions = array();
$VNStat      = array();

$PageOptions['ContactEmail']                         = 'your_email';	// Support E-Mail address
$PageOptions['CustomTXT']                            = 'custom_header';	// custom text in your header
$PageOptions['DashboardVersion']                     = '2.4.2_dark';		// Dashboard Version
$PageOptions['PageRefreshActive']                    = true;		// Activate automatic refresh
$PageOptions['PageRefreshDelay']                     = '3000';		// Page refresh time in miliseconds
$PageOptions['NumberOfModules']                      = MODQTD;		// Number of Modules enabled on reflector

$PageOptions['RepeatersPage'] = array();
$PageOptions['RepeatersPage']['LimitTo']             = 99;		// Number of Repeaters to show
$PageOptions['RepeatersPage']['IPModus']             = 'ShowLast3ByteOfIP';	// See possible options above
$PageOptions['RepeatersPage']['MasqueradeCharacter'] = '###';		// Character used for  masquerade

$PageOptions['PeerPage'] = array();
$PageOptions['PeerPage']['LimitTo']                  = 99;		// Number of peers to show
$PageOptions['PeerPage']['IPModus']                  = 'ShowLast3ByteOfIP';	// See possible options above
$PageOptions['PeerPage']['MasqueradeCharacter']      = '###';		// Character used for masquerade
$PageOptions['LastHeardPage']['LimitTo']             = 109;		// Number of stations to show

$PageOptions['ModuleNames'] = array();					          // Module nomination
$PageOptions['ModuleNames']['A']                     = 'Geral';	// '<b>Link</b><br>XLXBRA-A';
$PageOptions['ModuleNames']['B']                     = 'Beacons';
$PageOptions['ModuleNames']['C']                     = 'YSF Interlink';
$PageOptions['ModuleNames']['D']                     = 'D-Star';
$PageOptions['ModuleNames']['E']                     = 'Echo Test';
$PageOptions['ModuleNames']['F']                     = 'Fox';
$PageOptions['ModuleNames']['G']                     = 'Golf';
$PageOptions['ModuleNames']['H']                     = 'Hotel';

$PageOptions['MetaDescription']                      = 'XLX is a D-Star Reflector System for Ham Radio Operators.';	// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaKeywords']                         = 'Ham Radio, D-Star, XReflector, XLX, XRF, DCS, REF';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaAuthor']                           = 'PU5KOD';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaRevisit']                          = 'After 3 Days';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaRobots']                           = 'index,follow';		// Meta Tag Values, usefull for Search Engine

$PageOptions['Peers']['Show']                        = true;	// Show links whith other reflectors
$PageOptions['UserPage']['ShowFilter']               = true;	// Show Filter on Users page
$PageOptions['Traffic']['Show']                      = true;	// Enable vnstat traffic statistics
$PageOptions['IRCDDB']['Show']                       = true;	// Show D-Star live traffic status

$Service['PIDFile']                                  = '/var/log/xlxd.pid';
$Service['XMLFile']                                  = '/var/log/xlxd.xml';

$CallingHome['Active']                               = true;				// xlx phone home, true or false
$CallingHome['MyDashBoardURL']                       = 'http://your_dashboard';		// dashboard url
$CallingHome['ServerURL']                            = 'http://xlxapi.rlx.lu/api.php';	// database server, do not change !!!!
$CallingHome['PushDelay']                            = 300;				// push delay in seconds
$CallingHome['Country']                              = "your_country";			// Country
$CallingHome['Comment']                              = "your_comment";	// Comment. Max 100 character
$CallingHome['HashFile']                             = "/xlxd/callinghome.php";		// Make sure the apache user has read and write permissions in this folder.
$CallingHome['LastCallHomefile']                     = "/xlxd/lastcallhome.php";	// Path to lastcallhome file
$CallingHome['OverrideIPAddress']                    = "";		// Insert your IP address here. Leave blank for autodetection. No need to enter a fake address.
$CallingHome['InterlinkFile']                        = "/xlxd/xlxd.interlink";		// Path to interlink file

$VNStat['Interfaces']                                = array();
$VNStat['Interfaces'][0]['Name']                     = 'netact';  // Name of the interface that vnstat is using as default
$VNStat['Interfaces'][0]['Address']                  = 'netact';
$VNStat['Binary']                                    = '/usr/bin/vnstat';

/*
include an extra config file for people who dont like to mess with shipped config.ing.php
this makes updating dashboard from git a little bit easier
*/

if (file_exists("../config.inc.php")) {
 include ("../config.inc.php");
}

?>
