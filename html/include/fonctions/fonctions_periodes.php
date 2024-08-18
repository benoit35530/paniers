<?php

$g_periode_libelle = "CONCAT($base_periodes.libelle, ' (du ', DATE_FORMAT(datedebut,'%d/%m/%Y'), ' au ', DATE_FORMAT(datefin,'%d/%m/%Y'), ')') ";

function nombre_periodes() {
    global $base_periodes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_periodes where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_periode_et_dates() {

    global $g_lib_somme_admin,$tab_types_permanences,$tab_permanences_defauts,$base_producteurs,$liste_mois,
        $jour_commande, $periodicite_commande,$base_periodes;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select datedebut,datefin,datecommande from $base_periodes where 1 order by datefin desc limit 1");
    if(list($datedebut,$datefin,$datecommande) = mysqli_fetch_row($rep0)) {
        $dateref = date_format(date_create($datefin), "U");
    } else {
        $dateref = time();
    }

    if($periodicite_commande == "mensuel") {
        $day = $jour_commande;
        $month = date("n",$dateref) + 1;
        if($month > 12) {
            $month = 1;
            $year = date("Y") + 1;
        } else {
            $year = date("Y");
        }

        $libelle = $liste_mois[$month - 1] . " " . $year;
        $datedebut = date("01/m/Y",strtotime("$month/01/$year"));
        $datefin = date("t/m/Y",strtotime("$month/01/$year"));
        $datecommande = date("d/m/Y", strtotime("last $day",strtotime("$month/01/$year")));
    } else {
        $day = $jour_commande;
        $month = date("n",$dateref) + 1;
        if($month > 12) {
            $month = 1;
            $year = date("Y") + 1;
        } else {
            $year = date("Y");
        }

        $t = strtotime("next $day",$dateref);
        $libelle = "Semaine " . date("W - Y",$t);
        $datedebut = date("d/m/Y", $t);
        $datefin = date("d/m/Y", strtotime("+6 day",$t));
        $datecommande = $datedebut;
    }

    $etat = "Preparation";

    $champs["libelle"] = array("Ajout d'une période","*Libellé","*Date de début","*Date de fin","*Date de commande","Etat");
    $champs["type"] = array("","text","datepicker","datepicker","datepicker","libre");
    $champs["lgmax"] = array("","80","10","10","10","20");
    $champs["taille"] = array("","40","10","10","10","20");
    $champs["nomvar"] = array("","libelle","datedebut","datefin","datecommande","etat");
    $champs["valeur"] = array("",$libelle,$datedebut,$datefin,$datecommande,afficher_etats_periode("etat",$etat));
    $champs["aide"] = array("",
                            "Libellé de la période","Date de début (jj/mm/aaaa)",
                            "Date de fin (jj/mm/aaaa)",
                            "Date de commande (jj/mm/aaaa)",
                            "Etat de la periode (preparation: visible seulement par les producteurs et administrateurs, active: visible par tout le monde, close: les commandes sont closes)");

    $last = false;
    if($periodicite_commande == "mensuel") {
        $time = strtotime("next $day", strtotime("last $day",strtotime("$month/01/$year")));
    } else {
        $time = $t;
    }

    for($i = 1; !$last; $i++) {
        $datelivraison = date("d/m/Y", $time);
        if($periodicite_commande == "mensuel") {
            $time = strtotime("next $day",$time);
            $last = date("m",$time) != $month;
        } else {
            $last = true;
        }

        $champs["libelle"] = array_merge($champs["libelle"], array("Ajout date n° $i","*Date de livraison"));
        $champs["type"] = array_merge($champs["type"], array("titre","datepicker"));
        $champs["lgmax"] = array_merge($champs["lgmax"], array("","10"));
        $champs["taille"] = array_merge($champs["taille"], array("","10"));
        $champs["nomvar"] = array_merge($champs["nomvar"], array("","datelivraison[$i]"));
        $champs["valeur"] = array_merge($champs["valeur"], array("",$datelivraison));
        $champs["aide"] = array_merge($champs["aide"], array("","Date de livraison (jj/mm/aaaa), effacez ce champ pour ne pas ajouter cette date de livraison."));

        $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where etat='Actif' order by produits");
        $producteurs = "";
        while(list($idproducteur,$nom,$produits) = mysqli_fetch_row($rep0)) {
            $producteurs .= html_checkbox_input("producteurs[$i][$idproducteur]", "1", "$produits ($nom)",
                                                !$absences[$idproducteur]) . "<br>";
        }

        $champs["libelle"][] = "Producteurs";
        $champs["type"][] = "libre";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = $producteurs;
        $champs["aide"][] = "";

        if(count($tab_permanences_defauts) > 0) {
            $permanences = "";
            foreach($tab_permanences_defauts as $type=>$defauts) {
                $permanences .= html_checkbox_input("permanences[$i][$type]", "1",
                                                    $tab_types_permanences[$type] .
                                                    " (" .
                                                    $defauts[0] . '-' . $defauts[1] . ", " .
                                                    $defauts[2] ." participant" . ($defauts[2] > 1 ? 's' : '')
                                                    . ')',
                                                    $defauts[3]) .
                    "<br>";
            }
            $champs["libelle"][] = "Permanences";
            $champs["type"][] = "libre";
            $champs["lgmax"][] = "";
            $champs["taille"][] = "";
            $champs["nomvar"][] = "";
            $champs["valeur"][] = $permanences;
            $champs["aide"][] = "";
        }
    }

    $champs["libelle"][] = "";
    $champs["type"][] = "submit";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = " Ajouter ";
    $champs["aide"][] = "";

    return(saisir_enregistrement($champs,"?action=confajoutboncde","formajoutboncde","70","20"));
}

function formulaire_periode($cde="ajout",$id=0,$libelle="",$datedebut="",$datefin="",$datecommande="",$etat) {
    global $g_lib_somme_admin,$base_dates,$base_permanences;
    $champs["libelle"] = array(($cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ")) . "d'une période","*Libellé","*Date de début","*Date de fin","*Date de commande","Etat");
    $texttype = $cde == "modif" || $cde == "ajout" ? "text" : "afftext";
    $datetype = $cde == "modif" || $cde == "ajout" ? "datepicker" : "afftext";
    $champs["type"] = array("",$texttype,$datetype,$datetype,$datetype,"libre");
    $champs["lgmax"] = array("","80","10","10","10","20");
    $champs["taille"] = array("","40","10","10","10","20");
    $champs["nomvar"] = array("","libelle","datedebut","datefin","datecommande","etat");
    $champs["valeur"] = array("",$libelle,$datedebut,$datefin,$datecommande,afficher_etats_periode("etat",$etat),);
    $champs["aide"] = array("","Libellé de la période","Date de début (jj/mm/aaaa)","Date de fin (jj/mm/aaaa)","Date de commande (jj/mm/aaaa), en géneral, le dernier mercredi du mois précedent la période","Etat de la periode (preparation: visible seulement par les producteurs et administrateurs, active: visible par tout le monde, close: les commandes sont closes)");

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_permanences.id from $base_permanences " .
                        "inner join $base_dates on $base_dates.datelivraison = date " .
                        "where idperiode = $id");
    if($cde == "suppr" && mysqli_num_rows($rep0) > 0) {
        $champs["libelle"][] = "Permanences";
        $champs["type"][] = "libre";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = html_checkbox_input("supprpermanences", "1",
                                                  "Supprimer les permances associées aux dates de cette période",
                                                  "1");
        $champs["aide"][] = "";
    }

    $champs["libelle"][] = "";
    $champs["type"][] = "submit";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = $cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ");
    $champs["aide"][] = "";

    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formperiode","70","20"));
}

function gerer_liste_periodes($filtre_etat = "-2") {
    global $base_periodes,$base_dates,$base_bons_cde;

    $filter = "";
    if($filtre_etat == "" || $filtre_etat == "-1") {
        $filter = "1";
    } else if($filtre_etat == "-2") {
        $filter = "$base_periodes.etat != 'Close'";
    } else {
        $filter = "$base_periodes.etat = '$filtre_etat'";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_periodes.id,libelle,datedebut,datefin,datecommande,relancemail," .
                       "$base_periodes.etat,$base_periodes.datemodif," .
                       "(select count(id) from $base_bons_cde where $base_bons_cde.idperiode = $base_periodes.id) " .
                       "from $base_periodes where $filter order by datedebut desc");
    $chaine = "";
    if (mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_colonne("","","center","","","","","Libellé","","thliste");
        $chaine .= html_colonne("","","center","","","","","Date de début","","thliste");
        $chaine .= html_colonne("","","center","","","","","Date de fin","","thliste");
        $chaine .= html_colonne("","","center","","","","","Date de commande","","thliste");
        $chaine .= html_colonne("","","center","","","","","Relance faite","","thliste");
        $chaine .= html_colonne("","","center","","","","","Etat","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifiée le","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$libelle,$datedebut,$datefin,$datecommande,$relancemail,$etat,$datemodif,$nbcdes) =
               mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $action = html_lien("?action=modif&id=$id","_top","Modifier");
            if($etat == "Preparation") {
                $action .= " | " . html_lien("?action=activer&id=$id","_top","Activer");
                $action .= " | " . html_lien("?action=notification&id=$id","_top","Notification");
            } else if($etat == "Active") {
                $action .= " | " . html_lien("?action=clore&id=$id","_top","Clore");
            }
            if($nbcdes == 0) {
                $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_colonne("","","left","","","","","$libelle","","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateexterne($datedebut),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateexterne($datefin),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateexterne($datecommande),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",$relancemail,"","tdliste");
            $chaine .= html_colonne("","","center","","","","",$etat,"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        if($filtre_etat == "" || $filtre_etat == "-1") {
            $chaine .= afficher_message_erreur("Aucune période dans la base...");
        } else if($filtre_etat == "-2") {
            $chaine .= afficher_message_erreur("Aucune période non close dans la base...");
        } else {
            $chaine .= afficher_message_erreur("Aucune période $filtre_etat dans la base...");
        }
    }
    return($chaine);
}

function retrouver_periode($id,$short=false) {
    global $base_periodes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select libelle,datedebut,datefin from $base_periodes where id='$id'");
    $texte = "??? periode n° $id ???";
    if (mysqli_num_rows($rep) != 0)
    {
        list($libelle,$datedebut,$datefin) = mysqli_fetch_row($rep);
        $texte = ($short ? "$libelle" : "$libelle (du " . dateexterne($datedebut) . " au " . dateexterne($datefin) . ")");
    }
    return($texte);
}

function periode_active($idperiode) {
    global $base_periodes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select etat, UNIX_TIMESTAMP(datecommande) - UNIX_TIMESTAMP(curdate()) " .
                       "from $base_periodes where id='$idperiode'");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($etat, $restant) = mysqli_fetch_row($rep);
        if($etat == "Active" && $restant >= ($g_delta_date_verrouillage * 24 * 3600)) {
            return True;
        }
    }
    return False;
}

function afficher_liste_periodes($nomvariable="idperiode",$defaut=0,$actives=False,$addAll=False) {
    global $base_periodes;
    $condition = $actives ? "etat!='Close'" : "1";
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    if($defaut == "" || $defaut == 0) $defaut = retrouver_periode_courante();
    if($addAll) {
        $texte .= "<option value=\"-1\" " . ($defaut == "-1" ? "selected" : "") .">Toutes les périodes</option>\n";
        $texte .= "<option value=\"-2\" " . ($defaut == "-2" ? "selected" : "") .">Périodes non closes</option>\n";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,libelle,datedebut,datefin from $base_periodes where $condition order by datedebut desc");
    if(mysqli_num_rows($rep) != 0)
    {
        while (list($id,$libelle,$datedebut,$datefin) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $id . "\"";
            if ($id == $defaut) $texte .= " selected";
            $texte .= ">" . $libelle . " du " . dateexterne($datedebut) . " au " . dateexterne($datefin) . "</option>\n";
        }
    }
    else
    {
        $texte .= "<option value=\"\">Pas de commande possible en ce moment...</option>";
    }
    $texte .= "</select>\n";
    return($texte);
}

function afficher_etats_periode($nomvariable, $defaut="Preparation") {
    global $tab_etats_periodes;
    reset($tab_etats_periodes);
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    foreach($tab_etats_periodes as $key => $val)
    {
        $texte .= "<option value=\"" . $key . "\"";
        if ($key == $defaut) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return $texte;
}

function afficher_etats_periode_pour_filtre($nomvariable, $default="-2") {
    global $tab_etats_periodes;
    reset($tab_etats_periodes);
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $texte .= "<option value=\"-1\" " . ($default == "-1" ? "selected" : "") .">Toutes les périodes</option>\n";
    $texte .= "<option value=\"-2\" " . (($default == "" || $default == "-2") ? "selected" : "") .
        ">Périodes Non closes</option>\n";
    foreach($tab_etats_periodes as $key => $val)
    {
        $texte .= "<option value=\"" . $key . "\"";
        if ($key == $default) $texte .= " selected";
        $texte .= ">" . $val . "</option>\n";
    }
    $texte .= "</select>\n";
    return "$texte";
}

function retrouver_periode_derniere() {
    global $base_periodes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, datecommande from $base_periodes order by datecommande desc limit 1");
    $texte = 0;
    if (mysqli_num_rows($rep) != 0) {
        list($texte, $datecommande) = mysqli_fetch_row($rep);
    }
    return($texte);
}

function retrouver_periode_courante($verrouillage = false) {
    global $base_periodes, $g_delta_date_verrouillage;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, etat, UNIX_TIMESTAMP(datecommande) - UNIX_TIMESTAMP(curdate()) from ".
                       "$base_periodes where datecommande >= curdate() order by datecommande limit 1");
    $texte = 0;
    if (mysqli_num_rows($rep) != 0)
    {
        list($texte, $etat, $restant) = mysqli_fetch_row($rep);
        if($etat == "Close" && $verrouillage) {
            $texte = 0; // La periode courante est verouillée.
        }
        else if($verrouillage && ($restant < ($g_delta_date_verrouillage * 24 * 3600))) {
            $texte = -1; // La periode courante est verouillée.
        }
    }
    return $texte;
}

function afficher_date_prochaine_commande() {
    global $base_periodes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select datecommande from $base_periodes where datecommande >= curdate() and etat='Active' ".
                       "order by datecommande limit 1");
    $texte = "";
    if (mysqli_num_rows($rep) != 0)
    {
        list($texte) = mysqli_fetch_row($rep);
    }
    return $texte;

}

function afficher_date_verrouillage_prochaine_commande() {
    global $base_periodes, $g_delta_date_verrouillage;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select datecommande - INTERVAL $g_delta_date_verrouillage DAY from $base_periodes ".
                       "where datecommande >= curdate() order by datecommande limit 1");
    $texte = "";
    if(mysqli_num_rows($rep) != 0)
    {
        list($texte) = mysqli_fetch_row($rep);
    }
    return($texte);
}

function controler_date_fin_commande() {
    require_once('fonctions_generales.php');
    require_once('fonctions_communes.php');
    require_once(ABSPATH . WPINC . '/pluggable.php');
    require_once(ABSPATH . WPINC . '/general-template.php');

    ecrire_log_admin("Controle date fin de commande");

    global $g_envoyer_relance, $base_clients, $base_periodes, $g_delta_date_relance, $g_delta_date_verrouillage,
        $g_email_relance, $email_gestionnaires;
    if(!$g_envoyer_relance || !isset($email_gestionnaires) || $email_gestionnaires == '')
    {
        ecrire_log_admin("Controle date fin de commande non actif");
        return;
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datecommande,datecommande - INTERVAL $g_delta_date_verrouillage DAY " .
                       "from $base_periodes where datecommande >= curdate() and etat='Active' and ".
                       "datediff(datecommande, curdate()) <= '$g_delta_date_relance' and relancemail != 'oui' ".
                       "order by datecommande limit 1");
    if(mysqli_num_rows($rep) != 0)
    {
        list($id,$datecommande,$dateverrouillage) = mysqli_fetch_row($rep);
        mysqli_query($GLOBALS["___mysqli_ston"], "update $base_periodes set relancemail = 'oui' where id='$id'");
        $vars = array(
                "%DATE_COMMANDE%" => dateexterne($datecommande),
                "%DATE_VERROUILLAGE%" => dateexterne($dateverrouillage),
        );
        $name = get_bloginfo("name");

        $mail_subject = utf8_decode(stripslashes(message_courrier("relancesujet", $vars)));
        if(!isset($g_email_relance) || $g_email_relance == "") {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nom,prenom,email from $base_clients where etat='Actif'");
            while(list($nom,$prenom,$email) = mysqli_fetch_row($rep)) {
                $user_vars = array_merge(array("%EMAIL%" => $email, "%PRENOM%" => $prenom, "%NOM%" => $nom), $vars);
                send_email($email, "", $mail_subject, utf8_decode(stripslashes(message_courrier("relancemessage", $user_vars))));
            }
        } else {
            send_email($g_email_relance, "", $mail_subject, utf8_decode(stripslashes(message_courrier("relancemessage", $vars))));
        }
    } else {
        ecrire_log_admin("Controle date fin de commande: pas de commandes");
    }
}

function notification_producteurs_form($idperiode) {
    global $email_gestionnaires;
    $vars = array(
        "%PERIODE%" => retrouver_periode($idperiode),
    );
    $subject = message_courrier("notificationproducteurssujet", $vars);
    $message = message_courrier("notificationproducteursmessage", $vars);

    $texte = afficher_titre("Notification dates de livraisons producteurs");
    $champs["libelle"] = array("Message","CC / Répondre A", "Sujet", "Message", "", "");
    $champs["type"] = array("", "text","text", "textarea", "hidden", "submit");
    $champs["lgmax"] = array("", "80", "80", "15","","");
    $champs["taille"] = array("","80", "80","80","","");
    $champs["nomvar"] = array("","mail_cc","mail_subject", "mail_message","idperiode", "");
    $champs["valeur"] = array("","",$subject,$message,$idperiode, "Valider");
    $champs["aide"] = array("","","","Le texte %LISTE_DATES% sera remplacé par les dates de livraison du producteur","","");
    $texte .= saisir_enregistrement($champs,"?action=confnotification","notification",50,20,5,5,false,"");
    return $texte;
}

?>
