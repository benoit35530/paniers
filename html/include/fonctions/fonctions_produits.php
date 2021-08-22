<?php

function nombre_produits() {
    global $base_produits;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_produits where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_produit($cde="ajout",$id=0,$description="",$prix=0.0,$idproducteur=0) {
    global $g_lib_somme_admin;
    $libelle = $cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ");
    $type = $cde == "modif" || $cde == "ajout" ? "text" : "afftext";
    $button = $cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ");

    if(obtenir_producteur_utilisateur() == -1 && ($cde == "modif" || $cde == "ajout")) {
        $producteurtype = "libre";
        $producteurval = afficher_liste_producteurs("idproducteur",$idproducteur);
    } else {
        if($idproducteur == 0) {
            $idproducteur = obtenir_producteur_utilisateur();
        }
        $producteurtype = "afftext";
        $producteurval = retrouver_producteur($idproducteur);
    }

    $champs["libelle"] = array($libelle . "d'un produit","*Description","*Prix","*Producteur","");
    $champs["type"] = array("",$type,$type,$producteurtype,"submit");
    $champs["lgmax"] = array("","80","80","","");
    $champs["taille"] = array("","40","40","","");
    $champs["nomvar"] = array("","description","prix","idproducteur","");
    $champs["valeur"] = array("",$description,sprintf($g_lib_somme_admin,$prix),$producteurval,$button);
    $champs["aide"] = array("","Nom du produit","Prix du produit (avec un \".\" entre euros et centimes)","Producteur","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formproduit","70","20"));
}

function gerer_liste_produits() {
    global $base_produits,$base_producteurs,$base_commandes,$base_periodes,$g_lib_somme_admin;
    
    if(obtenir_producteur_utilisateur() > 0) {
        $filter_producteur = "id='" . obtenir_producteur_utilisateur() . "'";
    } else {
//        $filter_producteur = "1";
        $filter_producteur = "etat=Actif";
    }
    
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where $filter_producteur order by nom");
    $chaine = "";
    while(list($idproducteur,$nom,$produits) = mysqli_fetch_row($rep0))
    {
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,description,prix,idproducteur,datemodif,etat from $base_produits where idproducteur='$idproducteur' order by description");
        if ($rep1 && mysqli_num_rows($rep1) != 0)
        {
            $chaine .= html_debut_tableau("90%","0","2","0");
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","4",
                                    retrouver_producteur($idproducteur) . " - " . $produits,"","thliste");
            $chaine .= html_fin_ligne();
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("40%","","center","","","","","Description","","thliste");
            $chaine .= html_colonne("20%","","center","","","","","Prix","","thliste");
            $chaine .= html_colonne("20%","","center","","","","","Modifié le","","thliste");
            $chaine .= html_colonne("20%","","center","","","","","Action","","thliste");
            $chaine .= html_fin_ligne();
            while(list($id,$description,$prix,$idproducteur,$datemodif,$etat) = mysqli_fetch_row($rep1))
            {
                $chaine .= html_debut_ligne("","","","top");
                $chaine .= html_colonne("","","left","","","","","$description","","tdliste");
                $chaine .= html_colonne("","","right","","","","",sprintf($g_lib_somme_admin,$prix),"","tdliste");
                $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
                $action = html_lien("?action=modif&id=$id","_top","Modifier");
                $rep2 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_commandes where idproduit = '$id' limit 1");
                if(mysqli_num_rows($rep2) == 0) {
                    $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
                }

                $rep3 = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_commandes.id from $base_commandes " . 
                                    "inner join $base_periodes on $base_periodes.id=$base_commandes.idperiode ".
                                    "where idproduit='$id' and $base_periodes.etat != 'Close' limit 1");
                $commande_active = mysqli_num_rows($rep3) != 0;
                if($etat == "Actif") {
                    $desactiver = true;
                    $idperiode = retrouver_periode_courante();
                    if($idperiode > 0) {
                        $rep3 = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_commandes.id from $base_commandes " . 
                                            "inner join $base_periodes on $base_periodes.id=$base_commandes.idperiode ".
                                            "where idproduit='$id' and $base_periodes.etat != 'Close' limit 1");
                    }
                    if(!$commande_active) {
                        $action .= " | " . html_lien("?action=modifetat&id=$id&etat=Inactif", "_top", "Desactiver");
                    }
                }
                else  {
                    $action .= " | " .  html_lien("?action=modifetat&id=$id&etat=Actif", "_top", "Activer");
                }
                $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
                $chaine .= html_fin_ligne();

            }
            $chaine .= html_fin_tableau() . "<br><br>";
        }
        else
        {
            $chaine .= afficher_message_erreur("Aucun produit dans la base pour le producteur : " . retrouver_producteur($idproducteur)) . "<br><br>";

        }
    }
    return($chaine);
}

function retrouver_produit($id) {
    global $base_produits, $tab_produits;
    if(!isset($tab_produits)) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,description,prix,idproducteur from $base_produits where 1");
        while (list($idproduit,$description,$prix,$idproducteur) = mysqli_fetch_row($rep))
        {
            $tab_produits[$idproduit] = array($description,$prix,$idproducteur);
        }
    }
    if(!isset($tab_produits[$id])) {
        return "??? produit n° $id ???";
    }
    return $tab_produits[$id][0];
}

function afficher_liste_produits($nomvariable="idproduit",$defaut=0,$actifOnly = True) {
    global $base_produits,$g_lib_somme;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,description,prix from $base_produits where " . ($actifOnly ? "etat='Actif'" : "1")  . " order by idproducteur");
    while (list($id,$description,$prix) = mysqli_fetch_row($rep))
    {
        $texte .= "<option value=\"" . $id . "\"";
        if ($id == $defaut) $texte .= " selected";
        $texte .= ">" . $description . " (" . sprintf($g_lib_somme,$prix) . ")</option>\n";
    }
    $texte .= "</select>\n";
    return($texte);
}

function retrouver_parametres_produit($id) {
    global $tab_produits;
    $params['description'] = retrouver_produit($id);
    if(isset($tab_produits[$id])) {
        $params['prix'] = $tab_produits[$id][1];
        $param['idproducteur'] = $tab_produits[$id][2];
    } else {
        $params['prix'] = 0.0;
        $params['idproducteur'] = "0";
    }
    return($params);
}

function liste_produits($idproducteur) {
    global $base_produits;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select description,prix from $base_produits where idproducteur = $idproducteur and etat='Actif'");
    $produits = array();
    if (mysqli_num_rows($rep) > 0) {
        while (list($description, $prix) = mysqli_fetch_row($rep))
        {
            $produits[$description] = $prix;
        }
    }
    return $produits;
}

?>
