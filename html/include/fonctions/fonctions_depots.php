<?php

function nombre_depots() {
    global $base_depots;
    global $nombre_depots, $checked_nombre_depots;
    if(!$checked_nombre_depots) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(id) from $base_depots where 1");
        list($nombre_depots) = mysqli_fetch_row($rep);
        $checked_nombre_depots = true;
    }
    return($nombre_depots);
}

function formulaire_depot($cde="ajout",$id=0,$nom="",$adresse="",$telephone = "", $email="") {
    $libelle = $cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ");
    $type = $cde == "modif" || $cde == "ajout" ? "text" : "afftext";
    $button = $cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ");

    $champs["libelle"] = array($libelle . "d'un dépôt","*Nom","*Adresse", "*Telephone","*Email","");
    $champs["type"] = array("",$type,$type,$type,$type,"submit");
    $champs["lgmax"] = array("","80","120", "80", "80","");
    $champs["taille"] = array("","40","60", "40","40","");
    $champs["nomvar"] = array("","nom","adresse", "telephone", "email","");
    $champs["valeur"] = array("",$nom,$adresse, $telephone, $email,$button);
    $champs["aide"] = array("","Nom du dépôt","Adresse", "Telephone", "Email", "");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formdepot","70","20"));
}

function gerer_liste_depots() {
    global $base_depots,$base_clients,$base_bons_cde;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,adresse,telephone,email,datemodif,etat from $base_depots where 1");
    $chaine = "";
    if ($rep && mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Nom","","thliste");
        $chaine .= html_colonne("","","center","","","","","Adresse","","thliste");
        $chaine .= html_colonne("","","center","","","","","Telephone","","thliste");
        $chaine .= html_colonne("","","center","","","","","Email","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifié le","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$nom,$adresse,$telephone,$email,$datemodif,$etat) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","","$nom","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$adresse","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$telephone","","tdliste");
            $chaine .= html_colonne("","","left","","","","","<a href=\"mailto:$email\">$email</a>","","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $action = html_lien("?action=modif&id=$id","_top","Modifier");
            $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_clients where iddepot = '$id'");
            $action .= (!$rep0 || mysqli_num_rows($rep0) == 0 ? " | " . html_lien("?action=suppr&id=$id","_top","Supprimer") : "");
            if($etat == "Actif") {
                $desactiver = true;
                $idperiode = retrouver_periode_courante();
                if($idperiode > 0) {
                    $rep3 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where iddepot='$id' and idperiode='$idperiode'");
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
        $chaine .= afficher_message_erreur("Aucun dépôt dans la base...");
    }
    return($chaine);
}

function retrouver_depot($id) {
    global $liste_depots;
    if(nombre_depots() == 0) {
        return "";
    }
    liste_depots();
    return isset($liste_depots[$id]) ? $liste_depots[$id] : "";
}

function liste_depots() {
    global $liste_depots, $liste_etat_depots,$liste_email_depots,$base_depots;
    if(!isset($liste_depots)) {
        $liste_depots = array();
        $liste_etat_depots = array();
        if(nombre_depots() > 0) {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, nom, email, etat from $base_depots where 1");
            while(list($id, $nom, $email, $etat) = mysqli_fetch_row($rep)) {
                $liste_depots[$id] = $nom;
                $liste_etat_depots[$id] = $etat;
                $liste_email_depots[$id] = $email;
            }
        }
    }
    return $liste_depots;
}

function retrouver_etat_depot($id) {
    global $liste_etat_depots;
    liste_depots();
    return isset($liste_etat_depots[$id]) ? $liste_etat_depots[$id] : "";
}

function retrouver_depot_email($id) {
    global $liste_email_depots;
    liste_depots();
    return isset($liste_email_depots[$id]) ? $liste_email_depots[$id] : "";
}

function afficher_liste_depots_et_tous($nomvariable="iddepot",$defaut=0) {
    return afficher_liste_depots($nomvariable, $defaut, True, False);
}

function afficher_liste_depots_actifs($nomvariable="iddepot",$defaut=0) {
    return afficher_liste_depots($nomvariable, $defaut, False, True);
}

function afficher_liste_depots($nomvariable="iddepot",$defaut=0,$addAll=False,$actifsOnly=False) {
    global $liste_etat_depots;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $depots = liste_depots();
    if($addAll) {
        $texte .= "<option value=\"-1\" " . ($defaut == 0 ? "selected" : "") .">Tous les dépôts</option>\n";
    }
    if($defaut > 0 && $actifsOnly && $liste_etat_depots[$defaut] != "Actif") {
        $texte .= "<option value=\"\" selected/>\n";
    }
    foreach($depots as $id => $nom)
    {
        if(!$actifsOnly || $liste_etat_depots[$id] == "Actif") {
            if($id == $defaut) {
                $texte .= "<option value=\"" . $id . "\" selected>" . $nom . " </option>\n";
            } else {
                $texte .= "<option value=\"" . $id . "\">" . $nom . " </option>\n";
            }
        }
    }
    $texte .= "</select>\n";
    return($texte);
}

?>
