<?php
if (file_exists("./pgs/functions.php")) {
    require_once("./pgs/functions.php");
} else {
    die("functions.php does not exist.");
}
if (file_exists("./pgs/config.inc.php")) {
    require_once("./pgs/config.inc.php");
} else {
    die("config.inc.php does not exist.");
}

if (!class_exists('ParseXML'))   require_once("./pgs/class.parsexml.php");
if (!class_exists('Node'))       require_once("./pgs/class.node.php");
if (!class_exists('xReflector')) require_once("./pgs/class.reflector.php");
if (!class_exists('Station'))    require_once("./pgs/class.station.php");
if (!class_exists('Peer'))       require_once("./pgs/class.peer.php");
if (!class_exists('Interlink'))  require_once("./pgs/class.interlink.php");

$Reflector = new xReflector();
$Reflector->SetFlagFile("./pgs/country.csv");
$Reflector->SetPIDFile($Service['PIDFile']);
$Reflector->SetXMLFile($Service['XMLFile']);

$Reflector->LoadXML();

if ($CallingHome['Active']) {
    $CallHomeNow = false;
    $LastSync = 0;
    $Hash = "";

    if (!file_exists($CallingHome['HashFile'])) {
        $Ressource = fopen($CallingHome['HashFile'], "w+");
        if ($Ressource) {
            $Hash = CreateCode(16);
            @fwrite($Ressource, "<?php\n");
            @fwrite($Ressource, "\n".'$Hash = "'.$Hash.'";');
            @fwrite($Ressource, "\n\n".'?>');
            @fflush($Ressource);
            @fclose($Ressource);
            @chmod($CallingHome['HashFile'], 0777);
        }
    } else {
        require_once($CallingHome['HashFile']);
    }

    if (@file_exists($CallingHome['LastCallHomefile'])) {
        if (@is_readable($CallingHome['LastCallHomefile'])) {
            $tmp = @file($CallingHome['LastCallHomefile']);
            if (isset($tmp[0])) {
                $LastSync = $tmp[0];
            }
            unset($tmp);
        }
    }

    if ($LastSync < (time() - $CallingHome['PushDelay'])) {
        $CallHomeNow = true;
        $Ressource = @fopen($CallingHome['LastCallHomefile'], "w+");
        if ($Ressource) {
            @fwrite($Ressource, time());
            @fflush($Ressource);
            @fclose($Ressource);
            @chmod($CallingHome['LastCallHomefile'], 0777);
        }
    }

    if ($CallHomeNow || isset($_GET['callhome'])) {
        $Reflector->SetCallingHome($CallingHome, $Hash);
        $Reflector->ReadInterlinkFile();
        $Reflector->PrepareInterlinkXML();
        $Reflector->PrepareReflectorXML();
        $Reflector->CallHome();
    }
} else {
    $Hash = "";
}

// Checks if the request is AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Sets $_GET['show'] to empty if not defined
$show = isset($_GET['show']) ? $_GET['show'] : '';

if (!$isAjax) {
    // If it is not an AJAX request, it includes the DOCTYPE, <html>, <head> and <script>
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="' . htmlspecialchars($PageOptions['MetaDescription']) . '">
    <meta name="keywords" content="' . htmlspecialchars($PageOptions['MetaKeywords']) . '">
    <meta name="author" content="' . htmlspecialchars($PageOptions['MetaAuthor']) . '">
    <meta name="revisit" content="' . htmlspecialchars($PageOptions['MetaRevisit']) . '">
    <meta name="robots" content="' . htmlspecialchars($PageOptions['MetaAuthor']) . '">
    <meta name="viewport" content="width=device-width, initial-scale=0.38">
    <title>' . htmlspecialchars($Reflector->GetReflectorName()) . ' Reflector Dashboard</title>
    <link rel="stylesheet" type="text/css" href="./css/layout.css">
    <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">';

    if ($PageOptions['PageRefreshActive']) {
        echo '
        <script src="./js/jquery-1.12.4.min.js"></script>
        <script>
            var PageRefresh;

            function ReloadPage() {
                var url = "./index.php?show=' . urlencode($show) . '";
                $.get(url, function(data) {
                    console.log("Atualizando conteúdo do body...");
                    $("body").html(data);
                })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Erro na requisição AJAX: " + textStatus + ", " + errorThrown);
                    })
                    .always(function() {
                        PageRefresh = setTimeout(ReloadPage, ' . $PageOptions['PageRefreshDelay'] . ');
                    });
            }';

        if ($show === '' || ($show !== 'liveircddb' && $show !== 'reflectors' && $show !== 'interlinks')) {
            echo '
            PageRefresh = setTimeout(ReloadPage, ' . $PageOptions['PageRefreshDelay'] . ');';
        }
        echo '

            function SuspendPageRefresh() {
                clearTimeout(PageRefresh);
            }
        </script>';
    }

    echo '
