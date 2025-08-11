<?php

require_once("fonctions_include.php");
require_once("http_header_exports.php");

if(!is_user_logged_in())
{
    header("Location: " . wp_login_url($_SERVER['PHP_SELF']));
    exit;
}

if(!current_user_can('consommateur') && !current_user_can('gestionnaire')) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"" . site_url() . "\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=" . site_url() . "\">";
    exit;
}

?>