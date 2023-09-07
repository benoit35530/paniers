<?php
require('../../../../wp-blog-header.php');
require_once("include/fonctions_include_exports.php");
require_once("include/header_imprimer.php");

if (isset($id) && $id != "" && $id != 0)
{
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,iddepot from $base_bons_cde where id='$id'");
    if (mysqli_num_rows($rep) != 0)
    {
        list($idboncde,$idperiode,$iddepot) = mysqli_fetch_row($rep);
        $qteproduit = retrouver_quantites_commande($id,$idperiode);
        $PAGE_Titre = "Commande n° " . $idboncde . " - ";
        $PAGE_Titre .= retrouver_client(0, true) . "<br>";
        $PAGE_Titre .= retrouver_periode($idperiode);
        $PAGE_Contenu = afficher_recapitulatif_commande($id);
    }
    else
    {
        $PAGE_Titre = "Détails d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
    }
}
else
{
    $PAGE_Titre = "Détails d'une commande";
    $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
}

afficher_corps_page($PAGE_Titre,"", $PAGE_Contenu);
require_once("include/footer_imprimer.php");
?>