<?php

foreach($_POST as $k=>$v) { $$k = $v; }

require_once('../../../../../wp-blog-header.php');
if(!isset($export))
{
    $export = $wp_query->get("action");
}

if(!isset($export) || ($export != "excel" && $export != "impression" && $export != "pdf")) {
    require_once("../include/fonctions_include_admin.php");
    require_once("../include/admin/admin_menu_exports.php");
}
else {
    require_once("../include/fonctions_include_exports.php");
    require_once("../include/admin/admin_header_exports.php");
}

if($export == "email") {
    $destsuccess = array();
    $destfailed = array();
}

if(!utilisateurIsAdmin()) {
    $idproducteur = obtenir_producteur_utilisateur();
    $iddepot = obtenir_depot_utilisateur();
    $exporttype = "impression-impression_pdf_excel-Imprimante_Pdf_Excel";
} else {
    $exporttype = "impression-impression_pdf_email_excel-Imprimante_Pdf_Email_Excel";
}

$depots=array();
if(isset($iddepot)) {
    if($iddepot == "-1") {
        if(!empty($idperiode)) {
            $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select iddepot from $base_bons_cde where idperiode='$idperiode' group by iddepot");
            while(list($depot) = mysqli_fetch_row($rep1)) {
                $depots[retrouver_depot($depot) . " $depot"] = $depot;
            }
        } else {
            foreach(liste_depots() as $id => $nom) {
                $depots[$nom . " $id"] = $id;
            }
        }
    }
    else {
        $depots[retrouver_depot($iddepot) . " $iddepot"] = $iddepot;
    }
}

$producteurs=array();
if(isset($idproducteur)) {
    if($idproducteur == "-1") {
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur from $base_commandes where idperiode='$idperiode' ".
                            "group by idproducteur");
        while(list($producteur) = mysqli_fetch_row($rep1)) {
            $producteurs[retrouver_producteur($producteur) . " $producteur"] = $producteur;
        }

        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur from $base_avoirs " .
                           "inner join $base_bons_cde on $base_bons_cde.id = $base_avoirs.idboncommande " .
                           "where $base_bons_cde.idperiode='$idperiode' and idproducteur!=0 group by idproducteur");
        while(list($producteur) = mysqli_fetch_row($rep1)) {
            $producteurs[retrouver_producteur($producteur) . " $producteur"] = $producteur;
        }

    } else {
        $producteurs[retrouver_producteur($idproducteur) . " $idproducteur"] = $idproducteur;
    }
}

function export_courrier_form($titre, $action, $formid, $subjectid, $messageid) {
    global $email_gestionnaires, $idperiode, $iddepot, $idproducteur, $export;
    $vars = array(
            "%PERIODE%" => retrouver_periode($idperiode),
            "%DEPOT%" => retrouver_depot($iddepot)
    );
    $subject = message_courrier($subjectid, $vars);
    $message = message_courrier($messageid, $vars);

    $texte = afficher_titre($titre);
    $champs["libelle"] = array("Message","CC / Répondre A", "Sujet", "Message", "", "", "", "", "");
    $champs["type"] = array("", "text","text", "textarea", "hidden", "hidden", "hidden", "hidden", "submit");
    $champs["lgmax"] = array("", "80", "80", "15","","","","");
    $champs["taille"] = array("","80", "80","80","","","");
    $champs["nomvar"] = array("","mail_cc","mail_subject", "mail_message","idperiode", "iddepot","idproducteur", "export", "");
    $champs["valeur"] = array("",$email_gestionnaires,$subject,$message,$idperiode,$iddepot,$idproducteur,$export, "Valider");
    $champs["aide"] = array("","","","","","","","","");
    $texte .= saisir_enregistrement($champs,"?action=$action",$formid,50,20,5,5,false,"");
    return $texte;
}

$output = "";

switch($action):

case "confclients":
    if($export == "email") {
        $output = export_courrier_form("Confirmation envoie de message aux dépôts",
                                       "clients",
                                       "formconfclients",
                                       "exportclientssujet",
                                       "exportclientsmessage");
        $export = "";
        break;
    }

