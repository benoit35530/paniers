<?php
//
// Fonctions générales
//
require_once(paniers_dir . "/../vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Mpdf\Mpdf;

function listeannee($valeur,$champ) {
    $tab_annee = array("2006","2007","2008","2009","2010","2011");
    $texte = "<select size=\"1\" name=\"$champ\">\n";
    foreach($tab_annee as $year)
    {
        $texte .= "<option value=\"" . $year . "\"";
        if ($valeur == $year) $texte .= " selected";
        $texte .= ">" . $year . "</option>\n";
    }
    $texte .= "</select>\n";
    return($texte);
}

function afficher_titre($titre) {
    global $tab_icones,$choixmenu;
    $choix = strtolower($choixmenu);
    $chaine = html_debut_tableau("100%","0","0","0");
    $chaine .= html_debut_ligne();
    $texte = (isset($tab_icones[$choix]) && $tab_icones[$choix] != "" ? html_image($tab_icones[$choix],"0","","50","50") . "&nbsp;" . $titre . "&nbsp;" . html_image($tab_icones[$choix],"0","","50","50") : $titre);
    $chaine .= html_colonne("","","center","","","","","<h2>$texte</h2>");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    return($chaine);
}

function afficher_bouton_moyen($libelle,$lien="",$target="_top",$style="boutonmoyen") {
    global $idxmenu_moyen;
    $chaine = html_image($idxmenu_moyen) . "&nbsp;" . ($lien == "" ? html_span_class(stripslashes($libelle),"$style") : html_lien("$lien","$target",stripslashes($libelle),"$style"));
    return("$chaine");
}

function afficher_bouton($libelle,$lien="",$target="_top",$style="boutonpetit") {
    global $idxmenu_petit;
    $chaine = ($lien == "" ? html_span_class(stripslashes($libelle),"$style") : html_lien("$lien","$target",stripslashes($libelle),"$style"));
    return("$chaine");
}

function afficher_message_info($message) {
    $chaine = html_debut_tableau("","0","5","5","","center","middle");
    $chaine .= html_debut_ligne();
    $chaine .= html_colonne("","","center","","","","","$message","","textemsginfo");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    return("$chaine");
}

function afficher_message_erreur($message) {
    $chaine = html_debut_tableau("","0","5","5","","center","middle");
    $chaine .= html_debut_ligne();
    $chaine .= html_colonne("","","center","","","","","$message","","textemsgerreur");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    return("$chaine");
}

// Enlever les secondes dans un champ TIME

function heures_minutes($temps) {
    $time_array = explode(":",$temps);
    return("$time_array[0]:$time_array[1]");
}

function dateexterne($date,$annee=true) {
    $date_array = explode("-",$date);
    return("$date_array[2]/$date_array[1]" . ($annee ? "/" . $date_array[0] : ""));
}

function datelitterale($date,$annee=true) {
    global $liste_mois;
    $date_array = explode("-",$date);
    return("$date_array[2] " . $liste_mois[$date_array[1] - 1] . ($annee ? " " . $date_array[0] : ""));
}

function dateinterne($date) {
    $date_array = explode("/",$date);
    return($date_array[2] . "-" . $date_array[1] . "-" . $date_array[0]);
}

function dateheureexterne($date) {
    $gdh = explode(" ",$date);
    $gdh_date = explode("-",$gdh[0]);
    return("$gdh_date[2]/$gdh_date[1]/$gdh_date[0] $gdh[1]");
}

function presenter_textarea($string,$abrege=false) {
    $string = stripslashes("$string");
    $t_lines = explode("\n", "$string");
    $outlines = "";
    $nb_lignes = 1;
    foreach($t_lines as $thisline)
    {
        $outlines .= "$thisline" . "<br>\n";
        if ($abrege && $nb_lignes++ > 5)
        {
            break;
        }
    }
    if ($abrege && $nb_lignes > 5) $outlines .= " ...";
    return("$outlines");
}

// Fonctions liées aux bases de données

function saisir_enregistrement($champs,$action,$formname,$prc_taille=95,$prc_indent=20,$spacing=0,$padding=6,$consigne=true,$target="") {
    global $lang,$dico;
    $texte = html_debut_tableau("$prc_taille%","",$padding,$spacing);
    $texte .= html_debut_form($action,true,$formname,$target);
    if ($champs["libelle"][0] != "")
    {
        $texte .= html_debut_ligne();
        $texte .= html_colonne("","","","","","","2",stripslashes($champs["libelle"][0]),"","champtitre","champtitretexte");
        $texte .= html_fin_ligne();
    }
    $texte .= html_debut_ligne();
    $texte .= html_colonne("","","","","","","2","","","champdebutenreg");
    $texte .= html_fin_ligne();
    if ($consigne)
    {
        $texte .= html_debut_ligne();
        $texte .= html_colonne("","","center","","","","2","Les champs précédés d'une \"*\" sont obligatoires","","","champconsigne");
        $texte .= html_fin_ligne();
    }
    for ($i = 1; $i < count($champs["libelle"]); $i++)
    {
        if ($champs["type"][$i] == "separateur")
        {
            $texte .= html_debut_ligne();
            $texte .= html_colonne("","","","","","","2",$champs["libelle"][$i],"","champseparateur");
            $texte .= html_fin_ligne();
        }
        else if ($champs["type"][$i] == "titre") {
            $texte .= html_debut_ligne();
            $texte .= html_colonne("","","","","","","2",stripslashes($champs["libelle"][$i]),"","champtitre");
            $texte .= html_fin_ligne();
        }
        else if ($champs["type"][$i] == "afftextfull") {
            $texte .= html_debut_ligne("","","","top");
            $texte .= html_colonne("100%","","","","","","2",stripslashes($champs["valeur"][$i]),"","champvaleur","champvaleurtexte");
            $texte .= html_fin_ligne();
        }
        else if ($champs["type"][$i] != "hidden")
        {
            $texte .= html_debut_ligne("","","","top");
            $texte .= html_colonne("$prc_indent%","","","","","","",$champs["libelle"][$i],"","champlibelle","champlibelletexte");
            $chaine2 = "";
            switch($champs["type"][$i]):
            case "afftext":
                $chaine2 .= stripslashes($champs["valeur"][$i]);
                break;
            case "datepicker":
                $chaine2 .= html_datepicker_input($champs["nomvar"][$i],stripslashes($champs["valeur"][$i]));
                break;
            case "affarea":
                $chaine2 .= presenter_textarea($champs["valeur"][$i]);
                break;
            case "cancel":
                $chaine2 .= html_lien($champs["valeur"][$i],"_top",$dico[$lang]['btn_retour'],"textenormalgras");
                break;
            case "checkbox":
                $chaine2 .= html_checkbox_input($champs["nomvar"][$i],"1",$champs["aide"][$i], $champs["valeur"][$i]);
                break;
            case "file":
                $chaine2 .= html_file_input($champs["nomvar"][$i],$champs["taille"][$i]);
                break;
            case "libre":
                $chaine2 .= $champs["valeur"][$i];
                break;
            case "password":
                $chaine2 .= html_password_input($champs["nomvar"][$i],$champs["taille"][$i],$champs["lgmax"][$i]);
                break;
            case "dummypassword":
                // This is used to disable auto-fill on some browsers...
                $chaine2 .= "<input type=\"text\" style=\"position:absolute; top:-100px\"/>\n";
                $chaine2 .= "<input type=\"password\" style=\"position:absolute; top:-100px\"/>\n";
                break;
            case "radio":
                /* Syntaxe : "valeur actuelle-options-libelles-finligne" */
                /* - options : option1_option2_...              */
                /* - libelles : libelle1_libelle2_...           */
                $valeurs = explode("-",$champs["valeur"][$i]);
                $finligne = (!isset($valeurs[3]) || $valeurs[3] == "" ? "&nbsp;&nbsp;" : $valeurs[3]);
                $options = explode("_",$valeurs[1]);
                $libelles = explode("_",$valeurs[2]);
                for ($j = 0; $j < count($options); $j++)
                {
                    $chaine2 .= html_radio_input($champs["nomvar"][$i],$options[$j],$libelles[$j],($valeurs[0] == $options[$j])) . $finligne;
                }
                break;
            case "submit":
                $chaine2 .= html_bouton_submit(
                    $champs["valeur"][$i] ?? "",
                    "",
                    ($champs["nomvar"][$i] ?? "") != "" ? $champs["nomvar"][$i] : "b1",
                    $champs["action"][$i] ?? "");
                break;
            case "text":
                $chaine2 .= html_text_input($champs["nomvar"][$i],stripslashes($champs["valeur"][$i]),$champs["taille"][$i],$champs["lgmax"][$i]);
                break;
            case "textreadonly":
                $chaine2 .= html_text_input($champs["nomvar"][$i],stripslashes($champs["valeur"][$i]),$champs["taille"][$i],$champs["lgmax"][$i], True);
                break;
            case "textarea":
                $chaine2 .= html_textarea_input($champs["nomvar"][$i],$champs["valeur"][$i],$champs["lgmax"][$i],$champs["taille"][$i]);
                break;
            default:
                break;
            endswitch;
            if (isset($champs["aide"][$i]) && $champs["aide"][$i] != "" && $champs["type"][$i] != "checkbox")
            {
                $chaine2 .= "<br>" . html_span_class($champs["aide"][$i],"champaidetexte") . "\n";
            }
            $texte .= html_colonne((100-$prc_indent)."%","","","","","","",$chaine2,"","champvaleur","champvaleurtexte");
            $texte .= html_fin_ligne();
        }
        else
        {
            $texte .= html_hidden($champs["nomvar"][$i],$champs["valeur"][$i]);
        }
    }
    $texte .= html_debut_ligne();
    $texte .= html_colonne("","","","","","","2","","","champfinenreg");
    $texte .= html_fin_ligne();
    $texte .= html_fin_form();
    $texte .= html_fin_tableau();
    return("$texte");
}

function afficher_enregistrement($champs,$prc_taille=95,$prc_indent=20,$spacing=0,$padding=6) {
    $texte = html_debut_tableau("$prc_taille%","",$padding,$spacing);
    if ($champs["libelle"][0] != "")
    {
        $texte .= html_debut_ligne();
        $texte .= html_colonne("","","","","","","2",$champs["libelle"][0],"","champtitre","champtitretexte");
        $texte .= html_fin_ligne();
    }
    $texte .= html_debut_ligne();
    $texte .= html_colonne("","","","","","","2","","","champdebutenreg");
    $texte .= html_fin_ligne();
    for ($i = 1; $i < count($champs["libelle"]); $i++)
    {
        $texte .= html_debut_ligne("","","","top");
        $texte .= html_colonne($prc_indent."%","","","","","","",$champs["libelle"][$i],"","champlibelle","champlibelletexte");
        $texte .= html_colonne((100-$prc_indent)."%","","","","","","",stripslashes($champs["valeur"][$i]),"","champvaleur","champvaleurtexte");
        $texte .= html_fin_ligne();
    }
    $texte .= html_debut_ligne();
    $texte .= html_colonne("","","","","","","2","","","champfinenreg");
    $texte .= html_fin_ligne();
    $texte .= html_fin_tableau();
    return("$texte");
}

function remplacer_car_speciaux($chaine) {

    return(str_replace(array("\x0a","\x0d"),array('\n','\r'),$chaine));

}

function email($sender,$dest,$objet,$body) {

    $sender .= ($sender == "webmaster" ? "@webazar.org" : "");

    $headers = "From: <$sender>\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-type: text/plain; charset=iso-8859-1\n";
    $headers .= "Reply-to: $sender\n";

    return( mail( $dest, $objet, $body, $headers ) );

}

function afficher_corps_page($PAGE_Titre="",$PAGE_Message="",$PAGE_Contenu="") {

    echo html_debut_tableau("100%","0","5","5");

    if ($PAGE_Titre != "")
    {
        echo html_debut_ligne("","","","top");
        echo html_colonne("100%","","center","","","","",afficher_titre($PAGE_Titre));
        echo html_fin_ligne();

    }

    if ($PAGE_Message != "")
    {

        echo html_debut_ligne("","","","top");
        echo html_colonne("100%","","center","","","","",afficher_message_erreur($PAGE_Message));
        echo html_fin_ligne();

    }

    if ($PAGE_Contenu != "")
    {

        echo html_debut_ligne("","","","top");
        echo html_colonne("100%","","center","","","","",$PAGE_Contenu);
        echo html_fin_ligne();

    }

    echo html_fin_tableau();

}

function export_as_pdf_email($output)
{
    $out = file_get_contents(paniers_dir . "/include/admin/admin_header_exports.php");
    $out .= $output;
    $out .= file_get_contents(paniers_dir . "/include/admin/admin_footer_exports.php");

    try {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($out);
        return $mpdf->Output("name.pdf", "S");
    } catch (Exception $e) {
        $formatter = new ExceptionFormatter($e);
        echo $formatter->getHtmlMessage();
    }
}

function export_as_pdf($output)
{
    $out = file_get_contents(paniers_dir . "/include/admin/admin_header_exports.php");
    $out .= $output;
    $out .= file_get_contents(paniers_dir . "/include/admin/admin_footer_exports.php");

    try {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($out);
        $mpdf->Output();
    } catch (Exception $e) {
        $formatter = new ExceptionFormatter($e);
        echo $formatter->getHtmlMessage();
    }
}

function send_email($mail_to,$mail_cc,$mail_subject, $mail_message)
{
    $mail = new PHPMailer(true);
    try {
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = $paniers_data["smtpserver"];
        $mail->SMTPAuth   = true;
        $mail->Username   = $paniers_data["smtpuser"];
        $mail->Password   = $paniers_data["smtppassword"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 465;                                 //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        //Recipients
        $mail->setFrom('contact@panierseden.fr', 'Paniers d\'EDEN');
        $mail->addAddress($mail_to);
        if($mail_cc != "") {
           $mail->addCC($mail_cc);
        }

        $mail->isHTML(false);
        $mail->Subject = $mail_subject;
        $mail->Body    = $mail_message;
        $mail->send();
    } catch (Exception $e) {
        echo "Echec de l'envoi: {$mail->ErrorInfo}";
        return false;
    }
    return true;
}


function send_export_email($mail_to,$mail_cc,$mail_subject, $mail_message, $output = "")
{
    $paniers_data = get_option("paniers_data");

    $mail = new PHPMailer(true);
    try {
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = $paniers_data["smtpserver"];
        $mail->SMTPAuth   = true;
        $mail->Username   = $paniers_data["smtpuser"];
        $mail->Password   = $paniers_data["smtppassword"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 465;                                 //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above


        //Recipients
        $mail->setFrom('contact@panierseden.fr', 'Paniers d\'EDEN');
        $mail->addAddress($mail_to);
        $user = wp_get_current_user();
        $mail->addReplyTo($user->user_email);
        if($mail_cc != "") {
           $mail->addCC($mail_cc);
        }

        if ($output != "") {
            $mail->addStringAttachment(export_as_pdf_email($output), "export.pdf", $encoding = "base64", "application/pdf");
        }

        $mail->isHTML(false);
        $mail->Subject = utf8_decode(stripslashes($mail_subject));
        $mail->Body    = utf8_decode(stripslashes($mail_message));
        $mail->send();

    } catch (Exception $e) {
        echo "Echec de l'envoi: {$mail->ErrorInfo}";
        return false;
    }
    return true;
}

function send_export_email2($mail_to,$mail_cc,$mail_subject, $mail_message, $output = "")
{
    global $email_gestionnaires;
    // $mail_subject = utf8_decode(stripslashes($mail_subject));
    $mail_to = "foucher.benoit@neuf.fr";
    $mail_subject = '=?UTF-8?Q?'.quoted_printable_encode($mail_subject).'?=';
    // $mail_message = utf8_decode(stripslashes($mail_message));
    $mail_boundary = md5(uniqid(time()));
    $mail_headers = "MIME-Version: 1.0\r\n";
    $mail_headers .= "Content-Type: multipart/mixed; boundary=\"$mail_boundary\"\r\n";
    $user = wp_get_current_user();
    // $mail_headers .= "From: " . $user->display_name . " <". $user->user_email . ">\r\n";
    $mail_headers .= "From: Paniers d'EDEN <contact@panierseden.fr>\r\n";
    if($mail_cc != "") {
        $mail_headers .= "Reply-To: $user->user_email\r\n";
    }
    if($mail_cc != "") {
        $mail_headers .= "Cc: $mail_cc\r\n";
    }

    $mail_body = "--$mail_boundary\r\n";
    $mail_body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
    $mail_body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mail_body .= $mail_message . "\r\n\r\n";
    $mail_body .= "--$mail_boundary\r\n";

    if($output != "") {
        // // // $excel_output = "<html>";
        // // // $excel_output .= "<header>";
        // // // $excel_output .= "<meta http-equiv=\"Content-Type\" content=\"application/vnd.ms-excel; charset=utf-8\" />";
        // // // $excel_output .= "</header>";
        // // // $excel_output .= "<body>";
        // // // $excel_output .= $output;
        // // // $excel_output .= "</body>";
        // // // $excel_output .= "</html>";

        // // $mail_body .= "Content-type: application/vnd.ms-excel; name=export.xls\r\n";
        // // $mail_body .= "Content-transfer-encoding: base64\r\n";
        // // $mail_body .= "Content-Disposition: attachment; filename=export.xls\r\n\r\n";
        // // $mail_body .= chunk_split(base64_encode($excel_output))  . " \r\n";

        // $mail_body .= "--$mail_boundary\r\n";

        $mail_body .= "Content-Type: application/pdf; name=export.pdf\r\n";
        $mail_body .= "Content-Transfer-Encoding: base64\r\n";
        $mail_body .= "Content-Disposition: attachment; filename=export.pdf\r\n\r\n";
        $mail_body .= chunk_split(base64_encode(export_as_pdf($output)))  . "\r\n\r\n";

        $mail_body .= "--$mail_boundary--\r\n";
    }
    return wp_mail($mail_to, $mail_subject, $mail_body, $mail_headers);
}

function message_courrier($id, $vars = array())
{
    $paniers_data = get_option("paniers_data");
    $msg = $paniers_data[$id];
    $search = array("%BLOGURL%", "%BLOGNAME%", "%EMAIL_GESTIONNAIRES%");
    $replace = array(get_bloginfo('wpurl'), get_bloginfo('name'), $paniers_data["adressegestionnaires"]);
    foreach($vars as $s => $r) {
        $search[] = $s;
        $replace[] = $r;
    }
    return str_replace($search, $replace, $paniers_data[$id]);
}

?>
