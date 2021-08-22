<?php

foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_avoirs.php");

switch($action):

case "ajout":
    echo afficher_titre("Ajouter un avoir client");
    $champs["libelle"] = array("Choisissez le client","*Client","Producteur","Montant", "Description", "");
    $champs["type"] = array("","libre","libre","text","textarea","submit");
    $champs["lgmax"] = array("","","","","","");
    $champs["taille"] = array("","40","40","10","60","");
    $champs["nomvar"] = array("","idclient","idproducteur","montant","description","");
    $champs["valeur"] = array("",afficher_liste_clients("idclient",0,True,True),
                              afficher_liste_producteurs("idproducteur",0,True), "0.0",""," Valider ");
    $champs["aide"] = array("","","Choisissez le producteur sur lequel sera déduit l'avoir. Ne choisissez pas de producteur pour affecter l'avoir sur le compte des paniers.", "", "","");
    echo saisir_enregistrement($champs,"?action=confajout","formavoirclient",60,20,5,5,true);
    break;
    
case "confajout":

    echo afficher_titre("Ajout d'un avoir client");
    $message = "";
    if(!isset($idclient) || $idclient == "" || $idclient == 0) $message .= "client manquant, ";
    if(!isset($montant)) $message .= "montant manquant, ";
    if($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter cet avoir : " . $message);
    }
    else
    {
        $description = addslashes($description);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_avoirs (id,idclient,idproducteur,montant,description,datemodif) values ('','$idclient','$idproducteur','$montant','$description',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("L'avoir n° $last_id est ajoutée");
        ecrire_log_admin("Avoir n° $last_id ajouté");
    }
    echo gerer_liste_avoirs(retrouver_periode_derniere());
    break;

case "ajoutlivraison":
    echo afficher_titre("Ajouter un avoir livraison annulée");
    $champs["libelle"] = array("Choisissez le producteur et la période","*Producteur","*Période", "Description", "");
    $champs["type"] = array("","libre","libre","textarea","submit");
    $champs["lgmax"] = array("","","","","");
    $champs["taille"] = array("","40","40","60","");
    $champs["nomvar"] = array("","idproducteur","idperiode","description","");
    $champs["valeur"] = array("",afficher_liste_producteurs("idproducteur"),afficher_liste_periodes("idperiode",0,True), ""," Valider ");
    $champs["aide"] = array("","","Choisissez le producteur et période de la livraison annulée. Des avoirs seront ajoutés pour chaque client ayant commandé.", "", "");
    echo saisir_enregistrement($champs,"?action=confajout","formavoirlivraison",60,20,5,5,true);
    break;
    
case "confajoutlivraison":

    echo afficher_titre("Ajout d'un avoir pour annulation de livraison");
    $message = "";
    if(!isset($idproducteur) || $idproducteur == "" || $idproducteur == 0) $message .= "producteur manquant, ";
    if(!isset($idperiode) || $idperiode == "" || $idperiode == 0) $message .= "période manquante, ";
    if($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter cet avoir : " . $message);
    }
    else
    {
        $description = addslashes($description);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_avoirs (id,idclient,idproducteur,montant,description,datemodif) values ('','$idclient','$idproducteur','$montant','$description',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("L'avoir n° $last_id est ajoutée");
        ecrire_log_admin("Avoir n° $last_id ajouté");
    }
    echo gerer_liste_avoirs(retrouver_periode_derniere());
    break;

case "modif":

    echo afficher_titre("Modifier un avoir");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_avoirs(retrouver_periode_derniere());
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idclient,idproducteur,idboncommande,montant,description from $base_avoirs " .
                           "where id = '$id'");
        if (mysqli_num_rows($rep) == 0) {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_avoirs();
        }
        else if($idboncommande != 0) {
            echo afficher_message_erreur("Cet avoir est déjà réglé !!!");
            echo gerer_liste_avoirs(True);
        }
        else {
            list($id,$idclient,$idproducteur,$idboncommande,$montant,$description) = mysqli_fetch_row($rep);
            echo formulaire_avoir("modif",$id,$idclient,$idproducteur,$montant,$description);
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'un avoir");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_avoirs(retrouver_periode_derniere());
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_avoirs where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if(!isset($montant) || $montant == "") $message .= "montant manquant, ";
            if($message != "")
            {
                echo afficher_message_erreur("La avoir n° $id ne peut pas être modifiée, erreur : " . $message);
                echo formulaire_avoir("modif",$id,$idclient,$idproducteur,$idboncommande,$montant,$description);
            }
            else
            {
                $description = addslashes($description);
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set montant='$montant',idproducteur='$idproducteur'," .
                            "idboncommande='$idboncommande',description='$description' where id='$id'");
                ecrire_log_admin("Avoir n° $id modifiée");
                echo afficher_message_info("L'avoir n° $id est modifiée");
                echo gerer_liste_avoirs();
            }
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_avoirs();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer un avoir");

    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_avoirs(retrouver_periode_derniere());
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idclient,idproducteur,idboncommande,montant,description from $base_avoirs " .
                           "where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$idclient,$idproducteur,$idboncommande,$montant,$description) = mysqli_fetch_row($rep);
            if($idboncommande == 0)
            {
                echo formulaire_avoir("suppr",$id,$idclient,$idproducteur,$idboncommande,$montant,$description);
            }
            else
            {
                echo afficher_message_erreur("L'avoir n° $id ne peut pas être supprimé: il est associé à une commande");
                echo gerer_liste_avoirs();
            }
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_avoirs();
        }
    }

    break;

case "confsuppr":
    echo afficher_titre("Suppression d'un avoir");
    if(!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_avoirs where id='$id' and idboncommande=0");
        if(mysqli_num_rows($rep) != 0)
        {
            mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_avoirs where id='$id' limit 1");
            echo afficher_message_info("L'avoir n° $id est supprimé");
            ecrire_log_admin("Avoir n° $id supprimé");
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
        }
    }
    echo gerer_liste_avoirs();
    break;

case "filtrer":
    echo afficher_titre("Avoirs période : " . retrouver_periode($idperiode));
    echo gerer_liste_avoirs_periode($idperiode);
    break;

case "listerregle":
    echo afficher_titre("Avoirs réglés");
    echo gerer_liste_avoirs(False);
    break;

case "listerencours":
default:
    echo afficher_titre("Avoirs en cours");
    echo gerer_liste_avoirs(True);
    echo "<br><br><br>";

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_periodes where etat != 'Close' order by datedebut desc");
    while(list($idperiode) = mysqli_fetch_row($rep))
    {
        if($idperiode > 0) {
            echo afficher_titre("Avoirs période : " . retrouver_periode($idperiode));
            echo gerer_liste_avoirs_periode($idperiode);
        }
    }
    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>