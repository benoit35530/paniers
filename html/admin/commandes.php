<?php
foreach($_POST as $k=>$v) $$k=$v;

require_once("../include/fonctions_include_admin.php");
require_once("../include/admin/admin_menu_commandes.php");

if(!isset($tri)) $tri = 0;

switch($action)
{
case "ajouter": {
    echo afficher_titre("Ajouter une commande");
    $champs["libelle"] = array("Choisissez le client et la période","*Client","*Période","");
    $champs["type"] = array("","libre","libre","submit");
    $champs["lgmax"] = array("","","","");
    $champs["taille"] = array("","","","");
    $champs["nomvar"] = array("","","","");
    $champs["valeur"] = array("",
                              afficher_liste_clients("idclient",0,True,True),
                              afficher_liste_periodes("idperiode",0,True)," Valider ");
    $champs["aide"] = array("","","","");
    echo saisir_enregistrement($champs,"?action=preparer","formcde",60,20,5,5,true);
}
break;

case "preparer": {
    if(isset($idclient,$idperiode) && $idclient != 0 && $idperiode != 0) {

        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idperiode='$idperiode' and idclient='$idclient'");
        if(mysqli_num_rows($rep) != 0) {
            list($id) = mysqli_fetch_row($rep);
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,idclient,iddepot from $base_bons_cde where id='$id'");
            if(mysqli_num_rows($rep) != 0) {
                list($idboncde,$idperiode,$idclient,$iddepot) = mysqli_fetch_row($rep);
                $qteproduit = retrouver_quantites_commande($id,$idperiode);
                echo afficher_titre("Modifier la commande n° : " . $idboncde . " pour " .
                                    retrouver_client($idclient,true)) . "<br>";
                echo afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"confmodifier",$id, $idclient);
            }
        }
        else {
            echo afficher_titre("Remplir un bon de commande pour " . retrouver_client($idclient,true));
            echo afficher_message_info(retrouver_periode($idperiode)) . "<br>";
            echo afficher_formulaire_bon_commande($idperiode,retrouver_depot_client($idclient),array(), "enregistrer",
                                                  0, $idclient);
        }
    } else {
        echo afficher_titre("Toutes les commandes");
        echo afficher_message_erreur("Il manque le n° de client et/ou la période de commande !!!");
        echo gerer_liste_commandes();
    }
}
break;

case "enregistrer": {
    if($valider == " Enregistrer ") {
        if(!isset($iddepot)) {
            $iddepot = retrouver_depot_client($idclient);
            if(retrouver_etat_depot($iddepot) != "Actif") {
                $iddepot = 0;
            }
        }

        if(isset($idperiode) && $idperiode != "" && $idperiode != 0 &&
           isset($idclient) && $idclient != "" && $idclient != 0 &&
           isset($iddepot) && $iddepot != "" && $iddepot != 0) {

            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idperiode='$idperiode' and idclient='$idclient'");
            if(mysqli_num_rows($rep) != 0) {
                echo afficher_titre("Ajout d'une commande");
                echo afficher_message_erreur("Commande déjà enregistrée pour ce client et cette periode !!!");
                echo gerer_liste_commandes();
            }
            else {
                $idboncommande = enregistrer_bon_commande($idperiode,$idclient,$iddepot);
                enregistrer_commande($idperiode,$qteproduit,$idboncommande,$idclient);
                $codeclient = retrouver_code_client($idclient);
                echo afficher_titre("Commande enregistrée sous le n° " . $codeclient . "-" . $idboncommande);
                echo afficher_message_info(retrouver_client($idclient,true) . " - " . retrouver_periode($idperiode)) .
                    "<br>";
                echo afficher_recapitulatif_commande($idboncommande);
                ecrire_log_admin("Commande enregistrée sous le n° " . $codeclient . "-" . $idboncommande);
            }
        }
        else if(!(isset($iddepot) && $iddepot != "" && $iddepot != 0)) {
            echo afficher_titre("Remplir un bon de commande pour " . retrouver_client($idclient,true));
            echo afficher_message_erreur("Il manque le dépôt");
            echo afficher_formulaire_bon_commande($idperiode, retrouver_depot_client($idclient),$qteproduit,
                                                  "enregistrer", 0,$idclient);
        }
        else {
            echo afficher_titre("Ajout d'une commande");
            echo afficher_message_erreur("Il manque le n° de client et/ou la période de commande !!!");
            echo gerer_liste_commandes();
        }
    }
    else {
        if(isset($idperiode) && $idperiode != "" && $idperiode != 0 &&
           isset($idclient) && $idclient != "" && $idclient != 0) {
            echo afficher_titre("Remplir un bon de commande pour " . retrouver_client($idclient,true));
            echo afficher_message_info(retrouver_periode($idperiode)) . "<br>";
            echo afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"enregistrer", 0,
                                                  $idclient);
        }
        else {
            echo afficher_titre("Ajout d'une commande");
            echo afficher_message_erreur("Il manque le n° de client et/ou la période de commande !!!");
            echo gerer_liste_commandes();
        }
    }
}
break;

