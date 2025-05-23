<?php
if (!isset($_SESSION['FilterCallSign'])) {
   $_SESSION['FilterCallSign'] = null;
}
if (!isset($_SESSION['FilterModule'])) {
   $_SESSION['FilterModule'] = null;
}
if (isset($_POST['do'])) {
   if ($_POST['do'] == 'SetFilter') {
      if (isset($_POST['txtSetCallsignFilter'])) {
         $_POST['txtSetCallsignFilter'] = trim($_POST['txtSetCallsignFilter']);
         if ($_POST['txtSetCallsignFilter'] == "") {
            $_SESSION['FilterCallSign'] = null;
         } else {
            $_SESSION['FilterCallSign'] = "*".$_POST['txtSetCallsignFilter']."*";
            if (strpos($_SESSION['FilterCallSign'], "*") === false) {
               $_SESSION['FilterCallSign'] = "*".$_SESSION['FilterCallSign']."*";
            }
         }
      }
      if (isset($_POST['txtSetModuleFilter'])) {
         $_POST['txtSetModuleFilter'] = trim($_POST['txtSetModuleFilter']);
         if ($_POST['txtSetModuleFilter'] == "") {
            $_SESSION['FilterModule'] = null;
         } else {
            $_SESSION['FilterModule'] = $_POST['txtSetModuleFilter'];
         }
      }
   }
}
if (isset($_GET['do'])) {
   if ($_GET['do'] == "resetfilter") {
      $_SESSION['FilterModule'] = null;
      $_SESSION['FilterCallSign'] = null;
   }
}

// Function to get user data from SQLite database
function getUserData($callsign) {
    $dbFile = '/var/www/html/xlxd/users.db';
    try {
        $db = new SQLite3($dbFile);
    } catch (Exception $e) {
        return ['name' => '-', 'city_state' => '-'];
    }
    $callsign = strtoupper($callsign); // Ensure uppercase for matching
    $stmt = $db->prepare('SELECT name, city_state FROM users WHERE callsign = :callsign');
    $stmt->bindValue(':callsign', $callsign, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Separate city and state from city_state field using ", "
        $cityState = explode(', ', $row['city_state']);
        $cidade = trim($cityState[0]);
        $estado = isset($cityState[1]) ? trim($cityState[1]) : '';

        $data = [
            'name' => htmlspecialchars($row['name']),
            'city_state' => htmlspecialchars($cidade . ', ' . $estado)
        ];
    } else {
        $data = ['name' => '-', 'city_state' => '-'];
    }
    $db->close();
    return $data;
}
?>

