<?php

function nombre_produits() {
    global $base_produits;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_produits where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_produit($cde="ajout",$id=0,$nom="",$description="",$prix=0.0,$idproducteur=0) {
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

    $nom = htmlspecialchars($nom,ENT_QUOTES);
    $description = htmlspecialchars($description,ENT_QUOTES);

    $champs["libelle"] = array($libelle . "d'un produit","*Nom", "*Description","*Prix","*Producteur","");
    $champs["type"] = array("",$type,"textarea",$type,$producteurtype,"submit");
    $champs["lgmax"] = array("","80","5", "80","","");
    $champs["taille"] = array("","40","80","40","","");
    $champs["nomvar"] = array("","nom", "description","prix","idproducteur","");
    $champs["valeur"] = array("",$nom,$description,sprintf($g_lib_somme_admin,$prix),$producteurval,$button);
    $champs["aide"] = array("","Nom du produit","Description","Prix du produit (avec un \".\" entre euros et centimes)","Producteur","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formproduit","70","20"));
}

function gerer_liste_produits() {
    global $base_produits,$base_producteurs,$base_commandes,$base_periodes,$g_lib_somme_admin;

    if(obtenir_producteur_utilisateur() > 0) {
        $filter_producteur = "id='" . obtenir_producteur_utilisateur() . "'";
    } else {
        $filter_producteur = "etat='Actif'";
    }

    $produits_commandes = array();
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproduit from $base_commandes WHERE 1 group by idproduit");
    while(list($idproduit) = mysqli_fetch_row($rep0))
    {
        $produits_commandes[$idproduit] = $idproduit;
    }

    $produits_commande_sur_periode = array();
    $rep3 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproduit from $base_commandes " .
        "inner join $base_periodes on $base_periodes.id=$base_commandes.idperiode ".
        "where $base_periodes.etat != 'Close' group by idproduit");
    while(list($idproduit) = mysqli_fetch_row($rep3))
    {
        $produits_commande_sur_periode[$idproduit] = $idproduit;
    }

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where $filter_producteur order by ordre,nom");
    $chaine = "";
    while(list($idproducteur,$nom,$produits) = mysqli_fetch_row($rep0))
    {
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,idproducteur,datemodif,etat from $base_produits where idproducteur='$idproducteur' order by nom");
        if ($rep1 && mysqli_num_rows($rep1) != 0)
        {
            $chaine .= html_debut_tableau("90%","0","2","0");
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","5",
                                    retrouver_producteur($idproducteur) . " - " . $produits,"","thliste");
            $chaine .= html_fin_ligne();
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("10%","","center","","","","","Action","","thliste");
            $chaine .= html_colonne("20%","","center","","","","","Nom","","thliste");
            $chaine .= html_colonne("40%","","center","","","","","Description","","thliste");
            $chaine .= html_colonne("10%","","center","","","","","Prix","","thliste");
            $chaine .= html_colonne("20%","","center","","","","","Modifié le","","thliste");
            $chaine .= html_fin_ligne();
            while(list($id,$nom,$description,$prix,$idproducteur,$datemodif,$etat) = mysqli_fetch_row($rep1))
            {
                $chaine .= html_debut_ligne("","","","top");
                $action = html_lien("?action=modif&id=$id","_top","Modifier");
                if(!array_key_exists($id, $produits_commandes)) {
                    $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
                }
                if($etat == "Actif") {
                    if(!array_key_exists($id, $produits_commande_sur_periode)) {
                        $action .= " | " . html_lien("?action=modifetat&id=$id&etat=Inactif", "_top", "Desactiver");
                    }
                }
                else  {
                    $action .= " | " .  html_lien("?action=modifetat&id=$id&etat=Actif", "_top", "Activer");
                }
                $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
                $chaine .= html_colonne("","","left","","","","","$nom","","tdliste");
                $chaine .= html_colonne("","","left","","","","","$description","","tdliste");
                $chaine .= html_colonne("","","right","","","","",sprintf($g_lib_somme_admin,$prix),"","tdliste");
                $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
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

function retrouver_nom_produit($id) {
    return retrouver_parametres_produit($id)['nom'];
}

function afficher_liste_produits($nomvariable="idproduit",$defaut=0,$actifOnly = True) {
    global $base_produits,$g_lib_somme;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,prix from $base_produits where " . ($actifOnly ? "etat='Actif'" : "1")  . " order by idproducteur");
    while (list($id,$nom,$prix) = mysqli_fetch_row($rep))
    {
        $texte .= "<option value=\"" . $id . "\"";
        if ($id == $defaut) $texte .= " selected";
        $texte .= ">" . $nom . " (" . sprintf($g_lib_somme,$prix) . ")</option>\n";
    }
    $texte .= "</select>\n";
    return($texte);
}

function retrouver_parametres_produit($id) {
    global $base_produits, $tab_produits;
    if(!isset($tab_produits)) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,idproducteur from $base_produits where 1");
        while (list($idproduit,$nom,$description,$prix,$idproducteur) = mysqli_fetch_row($rep))
        {
            $tab_produits[$idproduit]['nom'] = $nom;
            $tab_produits[$idproduit]['description'] = $description;
            $tab_produits[$idproduit]['prix'] = $prix;
            $tab_produits[$idproduit]['idproducteur'] = $idproducteur;
        }
    }

    if(isset($tab_produits[$id])) {
        return $tab_produits[$id];
    } else {
        $params['nom'] = "??? produit n° $id ???";
        $params['description'] = "???";
        $params['prix'] = 0.0;
        $params['idproducteur'] = "0";
        return $param;
    }
}

function liste_produits($idproducteur) {
    global $base_produits;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nom,prix from $base_produits where idproducteur = $idproducteur and etat='Actif'");
    $produits = array();
    if (mysqli_num_rows($rep) > 0) {
        while (list($nom, $prix) = mysqli_fetch_row($rep))
        {
            $produits[$nom] = $prix;
        }
    }
    return $produits;
}

?>