case "modifier": {
    if(isset($id) && $id != "" && $id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,idclient,iddepot from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$idperiode,$idclient,$iddepot) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
            echo afficher_titre("Modifier la commande n° : " . $idboncde . " pour " . retrouver_client($idclient,true)) . "<br>";
            echo afficher_formulaire_bon_commande($idperiode, $iddepot, $qteproduit, "confmodifier", $id, $idclient);
        }
        else {
            echo afficher_titre("Modification d'une commande");
            echo afficher_message_erreur("Commande introuvable !!!");
            echo gerer_liste_commandes();
        }
    }
    else {
        echo afficher_titre("Modification d'une commande");
        echo afficher_message_erreur("Il manque le n° de commande !!!");
        echo gerer_liste_commandes();
    }
}
    break;

case "confmodifier": {
    if(!isset($id) || $id == "" || $id == 0) {
        echo afficher_titre("Modification d'une commande");
        echo afficher_message_erreur("Il manque le n° de commande !!!");
        echo gerer_liste_commandes();
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idclient,iddepot from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$idclient,$iddepotorig) = mysqli_fetch_row($rep);
            if($valider == " Enregistrer ") {
                if(!isset($iddepot)) {
                    $iddepot = retrouver_depot_client($idclient);
                    if(retrouver_etat_depot($iddepot) != "Actif") {
                        $iddepot = 0;
                    }
                }

                if(isset($idperiode) && $idperiode != "" && $idperiode != 0 &&
                   isset($iddepot) && $iddepot != "" && $iddepot != 0) {
                    if($iddepot != $iddepotorig) {
                        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_bons_cde set iddepot=$iddepot where id='$id'");
                    }
                    enregistrer_commande($idperiode,$qteproduit,$id,$idclient);
                    echo afficher_titre("La commande n° " . $idboncde . " pour " .
                                        retrouver_client($idclient,true) . " a été modifiée");
                    echo afficher_recapitulatif_commande($id);
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_bons_cde set datemodif=now() where id='$id'");
                    ecrire_log_admin("Commande n° " . $idboncde . " modifiée");
                }
                else if(isset($iddepot) && $iddepot != "" && $iddepot != 0) {
                    echo afficher_titre("Modification de la commande n° " . $idboncde);
                    echo afficher_message_erreur("Pas de période sélectionnée !!!");
                    echo gerer_liste_commandes();
                }
                else {
                    echo afficher_titre("Modifier un bon de commande pour " . retrouver_client($idclient,true));
                    echo afficher_message_erreur("Pas de dépôt sélectionné!");
                    echo afficher_formulaire_bon_commande($idperiode,retrouver_depot_client($idclient),
                                                          $qteproduit,"confmodifier",$id,$idclient);
                }
            }
            else {
                if(isset($idperiode) && $idperiode != "" && $idperiode != 0) {
                    echo afficher_titre("Modifier un bon de commande pour " . retrouver_client($idclient,true));
                    echo afficher_message_info(retrouver_periode($idperiode)) . "<br>";
                    echo afficher_formulaire_bon_commande($idperiode,$iddepot,$qteproduit,"confmodifier",$id,
                                                          $idclient);
                }
                else {
                    echo afficher_titre("Modification de la commande n° " . $idboncde);
                    echo afficher_message_erreur("Pas de période sélectionnée !!!");
                    echo gerer_liste_commandes();
                }
            }
        }
        else {
            echo afficher_titre("Modification d'une commande");
            echo afficher_message_erreur("Commande introuvable !!!");
            echo gerer_liste_commandes();
        }
    }
}
    break;

