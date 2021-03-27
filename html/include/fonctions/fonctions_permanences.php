<?php

function nombre_permanences() {

    global $base_permanences;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_permanences where 1");
    list($nb) = mysqli_fetch_row($rep);

    return($nb);

}

function formulaire_permanence($cde="ajout",$id=0,$date="aaaa-mm-jj",$heuredebut="hh:mm",$heurefin="hh:mm",$nbparticipants=0,$typepermanence="") {
    $champs["libelle"] = array(($cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ")) . "d'une permanence","*Date","*Heure de début","*Heure de Fin","*Nombre de participants","Type de permanence","");
    $champs["type"] = array("",($cde == "modif" || $cde == "ajout" ? "datepicker" : "afftext"),($cde == "modif" || $cde == "ajout" ? "text" : "afftext"),($cde == "modif" || $cde == "ajout" ? "text" : "afftext"),($cde == "modif" || $cde == "ajout" ? "text" : "afftext"),($cde == "modif" || $cde == "ajout" ? "libre" : "afftext"),"submit");
    $champs["lgmax"] = array("","10","5","5","5","","");
    $champs["taille"] = array("","10","5","5","5","","");
    $champs["nomvar"] = array("","date","heuredebut","heurefin","nbparticipants","typepermanence","");
    $champs["valeur"] = array("",dateexterne($date),heures_minutes($heuredebut),heures_minutes($heurefin),$nbparticipants,afficher_liste_types_permanences("typepermanence",$typepermanence),($cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ")));
    $champs["aide"] = array("","Date de la permanence (jj/mm/aaaa)","Heure de début (hh:mm)","Heure de fin (hh:mm)","Nombre de participants souhaités","Choisissez le type de permanence","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formpermanence","70","20"));
}

function gerer_liste_permanences() {
    global $base_permanences,$base_permanenciers,$tab_types_permanences;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence,datemodif from $base_permanences where 1 order by date desc, heuredebut desc");
    $chaine = "";
    if(mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Date","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de début","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de fin","","thliste");
        $chaine .= html_colonne("","","center","","","","","Nombre de participants","","thliste");
        $chaine .= html_colonne("","","center","","","","","Nombre d'inscrits","","thliste");
        $chaine .= html_colonne("","","center","","","","","Type de permanence","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifiée le","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$date,$heuredebut,$heurefin,$nbparticipants,$nbinscrits,$typepermanence,$datemodif) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","",dateexterne($date),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heuredebut),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heurefin),"","tdliste");
            $chaine .= html_colonne("","","center","","","","","$nbparticipants","","tdliste");
            $chaine .= html_colonne("","","center","","","","","$nbinscrits","","tdliste");
            $chaine .= html_colonne("","","center","","","","",$tab_types_permanences[$typepermanence],"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $action = html_lien("?action=modif&id=$id&annee=$annee","_top","Modifier");
            $action .= ($nbinscrits == 0 ? " | " . html_lien("?action=suppr&id=$id","_top","Supprimer") : "");
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucune permanence dans la base...");
    }
    return($chaine);
}

function retrouver_permanence($id) {
    global $base_permanences;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select date,heuredebut,heurefin from $base_permanences where id='$id'");
    $texte = "??? permanence n° $id ???";
    if(mysqli_num_rows($rep) != 0)
    {
        list($date,$heuredebut,$heurefin) = mysqli_fetch_row($rep);
        $texte = dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "]";
    }
    return($texte);
}

function afficher_liste_permanences($nomvariable="permanences",$defaut=0) {
    global $base_permanences,$tab_types_permanences;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,typepermanence from $base_permanences where nbinscrits < nbparticipants");
    while (list($id,$date,$heuredebut,$heurefin,$typepermanence) = mysqli_fetch_row($rep))
    {
        $texte .= "<option value=\"" . $id . "\"";
        if($id == $defaut) $texte .= " selected";
        $texte .= ">" . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "] " . $tab_types_permanences[$typepermanence] . "</option>\n";
    }
    $texte .= "</select>\n";
    return($texte);
}

function retrouver_parametres_permanence($id) {
    global $base_permanences;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where id='$id'");
    if(mysqli_num_rows($rep) != 0)
    {
        list($params['date'],$params['heuredebut'],$params['heurefin'],$params['nbparticipants'],$params['nbinscrits'],$params['typepermanence']) = mysqli_fetch_row($rep);
    }
    else
    {
        $params['date'] = "??? permanence n° $id inconnue ???";
        $params['heuredebut'] = "???";
        $params['heurefin'] = "???";
        $params['nbparticipants'] = -1;
        $params['nbinscrits'] = -1;
        $params['typepermanence'] = "???";
    }
    return($params);
}

function afficher_planning_permanences($admin=true,$idclient=0) {
    global $base_permanences,$tab_types_permanences;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where date >= curdate() order by date, heuredebut");
    $chaine = "";
    if(mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("70%","","","");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Date","","thliste");
        $chaine .= html_colonne("","","center","","","","","Type","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de début","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de fin","","thliste");
        $chaine .= html_colonne("","","center","","","","","Participants","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$date,$heuredebut,$heurefin,$nbparticipants,$nbinscrits,$typepermanence) = mysqli_fetch_row($rep))
        {
            $tab_permanenciers = retrouver_permanenciers($id,$admin);
            $inscrits = "";
            if(count($tab_permanenciers) != 0)
            {
                while(list($key,$val) = each($tab_permanenciers))
                {
                    $inscrits .= $val . "<br>";
                }
            }
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","",dateexterne($date),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",$tab_types_permanences[$typepermanence],"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heuredebut),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heurefin),"","tdliste");
            $chaine .= html_colonne("","","left","","","","",$inscrits,"","tdliste");
            $action = "";
            if(!$admin)
            {
                $pas_deja_inscrit = verifier_non_inscription($id,$idclient);
                if(($nbinscrits < $nbparticipants) && $pas_deja_inscrit)
                {
                    $action = html_lien("?action=inscrire&id=$id","_top","S'inscrire");
                }
                if(!$pas_deja_inscrit)
                {
                    $action = html_lien("?action=desinscrire&id=$id","_top","Se désinscrire");                
                }

            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucune permanence dans la base...");
    }
    return($chaine);
}

function retrouver_permanences_disponibles($nomvariable,$idclient) {

    global $base_permanences,$tab_types_permanences;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where 1 order by date, heuredebut");
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";

    while (list($id,$date,$heuredebut,$heurefin,$nbparticipants,$nbinscrits,$typepermanence) = mysqli_fetch_row($rep))
    {

        if($nbinscrits < $nbparticipants && verifier_non_inscription($id,$idclient))
        {

            $texte .= "<option value=\"" . $id . "\">" . dateexterne($date) . " [" . heures_minutes($heuredebut) . " - " . heures_minutes($heurefin) . "] " . $tab_types_permanences[$typepermanence]. "</option>\n";

        }

    }

    $texte .= "</select>\n";
    return($texte);

}

function choisir_permanence($idclient) {

    $champs["libelle"] = array("S'inscrire à une permanence","*Permanence","");
    $champs["type"] = array("","libre","submit");
    $champs["lgmax"] = array("","","");
    $champs["taille"] = array("","","");
    $champs["nomvar"] = array("","","");
    $champs["valeur"] = array("",retrouver_permanences_disponibles("id",$idclient)," Valider ");
    $champs["aide"] = array("","Choisissez la permanence qui vous convient","");
    return(saisir_enregistrement($champs,"?action=confinscrire","formpermanence","70","20"));

}

function afficher_liste_types_permanences($nomvariable="idtypepermanence",$defaut="") {

    global $tab_types_permanences;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    while(list($key,$val) = each($tab_types_permanences))
    {

        $texte .= "<option value=\"" . $key . "\"";
        if($key == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return("$texte");

}

?>
