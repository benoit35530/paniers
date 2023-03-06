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
echo html_colonne("","","left","","","","",html_bouton_submit("Ajouter un producteur"));
echo html_fin_form();
echo html_debut_form("?action=filtrer",false,"formfiltrer");
if(!isset($filtre_etat)) {
    $filtre_etat = "Actif";
}
echo html_colonne("80%","","center","","","","",
                  afficher_etats_producteurs("filtre_etat", $filtre_etat, True) .
                  html_bouton_submit("Filtrer"));
echo html_fin_form();
echo html_debut_form("?",false,"formlister");
echo html_colonne("","","right","","","","",html_bouton_submit("Lister les producteurs"));
echo html_fin_form();
echo html_fin_ligne();
echo html_fin_tableau();

?>