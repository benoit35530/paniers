<?php

require_once("include/fonctions_include_public.php");

$titre_page = "Paniers d'Eden";
$PAGE_Titre = "";
$PAGE_Contenu = "";
$PAGE_Message = "";

$id = $wp_query->get("id");
$idperiode = $wp_query->get("idperiode");

$userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);
if(!$userid || $userid == "") {
    echo afficher_message_erreur("Cette fonction ne vous est pas accessible !");
    echo "<p align=\"center\"><a href=\"" . site_url() . "\">Cliquez ici pour retourner à l'accueil</a></p>";
    echo "<meta http-equiv=\"Refresh\" content=\"5; URL=" . site_url() . "\">";
    require_once("include/footer.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!empty($_POST['valider'])) {
        $valider =  $_POST['valider'];
    }
    if(!empty($_POST['qteproduit'])) {
        $qteproduit = $_POST['qteproduit'];
    }
    if(!empty($_POST["iddepot"])) {
        $iddepot = $_POST["iddepot"];
    }
}

switch($action):

case "ajoutercde":
    $idperiode = retrouver_periode_courante(true);
    if($idperiode == -1) {
        $PAGE_Contenu = afficher_message_erreur($message_commande_verouille);
    }
    else if($idperiode == 0 || !periode_active($idperiode)) {
        $PAGE_Contenu = afficher_message_erreur($message_commande_nondisponible);
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idboncde,iddepot from $base_bons_cde where idclient = '" . $userid . "' and idperiode='$idperiode'");
        if(mysqli_num_rows($rep) == 0) {
            $PAGE_Titre = "Créer une nouvelle commande";
            $PAGE_Contenu = afficher_formulaire_bon_commande($idperiode, retrouver_depot_client($userid),array(),
                                                             "enregistrercde",0,$userid);
        }
        else {
            list($id,$idboncde,$iddepot) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
            $PAGE_Titre = "Modifier la commande n° " . $idboncde;
            $PAGE_Contenu = afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"confmodifiercde",$id,
                                                             $userid);
        }
    }
break;

case "enregistrercde":
    if ($valider == " Enregistrer ") {
        if (isset($idperiode) && $idperiode != "" && $idperiode != 0 &&
            isset($iddepot) && $iddepot != "" && $iddepot != 0) {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idperiode='$idperiode' and idclient='$userid'");
            if(mysqli_num_rows($rep) != 0) {
                $PAGE_Titre = "Enregistrement d'une commande";
                $PAGE_Contenu = afficher_message_erreur("Commande déjà enregistrée");
            }
            else {
                $idboncommande = enregistrer_bon_commande($idperiode,$userid,$iddepot);
                enregistrer_commande($idperiode,$qteproduit,$idboncommande,$userid);
                $PAGE_Titre = "Commande enregistrée sous le n° C" . $userid . "-" . $idboncommande . " (" . html_lien("./imprimer.php?id=$idboncommande","_blank","l'imprimer") . ")";
                $PAGE_Contenu = afficher_recapitulatif_commande($idboncommande);
                ecrire_log_public("Commande enregistrée sous le n° C" . $userid . "-" . $idboncommande);
            }
        } else if(isset($idperiode) && $idperiode != "" && $idperiode != 0) {
            $PAGE_Contenu = afficher_message_erreur("Pas de dépôt sélectionné!");
            $PAGE_Contenu .= afficher_formulaire_bon_commande($idperiode,retrouver_depot_client($userid),$qteproduit,
                                                              "enregistrercde",0,$userid);
        } else {
            $PAGE_Titre = "Enregistrement d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Pas de période sélectionnée !!!");
        }
    }
    else {
        $PAGE_Titre = "Sélection des produits commandés";
        if (isset($idperiode) && $idperiode != "" && $idperiode != 0) {
            $PAGE_Contenu = afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"enregistrercde",0,
                                                             $userid);
        }
        else {
            $PAGE_Contenu = afficher_message_erreur("Pas de période sélectionnée !!!");
        }
    }
    break;

case "voircde":
    $PAGE_Titre = "Vos commandes passées et en cours";
    $PAGE_Contenu = lister_commandes($userid);
    break;

