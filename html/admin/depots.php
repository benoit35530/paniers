<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_depots.php");

switch($action):

case "ajout":
    echo afficher_titre("Ajouter un dépôt");
    echo formulaire_depot("ajout");
    break;

case "confajout":

    echo afficher_titre("Ajout d'un dépôt");
    $message = "";
    if (!isset($nom) || $nom == "") $message .= "nom du depot manquante, ";
    if (!isset($adresse) || $adresse == "") $message .= "adresse du depot manquante, ";
    if (!isset($telephone) || $telephone == "") $message .= "telephone du depot manquante, ";
    if (!isset($email) || $email == "") $message .= "email du depot manquante, ";
    if ($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter ce dépôt : " . $message);
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_depots (id,nom,adresse,telephone,email,datemodif) values ('','$nom','$adresse','$telephone','$email', now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("Le dépôt n° $last_id est ajouté");
        ecrire_log_admin("dépôt n° $last_id ajouté : $description $prix");
    }
    echo gerer_liste_depots();
    break;

case "modif":

    echo afficher_titre("Modifier un dépôt");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_depots();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,adresse,telephone,email from $base_depots where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$nom,$adresse,$telephone,$email) = mysqli_fetch_row($rep);
            echo formulaire_depot("modif",$id,$nom,$adresse,$telephone,$email);
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_depots();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'un depot");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_depots();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,adresse,telephone,email from $base_depots where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if (!isset($nom) || $nom == "") $message .= "nom du depot manquant,";
            if (!isset($adresse) || $adresse == "") $message .= "adresse du depot manquant, ";
            if (!isset($telephone) || $telephone == "") $message .= "telephone du depot manquant, ";
            if (!isset($email) || $email == "") $message .= "email du depot manquant, ";
            if ($message != "")
            {
                echo afficher_message_erreur("Le dépôt n° $id ne peut pas être modifié, erreur : " . $message);
                echo formulaire_depot("modif",$id,$description,$prix,$idproducteur);
            }
            else
            {
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_depots set nom='$nom',adresse='$adresse',telephone='$telephone',email='$email', datemodif=now() where id='$id'");
                ecrire_log_admin("dépôt n° $id modifié : $description $prix");
                echo afficher_message_info("Le dépôt n° $id est modifié");
                echo gerer_liste_depots();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_depots();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer un depot");

    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_depots();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,adresse,telephone,email from $base_depots where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_clients where iddepot = '$id'");
            if(mysqli_num_rows($rep0) == 0)
            {
                list($id,$nom,$adresse,$telephone,$email) = mysqli_fetch_row($rep);
                echo formulaire_depot("suppr",$id,$nom,$adresse,$telephone,$email);
            }
            else
            {
                echo afficher_message_erreur("Le dépôt n° $id ne peut pas être supprimé : il a encore des clients associés !!!");
                echo gerer_liste_depots();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_depots();
        }
    }

    break;

case "confsuppr":
    echo afficher_titre("Suppression d'un dépôt");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom from $base_depots where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_clients where iddepot = '$id'");
            if (mysqli_num_rows($rep0) == 0)
            {
                list($id,$description) = mysqli_fetch_row($rep);
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_depots where id='$id' limit 1");
                echo afficher_message_info("dépôt n° $id supprimé");
                ecrire_log_admin("dépôt n° $id supprimé : $description");
            }
            else
            {
                echo afficher_message_erreur("Le depot n° $id ne peut pas être supprimé : il a encore des clients associés !!!");
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
        }
    }
    echo gerer_liste_depots();
    break;

case "modifetat":
    echo afficher_titre("Modification de l'état d'un depot");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_depots();
    }
    else
    {
        mysqli_query($GLOBALS["___mysqli_ston"], "update $base_depots set etat='$etat',datemodif=now() where id='$id'");
        ecrire_log_admin("dépôt n° $id modifié : $etat");
        echo afficher_message_info("Le dépôt n° $id est modifié");
        echo gerer_liste_depots();
    }
    break;

default:
    echo afficher_titre("Gestion des dépôts");
    echo gerer_liste_depots();
    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>