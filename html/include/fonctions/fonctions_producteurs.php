<?php

function nombre_producteurs() {
    global $base_producteurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_producteurs where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_producteur($cde="ajout",$id=0,$nom="",$email="",$telephone = "", $ordrecheque="",$produits="") {
    $libelle = $cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ");
    $typetexte = $cde == "modif" || $cde == "ajout" ? "text" : "afftext";
    $button = $cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ");

    $champs["libelle"] = array($libelle . "d'un producteur","*Nom","*Email", "Telephone", "*Ordre des chèques","*Description produits","");
    $champs["type"] = array("",$typetexte,$typetexte,$typetexte,$typetexte,$typetexte,"submit");
    $champs["lgmax"] = array("","80","80", "80", "80", "120","");
    $champs["taille"] = array("","40","40", "40","40", "60","");
    $champs["nomvar"] = array("","nom","email", "telephone", "ordrecheque","produits","");
    $champs["valeur"] = array("",$nom,$email, $telephone, $ordrecheque,$produits,$button);
    $champs["aide"] = array("","Nom du producteur","Email", "Telephone", "Ordre à mettre sur les chèques","Description des produits vendus","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formproducteur","70","20"));
}

function gerer_liste_producteurs($etat = "Actif") {
    global $base_producteurs,$base_produits;

    $filter = "";
    if($etat == "-1") {
        $filter = "1";
    } else {
        $filter = "etat='$etat'";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,email,telephone,ordrecheque,produits,datemodif,etat from $base_producteurs where $filter order by nom");
    $chaine = "";
    if (mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Nom","","thliste");
        $chaine .= html_colonne("","","center","","","","","Email","","thliste");
        $chaine .= html_colonne("","","center","","","","","Telephone","","thliste");
        $chaine .= html_colonne("","","center","","","","","Ordre des chèques","","thliste");
        $chaine .= html_colonne("","","center","","","","","Produits","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifié le","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$nom,$email,$telephone,$ordrecheque,$produits,$datemodif,$etat) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","","$nom","","tdliste");
            $chaine .= html_colonne("","","left","","","","","<a href=\"mailto:$email\">$email</a>","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$telephone","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$ordrecheque","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$produits","","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $action = html_lien("?action=modif&id=$id&annee=$annee","_top","Modifier");
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_produits where idproducteur = '$id'");
            $action .= (!$rep0 || mysqli_num_rows($rep0) == 0 ? " | " . html_lien("?action=suppr&id=$id&annee=$annee","_top","Supprimer") : "");
            if($etat == "Actif") {
                $desactiver = true;
                if($periode > 0) {
                    $rep3 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where idproducteur='$id' and idperiode='$periode'");
                    $desactiver = mysqli_num_rows($rep3) == 0;
                }
                if($desactiver) {
                    $action .= " | " . html_lien("?action=modifetat&id=$id&etat=Inactif", "_top", "Desactiver");
                }
            }
            else if($etat == "Inactif") {
                $action .= " | " .  html_lien("?action=modifetat&id=$id&etat=Actif", "_top", "Activer");
            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucun producteur dans la base...");
    }
    return($chaine);
}

function liste_producteurs() {
    global $tab_producteurs,$base_producteurs;
    if(!isset($tab_producteurs)) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits,email,ordrecheque from $base_producteurs where 1");
        while(list($id,$nom,$produits,$email,$ordrecheque) = mysqli_fetch_row($rep)) {
            $tab_producteurs[$id]["nom"] = $nom;
            $tab_producteurs[$id]["produits"] = $produits;
            $tab_producteurs[$id]['email'] = $email;
            $tab_producteurs[$id]['ordrecheque'] = $ordrecheque;
        }
    }
    return $tab_producteurs;
}

function retrouver_producteur($id) {
    $producteurs = liste_producteurs();
    if(!isset($producteurs[$id])) {
        return  "";
    }
    return $producteurs[$id]["nom"];
}

function retrouver_producteur_info($id) {
    $producteurs = liste_producteurs();
    if(!isset($producteurs[$id])) {
        return False;
    }
    return $producteurs[$id];
}

function retrouver_produits_producteurs() {
    global $base_producteurs,$tab_produits_producteurs;
    if(!isset($tab_produits_producteurs)) {
        $tab_produits_producteurs = array();
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,produits from $base_producteurs where 1");
        while (list($idproducteur,$produit) = mysqli_fetch_row($rep1))
        {
            $tab_produits_producteurs[$idproducteur] = $produit;
        }
    }
    return $tab_produits_producteurs;
}

function retrouver_producteur_email($id) {
    global $base_producteurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select email from $base_producteurs where id='$id'");
    $texte = '';
    if (mysqli_num_rows($rep) != 0)
    {
        list($texte) = mysqli_fetch_row($rep);
    }
    return($texte);
}

function afficher_liste_producteurs_et_tous($nomvariable="idproducteur",$defaut=-1) {
    return afficher_liste_producteurs($nomvariable, $defaut, True, True);
}

function afficher_liste_producteurs($nomvariable="idproducteur",$defaut=0,$actifOnly=True,$addAll=False) {
    global $base_producteurs;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if($addAll) {
        $texte .= "<option value=\"-1\"" . ($default == -1 ? " selected" : "") . ">Tous</option>";
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where " . ($actifOnly ? "etat = 'Actif'" : "1"));
    if(mysqli_num_rows($rep) != 0)
    {
        if($defaut == 0) $texte .= "<option value=\"0\" selected>Choisissez un producteur...</option>\n";
        while (list($id,$nom,$produits) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $id . "\"";
            if ($id == $defaut) $texte .= " selected";
            $texte .= ">" . $nom . " (" . $produits . ") </option>\n";
        }
    }
    else
    {
        $texte .= "<option value=\"\">Pas de producteurs...</option>";
    }
    $texte .= "</select>\n";
    return($texte);
}

function afficher_liste_texte_produits() {
    $producteurs = liste_producteurs();
    $texte = "<ul>\n";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where 1");
    foreach($producteurs as $id => $params)
    {
        $texte .= "<li>(" . $params["produits"] . ")</li>\n";
    }
    $texte .= "</li>\n";
    return($texte);
}

function retrouver_parametres_producteur($id) {
    $producteurs = liste_producteurs();
    if(!isset($producteurs[$id])) {
        $params['nom'] = "??? producteur n° $id inconnu ???";
        $params['email'] = "???";
        $params['ordrecheque'] = "???";
        $params['produits'] = "??? ";
        return $param;
    }
    return $producteurs[$id];
}

function afficher_etats_producteurs($nomvariable, $defaut="Inactif", $addAll = False) {
    global $tab_etats_producteurs;
    reset($tab_etats_producteurs);
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if(count($tab_etats_producteurs) > 1 && $addAll) {
        $texte .= "<option value=\"-1\" " . (($defaut == "" || $defaut == "-1") ? "selected" : "") .">Tous</option>\n";
    }
    while(list($key,$val) = each($tab_etats_producteurs))
    {
        $texte .= "<option value=\"" . $key . "\"";
        if ($key == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return "$texte";
}

?>
