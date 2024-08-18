<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_dates.php");

global $tab_permanences_defauts;

switch($action):

case "ajout":

    echo afficher_titre("Ajouter une date");
    echo formulaire_date("ajout");
    break;

case "confajout":

    echo afficher_titre("Ajout d'une date");
    $message = "";
    if (!isset($datelivraison) || $datelivraison == "") $message .= "date de début de la date manquante, ";
    if (!isset($idperiode) || $idperiode == "" || $idperiode == 0) $message .= "id de période manquant, ";
    if ($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter cette date : " . $message);
    }
    else
    {
        $datelivraison = dateinterne($datelivraison);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_dates (id,datelivraison,idperiode,datemodif) values ('','$datelivraison','$idperiode',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_producteurs where 1");
        while(list($idproducteur) = mysqli_fetch_row($rep0))
        {
            if(!array_key_exists($idproducteur, $producteurs) || !$producteurs[$idproducteur])
            {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_absences (iddate,idproducteur) values ('$last_id','$idproducteur')");
            }
        }
        if(isset($permanences)) {
            foreach($permanences as $type => $checked) {
                if($checked) {
                    $heuredebut = $tab_permanences_defauts[$type][0];
                    $heurefin = $tab_permanences_defauts[$type][1];
                    $nbparticipants = $tab_permanences_defauts[$type][2];
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanences (id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence,datemodif) values ('','$datelivraison','$heuredebut','$heurefin','$nbparticipants','0','$type',now())");
                    $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
                    echo afficher_message_info("La permanence n° $last_id est ajoutée");
                }
            }
        }
        echo afficher_message_info("La date n° $last_id est ajoutée");
        ecrire_log_admin("Date n° $last_id ajoutée : $datelivraison");
    }
    echo gerer_liste_dates();
    break;

case "modif":

    echo afficher_titre("Modifier une date");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_dates();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison,idperiode from $base_dates where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$datelivraison,$idperiode) = mysqli_fetch_row($rep);
            echo formulaire_date("modif",$id,dateexterne($datelivraison),$idperiode);
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_dates();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'une date");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_dates();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison,idperiode from $base_dates where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if (!isset($datelivraison) || $datelivraison == "") $message .= "date de début de la date manquante, ";
            if (!isset($idperiode) || $idperiode == "" || $idperiode == 0) $message .= "id de période manquant, ";
            if ($message != "")
            {
                echo afficher_message_erreur("La date n° $id ne peut pas être modifiée, erreur : " . $message);
                echo formulaire_date("modif",$id,$datelivraison,$idperiode);
            }
            else
            {
                $datelivraison = dateinterne($datelivraison);
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_dates set datelivraison='$datelivraison',idperiode='$idperiode',datemodif=now() where id='$id'");
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_absences where iddate='$id'");
                $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_producteurs where 1");
                while(list($idproducteur) = mysqli_fetch_row($rep0))
                {
                    if(!array_key_exists($idproducteur, $producteurs) || !$producteurs[$idproducteur])
                    {
                        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_absences (iddate,idproducteur) values ('$id','$idproducteur')");
                    }
                }
                ecrire_log_admin("Date n° $id modifiée : $datelivraison");
                echo afficher_message_info("La date n° $id est modifiée");
                echo gerer_liste_dates();
            }
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_dates();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer une date");

    if(!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_dates();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison,idperiode from $base_dates where id='$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where iddatelivraison = '$id'");
            if (mysqli_num_rows($rep0) == 0)
            {
                list($id,$datelivraison,$idperiode) = mysqli_fetch_row($rep);
                echo formulaire_date("suppr",$id,dateexterne($datelivraison),$idperiode);
            }
            else
            {
                echo afficher_message_erreur("La date n°$id ne peut pas être supprimée : elle a encore des commandes !!!");
                echo gerer_liste_dates();
            }
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_dates();
        }
    }

    break;

case "detail":

    echo afficher_titre("Détails d'une date");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
        echo gerer_liste_dates();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison,idperiode from $base_dates where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$datelivraison,$idperiode) = mysqli_fetch_row($rep);
            echo formulaire_date("detail",$id,dateexterne($datelivraison),$idperiode);
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
            echo gerer_liste_dates();
        }
    }
    break;

case "confsuppr":

    echo afficher_titre("Suppression d'une date");

    if(!isset($id))
    {
        echo afficher_message_erreur("Identifiant manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where id='$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where iddatelivraison = '$id'");
            if(mysqli_num_rows($rep0) == 0)
            {
                list($id,$datelivraison) = mysqli_fetch_row($rep);
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_dates where id='$id' limit 1");
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_absences where iddate='$id'");
                echo afficher_message_info("La date n° $id est supprimée");
                ecrire_log_admin("Date n° $id supprimée : $datelivraison");
            }
            else
            {
                echo afficher_message_erreur("La date n° $id ne peut pas être supprimée : elle a encore des commandes !!!");
            }
        }
        else
        {
            echo afficher_message_erreur("Identifiant inconnu !!!");
        }
    }

    echo gerer_liste_dates();

    break;

case "filtrer":
    if($idperiode > 0) {
        echo afficher_titre("Les dates pour la période : " . retrouver_periode($idperiode));
    } else {
        echo afficher_titre("Liste des dates");
    }
    echo gerer_liste_dates($idperiode);
    break;

default:
    echo afficher_titre("Les dates pour les périodes non closes");
    echo gerer_liste_dates();
    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>