<?php

function nombre_producteurs() {
    global $base_producteurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_producteurs where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_producteur($cde="ajout",$id=0,$nom="",$email="",$telephone = "", $paiement="",$produits="", $ordre="0") {
    $libelle = $cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ");
    $typetexte = $cde == "modif" || $cde == "ajout" ? "text" : "afftext";
    $button = $cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ");

    $champs["libelle"] = array($libelle . "d'un producteur","*Nom","*Email", "Telephone", "Paiement","*Description produits", "Ordre d'affichage", "");
    $champs["type"] = array("",$typetexte,$typetexte,$typetexte,$typetexte,$typetexte,$typetexte,"submit");
    $champs["lgmax"] = array("","80","80", "80", "80", "120","10", "");
    $champs["taille"] = array("","40","40", "40","40", "60", "10", "");
    $champs["nomvar"] = array("","nom","email", "telephone", "paiement","produits","ordre", "");
    $champs["valeur"] = array("",$nom,$email, $telephone, $paiement,$produits,$ordre,$button);
    $champs["aide"] = array("","Nom du producteur","Email", "Telephone", "Si paiement spécifique","Description des produits vendus","Ordre d'affichage", "");
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

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,email,telephone,paiement,produits,datemodif,etat,ordre from $base_producteurs where $filter order by ordre,nom");
    $chaine = "";
    if (mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_colonne("","","center","","","","","Nom","","thliste");
        $chaine .= html_colonne("","","center","","","","","Email","","thliste");
        $chaine .= html_colonne("","","center","","","","","Telephone","","thliste");
        $chaine .= html_colonne("","","center","","","","","Paiement","","thliste");
        $chaine .= html_colonne("","","center","","","","","Produits","","thliste");
        $chaine .= html_colonne("","","center","","","","","Ordre d'affichage","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifié le","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$nom,$email,$telephone,$paiement,$produits,$datemodif,$etat, $ordre) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $action = html_lien("?action=modif&id=$id","_top","Modifier");
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_produits where idproducteur = '$id'");
            $action .= (!$rep0 || mysqli_num_rows($rep0) == 0 ? " | " . html_lien("?action=suppr&id=$id","_top","Supprimer") : "");
            if($etat == "Actif") {
                $action .= " | " . html_lien("?action=modifetat&id=$id&etat=Inactif", "_top", "Desactiver");
            }
            else if($etat == "Inactif") {
                $action .= " | " .  html_lien("?action=modifetat&id=$id&etat=Actif", "_top", "Activer");
            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_colonne("","","left","","","","","$nom","","tdliste");
            $chaine .= html_colonne("","","left","","","","","<a href=\"mailto:$email\">$email</a>","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$telephone","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$paiement","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$produits","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$ordre","","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
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
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits,email,paiement from $base_producteurs where 1");
        while(list($id,$nom,$produits,$email,$paiement) = mysqli_fetch_row($rep)) {
            $tab_producteurs[$id]["nom"] = $nom;
            $tab_producteurs[$id]["produits"] = $produits;
            $tab_producteurs[$id]['email'] = $email;
            $tab_producteurs[$id]['paiement'] = $paiement;
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
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,produits from $base_producteurs where 1 order by ordre,nom");
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

function afficher_liste_producteurs($nomvariable="idproducteur",$default=0,$actifOnly=True,$addAll=False) {
    global $base_producteurs;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if($addAll) {
        $texte .= "<option value=\"-1\"" . ($default == -1 ? " selected" : "") . ">Tous</option>";
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where " . ($actifOnly ? "etat = 'Actif'" : "1") . " order by ordre,nom");
    if(mysqli_num_rows($rep) != 0)
    {
        if($default == 0) $texte .= "<option value=\"0\" selected>Choisissez un producteur...</option>\n";
        while (list($id,$nom,$produits) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $id . "\"";
            if ($id == $default) $texte .= " selected";
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
        $params['paiement'] = "???";
        $params['produits'] = "??? ";
        return $params;
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
    foreach($tab_etats_producteurs as $key => $val)
    {
        $texte .= "<option value=\"" . $key . "\"";
        if ($key == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return "$texte";
}

?>
