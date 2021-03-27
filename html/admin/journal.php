<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_journal.php");

if (!isset($action)) $action = "";

switch($action):

case "date":

    if (isset($datedeb) && $datedeb != "" && isset($datefin) && $datefin != "")
    {
        echo afficher_titre("Journal des actions entre : " . dateexterne($datedeb) . " et " . dateexterne($datefin));
        echo "<blockquote><pre>";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select date,auteur,commentaire from $base_journal where " . ($datedeb != "0000-00-00" ? "date >= '$datedeb 00:00:00'" : "1") . ($datefin != "9999-99-99" ? " and date <= '$datefin 23:59:59'" : "1") . " order by date desc");
        while (list($date,$auteur,$commentaire) = mysqli_fetch_row($rep))
        {
            echo dateheureexterne($date) . " [" . $auteur . "] " . $commentaire . "<br>";
        }
        echo "</pre></blockquote>";
    }
    else
    {
        echo afficher_titre("Journal des actions");
        echo afficher_message_erreur("Nom d'utilisateur manquant ou incorrect");
    }

    break;

case "select":

    if (isset($nomutil) && $nomutil != "")
    {
        echo afficher_titre("Journal des actions de : " . $nomutil);
        echo "<blockquote><pre>";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select date,auteur,commentaire from $base_journal where auteur = '$nomutil' order by date desc");
        while (list($date,$auteur,$commentaire) = mysqli_fetch_row($rep))
        {
            echo dateheureexterne($date) . " [" . $auteur . "] " . $commentaire . "<br>";
        }
        echo "</pre></blockquote>";
    }
    else
    {
        echo afficher_titre("Journal des actions");
        echo afficher_message_erreur("Nom d'utilisateur manquant ou incorrect");
    }

    break;

default:

    echo afficher_titre("Journal des actions");
    echo "<blockquote><pre>";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select date,auteur,commentaire from $base_journal where 1 order by date desc");
    while (list($date,$auteur,$commentaire) = mysqli_fetch_row($rep))
    {
        echo dateheureexterne($date) . " [" . $auteur . "] " . $commentaire . "<br>";
    }
    echo "</pre></blockquote>";

    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>