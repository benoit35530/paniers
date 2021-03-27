<?php

// Reformattage terminé

//
// Fonctions d'affichage HTML
//

function html_afficher_champ($libelle,$champ,$oblig=false,$aide="",$indent="20") {

    $indent2 = 100 - $indent;
    $chaine = html_debut_ligne("","","","top");
    if ($oblig) { $etoile = html_span_class("*","textepetit"); } else { $etoile = ""; }
    $chaine .= html_colonne("$indent%","","left","","","","",$etoile . html_span_class($libelle,"textenormalgras"));
    $texte = html_span_class($champ,"textenormal");
    if ($aide != "") $texte .= "<br>" . html_span_class($aide,"textepetit");
    $chaine .= html_colonne("$indent2%","","left","","","","",$texte);
    $chaine .= html_fin_ligne();
    return("$chaine");
}

function html_champ($libelle,$champ,$type,$lg_max,$lg_case,$valeur,$aide="",$oblig=false,$indent="20") {

    $chaine = html_debut_ligne("","","","top");
    $texte = "";
    if ($oblig) $texte .= html_span_class("*","textepetit");
    $texte .= html_span_class($libelle,"textenormalgras");
    $chaine .= html_colonne("$indent%","","left","","","","",$texte);

    $texte = "";
    if ($type == 'text' || $type == 'password')
    {
        $texte .= "<input type=\"$type\" name=\"$champ\" size=\"$lg_case\" maxlength=\"$lg_max\"";
    }
    else if ($type == 'textarea')
    {
        $texte .= "<textarea rows=\"$lg_max\" name=\"$champ\" cols=\"$lg_case\"";
    }
    else if ($type == 'file')
    {
        $texte .= "<input type=\"file\" size=\"$lg_max\" name=\"$champ\"";
    }

    if ($valeur != '' && $type != 'textarea')
    {
        $texte .= " value=\"" . htmlentities(stripslashes($valeur),ENT_QUOTES) . "\"";
    }
    $texte .= ">";
    if ($type == 'textarea')
    {
        if ($valeur != '') $texte .= htmlentities(stripslashes($valeur),ENT_QUOTES);
        $texte .= "</textarea>";
    }

    if ($aide != "") $texte .= "<br>" . html_span_class($aide,"textepetit");
    $indent2 = 100 - $indent;

    $chaine .= html_colonne("$indent2%","","left","","","","",$texte);
    $chaine .= html_fin_ligne();
    return("$chaine");
}

function html_span_class($texte,$classe="textenormal") {
    if ($texte == "") return("");
    return("<span class=\"$classe\">$texte</span>");
}

function html_gras($texte) {
    if ($texte == "") return("");
    return("<b>$texte</b>");
}

function html_centre($texte) {
    if ($texte == "") return("");
    return("<center>$texte</center>");
}

function html_image($fichier,$bordure="0",$alt="",$larg="",$haut="",$alignement="middle") {
    if ($fichier == "") return("");
    $chaine = "<img align=\"$alignement\" src=\"$fichier\" border=\"$bordure\"";
    if ($larg != "") $chaine .= " width=\"" . $larg . "\"";
    if ($haut != "") $chaine .= " height=\"" . $haut . "\"";
    if ($alt != "") $chaine .= " alt=\"" . $alt . "\"";
    $chaine .= ">";
    return("$chaine");
}

function html_lien($lien,$target="_top",$texte,$classe="") {
    if ($lien == "" || $texte == "") return("");
    $chaine = "<a";
    if ($lien != "") $chaine .= " href=\"" . $lien . "\"";
    if ($target != "") $chaine .= " target=\"" . $target . "\"";
    if ($classe != "") $chaine .= " class=\"" . $classe . "\"";
    return("$chaine>$texte</a>");
}

