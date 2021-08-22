<?php

function nombre_dates() {
    global $base_dates;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_dates where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function retrouver_absences($idperiode) {
    global $base_absences, $base_dates;
    $absences = array();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur,iddate from $base_dates inner join $base_absences on $base_absences.iddate=$base_dates.id  where idperiode='$idperiode'");
    if ($rep && mysqli_num_rows($rep) != 0)
    {
        while (list($idproducteur, $iddate) = mysqli_fetch_row($rep))
        {
            $absences[$iddate][$idproducteur] = true;
        }
    }
    return $absences;
}

function formulaire_date($cde="ajout",$id=0,$datelivraison="",$idperiode=0) {
    global $g_lib_somme_admin, $base_producteurs, $base_absences,$tab_permanences_defauts,$tab_types_permanences,
        $base_permanences;
    if($id != 0)
    {
        $absences = array();
        $rep2 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur from $base_absences where iddate = '$id'");
        if ($rep2 && mysqli_num_rows($rep2) != 0)
        {
            while(list($idproducteur) = mysqli_fetch_row($rep2))
            {
                $absences[$idproducteur] = true;
            }
        }
    }

    $champs["libelle"] = array(($cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : ($cde == "detail" ? "Détails " : "Suppression "))) . "d'une date","*Date de livraison","*Période");
    $fieldtype = 
    $champs["type"] = array("",($cde == "modif" || $cde == "ajout" ? "datepicker" : "afftext"),
                               ($cde == "modif" || $cde == "ajout" ? "libre" : "afftext"));
    $champs["lgmax"] = array("","10","");
    $champs["taille"] = array("","10","");
    $champs["nomvar"] = array("","datelivraison","idperiode");
    $champs["valeur"] = array("",$datelivraison,($cde == "modif" || $cde == "ajout" ? afficher_liste_periodes("idperiode",$idperiode, True) : retrouver_periode($idperiode)));
    $champs["aide"] = array("","Date de livraison (jj/mm/aaaa)","Indiquez à quelle période appartient cette date");

    $producteurs = "";
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where etat='Actif' order by produits");
    while(list($idproducteur,$nom,$produits) = mysqli_fetch_row($rep0))
    {
        $producteurs .= html_checkbox_input("producteurs[$idproducteur]", "1", "$produits ($nom)", 
                                            !$absences[$idproducteur]) . "<br>";
    }

    $champs["libelle"][] = "Producteurs";
    $champs["type"][] = "libre";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = $producteurs;
    $champs["aide"][] = "";

    if($cde == "ajout" && count($tab_permanences_defauts) > 0) {
        $permanences = "";
        foreach($tab_permanences_defauts as $type=>$defauts) {
            $permanences .= html_checkbox_input("permanences[$type]", "1", 
                                                $tab_types_permanences[$type] . 
                                                " (" . $defauts[0] . '-' . $defauts[1] . ", " . 
                                                $defauts[2] ." participant" . ($defauts[2] > 1 ? 's' : '') . ')', 
                                                $defauts[3]) . "<br>";
        }
        $champs["libelle"][] = "Permanences";
        $champs["type"][] = "libre";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = $permanences;
        $champs["aide"][] = "";
    }

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where date = $datelivraison");
    if($cde == "suppr" && mysqli_num_rows($rep0) > 0) {
        $champs["libelle"][] = "Permanences";
        $champs["type"][] = "libre";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = html_checkbox_input("supprpermanences", "1", 
                                                  "Supprimer les permances associées à cette date", 
                                                  "1");
        $champs["aide"][] = "";
    }

    if($cde != "detail") {
        $champs["libelle"][] = "";
        $champs["type"][] = "submit";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = ($cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer "));
        $champs["aide"][] = "";
    }

    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formdate","70","20"));
}

function gerer_liste_dates($idperiode="-2") {
    global $base_dates,$base_commandes,$base_periodes,$g_periode_libelle;
    $condition = "";
    if($idperiode > 0) { 
        $condition = "idperiode='$idperiode'";
    } else if($idperiode == "-2") {
        $condition = "$base_periodes.etat != 'Close'";
    } else {
        $condition = "1";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_dates.id,datelivraison,idperiode,$base_dates.datemodif," . 
                       $g_periode_libelle . ",$base_periodes.etat " . 
                       "from $base_dates " . 
                       "inner join $base_periodes on $base_periodes.id = $base_dates.idperiode " .
                       "where $condition order by datelivraison desc");
    $chaine = "";
    if (mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("70%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Date de livraison","","thliste");
        $chaine .= html_colonne("","","center","","","","","Période","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifiée le","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$datelivraison,$idperiode,$datemodif,$periode,$etat) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","",dateexterne($datelivraison),"","tdliste");
            $chaine .= html_colonne("","","left","","","","",$periode,"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            if($etat != "Close") {
                $action = html_lien("?action=modif&id=$id","_top","Modifier");
                $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $base_commandes where iddatelivraison = '$id'");
                if(mysqli_num_rows($rep0) == 0) {
                    $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
                }
            } else {
                $action = html_lien("?action=detail&id=$id","_top","Détails");
            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucun date dans la base...");
    }
    return($chaine);
}

function retrouver_date($id) {
    global $base_dates;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select datelivraison from $base_dates where id='$id'");
    $texte = "??? date n° $id ???";
    if (mysqli_num_rows($rep) != 0)
    {
        list($datelivraison) = mysqli_fetch_row($rep);
        $texte = dateexterne($datelivraison);
    }
    return($texte);
}

function retrouver_dates_periode($idperiode, $idproducteur = 0) {
    global $base_dates;
    $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode='$idperiode' order by datelivraison asc");
    $param = array();
    if($idproducteur > 0)
    {
        $absences = retrouver_absences($idperiode);
        while(list($id,$datelivraison) = mysqli_fetch_row($rep1))
        {
            if(!$absences[$id][$idproducteur])
            {
                $param[$id] = $datelivraison;
            }
        }
    }
    else
    {
        while(list($id,$datelivraison) = mysqli_fetch_row($rep1))
        {
            $param[$id] = $datelivraison;
        }
    }
    return($param);
}

function afficher_liste_dates($nomvariable="iddatelivraison",$defaut=0) {
    global $base_dates, $base_periodes;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_dates.id,datelivraison " . 
        "from $base_dates " . 
        "inner join $base_periodes on $base_periodes.id = $base_dates.idperiode " .
        "where $base_periodes.etat != 'Close' order by datelivraison desc");

    while (list($id,$datelivraison) = mysqli_fetch_row($rep))
    {
        $texte .= "<option value=\"" . $id . "\"";
        if ($id == $defaut) $texte .= " selected";
        $texte .= ">" . dateexterne($datelivraison) . "</option>\n";
    }
    $texte .= "</select>\n";
    return($texte);
}

?>
