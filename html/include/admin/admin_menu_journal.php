<?php
if(!utilisateurIsAdmin()) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"./index.php\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=./index.php\">";
    exit;
}

echo html_debut_tableau("100%","0","2","0");
echo html_debut_ligne("","","","","","","");
echo html_debut_form("?",false,"formlister");
echo html_colonne("","","left","","","","",html_bouton_submit("Tout le journal"));
echo html_fin_form();
echo html_debut_form("?action=date",false,"formdate");
if (!isset($datedeb)) $datedeb = "0000-00-00";
if (!isset($datefin)) $datefin = "9999-99-99";
echo html_colonne("","","center","","","","",html_bouton_submit("Date entre") .  html_text_input("datedeb",$datedeb,"10","10") . " et " .  html_text_input("datefin",$datefin,"10","10"));
echo html_fin_form();
echo html_debut_form("?action=select",false,"formselect");
echo html_colonne("","","right","","","","",selectionner_utilisateur("nomutil","") . html_bouton_submit("Actions par utilisateur"));
echo html_fin_form();
echo html_fin_ligne();
echo html_fin_tableau();
?>