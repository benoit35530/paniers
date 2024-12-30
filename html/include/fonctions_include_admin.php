<?php

require_once('../../../../../wp-blog-header.php');
require_once('../../../../../wp-includes/media.php');

require_once("fonctions_include.php");
require_once("fonctions/fonctions_stats.php");

$action = $wp_query->get("action");

if(!is_user_logged_in()) {
    $loginurl = wp_login_url($_SERVER['PHP_SELF']);
    header("Location: $loginurl");
    exit;
}

require_once("admin/admin_header_gen.php");
require_once("admin/admin_menu_princ.php");

if(!current_user_can('gestionnaire') && !current_user_can("add_users")) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"" . site_url() . "\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=" . site_url() . "\">";
    exit;
} else {
    $nom_script = basename($_SERVER['PHP_SELF'],".php");
    $fonctions = obtenir_fonctions_utilisateur();
    if((strpos( $fonctions, $nom_script) === false) && $nom_script != "index") {
        echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
        echo "<p align=\"center\"><a href=\"" . site_url() . "\">Cliquez ici pour retourner à l'accueil</a></p>";
        echo "<meta http-equiv=\"Refresh\" content=\"5; URL=" . site_url() . "\">";
        exit;
    }
}


?>