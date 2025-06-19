<?php

$Result = @fopen($CallingHome['ServerURL']."?do=GetReflectorList", "r");

$INPUT = "";

if ($Result) {

   while (!feof ($Result)) {
       $INPUT .= fgets ($Result, 1024);
   }

   $XML = new ParseXML();
   $Reflectorlist = $XML->GetElement($INPUT, "reflectorlist");
   $Reflectors    = $XML->GetAllElements($Reflectorlist, "reflector");
}

fclose($Result);
?>
<table class="listingtable">
 <tr>
   <th width="40">#</th>
   <th width="80">Reflector</th>
   <th width="170">Start of Activity</th>
   <th width="130">Duration</th>
   <th width="100">Protocol</th>
   <th width="90">Module(s)</th><?php

if ($PageOptions['PeerPage']['IPModus'] != 'HideIP') {
   echo '
   <th width="140">Server IP</th>';
}

?>
 </tr>
<?php

$odd = "";
$Reflector->LoadFlags();

for ($i=0;$i<$Reflector->PeerCount();$i++) {

   if ($odd == "#252525") { $odd = "#2c2c2c"; } else { $odd = "#252525"; }

   echo '
   <tr height="30" bgcolor="'.$odd.'" onMouseOver="this.bgColor=\'#586553\';" onMouseOut="this.bgColor=\''.$odd.'\';">
   <td align="center">'.($i+1).'</td>';
   $Name = $Reflector->Peers[$i]->GetCallSign();
   $URL = '';
   for ($j=1;$j<count($Reflectors);$j++) {
      if ($Name === $XML->GetElement($Reflectors[$j], "name")) {
         $URL  = $XML->GetElement($Reflectors[$j], "dashboardurl");
      }
   }
   if ($Result && (trim($URL) != "")) {
      echo '
   <td align="center"><a href="'.$URL.'" target="_blank" class="pl" title="Click here to visit the dashboard of '.$Name.'">'.$Name.'</a></td>';
   } else {
      echo '
   <td align="center">'.$Name.'</td>';
}
   echo '
   <td align="center">'.date("d/m/Y, H:i:s", $Reflector->Peers[$i]->GetLastHeardTime()).'</td>
   <td align="center">'.FormatSeconds(time()-$Reflector->Peers[$i]->GetConnectTime()).'</td>
   <td align="center">'.$Reflector->Peers[$i]->GetProtocol().'</td>
   <td align="center">'.$Reflector->Peers[$i]->GetLinkedModule().'</td>';
   if ($PageOptions['PeerPage']['IPModus'] != 'HideIP') {
      echo '
   <td align="center">';
      $Bytes = explode(".", $Reflector->Peers[$i]->GetIP());
      if ($Bytes !== false && count($Bytes) == 4) {
         switch ($PageOptions['PeerPage']['IPModus']) {
            case 'ShowLast1ByteOfIP'      : echo $PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$Bytes[3]; break;
            case 'ShowLast2ByteOfIP'      : echo $PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$Bytes[2].'.'.$Bytes[3]; break;
            case 'ShowLast3ByteOfIP'      : echo $PageOptions['PeerPage']['MasqueradeCharacter'].'.'.$Bytes[1].'.'.$Bytes[2].'.'.$Bytes[3]; break;
            default                       : echo $Reflector->Peers[$i]->GetIP();
         }
      }
      echo '</td>';
   }
   echo '
   </tr>';
   if ($i == $PageOptions['PeerPage']['LimitTo']) { $i = $Reflector->PeerCount()+1; }
}

?>

</table>
