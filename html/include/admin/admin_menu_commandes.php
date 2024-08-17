<?php

if(!utilisateurIsAdmin()) {
    if($action != "" && $action != "detail" && $action != "filtrer") {
        echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
        echo "<p align=\"center\"><a href=\"./index.php\">Cliquez ici pour retourner à l'accueil</a></p>";
        echo "<meta http-equiv=\"Refresh\" content=\"5; URL=./index.php\">";
        exit;
    }
}

echo html_debut_tableau("100%","0","2","0");
echo html_debut_ligne("","","","","","","");

if(utilisateurIsAdmin()) {
    echo html_debut_form("?action=ajouter",false,"formajouter");
    echo html_colonne("5%","","left","","","","",html_bouton_submit("Ajouter une commande"));
    echo html_fin_form();
}

echo html_debut_form("?action=filtrer",false,"formfiltrer");
if(!isset($idperiode)) {
    $idperiode = "-2";
}

if(obtenir_depot_utilisateur() == -1 || obtenir_producteur_utilisateur() > 0)  {
    $depots = afficher_liste_depots_et_tous("iddepot");
    $iddepot = -1;
} else {
    $depots = "";
    $iddepot = obtenir_depot_utilisateur();
}

echo html_colonne("95%","","center","","","","",
                  afficher_liste_periodes("idperiode",$idperiode,false,true) .
                  $depots .
                  html_bouton_submit("Filtrer"));

echo html_fin_form();
echo html_fin_ligne();
echo html_fin_tableau();
?>