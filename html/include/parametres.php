<?php

// URL de base du site
$url_base = paniers_plugin_url . "/";

global $wpdb;
global $paniers_dbprefix;
$paniers_dbprefix = $wpdb->prefix;

$paniers_data = get_option('paniers_data');

function get_paniers_option($paniers_data, $key, $default='') {
    if(array_key_exists($key, $paniers_data)) {
        return $paniers_data[$key];
    } else {
        return $default;
    }
}

$url_page_consommateur     = get_paniers_option($paniers_data, 'pageconsommateurs');
$url_page_gestionnaire     = get_paniers_option($paniers_data, 'pagegestionnaires');

$email_gestionnaires       = get_paniers_option($paniers_data, "adressegestionnaires");

$g_envoyer_relance         = get_paniers_option($paniers_data, "envoyerrelance") == "1";
$g_email_relance           = get_paniers_option($paniers_data, "adresserelance");
$g_delta_date_relance      = intval(get_paniers_option($paniers_data, 'deltarelance')); // delta  en jours avant envoi rappel
$g_delta_date_verrouillage = intval(get_paniers_option($paniers_data, 'deltaverrouillage')); // delta en jours avant verrouillage commandes

$message_commande_verouille     = get_paniers_option($paniers_data, "commandeverouille");
$message_commande_nondisponible = get_paniers_option($paniers_data, "commandenondisponible");

$jour_commande           = get_paniers_option($paniers_data, "jourcommande");
$periodicite_commande    = get_paniers_option($paniers_data, "periodicite");

// Noms des bases de données
$base_absences          = $paniers_dbprefix . "paniers_absences";
$base_avoirs            = $paniers_dbprefix . "paniers_avoirs";
$base_producteurs	    = $paniers_dbprefix . "paniers_producteurs";
$base_produits		    = $paniers_dbprefix . "paniers_produits";
$base_utilisateurs	    = $paniers_dbprefix . "paniers_baseutils";
$base_journal		    = $paniers_dbprefix . "paniers_journal";
$base_clients		    = $paniers_dbprefix . "paniers_clients";
$base_periodes		    = $paniers_dbprefix . "paniers_periodes";
$base_dates		        = $paniers_dbprefix . "paniers_dates";
$base_commandes		    = $paniers_dbprefix . "paniers_commandes";
$base_actualites	    = $paniers_dbprefix . "paniers_actualites";
$base_bons_cde		    = $paniers_dbprefix . "paniers_bons_cde";
$base_permanences	    = $paniers_dbprefix . "paniers_permanences";
$base_permanenciers	    = $paniers_dbprefix . "paniers_permanenciers";
$base_depots            = $paniers_dbprefix . "paniers_depots";

// Liste des mois de l'année
$liste_mois = array("Janvier", "Fevrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");

// Liste des jours de la semaine
$liste_jours = array("monday" => "Lundi",
                     "tuesday" => "Mardi",
                     "wednesday" => "Mercredi",
                     "thursday" => "Jeudi",
                     "friday" => "Vendredi",
                     "saturday" => "Samedi",
                     "sunday" => "Dimanche");

$liste_jours_abbr = array("D", "L", "M", "M", "J", "V", "S");

// Parametres gestion
$tab_fonctions = array("avoirs"=>"Avoirs",
                       "utilisateurs"=>"Utilisateurs",
                       "producteurs"=>"Producteurs",
                       "produits"=>"Produits",
                       "periodes"=>"Périodes",
                       "clients"=>"Clients",
                       "commandes"=>"Commandes",
                       "journal"=>"Journal",
                       "dates"=>"Dates",
                       "permanences"=>"Permanences",
                       "permanenciers"=>"Permanenciers",
                       "depots"=>"Dépôts",
                       "exports"=>"Exports",
                       "stats"=>"Statistiques");

$tab_roles = array("super-administrateur" => "avoirs,utilisateurs,producteurs,produits,periodes,clients,commandes,journal,dates,depots,exports,stats,permanences,permanenciers",
                   "administrateur" => "avoirs,producteurs,produits,periodes,clients,commandes,dates,depots,exports,stats,permanences,permanenciers",
                   "producteur" => "produits,commandes,exports",
                   "depot" => "clients,commandes,exports");

// Libellés des sommes
$g_lib_somme = "%.2f &euro;";
$g_lib_somme_admin = "%.2f";

// Table des types de permanences
$tab_types_permanences = array();
$tab_permanences_defauts = array();
foreach(explode(';', get_paniers_option($paniers_data, "permanences")) as $id => $permanence) {
    $permanence = explode(',', $permanence);
    if(count($permanence) > 1) {
        $tab_types_permanences[trim($permanence[0])] = trim($permanence[1]);
    }
    if(count($permanence) == 6) {
        $tab_permanences_defauts[trim($permanence[0])] = array(trim($permanence[2]),
                                                               trim($permanence[3]),
                                                               trim($permanence[4]),
                                                               trim($permanence[5]));
    }
}

// Table des états clients
$tab_etats_clients = array("Actif"=>"Actif",
                           "Inactif"=>"Inactif");

// Table des états periodes
$tab_etats_periodes = array("Preparation" => "En preparation",
                            "Active" => "Active",
                            "Close" => "Close");

// Table des états producteurs
$tab_etats_producteurs = array("Actif"=>"Actif", "Inactif"=>"Inactif");


$tab_villes_clients = explode(";", get_paniers_option($paniers_data, "villes"));

$g_ordrecheque = get_paniers_option($paniers_data, "ordrecheque");

// Couleurs des graphiques

$g_col_bar				= "#33CC33";				// couleur des barres des graphes
$g_col_axe				= "#FFCC33";				// couleur des axes des graphes
$g_col_grille			= "#CCCCCC";				// couleur de la grille
$g_col_text				= "#000000";				// couleur du texte
$g_col_fond				= "#FFFFFF";					// couleur de fond
$g_col_champ			= "#CCCCCC";				// couleur du fond des champs

?>