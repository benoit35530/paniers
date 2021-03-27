<?php

require_once('../../../../wp-blog-header.php');
require_once("fonctions_include.php");
require_once("http_header.php");

$action = $wp_query->get("action");

if(!is_user_logged_in()) {
    $loginurl = wp_login_url($_SERVER['PHP_SELF']);
    header("Location: $loginurl");
    exit;
}

if($action == '') {
    global $url_page_consommateur, $url_page_gestionnaire;
    if(current_user_can('gestionnaire')) {
        header("Location: " . $url_page_gestionnaire);
    } else {
        header("Location: " . $url_page_consommateur);
    }
    exit;
}

function assignPageTitle() {
    return "Commandes Paniers | ";
}
add_filter('wp_title', 'assignPageTitle');

require_once("header.php");

if(!current_user_can('consommateur')) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"" . site_url() . "\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=" . site_url() . "\">";
    require_once("footer.php");
    exit;
}

?>