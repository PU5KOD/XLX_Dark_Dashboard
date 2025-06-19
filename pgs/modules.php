<table class="listingtable">
 <tr>
 <th width="60" rowspan="2">Mód.</th>
 <th width="110" rowspan="2">Name</th>
 <th width="90" rowspan="2">Active<br/>connections</th>
 <th colspan="2">DPlus (REF)</th>
 <th colspan="2">DExtra (XRF)</th>
 <th colspan="2">DCS (DCS/XLX)</th>
 <th width="60" rowspan="2">DMR</th>
 <th width="60" rowspan="2">YSF<br/>DG-ID</th>
 </tr>
 <tr>
 <th width="90">URCALL</th>
 <th width="70">DTMF</th>
 <th width="90">URCALL</th>
 <th width="70">DTMF</th>
 <th width="90">URCALL</th>
 <th width="70">DTMF</th>
 </tr>
<?php

$ReflectorNumber = substr($Reflector->GetReflectorName(), 3, 3);
$NumberOfModules = isset($PageOptions['NumberOfModules']) ? min(max($PageOptions['NumberOfModules'],0),26) : 26;

$odd = "";

for ($i = 1; $i <= $NumberOfModules; $i++) {

 $module = chr(ord('A')+($i-1));

 if ($odd == "#252525") { $odd = "#2c2c2c"; } else { $odd = "#252525"; }

 echo '
 <tr height="30" bgcolor="'.$odd.'" onMouseOver="this.bgColor=\'#586553\';" onMouseOut="this.bgColor=\''.$odd.'\';">
 <td align="center">'. $module .'</td>
 <td align="center">'. (empty($PageOptions['ModuleNames'][$module]) ? '-' : $PageOptions['ModuleNames'][$module]) .'</td>
 <td align="center">'. count($Reflector->GetNodesInModulesByID($module)) .'</td>
 <td align="center">'. 'REF' . $ReflectorNumber . $module . 'L' .'</td>
 <td align="center">'. (is_numeric($ReflectorNumber) ? '*' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
 <td align="center">'. 'XRF' . $ReflectorNumber . $module . 'L' .'</td>
 <td align="center">'. (is_numeric($ReflectorNumber) ? 'B' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
 <td align="center">'. 'DCS' . $ReflectorNumber . $module . 'L' .'</td>
 <td align="center">'. (is_numeric($ReflectorNumber) ? 'D' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
 <td align="center">'. (4000+$i) .'</td>
 <td align="center">'. (9+$i) .'</td>
 </tr>';
}

?>

</table>
