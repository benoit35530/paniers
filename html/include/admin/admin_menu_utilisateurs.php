<?php
/* if(!utilisateurIsAdmin()) { */
/*     echo afficher_message_erreur("Cette fonction ne vous est pas accessible !"); */
/*     echo "<p align=\"center\"><a href=\"./index.php\">Cliquez ici pour retourner à l'accueil</a></p>"; */
/*     echo "<meta http-equiv=\"Refresh\" content=\"5; URL=./index.php\">"; */
/*     exit; */
/* } */

echo html_debut_tableau("100%","0","2","0");
echo html_debut_ligne("","","","","","","");
echo html_debut_form("?action=ajout",false,"formajouter");
echo html_colonne("","","left","","","","",html_bouton_submit("Ajouter un utilisateur"));
echo html_fin_form();
echo html_debut_form("?",false,"formlister");
echo html_colonne("","","right","","","","",html_bouton_submit("Lister les utilisateurs"));
echo html_fin_form();
echo html_fin_ligne();
echo html_fin_tableau();
?>