</head>
<body>';
}

// Contents of <body> (will be returned for both normal and AJAX requests)
?>
    <?php if (file_exists("./tracking.php")) { include_once("tracking.php"); }?>
    <div id="top" style="text-align: center;">
        <img src="./img/header.png" alt="XLX Gateway" style="margin: 0 auto;">
    </div>
    <div id="menubar">
        <div id="menu">
            <table border="0">
                <tr>
                    <td><a href="./index.php" class="menulink<?php if ($show === '') { echo 'active'; } ?>">Atividade Recente</a></td>
                    <td><a href="./index.php?show=repeaters" class="menulink<?php if ($show === 'repeaters') { echo 'active'; } ?>">Estações Conectadas (<?php echo $Reflector->NodeCount(); ?>)</a></td>
                    <?php
                    if ($PageOptions['Peers']['Show']) {
                        echo '
                        <td><a href="./index.php?show=peers" class="menulink';
                        if ($show === 'peers') { echo 'active'; }
                        echo '">Links (' . $Reflector->PeerCount() . ')</a></td>';
                    }
                    ?>
                    <td><a href="./index.php?show=modules" class="menulink<?php if ($show === 'modules') { echo 'active'; } ?>">Módulos Ativos</a></td>
                    <td><a href="./index.php?show=reflectors" class="menulink<?php if ($show === 'reflectors') { echo 'active'; } ?>">Refletores XLX</a></td>
                    <?php
                    if ($PageOptions['Traffic']['Show']) {
                        echo '
                        <td><a href="./index.php?show=traffic" class="menulink';
                        if ($show === 'traffic') { echo 'active'; }
                        echo '">Rede</a></td>';
                    }
                    if ($PageOptions['IRCDDB']['Show']) {
                        echo '
                        <td><a href="./index.php?show=liveircddb" class="menulink';
                        if ($show === 'liveircddb') { echo 'active'; }
                        echo '">Tráfego</a></td>';
                    }
                    ?>
                </tr>
            </table>
        </div>
    </div>
    <div id="content" align="center">
        <?php
        if ($CallingHome['Active']) {
            if (!is_readable($CallingHome['HashFile']) && (!is_writeable($CallingHome['HashFile']))) {
                echo '
                <div class="error">
                    your private hash in ' . $CallingHome['HashFile'] . ' could not be created, please check your config file and the permissions for the defined folder.
                </div>';
            }
        }

        switch ($show) {
            case 'users'      : require_once("./pgs/users.php"); break;
            case 'repeaters'  : require_once("./pgs/repeaters.php"); break;
            case 'liveircddb' : require_once("./pgs/liveircddb.php"); break;
            case 'peers'      : require_once("./pgs/peers.php"); break;
            case 'modules'    : require_once("./pgs/modules.php"); break;
            case 'reflectors' : require_once("./pgs/reflectors.php"); break;
            case 'traffic'    : require_once("./pgs/traffic.php"); break;
            default           : require_once("./pgs/users.php");
        }
        ?>
        <div style="width:100%;text-align:center;margin-top:50px;color:#c3dcba;">
            <br />Refletor D-Star Multiprotocolo <b><?php echo $Reflector->GetReflectorName(); ?></b> v<?php echo $Reflector->GetVersion();?> - Dashboard v<?php echo $PageOptions['DashboardVersion']; ?>
            | Personalizado por Daniel K. <b><a href="https://www.qrz.com/db/PU5KOD">PU5KOD</a></b>
            <br />Tempo em serviço: <span id="suptime"><?php echo FormatSeconds($Reflector->GetServiceUptime());?></span>
            <?php echo '<p><a href="https://github.com/PU5KOD/xlxd"><center><img src="./img/debian_powered.jpg" width="100"></center></a></p>';?>
        </div>
    </div>
<?php
if (!$isAjax) {
    // If it is not an AJAX request, close the <body> and <html> tags
    echo '</body>
</html>';
}
?>