case "clients": {
    $pageBreak = False;
    foreach($depots as $nomdepot => $depot) {
        if($export == "email") {
            $output = "";
            $pageBreak = False;
        }
        if($pageBreak) {
            $output .= "<div style=\"page-break-before: always\"/>";
            $pageBreak = False;
        }
        $output .= afficher_titre("Liste des Consommateurs du dépôt \"" . retrouver_depot($depot) . "\"");
        $output .= export_clients($depot);
        $mail_to = retrouver_depot_email($depot);
        $pageBreak = True;
        if($export == "email") {
            if(send_export_email($mail_to, $mail_cc, $mail_subject, $mail_message, $output)) {
                $destsuccess[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
            } else {
                $destfailed[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
            }
        }
    }
    ecrire_log_admin("Export : liste des consommateurs");
}
break;

case "confrecapclients": {
    if($export == "email") {
        $output = export_courrier_form("Confirmation envoie de message aux dépôts",
                                       "recapclients", "formconfrecapclients", "exportrecapcommandessujet",
                                       "exportrecapcommandesmessage");
        $export = "";
        break;
    }
}

case "recapclients": {
    if (isset($idperiode) && $idperiode != "" && $idperiode != 0) {
        $pageBreak = False;
        foreach($depots as $nomdepot => $depot) {
            if($export == "email") {
                $output = "";
                $pageBreak = False;
            }
            $rep2 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_dates where idperiode='$idperiode' order by datelivraison asc");
            while(list($id) = mysqli_fetch_row($rep2))
            {
                if($pageBreak)
                {
                    $output .= "<div style=\"page-break-before: always\"/>";
                    $pageBreak = False;
                }
                $recap = recapitulatif_commandes_clients($idperiode, $id, $depot);
                if($recap != "") {
                    $output .= $recap . "<br>";
                    $pageBreak = True;
                }
            }
            if($export == "email") {
                $mail_to = retrouver_depot_email($depot);
                if(send_export_email($mail_to, $mail_cc, $mail_subject, $mail_message, $output)) {
                    $destsuccess[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
                } else {
                    $destfailed[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
                }
            }
        }
        ecrire_log_admin("Export : recapitulatif des commandes clients ($export)");
    }
}
break;

case "confrecapproducteurs": {
    if($export == "email") {
        $output = export_courrier_form("Confirmation envoie de message aux producteurs",
                                       "recapproducteurs", "formconfrecapproducteur", "exportcommandessujet",
                                       "exportcommandesmessage");
        $export = "";
        break;
    }
}

case "recapproducteurs": {
    if (isset($idproducteur) && $idproducteur != "" && $idproducteur != 0 &&
        isset($idperiode) && $idperiode != "" && $idperiode != 0) {
        $pageBreak = False;
        if(count($producteurs) == 0) {
            $output = "Pas de commandes pour cette période...";
        }
        else {
            foreach($producteurs as $nomproducteur => $producteur) {
                if($export == "email") {
                    $output = "";
                    $pageBreak = False;
                }
                foreach($depots as $nomdepot => $depot) {
                    if($pageBreak) {
                        $output .= "<div style=\"page-break-before: always\"/>";
                    }
                    $output .= afficher_titre("Commandes du dépôt \"". retrouver_depot($depot) . "\" pour " .
                                              retrouver_producteur($producteur) . ", période : " .
                                              retrouver_periode($idperiode));
                    $output .= recapituler_par_producteur($producteur,$idperiode,$depot) . "<br><br>";
                    $output .= recapituler_par_producteur_client($producteur,$idperiode,$depot);
                    $pageBreak = True;
                }
                if($export == "email") {
                    $mail_to = retrouver_producteur_email($producteur);
                    if(send_export_email($mail_to, $mail_cc, $mail_subject, $mail_message, $output)) {
                        $destsuccess[] = $mail_to . " (producteur " . retrouver_producteur($producteur) . ")";
                    } else {
                        $destfailed[] = $mail_to . " (producteur " . retrouver_producteur($producteur) . ")";
                    }
                }
            }
        }
        ecrire_log_admin("Export : recapitulatif des commandes producteurs ($export)");
    }
}
break;

case "confrecapcheques": {
    if($export == "email") {
        $output = export_courrier_form("Confirmation envoie de message aux producteurs",
                "recapcheques", "formconfrecapcheques", "exportchequessujet", "exportchequesmessage");
        $export = "";
        break;
    }
}

case "recapcheques": {
    if (isset($idperiode) && $idperiode != "" && $idperiode != 0)
    {
        $pageBreak = False;
        foreach($depots as $nomdepot => $depot) {
            if($export == "email") {
                $output = "";
                $pageBreak = False;
            }
            if($pageBreak)
            {
                $output .= "<div style=\"page-break-before: always\"/>";
            }
            $output .= afficher_titre("Chèques clients du dépôt \"". retrouver_depot($depot) . "\" pour la période : " . retrouver_periode($idperiode));
            $output .= recapitulatif_cheques_clients($idperiode, $depot);
            if($export == "email") {
                $mail_to = retrouver_depot_email($depot);
                if(send_export_email($mail_to, $mail_cc, $mail_subject, $mail_message, $output)) {
                    $destsuccess[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
                } else {
                    $destfailed[] = $mail_to . " (dépôt ". retrouver_depot($depot) . ")";
                }
            }
            $pageBreak = True;
        }
        ecrire_log_admin("Export : recapitulatif des chèques ($export)");
    }
}
break;

case "boncommande": {
    $qteproduit = array();
    $output .= afficher_recapitulatif_commande3(retrouver_periode_derniere());
}
break;

default: {
    $output .= afficher_titre("Imprimer un bon de commande vierge");
    $output .= html_centre(html_lien("?action=boncommande&export=impression","","Bon de commande vierge"));

    if($idproducteur > 0) {
        $producteurtype = "afftext";
        $producteurvalue = retrouver_producteur($idproducteur);
    } else {
        $producteurtype = "libre";
        $producteurvalue = afficher_liste_producteurs_et_tous();
    }

    if($iddepot > 0) {
        $depottype = "afftext";
        $depotvalue = retrouver_depot($iddepot);
    } else {
        $depottype = "libre";
        $depotvalue = afficher_liste_depots_et_tous();
    }

    if(utilisateurIsAdmin() || $idproducteur == -1 || $iddepot > 0) {
        $output .= afficher_titre("Produits commandés par chaque client pour une période");
        $champs["libelle"] = array("Choisissez la période et le dépôt","Période","Dépôt","Format d'export","");
        $champs["type"] = array("","libre",$depottype,"radio","submit");
        $champs["lgmax"] = array("","","","","");
        $champs["taille"] = array("","","","","");
        $champs["nomvar"] = array("","","","export","");
        $champs["valeur"] = array("",afficher_liste_periodes(),$depotvalue, $exporttype," Valider ");
        $champs["aide"] = array("","","","","");
        $output .= saisir_enregistrement($champs,"?action=confrecapclients","formrecapclients",50,20,5,5,false,"");
        $output .= "<p>";

        $output .= afficher_titre("Montants des chèques clients pour une période");
        $champs["libelle"] = array("Choisissez la période et le dépôt","Période","Dépôt","Format d'export","");
        $champs["type"] = array("","libre",$depottype, "radio","submit");
        $champs["lgmax"] = array("","","","", "");
        $champs["taille"] = array("","","","", "");
        $champs["nomvar"] = array("","","","export","");
        $champs["valeur"] = array("",afficher_liste_periodes(),$depotvalue,$exporttype," Valider ");
        $champs["aide"] = array("","","","","");
        $output .= saisir_enregistrement($champs,"?action=confrecapcheques","formchequesclients",50,20,5,5,false,"");
        $output .= "<p>";
    }

    if(utilisateurIsAdmin() || $iddepot == -1 || $idproducteur > 0) {
        $output .= afficher_titre("Récapitulatif des commandes par producteur");
        $champs["libelle"] = array("Choisissez le producteur, la période et le dépôt","Producteur","Période","Dépôt","Format d'export","");
        $champs["type"] = array("",$producteurtype,"libre","libre","radio","submit");
        $champs["lgmax"] = array("","","","","","");
        $champs["taille"] = array("","","","","","");
        $champs["nomvar"] = array("","","","","export","");
        $champs["valeur"] = array("",$producteurvalue,afficher_liste_periodes(),afficher_liste_depots_et_tous(),$exporttype," Valider ");
        $champs["aide"] = array("","","","","","");
        $output .= saisir_enregistrement($champs,"?action=confrecapproducteurs","formrecapproducteurs",50,20,5,5,false,"");
    }

    if(utilisateurIsAdmin() || $idproducteur == -1 || $iddepot > 0) {
        $output .= afficher_titre("Liste des clients actifs");
        $champs["libelle"] = array("Choisissez le dépôt", "Dépôt", "Format d'export","");
        $champs["type"] = array("", $depottype,"radio","submit");
        $champs["lgmax"] = array("","","","");
        $champs["taille"] = array("","","","");
        $champs["nomvar"] = array("","","export","");
        $champs["valeur"] = array("", $depotvalue, $exporttype," Valider ");
        $champs["aide"] = array("","","","");
        $output .= saisir_enregistrement($champs,"?action=confclients","formclients",50,20,5,5,false,"");
    }
}
break;

endswitch;

if($export == "email") {
    $output = "<center>";
    if(count($destsuccess) > 0) {
        $output .= "<h1>Les emails ont été envoyées avec succès aux destinataires suivant:</h1>";
        foreach($destsuccess as $email) {
            $output .= "<br>" . $email;
        }
        if(count($destsucess) > 0 && $mail_cc != '') {
            $output .= "<br>" . $mail_cc . " (CC:)";
        }
    }
    if(count($destfailed) > 0) {
        $output .= "<h1>L'envoie a échoué pour les destinataires suivant:</h1>";
        foreach($destfailed as $email) {
            $output .= "<br>" . $email;
        }
    }
    $output .= "<br><br><br><a href=\"exports.php\">Retour à la page des exports</a>";
    $output .= "</center>";
    echo $output;
}
else if($export == "pdf") {
    echo export_as_pdf($output);
}
else {
    echo $output;
}

if (!isset($export) || ($export != "excel" && $export != "impression" && $export != "pdf")) {
    require_once("../include/admin/admin_footer.php");
}
else {
    require_once("../include/admin/admin_footer_exports.php");
}
?>