case "supprimer": {
    if(!isset($id) || $id == "" || $id == 0) {
        echo afficher_titre("Suppression d'une commande");
        echo afficher_message_erreur("Il manque le n° de commande !!!");
        echo gerer_liste_commandes();
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idperiode,idclient,etat,datemodif from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$idperiode,$idclient,$etat,$datemodif) = mysqli_fetch_row($rep);
            $champs["libelle"] = array("Commande n° $idboncde","Client","Période","Créée le","","");
            $champs["type"] = array("","afftext","afftext","afftext","submit","submit");
            $champs["lgmax"] = array("","","","","","");
            $champs["taille"] = array("","","","","","");
            $champs["nomvar"] = array("","","","","valider","valider");
            $champs["valeur"] = array("",retrouver_client($idclient,true),retrouver_periode($idperiode),dateheureexterne($datemodif)," Annuler "," Valider ");
            $champs["aide"] = array("","","","","","");
            echo afficher_titre("Suppression de la commande n° $idboncde");
            echo saisir_enregistrement($champs,"?action=confsupprimer&id=$id","formsupprimer",70,20,2,2,false);
        }
        else {
            echo afficher_titre("Suppression d'une commande");
            echo afficher_message_erreur("Commande introuvable !!!");
            echo gerer_liste_commandes();
        }
    }
}
    break;

case "confsupprimer": {
    if(!isset($id) || $id == "" || $id == 0) {
        echo afficher_titre("Suppression d'une commande");
        echo afficher_message_erreur("Il manque le n° de commande !!!");
    }
    else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde) = mysqli_fetch_row($rep);
            if($valider == " Valider ")
                {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_bons_cde where id='$id'");
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_commandes where idboncommande='$id'");
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where idboncommande='$id'");
                    echo afficher_titre("La commande n° $idboncde a été supprimée");
                    ecrire_log_admin("Commande n° $idboncde supprimée");
                }
            else {
                echo afficher_titre("Toutes les commandes");
            }
        }
        else {
            echo afficher_titre("Suppression d'une commande");
            echo afficher_message_erreur("Commande introuvable !!!");
        }
    }
    echo gerer_liste_commandes();
}
    break;

case "detail": {
    if(isset($id) && $id != "" && $id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde,idclient from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idboncde,$idclient) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
            echo afficher_titre("Détails de la commande n° " . $idboncde . " (" .
                                retrouver_client($idclient) . ")");
            echo afficher_recapitulatif_commande($id);
        }
        else {
            echo afficher_titre("Toutes les commandes");
            echo afficher_message_erreur("Commande introuvable !!!");
            echo gerer_liste_commandes();
        }
    }
    else {
        echo afficher_titre("Toutes les commandes");
        echo afficher_message_erreur("Il manque le n° de commande !!!");
        echo gerer_liste_commandes();
    }
}
    break;

case "filtrer":
    if($idperiode > 0) {
        echo afficher_titre("Les commandes pour la période : " . retrouver_periode($idperiode));
    } else {
        echo afficher_titre("Liste des commandes");
    }
    echo gerer_liste_commandes($tri,$idperiode,$iddepot,$action);
    break;

default: {
    echo afficher_titre("Les commandes pour les périodes non closes");
    echo gerer_liste_commandes($tri,$idperiode, isset($iddepot) ? $iddepot : 0);
}
    break;
}

require_once("../include/admin/admin_footer.php");
?>