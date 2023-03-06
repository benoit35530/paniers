<?php

function nombre_clients() {
    global $base_clients;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_clients where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function afficher_compte_client($id) {
    global $base_clients;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nom,prenom,email,codeclient,telephone,ville,iddepot from $base_clients where id='$id'");
    if (list($nom,$prenom,$email,$codeclient,$telephone, $ville) = mysqli_fetch_row($rep))
    {
        $champs["libelle"] = array("Votre compte","Code client","Nom","Prenom","Email","Téléphone","Ville","Dépôt","");
        $champs["type"] = array("","afftext","afftext","afftext","afftext","afftext","afftext", "afftext", "submit");
        $champs["lgmax"] = array("","","","","","","","");
        $champs["taille"] = array("","","","","","","","");
        $champs["nomvar"] = array("","","","","","","","");
        $champs["valeur"] = array("","$codeclient","$nom","$prenom","$email","$telephone", "$ville", retrouver_depot($iddepot)," Retour ");
        $champs["aide"] = array("","","","","", "");
        return(saisir_enregistrement($champs,"?","formsaisir","70","30"));
    }
    else
    {
        return(afficher_message_erreur("Client n° $id introuvable !"));
    }

}

function saisir_parametres_client($id,$cde) {
    global $base_clients;
    if($cde == 'ajout')
    {
        $id = "";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select max(cast(substr(codeclient,2) as unsigned))+1 from $base_clients where 1");
        if($rep) {
            list($codeclient) = mysqli_fetch_row($rep);
        }
        if($codeclient == "") {
            $codeclient = "1";
        }
        $codeclient = "C" . $codeclient;
        $nom = "";
        $prenom = "";
        $email = "";
        $telephone = "";
        $ville = "";
        $motpasse = wp_generate_password(8, false);
        $cotisation = 0.0;
        $formulaire = "?action=confajout";
        $texte_bouton = "Ajouter";
        $titre_form = "Saisir les valeurs du compte client";
        $iddepot = obtenir_depot_utilisateur();
        $etat = "Actif";
    }
    else
    {
        $motpasse = "";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select codeclient,nom,prenom,email,telephone,ville,iddepot,etat,cotisation from $base_clients where id='$id'");
        if (list($codeclient,$nom,$prenom,$email,$telephone,$ville,$iddepot,$etat,$cotisation) = mysqli_fetch_row($rep))
        {
            $formulaire = "?action=confmodif&id=$id";
            $texte_bouton = "Modifier";
            $titre_form = "Saisir les nouvelles valeurs du compte client";
        }
        else
        {
            return(afficher_message_erreur("Client n° $id introuvable !"));
        }
    }

    if(obtenir_depot_utilisateur() == -1) {
        $typedepot =  "libre";
        $valdepot = afficher_liste_depots("iddepot", $iddepot);
    } else {
        $typedepot = "afftext";
        $valdepot = retrouver_depot($iddepot);
    }

    $champs["libelle"] = array("$titre_form","", "*Code client","*Nom","*Prénom","Etat","Email","Telephone","Ville","Dépôt","Cotisation","*Mot de passe","");
    $champs["type"] = array("","dummypassword", "text","text","text","libre","text","text","libre",$typedepot,"text",
                            ($cde == 'ajout' ? "text" : "password"),"submit");
    $champs["lgmax"] = array("","","20","40","40","","100","40","40","40","8","8","");
    $champs["taille"] = array("","","20","40","40","","70","30","40","40","8","8","");
    $champs["nomvar"] = array("","","codeclient","nom","prenom","etat","email","telephone","ville","iddepot","cotisation","motpasse","");

    $champs["valeur"] = array("","","$codeclient","$nom","$prenom",
                              afficher_etats_client("etat",$etat),
                              "$email","$telephone",
                              afficher_villes_client("ville", $ville),
                              $valdepot,
                              sprintf("%.02f",$cotisation),$motpasse,$texte_bouton);
    $champs["aide"] = array("","","Code du client, sous la forme Cxx, prendre celui qui est proposé par défaut...",
                            "Nom du client","Prénom du client",
                            "Actif : connexion possible - Inactif : connexion impossible",
                            "Email","N° de téléphone","Ville","Dépôt",
                            "Cotisation en Euros, avec point pour séparer les centimes","Mot de passe","");
    return(saisir_enregistrement($champs,"$formulaire","formsaisir",60,25));
}

function supprimer_client($id) {
    global $base_clients;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select codeclient,nom,prenom,email,telephone,ville,iddepot,etat from $base_clients where id='$id'");
    if (list($codeclient,$nom,$prenom,$email,$telephone,$ville,$iddepot,$etat) = mysqli_fetch_row($rep))
    {
        $champs["libelle"] = array("Supprimer le client n° $id","Code client","Nom","Prénom","Etat","E-mail","Téléphone","Ville","Dépôt","");
        $champs["type"] = array("","afftext","afftext","afftext","afftext","afftext","afftext","afftext","afftext","submit");
        $champs["lgmax"] = array("","","","","","","","","");
        $champs["taille"] = array("","","","","","","","","");
        $champs["nomvar"] = array("","","","","","","","","");
        $champs["valeur"] = array("","$codeclient","$nom","$prenom","$etat","$email","$telephone",afficher_villes_client("ville", $ville),retrouver_depot($iddepot), "Supprimer");
        $champs["aide"] = array("","","","","","","","");
        return(saisir_enregistrement($champs,"?action=confsuppr&id=$id","formsupprimer"));
    }
    else
    {
        return(afficher_message_erreur("Client n° $id introuvable !"));
    }
}

function gerer_clients($tri=1, $iddepot = -1, $etat = "Actif", $action = "filtrer") {
    global $base_clients,$base_bons_cde;

    if($iddepot == -1) {
        $iddepot = obtenir_depot_utilisateur();
    }

    if($action == "filtrer") {
        $action = "&action=$action&filtre_etat=$etat&iddepot=$iddepot";
    }

    $chaine = html_debut_tableau("40%", "0", "2", "0");
    $chaine .= html_debut_ligne("", "", "", "", "", "");
    $chaine .= html_colonne("", "", "center", "", "", "", "", "Ville", "", "thliste");
    $chaine .= html_colonne("", "", "center", "", "", "", "", "Nombre de clients (actifs)", "", "thliste");
    $chaine .= html_fin_ligne();
    $total = 0;


    $filter = "";
    if($iddepot != "" && $iddepot != -1) {
        $filter .= " iddepot=$iddepot";
    }
    if($etat != "" && $etat != "-1") {
        if($filter != "") {
            $filter .= " and ";
        }
        $filter .= " etat='$etat'";
    }
    if($filter == "") {
        $filter = "1";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select ville, count(*) from $base_clients where $filter group by ville");
    while($rep && list($ville,$count) = mysqli_fetch_row($rep)) {
        $chaine .= html_debut_ligne("","","","","","","");
        $chaine .= html_colonne("", "", "left", "", "", "", "", $ville, "", "tdliste");
        $chaine .= html_colonne("", "", "center", "", "", "", "", $count, "", "tdliste");
        $chaine .= html_fin_ligne();
        $total += $count;
    }
    $chaine .= html_debut_ligne("","","","","","","");
    $chaine .= html_colonne("", "", "left", "", "", "", "", "Total", "", "thliste");
    $chaine .= html_colonne("", "", "center", "", "", "", "", $total, "", "thliste");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    $chaine .= "<br><br>";
    $chaine .= html_debut_tableau("95%","0","2","0");
    $chaine .= html_debut_ligne("","","","","","","");
    $chaine .= html_colonne("","","center","","","","","Actions","","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=0" . $action,"_top","Code client"),"","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=1" . $action,"_top","Nom"),"","thliste");
    $chaine .= html_colonne("","","center","","","","","Prénom","","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=2" . $action,"_top","Etat"),"","thliste");
    $chaine .= html_colonne("","","center","","","","","E-mail","","thliste");
    $chaine .= html_colonne("","","center","","","","","Téléphone","","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=3" . $action,"_top","Ville"),"","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=5" . $action,"_top","Dépôt"),"","thliste");
    $chaine .= html_colonne("","","center","","","","","Dernière connexion","","thliste");
    $chaine .= html_colonne("","","center","","","","",html_lien("?tri=4","_top","Cotisation"),"","thliste");
    $chaine .= html_colonne("","","center","","","","","Modifié le","","thliste");
    $chaine .= html_fin_ligne();

    $champs_order = array();
    $champs_order[0] = "cast(substring(codeclient, 1) as UNSIGNED) asc";
    $champs_order[1] = "nom, prenom asc";
    $champs_order[2] = "etat asc";
    $champs_order[3] = "ville asc";
    $champs_order[4] = "cotisation, nom, prenom asc";
    $champs_order[5] = "iddepot, nom, prenom asc";
    $order = ($tri >= 0 && $tri <= 5) ? $champs_order[$tri] : "nom, prenom asc";

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,codeclient,nom,prenom,email,telephone,ville,iddepot,etat,derncnx,cotisation,datemodif from $base_clients where $filter order by $order");
    while($rep && list($id,$codeclient,$nom,$prenom,$email,$telephone,$ville,$iddepot,$etat,$derncnx,$cotisation,$datemodif) = mysqli_fetch_row($rep)) {
        $chaine .= html_debut_ligne("","","","","","","");
        $action = html_lien("?action=modif&id=$id&tri=$tri","_top","Modif.");
        $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idclient='$id'");
        if(mysqli_num_rows($rep0) == 0) {
            $action .= " | " . html_lien("?action=suppr&id=$id&tri=$tri","_top","Suppr.");
        }
        $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$codeclient,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$nom,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$prenom,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$etat,"","tdliste");
        $chaine .= html_colonne("","","left","","","","","<a href=\"mailto:$email\">$email</a>","","tdliste");
        $chaine .= html_colonne("","","center","","","","",$telephone,"","tdliste");
        $chaine .= html_colonne("","","center","","","","",$ville,"","tdliste");
        $chaine .= html_colonne("","","center","","","","",retrouver_depot($iddepot),"","tdliste");
        $chaine .= html_colonne("","","center","","","","",dateheureexterne($derncnx),"","tdliste");
        $chaine .= html_colonne("","","right","","","","",sprintf("%.02f ",$cotisation),"","tdliste");
        $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
        $chaine .= html_fin_ligne();
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select sum(cotisation) from $base_clients where $filter");
    if($rep) {
        list($total_cotisation) = mysqli_fetch_row($rep);
    } else {
        $total_cotisation = 0.0;
    }
    $chaine .= html_debut_ligne("","","","","","","");
    $chaine .= html_colonne("","","right","","","","9","Total des cotisations","","thliste");
    $chaine .= html_colonne("","","right","","","","",sprintf("%.02f ",$total_cotisation),"","thliste");
    $chaine .= html_colonne("","","center","","","","2","","","thliste");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    return($chaine);
}

