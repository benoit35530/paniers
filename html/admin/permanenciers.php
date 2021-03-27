<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_permanenciers.php");

switch($action):

case "ajout":

    echo afficher_titre("Ajouter un permanencier");
    echo formulaire_permanencier("ajout");
    break;

case "confajout":

    echo afficher_titre("Ajout d'un permanencier");
    $message = "";
    if (!isset($idpermanence) || $idpermanence == "" || $idpermanence == 0) $message .= "date de la permanence manquante ou incorrecte, ";
    if (!isset($idclient) || $idclient == "" || $idclient == 0) $message .= "client manquant ou incorrect, ";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where id='$idpermanence' and nbinscrits < nbparticipants");
    if (mysqli_num_rows($rep) == 0) $message .= "permanence introuvable ou complète";
    if ($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter ce permanencier : " . $message);
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanenciers (id,idpermanence,idclient,commentaire,datemodif) values ('','$idpermanence','$idclient','$commentaire',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits+1 where id='$idpermanence'");
        echo afficher_message_info("Permanencier n° $last_id ajouté : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
        ecrire_log_admin("Permanencier n° $last_id ajouté : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
    }
    echo gerer_liste_permanenciers();
    break;

case "modif":

    echo afficher_titre("Modifier un permanencier");
    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanencier manquant !!!");
        echo gerer_liste_permanenciers();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idpermanence,idclient,commentaire from $base_permanenciers where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$idpermanence,$idclient,$commentaire) = mysqli_fetch_row($rep);
            echo formulaire_permanencier("modif",$id,$idpermanence,$idclient,$commentaire);
        }
        else
        {
            echo afficher_message_erreur("N° de permanencier inconnu !!!");
            echo gerer_liste_permanenciers();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'un permanencier");
    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanencier manquant !!!");
        echo gerer_liste_permanenciers();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanenciers where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if (!isset($idpermanence) || $idpermanence == "" || $idpermanence == 0) $message .= "date de la permanence manquante ou incorrecte, ";
            if (!isset($idclient) || $idclient == "" || $idclient == 0) $message .= "client manquant ou incorrect, ";
            if ($message != "")
            {
                echo afficher_message_erreur("Le permanencier n° $id ne peut pas être modifié, erreur : " . $message);
                echo formulaire_permanencier("modif",$id,$idpermanence,$idclient,$commentaire);
            }
            else
            {
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanenciers set idpermanence='$idpermanence',idclient='$idclient',commentaire='$commentaire',datemodif=now() where id='$id'");
                echo afficher_message_info("Permanencier n° $id modifié : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
                ecrire_log_admin("Permanencier n° $id modifié : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
                echo gerer_liste_permanenciers();
            }
        }
        else
        {
            echo afficher_message_erreur("N° de permanencier inconnu !!!");
            echo gerer_liste_permanenciers();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer un permanencier");

    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanencier manquant !!!");
        echo gerer_liste_permanenciers();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idpermanence,idclient,commentaire from $base_permanenciers where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$idpermanence,$idclient,$commentaire) = mysqli_fetch_row($rep);
            echo formulaire_permanencier("suppr",$id,$idpermanence,$idclient,$commentaire);
        }
        else
        {
            echo afficher_message_erreur("N° de permanencier inconnu !!!");
            echo gerer_liste_permanenciers();
        }
    }

    break;

case "confsuppr":

    echo afficher_titre("Suppression d'un permanencier");

    if (!isset($id))
    {
        echo afficher_message_erreur("N° de permanencier manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idpermanence,idclient from $base_permanenciers where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$idpermanence,$idclient) = mysqli_fetch_row($rep);
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanenciers where id='$id' limit 1");
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits-1 where id='$idpermanence'");
            echo afficher_message_info("Permanencier n° $id supprimé : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
            ecrire_log_admin("Permanencier n° $id supprimé : " . retrouver_permanence($idpermanence) . " - " . retrouver_client($idclient));
        }
        else
        {
            echo afficher_message_erreur("N° de permanencier inconnu !!!");
        }
    }

    echo gerer_liste_permanenciers();

    break;

case "lister":

    echo afficher_titre("Tous les permanenciers");
    echo gerer_liste_permanenciers();

    break;

case "planning":
default:

    echo afficher_titre("Planning des permanences");
    echo afficher_planning_permanences(true);

    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>