function html_debut_tableau($largeur="",$bordure="",$cellpad="",$cellspace="",$couleur="",$alignement="center",$valignement="",$bgnd="",$classe="",$hauteur="") {
    $texte = "<table";
    if ($largeur != "") $texte .= " width=\"$largeur\"";
    if ($hauteur != "") $texte .= " height=\"$hauteur\"";
    if ($bordure != "") $texte .= " border=\"$bordure\"";
    if ($cellpad != "") $texte .= " cellpadding=\"$cellpad\"";
    if ($cellspace != "") $texte .= " cellspacing=\"$cellspace\"";
    if ($couleur != "") $texte .= " bgcolor=\"$couleur\"";
    if ($alignement != "") $texte .= " align=\"$alignement\"";
    if ($valignement != "") $texte .= " valign=\"$valignement\"";
    if ($bgnd != "") $texte .= " background=\"$bgnd\"";
    if ($classe != "") $texte .= " class=\"$classe\"";
    $texte .= ">\n";
    return("$texte");
}

function html_fin_tableau() {
    return("</table>\n");
}

function html_debut_ligne($larg="",$haut="",$align="",$valign="",$couleur="",$fond="",$nblig="",$classe="") {
    $chaine = "  <tr";
    if ($larg != "") $chaine .= " width=\"" . $larg . "\"";
    if ($haut != "") $chaine .= " height=\"" . $haut . "\"";
    if ($align != "") $chaine .= " align=\"" . $align . "\"";
    if ($valign != "") $chaine .= " valign=\"" . $valign . "\"";
    if ($couleur != "") $chaine .= " bgcolor=\"" . $couleur . "\"";
    if ($fond != "") $chaine .= " background=\"" . $fond . "\"";
    if ($nblig != "") $chaine .= " rowspan=\"" . $nblig . "\"";
    if ($classe != "") $chaine .= " class=\"$classe\"";
    $chaine .=  ">\n";
    return ("$chaine");
}

function html_fin_ligne() {
    return("  </tr>\n");
}

function html_colonne($larg="",$haut="",$align="",$valign="",$couleur="",$fond="",$nbcol="",$contenu="",$nblig="",$classe="",$classetexte="") {
    $chaine = "    <td";
    if ($larg != "") $chaine .= " width=\"" . $larg . "\"";
    if ($haut != "") $chaine .= " height=\"" . $haut . "\"";
    if ($align != "") $chaine .= " align=\"$align\" style=\"text-align: $align\"";
    if ($valign != "") $chaine .= " valign=\"" . $valign . "\"";
    if ($couleur != "") $chaine .= " bgcolor=\"" . $couleur . "\"";
    if ($fond != "") $chaine .= " background=\"" . $fond . "\"";
    if ($nbcol != "") $chaine .= " colspan=\"" . $nbcol . "\"";
    if ($nblig != "") $chaine .= " rowspan=\"" . $nblig . "\"";
    if ($classe != "") $chaine .= " class=\"" . $classe . "\"";
    $chaine .= ">\n";
    if ($classetexte != "" && $contenu != "") $contenu = html_span_class("$contenu","$classetexte");
    if ($contenu != "") $chaine .= "      " . $contenu . "\n";
    $chaine .= "    </td>\n";
    return ("$chaine");
}

function html_paragraphe($texte,$align="",$classe="") {
    $chaine = "<p";
    if ($align != "") $chaine .= " align=\"$align\"";
    if ($classe != "") $chaine .= " class=\"$classe\"";
    $chaine .=  ">" . $texte . "</p>\n";
    return("$chaine");
}

function html_select($nomvar,$valeur,$tb_libelles,$tb_valeurs,$taille=1,$multiple=false) {
    $chaine = "<select name=\"$nomvar\"" . ($taille > 1 ? " size=\"$taille\"" : "") . ($multiple ? " multiple" : "") . ">\n";
    for ($i = 0; $i < count($tb_valeurs); $i++)
    {
        $chaine .= "<option value=\"". $tb_valeurs[$i] . "\"" . ($valeur == $tb_valeurs[$i] ? " selected" : "") . ">" . $tb_libelles[$i] . "</option>\n";
    }
    $chaine .= "</select>\n";
    return("$chaine");
}