case "detaillercde":
    if(isset($id) && $id != "" && $id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,iddepot from $base_bons_cde where id='$id'");
        if (mysqli_num_rows($rep) != 0) {
            list($idboncde,$idperiode,$iddepot) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
            $PAGE_Titre = "Détails de la commande n° " . $idboncde . " (" .
                html_lien("./imprimer.php?id=$id","_blank","l'imprimer") . ")";
            $PAGE_Contenu = afficher_recapitulatif_commande($id);
        }
        else {
            $PAGE_Titre = "Détails d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
        }
    }
    else {
        $PAGE_Titre = "Détails d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
    }
    break;

case "modifiercde":
    if (isset($id) && $id != "" && $id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,iddepot from $base_bons_cde where id='$id'");
        if (mysqli_num_rows($rep) != 0) {
            list($idboncde,$idperiode,$iddepot) = mysqli_fetch_row($rep);
            if(!periode_active($idperiode)) {
                $PAGE_Contenu = afficher_message_erreur("Cette commande n'est plus modifiable!");
            } else {
                $qteproduit = retrouver_quantites_commande($id,$idperiode, $iddepot);
                $PAGE_Titre = "Modifier la commande n° " . $idboncde;
                $PAGE_Contenu = afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"confmodifiercde",$id,
                                                                 $userid);
            }
        }
        else {
            $PAGE_Titre = "Modification d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
        }
    }
    else {
        $PAGE_Titre = "Modification d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
    }
    break;

case "confmodifiercde":
    if(!isset($id) || $id == "" || $id == 0) {
        $PAGE_Titre = "Modification d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
    }
    else if(!periode_active($idperiode)) {
        $PAGE_Titre = "Modification d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Cette commande n'est plus modifiable!");
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,iddepot from $base_bons_cde where id='$id'");
        if (mysqli_num_rows($rep) != 0) {
            list($idboncde,$iddepotorig) = mysqli_fetch_row($rep);
            if($valider == " Enregistrer ") {
                if (isset($idperiode) && $idperiode != "" && $idperiode != 0 &&
                    isset($iddepot) && $iddepot != "" && $iddepot != 0) {
                    if($iddepotorig != $iddepot) {
                        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_bons_cde set iddepot=$iddepot where id='$id'");
                    }
                    enregistrer_commande($idperiode,$qteproduit,$id,$userid);
                    $PAGE_Titre = "La commande n° " . $idboncde . " a été modifiée";
                    $PAGE_Contenu = afficher_recapitulatif_commande($id);
                    ecrire_log_public("Commande n° " . $idboncde . " modifiée");
                }
                else if(!(isset($iddepot) && $iddepot != "" && $iddepot != 0)) {
                    $PAGE_Contenu = afficher_message_erreur("Pas de dépôt sélectionné!");
                    $PAGE_Contenu .= afficher_formulaire_bon_commande($idperiode,retrouver_depot_client($userid),
                                                                      $qteproduit,"confmodifiercde",$id,$userid);
                }
                else {
                    $PAGE_Titre = "Modification de la commande n° " . $idboncde;
                    $PAGE_Contenu = afficher_message_erreur("Pas de période sélectionnée !!!");
                }
            }
            else
            {
                $PAGE_Titre = "Modification de la commande n° $idboncde";
                if(isset($idperiode) && $idperiode != "" && $idperiode != 0) {
                    $PAGE_Contenu = afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,
                                                                     "confmodifiercde",$id,$userid);
                }
                else {
                    $PAGE_Contenu = afficher_message_erreur("Pas de période sélectionnée !!!");
                }
            }
        }
        else {
            $PAGE_Titre = "Modification d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
        }
    }
    break;

case "supprimercde":
    if (!isset($id) || $id == "" || $id == 0) {
        $PAGE_Titre = "Suppression d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,etat,datemodif from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$idperiode,$etat,$datemodif) = mysqli_fetch_row($rep);
            $champs["libelle"] = array("Commande n° $idboncde","Période","Créée le","","");
            $champs["type"] = array("","afftext","afftext","submit","submit");
            $champs["lgmax"] = array("","","","","");
            $champs["taille"] = array("","","","","");
            $champs["nomvar"] = array("","","","valider","valider");
            $champs["valeur"] = array("",retrouver_periode($idperiode),dateheureexterne($datemodif)," Annuler "," Valider ");
            $champs["aide"] = array("","","");
            $PAGE_Titre = "Suppression de la commande n° $idboncde";
            $PAGE_Contenu = saisir_enregistrement($champs,"?action=confsupprimercde&id=$id","formsupprimer",70,20,2,2,false);
        }
        else {
            $PAGE_Titre = "Suppression d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
        }
    }
    break;

