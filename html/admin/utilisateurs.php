<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_utilisateurs.php");

$titre_page = "Gérer les utilisateurs";


if (!isset($action)) $action = "";

switch($action):
case 'ajout':
    echo afficher_titre("Ajouter un utilisateur");
    echo saisir_parametres_utilisateur("0","ajout");
    break;
case 'modif':
    echo afficher_titre("Modifier un utilisateur");
    if (isset($id))
    {
        echo saisir_parametres_utilisateur($id,"modif");
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
    }
    break;
case 'suppr':
    echo afficher_titre("Supprimer un utilisateur");
    if (isset($id))
    {
        echo supprimer_utilisateur($id);
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
    }
    break;
case 'confajout':
    echo afficher_titre("Ajout d'un utilisateur");
    if(isset($nomutil) && isset($motpasse) && $nomutil != "" && $motpasse != "" && 
      isset($nom) && $nom != "" && isset($prenom) && $prenom != "" && isset($email) && $email != "") {
        $err = paniers_insertadmin();
        if($err != '') {
            echo afficher_message_erreur($err);
            echo saisir_parametres_utilisateur("0","ajout");
        } else {
            //$fonctions = implode(",",$tfonctions);
            if($superadmin) {
                $fonctions = $tab_roles["super-administrateur"];
            } else if($idproducteur > 0 && $iddepot > 0) {
                $fonctions = array_merge($tab_roles["producteur"], $tab_roles["depot"]);
            } else if($idproducteur > 0) {
                $fonctions = $tab_roles["producteur"];
            } else if($iddepot > 0) {
                $fonctions = $tab_roles["depot"];
            } else {
                $fonctions = $tab_roles["administrateur"];
            }

            mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_utilisateurs (nomutil,nom,prenom,motpasse,email,fonctions,idproducteur,iddepot,date) values ('$nomutil','$nom','$prenom','" . encode_password($motpasse) . "','$email','$fonctions','$idproducteur','$iddepot',now())");
            $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
            echo afficher_message_erreur("Utilisateur n° $last_id ajouté : $nomutil");
            ecrire_log_admin("Utilisateur n° $last_id ajouté : $nomutil");
            echo gerer_utilisateurs();
        }
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
        echo saisir_parametres_utilisateur("0","ajout");
    }
    break;
case 'confmodif':
    echo afficher_titre("Modification d'un utilisateur");
    if (isset($id) && isset($nomutil) && $nomutil != "" &&
        isset($nom) && $nom != "" && isset($prenom) && $prenom != "" && isset($email) && $email != "")
    {
        if($superadmin) {
            $fonctions = $tab_roles["super-administrateur"];
        } else if($idproducteur > 0 && $iddepot > 0) {
            $fonctions = $tab_roles["producteur"] . "," . $tab_roles["depot"];
        } else if($idproducteur > 0) {
            $fonctions = $tab_roles["producteur"];
        } else if($iddepot > 0) {
            $fonctions = $tab_roles["depot"];
        } else {
            $fonctions = $tab_roles["administrateur"];
        }

        $err = paniers_updateadmin();
        if($err != "") {
            echo afficher_message_erreur($err);
            echo saisir_parametres_utilisateur($id,"modif");
        }
        else {
            //$fonctions = implode(",",$tfonctions);
            mysqli_query($GLOBALS["___mysqli_ston"], "update $base_utilisateurs set nomutil='$nomutil',nom='$nom',prenom='$prenom'" . (isset($motpasse) && $motpasse != "" ? ",motpasse='" . encode_password($motpasse) . "'" : "") . ",email='$email',fonctions='$fonctions',idproducteur='$idproducteur',iddepot='$iddepot',date=now() where id='$id'");
            echo afficher_message_erreur("Utilisateur n° $id modifié : $nomutil");
            ecrire_log_admin("Utilisateur n° $id modifié : $nomutil");
            echo gerer_utilisateurs();
        }
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
        echo saisir_parametres_utilisateur($id,"modif");
    }
    break;
case 'confsuppr':
    echo afficher_titre("Suppression d'un utilisateur");
    if (isset($id))
    {
        paniers_removeadmin();
        mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_utilisateurs where id='$id' limit 1");
        echo afficher_message_erreur("Utilisateur n° $id supprimé : $nomutil");
        ecrire_log_admin("Utilisateur n° $id supprimé : $nomutil");
    }
    else
    {
        echo afficher_message_erreur("Paramètre manquant !");
    }
    echo gerer_utilisateurs();
    break;
default:
    echo afficher_titre("Gérer les utilisateurs");
    echo gerer_utilisateurs();
    break;
endswitch;

require_once("../include/admin/admin_footer.php");
?>