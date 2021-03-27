<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_stats.php");

$output = "";

switch($action):

case "producteurs":

    $output .= afficher_titre("Statistiques par producteur");
    $output .= stats_producteurs();
    ecrire_log_admin("Stats : par producteurs");
    break;

case "permanences":

    $output .= afficher_titre("Statistiques des permanences");
    $output .= stats_permanences();
    ecrire_log_admin("Stats : clients par permanences");
    break;

case "clients":

    $output .= afficher_titre("Statistiques par client");
    $output .= stats_clients();
    ecrire_log_admin("Stats : par client");
    break;

default:

    $output .= afficher_titre("Statistiques");

    $output .= html_debut_tableau("50%","0","2","0");

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","center","","","","","","","thliste");
    $output .= html_colonne("","","center","","","","","Nombre","","thliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de clients","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_clients(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre d'utilisateurs","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_utilisateurs(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de bons de commande","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_bons_commandes(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de lignes de commande","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_lignes_commandes(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de périodes de commande","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_periodes(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de dates de commande","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_dates(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de producteurs","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_producteurs(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de produits","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_produits(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de permanences","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_permanences(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_debut_ligne("","","","top");
    $output .= html_colonne("","","left","","","","","Nombre de permanenciers","","tdliste");
    $output .= html_colonne("","","center","","","","",nombre_permanenciers(),"","tdliste");
    $output .= html_fin_ligne();

    $output .= html_fin_tableau();

    break;

endswitch;

echo $output;
require_once("../include/admin/admin_footer.php");
?>