<table border="0">
   <tr>
      <td valign="top">
         <table class="listingtable">
             <?php
             if ($PageOptions['UserPage']['ShowFilter']) {
                 echo '
                 <tr>
                    <th colspan="10">
                       <table width="100%" border="0">
                          <tr>
                             <td align="center">
                                <form name="frmFilterCallSign" method="post" action="./index.php">
                                   <input type="hidden" name="do" value="SetFilter" />
                                   <input type="text" class="FilterField" value="' . $_SESSION['FilterCallSign'] . '" name="txtSetCallsignFilter"
placeholder="Indicativo" onfocus="SuspendPageRefresh();" onblur="setTimeout(ReloadPage, ' . $PageOptions['PageRefreshDelay'] . ');" />
                                   <input type="submit" value="Aplicar" class="FilterSubmit" />
                                </form>
                             </td>';
                 if (($_SESSION['FilterModule'] != null) || ($_SESSION['FilterCallSign'] != null)) {
                     echo '
                        <td><a href="./index.php?do=resetfilter" class="smalllink">Desativar Filtros</a></td>';
                 }
                 echo '
                             <td align="center" style="padding-right:3px;">
                                <form name="frmFilterModule" method="post" action="./index.php">
                                   <input type="hidden" name="do" value="SetFilter" />
                                   <input type="text" class="FilterField" value="' . $_SESSION['FilterModule'] . '" name="txtSetModuleFilter" placeholder="Módulo" onfocus="SuspendPageRefresh();" onblur="setTimeout(ReloadPage, ' . $PageOptions['PageRefreshDelay'] . ');" />
                                   <input type="submit" value="Aplicar" class="FilterSubmit" />
                                </form>
                             </td>
                       </table>
                    </th>
                 </tr>';
             }
             ?>
             <tr>
                <th>Indicativo</th>
                <th>Sufixo</th>
                <th>Gateway</th>
                <th>Operador</th>
                <th>Origem</th>
                <th>País</th>
                <th>Última Atividade</th>
                <th>DPRS</th>
                <th align="center" valign="middle"><img src="./img/speaker.png" alt="Listening on" style="width: 18px;"/></th>
             </tr>
             <?php
             $Reflector->LoadFlags();
             $odd = "";
             for ($i = 0; $i < $Reflector->StationCount(); $i++) {
                 $ShowThisStation = true;
                 if ($PageOptions['UserPage']['ShowFilter']) {
                     $CS = true;
                     if ($_SESSION['FilterCallSign'] != null) {
                         if (!fnmatch($_SESSION['FilterCallSign'], $Reflector->Stations[$i]->GetCallSign(), FNM_CASEFOLD)) {
                             $CS = false;
                         }
                     }
                     $MO = true;
                     if ($_SESSION['FilterModule'] != null) {
                         if (trim(strtolower($_SESSION['FilterModule'])) != strtolower($Reflector->Stations[$i]->GetModule())) {
                             $MO = false;
                         }
                     }
                     $ShowThisStation = ($CS && $MO);
                 }
                 if ($ShowThisStation) {
                     if ($odd == "#252525") { $odd = "#2c2c2c"; } else { $odd = "#252525"; }
                     echo '
                 <tr height="30" bgcolor="' . $odd . '" onMouseOver="this.bgColor=\'#586553\';" onMouseOut="this.bgColor=\'' . $odd . '\'">
                    <td width="80" align="center"><a href="https://www.qrz.com/db/' . $Reflector->Stations[$i]->GetCallsignOnly() . '" class="pl" title="Clique aqui para consultar o QRZ deste indicativo" target="_blank">' . $Reflector->Stations[$i]->GetCallsignOnly() . '</a></td>
                    <td width="50" align="center">' . $Reflector->Stations[$i]->GetSuffix() . '</td>';
                     // Fetch user data from SQLite database
                     $callsign = $Reflector->Stations[$i]->GetCallsignOnly();
                     $userInfo = getUserData($callsign);
                     echo '
                    <td width="90" align="center">' . $Reflector->Stations[$i]->GetVia();
                     if ($Reflector->Stations[$i]->GetPeer() != $Reflector->GetReflectorName()) {
                         echo ' / ' . $Reflector->Stations[$i]->GetPeer();
                     }
                     echo '</td>
                    <td width="220" align="center">' . $userInfo['name'] . '</td>
                    <td width="200" align="center">' . $userInfo['city_state'] . '</td>
                    <td align="center" width="40" valign="middle">';
                     list ($Flag, $Name) = $Reflector->GetFlag($Reflector->Stations[$i]->GetCallSign());
                     if (file_exists("./img/flags/" . $Flag . ".png")) {
                         echo '<a href="#" class="tip"><img src="./img/flags/' . $Flag . '.png" height="15" alt="' . $Name . '" /><span>' . $Name . '</span></a>';
                     }
                     echo '</td>
                    <td width="170" align="center">' . @date("d/m/Y, H:i:s", $Reflector->Stations[$i]->GetLastHeardTime()) . '</td>
                    <td width="40" align="center" valign="middle"><a href="http://www.aprs.fi/' . $Reflector->Stations[$i]->GetCallsignOnly() . '" class="pl" title="Clique aqui para consultar a localização do dispositivo" target="_blank"><img src="./img/satellite.png" style="width: 40%;"/></a></td>
                    <td align="center" width="30" valign="middle">';
                      if ($i == 0 && $Reflector->Stations[$i]->GetLastHeardTime() > (time() - 10)) {
                          echo '<img src="./img/tx.gif" style="margin-top:3px;" height="20"/>';
                      } else {
                          echo ($Reflector->Stations[$i]->GetModule());
                      }
                      echo '</td>
                 </tr>';
                 }
                 if ($i == $PageOptions['LastHeardPage']['LimitTo']) { $i = $Reflector->StationCount() + 1; }
             }
             ?>
         </table>
      </td>
   </tr>
</table>
<table class="listingtable" width="900px">
   <?php
   $Modules = $Reflector->GetModules();
   sort($Modules, SORT_STRING);
   for ($i = 0; $i < count($Modules); $i++) {
       // Fetch users for this module to get the count
       $Users = $Reflector->GetNodesInModulesByID($Modules[$i]);
       $userCount = count($Users);
       echo '<tr>';
       if (isset($PageOptions['ModuleNames'][$Modules[$i]])) {
           echo '<th>Módulo ' . $Modules[$i] . ' | ' . $PageOptions['ModuleNames'][$Modules[$i]] . ' (' . $userCount . ')</th>';
       } else {
           echo '<th>Módulo ' . $Modules[$i] . ' | ' . $Modules[$i] . ' (' . $userCount . ')</th>';
       }
       echo '</tr>';
       echo '<tr>';
       echo '<td style="border:0px;padding:0px;padding-bottom:10px;">';
       echo '<div style="display: flex; flex-wrap: wrap; gap: 5px; justify-content: center;">';
       $odd = "";
       $UserCheckedArray = array();
       for ($j = 0; $j < count($Users); $j++) {
           $Displayname = $Reflector->GetCallsignAndSuffixByID($Users[$j]);
           echo '<div style="border: 1px solid #444444; display: inline-block;">';
           echo '<a href="http://www.aprs.fi/' . $Displayname . '" class="pl" title="Clique aqui para consultar a localização da estação" target="_blank" style="background-color: ' . ($odd == "#252525" ? "#242424" : "#252525") . '; padding: 2px 5px; margin: 2px; display: inline-block;">' . $Displayname . '</a>';
           echo '</div>';
           $odd = ($odd == "#252525") ? "#242424" : "#252525";
           $UserCheckedArray[] = $Users[$j];
       }
       echo '</div>';
       echo '</td>';
       echo '</tr>';
   }
   ?>
</table>
