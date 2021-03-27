<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_permanences.php");

switch($action):

case "ajout":

    echo afficher_titre("Ajouter une permanence");
    echo formulaire_permanence("ajout");
    break;

case "confajout":

    echo afficher_titre("Ajout d'une permanence");
    $message = "";
    if (!isset($date) || $date == "" || $date == "jj/mm/aaaa") $message .= "date de la permanence manquante ou incorrecte, ";
    if (!isset($heuredebut) || $heuredebut == "" || $heuredebut == "hh:mm") $message .= "heure de début manquante ou incorrecte, ";
    if (!isset($heurefin) || $heurefin == "" || $heurefin == "hh:mm") $message .= "heure de fin manquante ou incorrecte, ";
    if (!isset($nbparticipants) || $nbparticipants == "" || $nbparticipants == 0) $message .= "nombre de participants manquant ou incorrect";
    if ($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter cette permanence : " . $message);
    }
    else
    {
        $date = dateinterne($date);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanences (id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence,datemodif) values ('','$date','$heuredebut','$heurefin','$nbparticipants','0','$typepermanence',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("Permanence n° $last_id ajoutée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
        ecrire_log_admin("Permanence n° $last_id ajoutée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
    }
    echo gerer_liste_permanences();
    break;

case "modif":

    echo afficher_titre("Modifier une permanence");
    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanence manquant !!!");
        echo gerer_liste_permanences();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,typepermanence from $base_permanences where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$date,$heuredebut,$heurefin,$nbparticipants,$typepermanence) = mysqli_fetch_row($rep);
            echo formulaire_permanence("modif",$id,$date,$heuredebut,$heurefin,$nbparticipants,$typepermanence);
        }
        else
        {
            echo afficher_message_erreur("N° de permanence inconnu !!!");
            echo gerer_liste_permanences();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'une permanence");
    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanence manquant !!!");
        echo gerer_liste_permanences();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if (!isset($date) || $date == "" || $date == "jj/mm/aaaa") $message .= "date de la permanence manquante ou incorrecte, ";
            if (!isset($heuredebut) || $heuredebut == "" || $heuredebut == "hh:mm") $message .= "heure de début manquante ou incorrecte, ";
            if (!isset($heurefin) || $heurefin == "" || $heurefin == "hh:mm") $message .= "heure de fin manquante ou incorrecte, ";
            if (!isset($nbparticipants) || $nbparticipants == "" || $nbparticipants == 0) $message .= "nombre de participants manquant ou incorrect";
            $date = dateinterne($date);
            if ($message != "")
            {
                echo afficher_message_erreur("La permanence n° $id ne peut pas être modifiée, erreur : " . $message);
                echo formulaire_permanence("modif",$id,$date,$heuredebut,$heurefin,$nbparticipants,$typepermanence);
            }
            else
            {
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set date='$date',heuredebut='$heuredebut',heurefin='$heurefin',nbparticipants='$nbparticipants',typepermanence='$typepermanence',datemodif=now() where id='$id'");
                echo afficher_message_info("Permanence n° $id modifiée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
                ecrire_log_admin("Permanence n° $id modifiée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
                echo gerer_liste_permanences();
            }
        }
        else
        {
            echo afficher_message_erreur("N° de permanence inconnu !!!");
            echo gerer_liste_permanences();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer une permanence");

    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanence manquant !!!");
        echo gerer_liste_permanences();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$date,$heuredebut,$heurefin,$nbparticipants,$nbinscrits,$typepermanence) = mysqli_fetch_row($rep);
            if ($nbinscrits == 0)
            {
                echo formulaire_permanence("suppr",$id,$date,$heuredebut,$heurefin,$nbparticipants,$typepermanence);
            }
            else
            {
                echo afficher_message_erreur("La permanence n° $id ne peut pas être supprimée : elle a encore des permanenciers !!!");
                echo gerer_liste_permanences();
            }
        }
        else
        {
            echo afficher_message_erreur("N° de permanence inconnu !!!");
            echo gerer_liste_permanences();
        }
    }

    break;

case "confsuppr":

    echo afficher_titre("Suppression d'une permanence");

    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanence manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbinscrits from $base_permanences where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$date,$heuredebut,$heurefin,$nbinscrits) = mysqli_fetch_row($rep);
            if ($nbinscrits == 0)
            {
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanences where id='$id' limit 1");
                echo afficher_message_info("Permanence n° $id supprimée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
                ecrire_log_admin("Permanence n° $id supprimée : " . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]");
            }
            else
            {
                echo afficher_message_erreur("La permanence n° $id ne peut pas être supprimée : elle a encore des permanenciers !!!");
            }
        }
        else
        {
            echo afficher_message_erreur("N° de permanence inconnu !!!");
        }
    }

    echo gerer_liste_permanences();

    break;

case "lister":

    echo afficher_titre("Toutes les permanences");
    echo gerer_liste_permanences();

    break;

case "planning":
default:

    echo afficher_titre("Planning des permanences");
    echo afficher_planning_permanences(true);

    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>