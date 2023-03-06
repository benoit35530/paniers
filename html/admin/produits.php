<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_produits.php");

switch($action):

case "ajout":
    echo afficher_titre("Ajouter un produit");
    echo formulaire_produit("ajout");
    break;

case "confajout":
    echo afficher_titre("Ajout d'un produit");
    $message = "";
    if(!isset($nom) || $nom == "") $message .= "nom du produit manquant, ";
    if(!isset($description) || $description == "") $message .= "description du produit manquante, ";
    if(!isset($prix) || $prix == "") $message .= "prix du produit manquant, ";
    if(!isset($idproducteur) || $idproducteur == "" || $idproducteur == 0) {
        $idproducteur = obtenir_producteur_utilisateur();
        if($idproducteur <= 0) {
            $message .= "producteur du produit manquant";
        }
    }
    if($message != "") {
        echo afficher_message_erreur("Impossible d'ajouter ce produit : " . $message);
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_produits (id,nom,description,prix,idproducteur,datemodif) values ('','$nom','$description','$prix','$idproducteur',now())");
        $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
        echo afficher_message_info("Le produit n° $last_id est ajouté");
        ecrire_log_admin("Produit n° $last_id ajouté : $nom $prix");
    }
    echo gerer_liste_produits();
    break;

case "modif":
    echo afficher_titre("Modifier un produit");
    if(!isset($id)) {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_produits();
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,idproducteur from $base_produits where id = '$id'");
        if(mysqli_num_rows($rep) != 0)
        {
            list($id,$nom,$description,$prix,$idproducteur) = mysqli_fetch_row($rep);
            echo formulaire_produit("modif",$id,$nom,$description,$prix,$idproducteur);
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_produits();
        }
    }
    break;

case "confmodif":
    echo afficher_titre("Modification d'un produit");
    if(!isset($id)) {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_produits();
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,idproducteur from $base_produits where id = '$id'");
        if(mysqli_num_rows($rep) != 0) {
            $message = "";
            if(!isset($nom) || $nom == "") $message .= "nom du produit manquant, ";
            if(!isset($description) || $description == "") $message .= "description du produit manquante, ";
            if(!isset($prix) || $prix == "") $message .= "prix du produit manquant, ";
            if(!isset($idproducteur) || $idproducteur == "" || $idproducteur == 0) {
                $idproducteur = obtenir_producteur_utilisateur();
                if($idproducteur <= 0) {
                    $message .= "producteur du produit manquant";
                }
            }
            if($message != "")
            {
                echo afficher_message_erreur("Le produit n° $id ne peut pas être modifié, erreur : " . $message);
                echo formulaire_produit("modif",$id,$nom,$description,$prix,$idproducteur);
            }
            else
            {
                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_produits set nom='$nom',description='$description',prix='$prix',idproducteur='$idproducteur',datemodif=now() where id='$id'");
                ecrire_log_admin("Produit n° $id modifié : $nom $prix");
                echo afficher_message_info("Le produit n° $id est modifié");
                echo gerer_liste_produits();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_produits();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer un produit");

    if(!isset($id)) {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_produits();
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,idproducteur from $base_produits where id = '$id'");
        if(mysqli_num_rows($rep) != 0) {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where idproduit = '$id'");
            if(mysqli_num_rows($rep0) == 0) {
                list($id,$nom,$description,$prix,$idproducteur) = mysqli_fetch_row($rep);
                echo formulaire_produit("suppr",$id,$nom,$description,$prix,$idproducteur);
            }
            else {
                echo afficher_message_erreur("Le produit n° $id ne peut pas être supprimé : il a encore des commandes !!!");
                echo gerer_liste_produits();
            }
        }
        else {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_produits();
        }
    }

    break;

case "confsuppr":

    echo afficher_titre("Suppression d'un produit");
    if(!isset($id)) {
        echo afficher_message_erreur("Identificateur manquant !!!");
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom from $base_produits where id = '$id'");
        if(mysqli_num_rows($rep) != 0) {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where idproduit = '$id'");
            if(mysqli_num_rows($rep0) == 0) {
                list($id,$nom) = mysqli_fetch_row($rep);
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_produits where id='$id' limit 1");
                echo afficher_message_info("Produit n° $id supprimé");
                ecrire_log_admin("Produit n° $id supprimé : $nom");
            }
            else {
                echo afficher_message_erreur("Le produit n° $id ne peut pas être supprimé : il a encore des commandes !!!");
            }
        }
        else {
            echo afficher_message_erreur("Identificateur inconnu !!!");
        }
    }
    echo gerer_liste_produits();
    break;

case "modifetat":
    echo afficher_titre("Modification de l'état d'un produit");
    if(!isset($id)) {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_produits();
    }
    else {
        mysqli_query($GLOBALS["___mysqli_ston"], "update $base_produits set etat='$etat',datemodif=now() where id='$id'");
        ecrire_log_admin("Produit n° $id modifié : $etat");
        echo afficher_message_info("Le produit n° $id est modifié");
        echo gerer_liste_produits();
    }
    break;

default:
    echo afficher_titre("Gestion des produits");
    echo gerer_liste_produits();
    break;

endswitch;

require_once("../include/admin/admin_footer.php");
?>