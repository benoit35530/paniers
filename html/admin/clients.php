<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_clients.php");

$titre_page = "Gérer les clients";

if (!isset($tri)) $tri = 1;
if (!isset($action)) $action = "";

switch($action):

case 'ajout':
    echo afficher_titre("Ajouter un client");
    echo saisir_parametres_client("0","ajout");
    break;

case 'modif':
    echo afficher_titre("Modifier un client");
    if(isset($id))
    {
        echo saisir_parametres_client($id,"modif");
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
        echo gerer_clients($tri);
    }
    break;

case 'suppr':
    echo afficher_titre("Supprimer un client");
    if(isset($id))
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idclient = '$id'");
        if(mysqli_num_rows($rep) == 0)
        {
            echo supprimer_client($id);
        }
        else
        {
            echo afficher_message_erreur("Le client " . retrouver_client($id,true) . " a encore des commandes !");
            echo gerer_clients($tri);
        }
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
        echo gerer_clients($tri);
    }
    break;

case 'confajout':
    echo afficher_titre("Ajout d'un client");
    if(isset($codeclient,$motpasse,$nom,$prenom) && $codeclient != "" && $motpasse != "" && $nom != "" && $prenom != "")
    {
        $err = paniers_insertclient();
        if($err != "") {
            echo afficher_message_erreur($err);
            echo saisir_parametres_client("0","ajout");
        } else {
            mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_clients (codeclient,motpasse,nom,prenom,email,telephone,ville,iddepot,etat,derncnx,datemodif,cotisation) values ('$codeclient','" . encode_password($motpasse) . "','$nom','$prenom','$email','$telephone','$ville','$iddepot','$etat',now(),now(),'$cotisation')");
            $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
            add_user_meta(username_exists($codeclient), 'paniers_consommateurId', $last_id, true);
            echo afficher_message_erreur("Client n° $last_id ajouté : $codeclient $nom $prenom");
            ecrire_log_admin("Client n° $last_id ajouté : $codeclient $nom $prenom");
            echo gerer_clients($tri);
        }
    }
    else {
        echo afficher_message_erreur("Paramètre manquant !");
        echo saisir_parametres_client("0","ajout");
    }

    break;

case 'confmodif':
    echo afficher_titre("Modification d'un client");
    if(isset($id,$codeclient,$nom,$prenom) && $id != "" && $id != 0 &&
       $codeclient != "" && $nom != "" && $prenom != "") {
        $err = paniers_updateclient();
        if($err != "") {
            echo afficher_message_erreur($err);
            echo saisir_parametres_client($id,"modif");
        } else {
            mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set codeclient='$codeclient'" . (isset($motpasse) && $motpasse != "" ? ",motpasse='" . encode_password($motpasse) . "'" : "") . ",nom='$nom',prenom='$prenom',email='$email',telephone='$telephone',ville='$ville',iddepot='$iddepot',etat='$etat',datemodif=now(),cotisation='$cotisation' where id='$id'");

            $wpId = username_exists($codeclient);
            if(!get_user_meta($wpId, 'paniers_consommateurId', true)) {
                update_user_meta($wpId, 'paniers_consommateurId', $id, true);
            }

            echo afficher_message_erreur("Client n° $id modifié : $codeclient $nom $prenom");
            ecrire_log_admin("Client n° $id modifié : $codeclient $nom $prenom");
            echo gerer_clients($tri);
        }
    }
    else {
        echo afficher_message_erreur("Paramètre manquant !");
        echo saisir_parametres_client($id,"modif");
    }

    break;

case 'confsuppr':
    echo afficher_titre("Suppression d'un client");
    if(isset($id))
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idclient = '$id'");
        if(mysqli_num_rows($rep) == 0)
        {
            paniers_removeclient($id);
            mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_clients where id='$id' limit 1");
            echo afficher_message_erreur("Client n° $id supprimé");
            ecrire_log_admin("Client n° $id supprimé");
        }
        else {
            echo afficher_message_erreur("Le client " . retrouver_client($id,true) . " a encore des commandes !");
        }
    }
    else {
        echo afficher_message_erreur("Paramètre manquant !");
    }
    echo gerer_clients($tri);
    break;

case 'reset':
    if($confirm == "1898390928") {
        $iddepot = obtenir_depot_utilisateur();
        if($iddepot > 0) {
            mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set cotisation=0.0 where iddepot='$iddepot'");
        } else {
            mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set cotisation=0.0");
        }
    }
    echo afficher_titre("Gérer les clients");
    echo gerer_clients($tri);
    break;

case 'filtrer':
    echo afficher_titre("Gérer les clients");
    echo gerer_clients($tri, $iddepot, $filtre_etat, "filtrer");
    break;

default:
    echo afficher_titre("Gérer les clients");
    echo gerer_clients($tri);
    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>
