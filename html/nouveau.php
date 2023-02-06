<?php
require('../../../../wp-blog-header.php');

$action = $wp_query->get("action");
$id = $wp_query->get("id");
$idperiode = $wp_query->get("idperiode");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST as $k=>$v) $$k=$v;
}

if($action == "imprimercde" && $valider == " Imprimer ")
{
    require_once("include/fonctions_include.php");
    require_once("include/http_header_exports.php");
    require_once("include/header_imprimer.php");

    $chaine = html_debut_tableau("40%", "0", "2", "0");
    $chaine .= html_debut_ligne("", "", "", "top");
    $chaine .= html_colonne("20%", "", "left", "", "", "", "", "Nom: ");
    $chaine .= html_colonne("", "", "left", "", "", "", "", $nom);
    $chaine .= html_fin_ligne();
    $chaine .= html_debut_ligne("", "", "", "top");
    $chaine .= html_colonne("", "", "left", "", "", "", "", "Prénom: ");
    $chaine .= html_colonne("", "", "left", "", "", "", "", $prenom);
    $chaine .= html_fin_ligne();
    $chaine .= html_debut_ligne("", "", "", "top");
    $chaine .= html_colonne("", "", "left", "", "", "", "", "Email: ");
    $chaine .= html_colonne("", "", "left", "", "", "", "", $email);
    $chaine .= html_fin_ligne();
    $chaine .= html_debut_ligne("", "", "", "top");
    $chaine .= html_colonne("", "", "left", "", "", "", "", "Téléphone: ");
    $chaine .= html_colonne("", "", "left", "", "", "", "", $telephone);
    $chaine .= html_fin_ligne();
    $chaine .= html_debut_ligne("", "", "", "top");
    $chaine .= html_colonne("", "", "left", "", "", "", "", "Ville: ");
    $chaine .= html_colonne("", "", "left", "", "", "", "", $ville);
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    $chaine .= "<br><br>";
    $chaine .= afficher_recapitulatif_commande2($idperiode,$iddepot,$qteproduit);
    $datetxt = afficher_date_prochaine_commande();
    afficher_corps_page("Nouveau consommateur", 
                        "(document à imprimer et apporter le " . datelitterale($datetxt, true) . " au local des paniers, avec le réglement par chèque du total à l'ordre de \"Paniers d'Eden\")", 
                        $chaine);
    require_once("include/footer_imprimer.php");
}
else
{
    require_once("include/fonctions_include.php");
    require_once("include/http_header.php");
    require_once("include/header.php");

    $idperiode = retrouver_periode_courante();
    if ($idperiode == 0 || !periode_active($idperiode)) {
        echo afficher_message_erreur($message_commande_nondisponible);
    } else {
        echo afficher_formulaire_bon_commande_nouveau_client($idperiode,
                                                             $qteproduit,
                                                             $nom, 
                                                             $prenom,
                                                             $email, 
                                                             $telephone, 
                                                             $ville);
    }
    require_once("include/footer.php");
}
?>