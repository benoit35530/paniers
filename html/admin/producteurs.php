<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_producteurs.php");

switch($action):

case "ajout":

    echo afficher_titre("Ajouter un producteur");
    echo formulaire_producteur("ajout");
    break;

case "confajout":

    echo afficher_titre("Ajout d'un producteur");
    $message = "";
    if(!isset($nom) || $nom == "") $message .= "nom du producteur manquant, ";
    if(!isset($email) || $email == "") $message .= "email du producteur manquant, ";
    if(!isset($produits) || $produits == "") $message .= "description des produits manquante";
    if(!isset($ordre) || $ordre == "") $message .= "ordre des producteur manquante";
    if(!isset($envoyerrecap)) $envoyerrecap = false;
    $nom = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $nom);
    $email = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $email);
    $paiement = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $paiement);
    $produits = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $produits);
    if($message != "")
    {
        echo afficher_message_erreur("Impossible d'ajouter ce producteur : " . $message);
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_producteurs (id,nom,email,envoyerrecap,telephone,paiement,produits,ordre,datemodif) values ('','$nom','$email','$envoyerrecap','$telephone', '$paiement','$produits','$ordre', now())");
        if(!$rep) {
            echo afficher_message_erreur("Impossible d'ajouter ce producteur : " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
        } else {
            $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
            echo afficher_message_info("Le producteur n° $last_id est ajouté");
            ecrire_log_admin("producteur n° $last_id ajouté : $nom");
        }
    }
    echo gerer_liste_producteurs();
    break;

case "modif":

    echo afficher_titre("Modifier un producteur");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_producteurs();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,email,envoyerrecap,telephone,paiement,produits,ordre from $base_producteurs where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            list($id,$nom,$email,$envoyerrecap,$telephone,$paiement,$produits,$ordre) = mysqli_fetch_row($rep);
            echo formulaire_producteur("modif",$id,$nom,$email,$envoyerrecap,$telephone,$paiement,$produits,$ordre);
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_producteurs();
        }
    }
    break;

case "confmodif":

    echo afficher_titre("Modification d'un producteur");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_producteurs();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,email,envoyerrecap,telephone,ordrecheque,produits,ordre from $base_producteurs where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $message = "";
            if (!isset($nom) || $nom == "") $message .= "nom du producteur manquant, ";
            if (!isset($email) || $email == "") $message .= "email du producteur manquant, ";
            if (!isset($produits) || $produits == "") $message .= "description des produits manquante";
            if (!isset($ordre) || $ordre == "") $message .= "ordre producteur manquante";
            if (!isset($envoyerrecap)) $envoyerrecap = false;
            if ($message != "")
            {
                echo afficher_message_erreur("Le producteur n° $id ne peut pas être modifié, erreur : " . $message);
                echo formulaire_producteur("modif",$id,$nom,$email,$envoyerrecap,$telephone,$paiement,$produits,$ordre);
            }
            else
            {
                $nom = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $nom);
                $paiement = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $paiement);
                $produits = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $produits);

                mysqli_query($GLOBALS["___mysqli_ston"], "update $base_producteurs set nom='$nom',email='$email',envoyerrecap='$envoyerrecap',telephone='$telephone', paiement='$paiement',produits='$produits',ordre='$ordre',datemodif=now() where id='$id'");
                ecrire_log_admin("Producteur n° $id modifié : $nom");
                echo afficher_message_info("Le producteur n° $id est modifié");
                echo gerer_liste_producteurs();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_producteurs();
        }
    }
    break;

case "suppr":

    echo afficher_titre("Supprimer un producteur");

    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_producteurs();
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,email,enoyerrecap,telephone,paiement,produits,ordre from $base_producteurs where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_produits where idproducteur = '$id'");
            if (mysqli_num_rows($rep0) == 0)
            {
                list($id,$nom,$email,$envoyerrecap,$telephone,$paiement,$produits,$ordre) = mysqli_fetch_row($rep);
                echo formulaire_producteur("suppr",$id,$nom,$email,$envoyerrecap,$telephone,$paiement,$produits,$ordre);
            }
            else
            {
                echo afficher_message_erreur("Le producteur n° $id ne peut pas être supprimé : il a encore des produits !!!");
                echo gerer_liste_producteurs();
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
            echo gerer_liste_producteurs();
        }
    }

    break;

case "confsuppr":

    echo afficher_titre("Suppression d'un producteur");

    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
    }
    else
    {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom from $base_producteurs where id = '$id'");
        if (mysqli_num_rows($rep) != 0)
        {
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_produits where idproducteur = '$id'");
            if (mysqli_num_rows($rep0) == 0)
            {
                list($id,$nom) = mysqli_fetch_row($rep);
                mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_producteurs where id='$id' limit 1");
                echo afficher_message_info("Producteur n° $id supprimé");
                ecrire_log_admin("Producteur n° $id supprimé : $nom");
            }
            else
            {
                echo afficher_message_erreur("Le producteur n° $id ne peut pas être supprimé : il a encore des produits !!!");
            }
        }
        else
        {
            echo afficher_message_erreur("Identificateur inconnu !!!");
        }
    }

    echo gerer_liste_producteurs();

    break;

case "modifetat":
    echo afficher_titre("Modification de l'état d'un producteur");
    if (!isset($id))
    {
        echo afficher_message_erreur("Identificateur manquant !!!");
        echo gerer_liste_producteurs();
    }
    else
    {
        mysqli_query($GLOBALS["___mysqli_ston"], "update $base_producteurs set etat='$etat',datemodif=now() where id='$id'");
        ecrire_log_admin("Producteur n° $id modifié : $etat");
        echo afficher_message_info("Le producteur n° $id est modifié");
        echo gerer_liste_producteurs();
    }
    break;

case 'filtrer':
    echo afficher_titre("Gestion des producteurs");
    echo gerer_liste_producteurs($filtre_etat);
    break;

default:

    echo afficher_titre("Gestion des producteurs");
    echo gerer_liste_producteurs();

    break;

    endswitch;

    require_once("../include/admin/admin_footer.php");
    ?>