function html_text_input($nomvar,$valeur,$taille,$lgmax, $readonly = False) {
    $chaine = "<input type=\"text\" autocomplete=\"off\" value=\"$valeur\" name=\"$nomvar\" size=\"$taille\" maxlength=\"$lgmax\" class=\"champinput\"";
    if($readonly)
    {
        $chaine .= " disabled=\"disabled\"";
    }
    return($chaine . "/>\n");
}

function html_datepicker_input($nomvar,$valeur) {
    return "<input type=\"text\" value=\"$valeur\" class=\"datepicker\" name=\"$nomvar\"/>";
}

function html_password_input($nomvar,$taille,$lgmax) {
    return("<input type=\"password\" autocomplete=\"off\" name=\"$nomvar\" size=\"$taille\" maxlength=\"$lgmax\" class=\"champinput\">\n");
}

function html_file_input($nomvar,$taille) {
    return("<input type=\"file\" name=\"$nomvar\" size=\"$taille\" class=\"champinput\">\n");
}

function html_textarea_input($nomvar,$valeur="",$rows=4,$cols=80) {
    return("<textarea class=\"champinput\" name=\"$nomvar\" rows=\"$rows\" cols=\"$cols\">" . stripslashes($valeur) . "</textarea>\n");
}

function html_hidden($nomvar,$valeur) {
    return("<input type=\"hidden\" value=\"$valeur\" name=\"$nomvar\">\n");
}

function html_checkbox_input($nomvar,$valeur,$libelle,$coche=false) {
    $chaine = "<input class=\"champinput\" type=\"checkbox\" name=\"$nomvar\" value=\"$valeur\"";
    if ($coche) $chaine .= " checked";
    $chaine .= ">&nbsp;&nbsp;" . $libelle;
    return ("$chaine");
}

function html_radio_input($nomvar,$valeur,$libelle,$coche=false) {
    $chaine = "<input class=\"champinput\" type=\"radio\" name=\"" . $nomvar . "\" value=\"" . $valeur . "\"";
    if ($coche) $chaine .= " checked";
    $chaine .= ">&nbsp;" . $libelle;
    return ("$chaine");
}

function html_bouton_submit($texte,$image="",$nomvar="b1") {
    if ($image != "")
    {
        return("<input type=\"image\" src=\"$image\" border=\"0\" alt=\"$nom\" name=\"image1\"/>\n");
    }
    else
    {
        return("<input type=\"submit\" value=\"$texte\" name=\"$nomvar\" class=\"champsubmit\"/>\n");
    }
}

function html_debut_form($action,$mpart=false,$nom="",$target="") {
    if ($mpart) { $texte = "<form enctype=\"multipart/form-data\""; } else { $texte = "<form"; }
    if ($nom != "") $texte .= " name=\"$nom\"";
    $texte .= " autocomplete=\"off\"";
    $texte .= " method=\"post\" action=\"$action\"" . ($target != "" ? " target=\"$target\"" : "") . ">\n";
    return("$texte");
}

function html_fin_form() {
    return("</form>\n");
}

function html_form_bouton($action, $nom, $texte) {
    return html_debut_form($action, false, $nom) . html_bouton_submit($texte) . html_fin_form();
}

function html_lien_mail($adresse="",$texte="",$classe="") {
    if ($adresse == "" || $adresse == "noemail") return("");
    $chaine = "<a ";
    if ($classe != "") $chaine .= " class=\"" . $classe . "\" ";
    if ($texte == "")
    {
        $chaine .= "href=\"mailto:virez.ca." . $adresse . ".pour.le.spam\">" . substr($adresse,0,strpos($adresse,"@")) . "@...</a>";
    }
    else
    {
        $chaine .= "href=\"mailto:virez.ca." . $adresse . ".pour.le.spam\">" . $texte . "</a>";
    }
    return("$chaine");
}

?>
