<?php

$Result = @fopen($CallingHome['ServerURL']."?do=GetReflectorList", "r");

if (!$Result) die("HEUTE GIBTS KEIN BROT");

$INPUT = "";
while (!feof ($Result)) {
 $INPUT .= fgets ($Result, 1024);
}
fclose($Result);

$XML = new ParseXML();
$Reflectorlist = $XML->GetElement($INPUT, "reflectorlist");
$Reflectors = $XML->GetAllElements($Reflectorlist, "reflector");

?>

<table class="listingtable">
 <tr>
 <th width="40">#</th>
 <th width="80">Refletor</th>
 <th width="200">País de Origem</th>
 <th width="70">Estado</th>
 <th width="400">Descrição</th>
 </tr>
<?php

$odd = "";

for ($i=0;$i<count($Reflectors);$i++) {

 $NAME = $XML->GetElement($Reflectors[$i], "name");
 $COUNTRY = $XML->GetElement($Reflectors[$i], "country");
 $LASTCONTACT = $XML->GetElement($Reflectors[$i], "lastcontact");
 $COMMENT = $XML->GetElement($Reflectors[$i], "comment");
 $DASHBOARDURL = $XML->GetElement($Reflectors[$i], "dashboardurl");

 if ($odd == "#252525") { $odd = "#2c2c2c"; } else { $odd = "#252525"; }

 echo '
 <tr height="30" bgcolor="'.$odd.'" onMouseOver="this.bgColor=\'#586553\';" onMouseOut="this.bgColor=\''.$odd.'\';">
 <td align="center">'.($i+1).'</td>
 <td align="center"><a href="'.$DASHBOARDURL.'" target="_blank" class="listinglink" title="Clique aqui para visitar dashboard do '.$NAME.'">'.$NAME.'</a></td>
 <td>'.$COUNTRY.'</td>
 <td align="center" valign="middle"><img src="./img/'; if ($LASTCONTACT<(time()-1800)) { echo 'down'; } else { echo 'up'; } echo '.png" height="25" /></td>
 <td>'.$COMMENT.'</td>
 </tr>';
}

?>
</table>
