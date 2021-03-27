<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_periodes.php");

switch($action):

case "ajout":

    echo afficher_titre("Ajouter une période");
    echo formulaire_periode("ajout");
    break;

case "confajout":
case "confajoutboncde":

    echo afficher_titre("Ajout d'une période");
    $message = "";
    if(!isset($libelle) || $libelle == "") $message .= "libellé de la période manquant, ";
    if(!isset($datedebut) || $datedebut == "") $message .= "date de début de la période manquante, ";
    if(!isset($datefin) || $datefin == "") $message .= "date de fin de la période manquante, ";
    if(!isset($datecommande) || $datecommande == "") $message .= "date de commande de la période manquante ";
    if(!isset($etat) || $etat == "") $message .= "etat manquant ";
    if($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter cette période : " . $message);
    }
    else
    {
        $datedebut = dateinterne($datedebut);
        $datefin = dateinterne($datefin);
        $datecommande = dateinterne($datecommande);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_periodes (id,libelle,datedebut,datefin,datecommande,relancemail,etat,datemodif) values ('','$libelle','$datedebut','$datefin','$datecommande','non','$etat',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("La période n° $last_id est ajoutée");
        ecrire_log_admin("Période n° $last_id ajoutée : $libelle du $datedebut au $datefin");

        $i = 1;
        $idperiode = $last_id;
        for($i = 1; $i < count($datelivraison) + 1; $i++) {
            if(!isset($datelivraison[$i]) || $datelivraison[$i] == "") {
                continue;
            }

            $date = dateinterne($datelivraison[$i]);
            mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_dates (id,datelivraison,idperiode,datemodif) values ('','$date','$idperiode',now())");
            $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
            ecrire_log_admin("Date n° $last_id ajoutée : $date");

            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_producteurs where 1");
            while(list($idproducteur) = mysqli_fetch_row($rep0)) {
                if(!$producteurs[$i][$idproducteur]) {
                    mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_absences (iddate,idproducteur) values ('$last_id','$idproducteur')");
                }
            }

            if(isset($permanences[$i])) {
                foreach($permanences[$i] as $type => $checked) {
                    if($checked) {
                        $heuredebut = $tab_permanences_defauts[$type][0];
                        $heurefin = $tab_permanences_defauts[$type][1];
                        $nbparticipants = $tab_permanences_defauts[$type][2];
                        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanences (id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence,datemodif) values ('','$date','$heuredebut','$heurefin','$nbparticipants','0','$type',now())");
                        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
                        ecrire_log_admin("Permanence n° $last_id ajoutée");
                    }
                }
            }
        }
    }
    echo gerer_liste_periodes();
    break;

case "ajoutboncde":

    echo afficher_titre("Ajouter une période et dates");
    echo formulaire_periode_et_dates();
    break;

case "modif":

    echo afficher_titre("Modifier une période");
    if(!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_periodes();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,libelle,datedebut,datefin,datecommande,etat from $base_periodes where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            list($id,$libelle,$datedebut,$datefin,$datecommande,$etat) = mysqli_fetch_row($rep);
            echo formulaire_periode("modif",$id,$libelle,dateexterne($datedebut),dateexterne($datefin),
                                    dateexterne($datecommande),$etat);
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_periodes();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'une période");
    if(!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_periodes();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,libelle,datedebut,datefin,datecommande from $base_periodes where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if(!isset($libelle) || $libelle == "") $message .= "libellé de la période manquant, ";
            if(!isset($datedebut) || $datedebut == "") $message .= "date de début de la période manquante, ";
            if(!isset($datefin) || $datefin == "") $message .= "date de fin de la période manquante, ";
            if(!isset($datecommande) || $datecommande == "") $message .= "date de commande de la période manquante ";
            if(!isset($etat) || $etat == "") $message .= "etat manquant ";
            if($message != "")
            {
                echo afficher_message_erreur("La période n° $id ne peut pas être modifiée, erreur : " . $message);
                echo formulaire_periode("modif",$id,$libelle,$datedebut,$datefin,$datecommande,$etat);
            }
            else
            {
                $datedebut = dateinterne($datedebut);
                $datefin = dateinterne($datefin);
                $datecommande = dateinterne($datecommande);
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_periodes set libelle='$libelle',datedebut='$datedebut',datefin='$datefin',datecommande='$datecommande',etat='$etat',datemodif=now() where id='$id'");
                ecrire_log_admin("Période n° $id modifiée : $libelle du $datedebut au $datefin");
                echo afficher_message_info("La période n° $id est modifiée");
                echo gerer_liste_periodes();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_periodes();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer une période");

    if(!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_periodes();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,libelle,datedebut,datefin,datecommande,etat from $base_periodes where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_bons_cde where idperiode = '$id'");
            if(mysqli_num_rows($rep0) == 0)
            {
                list($id,$libelle,$datedebut,$datefin,$datecommande,$etat) = mysqli_fetch_row($rep);
                echo formulaire_periode("suppr",$id,$libelle,dateexterne($datedebut),dateexterne($datefin),dateexterne($datecommande),$etat);
            }
            else
            {
                echo afficher_message_erreur("La période n° $id ne peut pas être supprimée : elle a des commandes !!!");
                echo gerer_liste_periodes();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_periodes();
        }
    }

    break;

case "confsuppr":

    echo afficher_titre("Suppression d'une période");
    if(!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,libelle from $base_periodes where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_bons_cde where idperiode = '$id'");
            if(mysqli_num_rows($rep0) == 0)
            {
                list($id,$libelle) = mysqli_fetch_row($rep);
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode = '$id'");
                if(mysqli_num_rows($rep) != 0) {
                    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep)) {
                        mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_dates where id='$iddate' limit 1");
                        mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_absences where iddate='$iddate'");
                        if($supprpermanences) {
                            mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanenciers where idpermanence=(select id from $base_permanences where date='$datelivraison')");
                            mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanences where date='$datelivraison'");
                        }
                        ecrire_log_admin("Date n° $iddate supprimée");
                    }
                }
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_periodes where id='$id' limit 1");
                echo afficher_message_info("La période n° $id est supprimée");
                ecrire_log_admin("Période n° $id supprimée : $libelle");
            }
            else
            {
                echo afficher_message_erreur("La période n° $id ne peut pas être supprimée : elle a encore des commandes !!!");
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
        }
    }

    echo gerer_liste_periodes();

    break;

case "activer":
    mysqli_query($GLOBALS["___mysqli_ston"], "update $base_periodes set etat='Active' where id='$id' and etat='Preparation'");
    ecrire_log_admin("Période n° $id modifiée : etat Active");
    echo afficher_titre("Gestion des périodes");
    echo gerer_liste_periodes();
    break;

case "clore":
    mysqli_query($GLOBALS["___mysqli_ston"], "update $base_periodes set etat='Close' where id='$id' and etat='Active'");
    ecrire_log_admin("Période n° $id modifiée : etat Close");
    echo afficher_titre("Gestion des périodes");
    echo gerer_liste_periodes();
    break;

case "notification":
    echo notification_producteurs_form($id);
    break;

case "confnotification":
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode='$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep0))
    {
        $dates[$nbdates]['id'] = $iddate;
        $dates[$nbdates]['datelivraison'] = $datelivraison;
        $nbdates++;
    }
    $absences = retrouver_absences($idperiode);

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,email from $base_producteurs where etat = 'Actif'");
    $destsuccess = [];
    $destfailed = [];
    while(list($idproducteur,$email) = mysqli_fetch_row($rep0)) {
        $strdates = "";
        for ($i = 0; $i < $nbdates; $i++) {
            if(!isset($absences[$dates[$i]['id']]) || !$absences[$dates[$i]['id']][$idproducteur]) {
                $strdates .= "- " . dateexterne($dates[$i]['datelivraison']) . "\r\n";
            }
        }
        $message = str_replace("%LISTE_DATES%", $strdates, $mail_message);
        if(send_export_email($email, $mail_cc, $mail_subject, $message)) {
            $destsuccess[] = $email . " (producteur " . retrouver_producteur($idproducteur) . ")";
        } else {
            $destfailed[] = $email . " (producteur " . retrouver_producteur($idproducteur) . ")";
        }
    }

    $output = "<center>";
    if(count($destsuccess) > 0) {
        $output .= "<h1>Les emails ont été envoyées avec succès aux destinataires suivant:</h1>";
        foreach($destsuccess as $email) {
            $output .= "<br>" . $email;
        }
        if(count($destsuccess) > 0 && $mail_cc != '') {
            $output .= "<br>" . $mail_cc . " (CC:)";
        }
    }
    if(count($destfailed) > 0) {
        $output .= "<h1>L'envoie a échoué pour les destinataires suivant:</h1>";
        foreach($destfailed as $email) {
            $output .= "<br>" . $email;
        }
    }
    $output .= "<br><br><br><a href=\"periodes.php\">Retour</a>";
    $output .= "</center>";
    echo $output;
    break;

case "filtrer":
    echo afficher_titre("Gestion des périodes");
    echo gerer_liste_periodes($filtre_etat);
    break;

case "lister":
    echo afficher_titre("Gestion des périodes");
    echo gerer_liste_periodes("-1");
    break;

default:

    echo afficher_titre("Gestion des périodes");
    echo gerer_liste_periodes("-2");

    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>
