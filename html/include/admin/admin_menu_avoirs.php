<?php

if(!utilisateurIsAdmin()) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"./index.php\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=./index.php\">";
    exit;
}

echo html_debut_tableau("100%","0","2","0");
echo html_debut_ligne("","","","","","","");
echo html_debut_form("?action=ajout",false,"formajouter");
echo html_colonne("","","left","","","","",html_bouton_submit("Ajouter un avoir client"));
echo html_fin_form();
echo html_debut_form("?action=ajoutlivraison",false,"formajouterlivraison");
echo html_colonne("","","left","","","","",html_bouton_submit("Ajouter un avoir livraison annulée"));
echo html_fin_form();
echo html_debut_form("?action=filtrer",false,"formfiltrer");
echo html_colonne("34%","","center","","","","",
                  afficher_liste_periodes("idperiode",retrouver_periode_courante(),false) . 
                  html_bouton_submit("Lister les avoirs par période"));
echo html_fin_form();
if($action == "listerregle") {
    echo html_debut_form("?action=listerencours",false,"formlister");
    echo html_colonne("33%","","right","","","","",html_bouton_submit("Lister les avoirs encours"));
    echo html_fin_form();
} else {
    echo html_debut_form("?action=listerregle",false,"formlister");
    echo html_colonne("33%","","right","","","","",html_bouton_submit("Lister les avoirs réglés"));
    echo html_fin_form();
}
echo html_fin_ligne();
echo html_fin_tableau();
?>