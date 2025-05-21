<?php /* Possible values for IPModus

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

$PageOptions['ContactEmail']                         = 'daniel@dvbr.net';	// Support E-Mail address

$PageOptions['DashboardVersion']                     = '2.4.2_hlk';		// Dashboard Version

$PageOptions['PageRefreshActive']                    = true;		// Activate automatic refresh
$PageOptions['PageRefreshDelay']                     = '3000';		// Page refresh time in miliseconds

$PageOptions['NumberOfModules']                      = 5;		// Number of Modules enabled on reflector

$PageOptions['RepeatersPage'] = array();
$PageOptions['RepeatersPage']['LimitTo']             = 99;		// Number of Repeaters to show
$PageOptions['RepeatersPage']['IPModus']             = 'ShowLast3ByteOfIP';	// See possible options above
$PageOptions['RepeatersPage']['MasqueradeCharacter'] = '###';		// Character used for  masquerade

$PageOptions['PeerPage'] = array();
$PageOptions['PeerPage']['LimitTo']                  = 99;		// Number of peers to show
$PageOptions['PeerPage']['IPModus']                  = 'ShowLast3ByteOfIP';	// See possible options above
$PageOptions['PeerPage']['MasqueradeCharacter']      = '###';		// Character used for  masquerade

$PageOptions['LastHeardPage']['LimitTo']             = 109;		// Number of stations to show

$PageOptions['ModuleNames'] = array();					// Module nomination
$PageOptions['ModuleNames']['A']                     = 'Geral';	// '<b>Link</b><br>XLX070-A';
$PageOptions['ModuleNames']['B']                     = 'Beacons';
$PageOptions['ModuleNames']['C']                     = 'YSF Interlink';
$PageOptions['ModuleNames']['D']                     = 'D-Star';
$PageOptions['ModuleNames']['E']                     = 'Echo Test';
$PageOptions['ModuleNames']['F']                     = 'Fox';
$PageOptions['ModuleNames']['G']                     = 'Golf';
$PageOptions['ModuleNames']['H']                     = 'Hotel';

$PageOptions['MetaDescription']                      = 'XLX is a D-Star Reflector System for Ham Radio Operators.';	// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaKeywords']                         = 'Ham Radio, D-Star, XReflector, XLX, XRF, DCS, REF, PU5KOD';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaAuthor']                           = 'PU5KOD';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaRevisit']                          = 'After 3 Days';		// Meta Tag Values, usefull for Search Engine
$PageOptions['MetaRobots']                           = 'index,follow';		// Meta Tag Values, usefull for Search Engine

$PageOptions['Peers']['Show']			                     = true;	// Show links whith other reflectors
$PageOptions['UserPage']['ShowFilter']               = false;	// Show Filter on Users page
$PageOptions['Traffic']['Show']                      = true;	// Enable vnstat traffic statistics
$PageOptions['IRCDDB']['Show']                       = true;	// Show D-Star live traffic status

$PageOptions['CustomTXT']                            = 'XLX Reflector by PU5KOD';	// custom text in your header

$Service['PIDFile']                                  = '/var/log/xlxd.pid';
$Service['XMLFile']                                  = '/var/log/xlxd.xml';

$CallingHome['Active']                               = true;				// xlx phone home, true or false
$CallingHome['MyDashBoardURL']                       = 'https://xlxreflector.net';		// dashboard url
$CallingHome['ServerURL']                            = 'http://xlxapi.rlx.lu/api.php';	// database server, do not change !!!!
$CallingHome['PushDelay']                            = 300;				// push delay in seconds
$CallingHome['Country']                              = "Brazil";			// Country
$CallingHome['Comment']                              = "XLXBRA Multiprotocol Reflector by PU5KOD, info: daniel@dvbr.net";	// Comment. Max 100 character
$CallingHome['HashFile']                             = "/xlxd/callinghome.php";		// Make sure the apache user has read and write permissions in this folder.
$CallingHome['LastCallHomefile']                     = "/xlxd/lastcallhome.php";	// Path to lastcallhome file
$CallingHome['OverrideIPAddress']                    = "";		// Insert your IP address here. Leave blank for autodetection. No need to enter a fake address.
$CallingHome['InterlinkFile']                        = "/xlxd/xlxd.interlink";		// Path to interlink file

$VNStat['Interfaces']                                = array();
$VNStat['Interfaces'][0]['Name']                     = 'eth0';
$VNStat['Interfaces'][0]['Address']                  = 'eth0';
$VNStat['Binary']                                    = '/usr/bin/vnstat';

/*
include an extra config file for people who dont like to mess with shipped config.ing.php
this makes updating dashboard from git a little bit easier
*/

if (file_exists("../config.inc.php")) {
 include ("../config.inc.php");
}

?>
