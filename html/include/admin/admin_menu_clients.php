<?php

if(!utilisateurIsAdmin() && obtenir_depot_utilisateur() == -1) {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"./index.php\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=./index.php\">";
    exit;
}

echo html_debut_tableau("100%","0","2","0");
echo html_debut_ligne("","","","","","","");
echo html_debut_form("?action=ajout",false,"formajouter");
echo html_colonne("","","left","","","","",html_bouton_submit("Ajouter un client"));
echo html_fin_form();
echo html_debut_form("?action=filtrer",false,"formfiltrer");
if(!isset($filtre_etat)) {
    $filtre_etat = "Actif";
}

if(obtenir_depot_utilisateur() == -1)  {
    $depots = afficher_liste_depots_et_tous("iddepot", $iddepot);
} else {
    $depots = "";
    $iddepot = obtenir_depot_utilisateur();
}

echo html_colonne("80%","","center","","","","", 
                  afficher_etats_client("filtre_etat", $filtre_etat, True) .
                  $depots .
                  html_bouton_submit("Filtrer"));
echo html_fin_form();
echo html_debut_form("?action=reset",false,"formreset");
echo html_colonne("","","right","","","","","
<script>
function resetCotisations() {
var r=confirm(\"Etes vous sûr de vouloir remettre à zéro les cotisations?\");
if(r==true) {
    document.formreset.setAttribute('action', '?action=reset');
    document.formreset.confirm.value = \"1898390928\";
    document.formreset.submit();
} else {
    document.formreset.setAttribute('action', '?');
    document.formreset.submit();
}
}
</script>
<input type=\"hidden\" name=\"confirm\"/>
<input type=\"submit\" value=\"Re-initialiser les cotisations\" onclick=\"resetCotisations()\" class=\"champsubmit\"/>");
echo html_fin_form();
echo html_fin_ligne();
echo html_fin_tableau();
?>