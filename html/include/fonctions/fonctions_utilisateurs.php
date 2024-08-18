<?php

function nombre_utilisateurs() {
    global $base_utilisateurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_utilisateurs where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function obtenir_fonctions_utilisateur() {
    global $base_utilisateurs,$admin_fonctions,$admin_producteur,$admin_depot;
    if(isset($admin_fonctions)) {
        return $admin_fonctions;
    }
    $id = get_user_meta(get_current_user_id(), 'paniers_gestionnaireId', true);
    if(!$id && current_user_can("add_users")) {
        return "utilisateurs";
    }
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select fonctions,idproducteur,iddepot from $base_utilisateurs where id='$id'");
    if(list($admin_fonctions,$admin_producteur,$admin_depot) = mysqli_fetch_row($rep)) {
        return $admin_fonctions;
    }
    return "";
}

function utilisateurIsAdmin() {
    return obtenir_producteur_utilisateur() == -1 && obtenir_depot_utilisateur() == -1;
}

function obtenir_producteur_utilisateur() {
    global $admin_producteur;
    if(isset($admin_producteur)) {
        return $admin_producteur;
    }
    obtenir_fonctions_utilisateur();
    return $admin_producteur;
}

function obtenir_depot_utilisateur() {
    global $admin_depot;
    if(isset($admin_depot)) {
        return $admin_depot;
    }
    obtenir_fonctions_utilisateur();
    return $admin_depot;
}

function afficher_fonctions_utilisateur($fonctions) {
    $tab_fonctions = explode(",",$fonctions);
    $chn = "";
    foreach($tab_fonctions as $key => $val) {
        $chn .= $val . ", ";
    }
    return($chn);
}

function choisir_fonctions_utilisateur($nomvar,$valeur="") {
    global $tab_fonctions;
    reset($tab_fonctions);
    $chn = "";
    foreach($tab_fonctions as $key => $val) {
        $chn .= html_checkbox_input($nomvar,$key,$val,!(strpos($valeur,$key) === false)) . "<br>\n";
    }
    return($chn);
}

function saisir_parametres_utilisateur($id,$cde) {
    global $base_utilisateurs;
    if($cde == 'ajout') {
        $id = "";
        $nomutil = "";
        $nom = "";
        $prenom = "";
        $email = "";
        $motpasse = "";
        $formulaire = "?action=confajout";
        $texte_bouton = "Ajouter";
        $titre_form = "Ajouter un utilisateur";
        $fonctions = ",";
        $idproducteur = -1;
        $iddepot = -1;
    }
    else {
        $motpasse = "";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nomutil,nom,prenom,email,fonctions,idproducteur,iddepot from $base_utilisateurs where id='$id'");
        if(list($nomutil,$nom,$prenom,$email,$fonctions,$idproducteur,$iddepot) = mysqli_fetch_row($rep))
        {
            $formulaire = "?action=confmodif&id=$id";
            $texte_bouton = "Modifier";
            $titre_form = "Modifier l'utilisateur n° $id";
        }
        else
        {
            return(afficher_message_erreur("Utilisateur n° $id introuvable !"));
        }
    }

    $isSuperAdmin = html_checkbox_input("superadmin", "1", "Super-administrateur",
                                        !(strpos($fonctions,"utilisateur") === false));

    $champs["libelle"] = array("$titre_form","","*Identifiant","*Nom", "*Prenom", "*Mot de passe","*Email","Producteur","Dépôt","*Fonctions","");
    $champs["type"] = array("","dummypassword","text","text","text","password","text","libre","libre","libre","submit");
    $champs["lgmax"] = array("","","20","20","20","20","40","40","50","","");
    $champs["taille"] = array("","","20","20","20","20","50","50","70","","");
    $champs["nomvar"] = array("","","nomutil","nom","prenom","motpasse","email","idproducteur","iddepot","","");
    $champs["valeur"] = array("","","$nomutil","$nom","$prenom","","$email",afficher_liste_producteurs_et_tous("idproducteur", $idproducteur),afficher_liste_depots_et_tous("iddepot", $iddepot), $isSuperAdmin,$texte_bouton);
    $champs["aide"] = array("","","Identifiant","Nom","Prenom","Mot de passe","Email","Producteur associé à ce compte, si un producteur est spécifié, l'utilisateur n'aura accès qu'aux produits de ce producteur","Dépôt associé à ce compte, si un dépôt est spécifié, l'utilisateur n'aura accès qu'aux commandes et clients de ce dépôt","Cochez la ou les cases correspondant aux fonctions accessibles à l'utilisateur","");
    return(saisir_enregistrement($champs,"$formulaire","formsaisir"));
}

function supprimer_utilisateur($id) {
    global $base_utilisateurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nomutil,nom,prenom,email,fonctions,date,idproducteur,iddepot,derncnx from $base_utilisateurs where id='$id'");
    if(list($nomutil,$nom,$prenom,$email,$fonctions,$idproducteur,$iddepot,$date,$derncnx) = mysqli_fetch_row($rep)) {
        $champs["libelle"] = array("Supprimer l'utilisateur n° $id","Identifiant","Nom","Prenom","Email","Fonctions","Producteur","Dépôt","Dernière connexion","Date","");
        $champs["type"] = array("","afftext","afftext","afftext","afftext","afftext","afftext","afftext","afftext","afftext","submit");
        $champs["lgmax"] = array("","","","","","","","","");
        $champs["taille"] = array("","","","","","","","","");
        $champs["nomvar"] = array("","","","","","","","","");
        $producteur = $idproducteur > 0 ? retrouver_producteur($idproducteur) : "";
        $depot = $iddepot > 0 ? retrouver_depot($iddepot) : "";
        $champs["valeur"] = array("","$nomutil","$nom", "$prenom","$email","$fonctions",$producteur,$depot,dateheureexterne("$derncnx"),dateheureexterne($date),"Supprimer");
        $champs["aide"] = array("","","","","","","","","");
        return(saisir_enregistrement($champs,"?action=confsuppr&id=$id","formsupprimer"));
    }
    else {
        return(afficher_message_erreur("Utilisateur n° $id introuvable !"));
    }
}

function gerer_utilisateurs() {
    global $base_utilisateurs;
    $chaine = html_debut_tableau("95%","0","2","0");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("10%","","center","","","","","Actions","","thliste");
    $chaine .= html_colonne("10%","","center","","","","","Identifiant","","thliste");
    $chaine .= html_colonne("10%","","center","","","","","Prenom - Nom","","thliste");
    $chaine .= html_colonne("15%","","center","","","","","Email","","thliste");
    $chaine .= html_colonne("30%","","center","","","","","Fonctions","","thliste");
    $chaine .= html_colonne("10%","","center","","","","","Modifié le","","thliste");
    $chaine .= html_colonne("15%","","center","","","","","Date","","thliste");
    $chaine .= html_fin_ligne();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nomutil,nom,prenom,email,fonctions,date,derncnx from $base_utilisateurs where 1 order by nomutil");
    while (list($id,$nomutil,$nom,$prenom,$email,$fonctions,$date,$derncnx) = mysqli_fetch_row($rep))
    {
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","",html_lien("?action=modif&id=$id","_top","Modif.") . " | " . html_lien("?action=suppr&id=$id","_top","Suppr."),"","tdliste");
        $chaine .= html_colonne("","","left","","","","",$nomutil,"","tdliste");
        $chaine .= html_colonne("","","left","","","","","$prenom $nom","","tdliste");
        $chaine .= html_colonne("","","left","","","","","<a href=\"mailto:$email\">$email</a>","","tdliste");
        $chaine .= html_colonne("","","left","","","","",afficher_fonctions_utilisateur($fonctions),"","tdliste");
        $chaine .= html_colonne("","","center","","","","",dateheureexterne($derncnx),"","tdliste");
        $chaine .= html_colonne("","","center","","","","",dateheureexterne($date),"","tdliste");
        $chaine .= html_fin_ligne();
    }
    $chaine .= html_fin_tableau();
    return($chaine);
}

?>