function retrouver_client($idclient,$affcodeclient=false) {
    global $base_clients;
    if($idclient <= 0) {
        $idclient = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nom,prenom,codeclient from $base_clients where id='$idclient'");
    $texte = "??? client n° $id ???";
    if (mysqli_num_rows($rep) != 0)
    {
        list($nom,$prenom,$codeclient) = mysqli_fetch_row($rep);
    }
    return("$prenom $nom" . ($affcodeclient ? " (" . $codeclient . ")" : ""));
}

function retrouver_code_client($idclient) {
    global $base_clients;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select codeclient from $base_clients where id='$idclient'");
    $texte = "??? code client n° $id ???";
    if (mysqli_num_rows($rep) != 0)
    {
        list($codeclient) = mysqli_fetch_row($rep);
    }
    return("$codeclient");
}

function retrouver_depot_client($id) {
    global $base_clients;
    if($id <= 0) {
        $id = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select iddepot from $base_clients where id='$id'");
    if($rep) {
        list($iddepot) = mysqli_fetch_row($rep);
        return $iddepot;
    } else {
        return 0;
    }
}

function afficher_villes_client($nomvariable,$defaut="Noyal sur Vilaine") {
    global $tab_villes_clients;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    while(list($key,$val) = each($tab_villes_clients))
    {
        $texte .= "<option value=\"" . $val . "\"";
        if ($val == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return("$texte");
}

function afficher_liste_clients($nomvariable="idclient",$defaut=0,$affcodeclient=true,$actifsOnly=false) {
    global $base_clients;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if($actifsOnly) {
        $filter = "etat = 'Actif'";
    } else {
        $filter = "1";
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,prenom,codeclient from $base_clients where $filter order by nom, prenom");
    if (mysqli_num_rows($rep) != 0)
    {
        if($defaut == 0) $texte .= "<option value=\"0\" selected>Choisissez un client...</option>\n";
        while(list($id,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $id . "\"";
            if ($id == $defaut) $texte .= " selected";
            $texte .= ">" . $nom . " " . $prenom . ($affcodeclient ? " (" . $codeclient . ")" : "") . "</option>\n";
        }
    }
    else
    {
        $texte .= "<option value=\"\">Pas de clients...</option>";
    }
    $texte .= "</select>\n";
    return($texte);
}

function afficher_etats_client($nomvariable, $defaut="Inactif", $addAll = False) {
    global $tab_etats_clients;
    reset($tab_etats_clients);
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if(count($tab_etats_clients) > 1 && $addAll) {
        $texte .= "<option value=\"-1\" " . (($defaut == "" || $defaut == "-1") ? "selected" : "") .">Tous</option>\n";
    }
    while(list($key,$val) = each($tab_etats_clients))
    {
        $texte .= "<option value=\"" . $key . "\"";
        if ($key == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return "$texte";
}

function export_clients($depot) {
    global $base_clients,$base_bons_cde;
    $chaine .= html_debut_tableau("100%","0","2","0");
    $chaine .= html_debut_ligne("","","","","","","");
    $chaine .= html_colonne("4%","","center","","","","","Code","","thliste");
    $chaine .= html_colonne("26%","","center","","","","","Nom Prénom","","thliste");
    $chaine .= html_colonne("32%","","center","","","","","E-mail","","thliste");
    $chaine .= html_colonne("16%","","center","","","","","Téléphone","","thliste");
    $chaine .= html_colonne("18%","","center","","","","","Ville","","thliste");
    $chaine .= html_fin_ligne();

    if($depot > 0) {
        $filter = "and iddepot=$depot";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select codeclient,nom,prenom,email,telephone,ville from $base_clients ".
                       "where etat='Actif' $filter order by nom, prenom asc");
    while(list($codeclient,$nom,$prenom,$email,$telephone,$ville) = mysqli_fetch_row($rep))
    {
        $chaine .= html_debut_ligne("","","","","","","");
        $chaine .= html_colonne("","","left","","","","",$codeclient,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$nom . " " . $prenom,"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$email,"","tdliste");
        $chaine .= html_colonne("","","center","","","","",$telephone,"","tdliste");
        $chaine .= html_colonne("","","center","","","","",$ville,"","tdliste");
        $chaine .= html_fin_ligne();
    }
    $chaine .= html_fin_tableau();
    return($chaine);
}

?>