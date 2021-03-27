<?php

echo html_debut_tableau("100%","0","2","1");
echo html_debut_ligne();
echo html_colonne("","","center","","","","",html_lien(site_url(), "_top","Retour au site"),"","textepetit");
echo html_colonne("","","center","","","","",html_lien("./index.php","_top","Accueil"),"","textepetit");

$ses_fonctions = explode(',', obtenir_fonctions_utilisateur());

if($ses_fonctions != '')
{
    for ($i = 0; $i < count($ses_fonctions); $i++)
    {
        echo html_colonne("","","center","","","","",html_lien("./" . $ses_fonctions[$i] . ".php?","_top",$tab_fonctions[$ses_fonctions[$i]]),"",( $ses_fonctions[$i] . ".php" != basename($_SERVER['PHP_SELF']) ? "textepetit" : "textepetitinv" ));
    }
}
else
{
    echo afficher_message_erreur("Connexion refusée !");
    exit;
}
echo html_fin_ligne();
echo html_fin_tableau() . "<br>";
?>