case "confsupprimercde":
    if (!isset($id) || $id == "" || $id == 0) {
        $PAGE_Titre = "Suppression d'une commande";
        $PAGE_Contenu = afficher_message_erreur("Il manque le n° de commande !!!");
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,etat from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$etat) = mysqli_fetch_row($rep);
            if ($valider == " Valider ")
            {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_bons_cde where id='$id'");
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_commandes where idboncommande='$id'");
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where idboncommande='$id'");
                $PAGE_Titre = "La commande n° $idboncde a été supprimée";
                ecrire_log_public("Commande n° $idboncde supprimée");
            }
            else
            {
                $PAGE_Titre = "Vos commandes passées et en cours";
            }
            $PAGE_Contenu = lister_commandes($userid);
        }
        else {
            $PAGE_Titre = "Suppression d'une commande";
            $PAGE_Contenu = afficher_message_erreur("Commande introuvable !!!");
        }
    }
    break;

case "planning":
    $PAGE_Titre = "Planning des permanences";
    $PAGE_Contenu = afficher_planning_permanences(false,$userid);
    break;

case "confinscrire":
case "inscrire":
    if (isset($id) && $id != "" && $id != 0)
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nbparticipants,nbinscrits from $base_permanences where id='$id' and date >= curdate()");
        if (mysqli_num_rows($rep) != 0)
        {
            list($nbparticipants,$nbinscrits) = mysqli_fetch_row($rep);
            if ($nbinscrits < $nbparticipants)
            {
                if (verifier_non_inscription($id,$userid))
                {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanenciers (id,idpermanence,idclient,commentaire,datemodif) values ('','$id','" . $userid . "','',now())");
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits+1 where id='$id'");
                    $PAGE_Titre = "Inscription à une permanence";
                    $PAGE_Message = "Merci de vous être inscrit à cette permanence";
                    $PAGE_Contenu = afficher_planning_permanences(false,$userid);
                    ecrire_log_public("Inscription à la permanence : " . retrouver_permanence($id));
                }
                else
                {
                    $PAGE_Titre = "Inscription à une permanence";
                    $PAGE_Message = "Vous êtes déjà inscrit à cette permanence !";
                    $PAGE_Contenu = afficher_planning_permanences(false,$userid);
                }
            }
            else
            {
                $PAGE_Titre = "Inscription à une permanence";
                $PAGE_Message = "Cette permanence est déjà complète !";
                $PAGE_Contenu = afficher_planning_permanences(false,$userid);
            }
        }
        else
        {
            $PAGE_Titre = "Inscription à une permanence";
            $PAGE_Message = "Cette permanence est inconnue ou passée !";
            $PAGE_Contenu = afficher_planning_permanences(false,$userid);
        }
    }
    else
    {
        $PAGE_Titre = "S'inscrire à une permanence";
        $PAGE_Contenu = choisir_permanence($userid);
    }
    break;

case "desinscrire":
    if (isset($id) && $id != "" && $id != 0)
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where id='$id' and date >= curdate()");
        if (mysqli_num_rows($rep) != 0)
        {
            if (!verifier_non_inscription($id,$userid))
            {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanenciers where idpermanence='$id' and idclient='" . $userid . "' limit 1");
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits-1 where id='$id'");
                $PAGE_Titre = "Désinscription d'une permanence";
                $PAGE_Message = "Vous êtes désinscrit de cette permanence";
                $PAGE_Contenu = afficher_planning_permanences(false,$userid);
                ecrire_log_public("Désinscription de la permanence : " . retrouver_permanence($id));
            }
            else
            {
                $PAGE_Titre = "Désinscription d'une permanence";
                $PAGE_Message = "Vous n'êtes pas inscrit à cette permanence !";
                $PAGE_Contenu = afficher_planning_permanences(false,$userid);
            }
        }
        else
        {
            $PAGE_Titre = "Désinscription d'une permanence";
            $PAGE_Message = "Cette permanence est inconnue ou passée !";
            $PAGE_Contenu = afficher_planning_permanences(false,$userid);
        }
    }
    else
    {
        $PAGE_Titre = "Désinscription d'une permanence";
        $PAGE_Message = "Il manque le n° de la permanence !";
        $PAGE_Contenu = afficher_planning_permanences(false,$userid);
    }
    break;

case "accueil":
default:
exit;

endswitch;

afficher_corps_page($PAGE_Titre,$PAGE_Message,$PAGE_Contenu);
require_once("include/footer.php");

?>