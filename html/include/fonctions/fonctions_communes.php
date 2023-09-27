<?php

function ecrire_log_public($texte) {
    global $base_journal;
    $user = wp_get_current_user();
    if ($user) {
        $userlogin = $user->user_login;
    } else {
        $userlogin = "<unknown>";
    }

    mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_journal (date,auteur,commentaire) values(now(),'" . $userlogin . "','$texte')");
}

function ecrire_log_admin($texte) {
    ecrire_log_public($texte);
}

function encode_password($password)    {
    return  '*' . strtoupper(sha1(sha1($password, true)));
}

function afficher_menu($choixmenu="") {
    global $choix_menu,$lang,$annee,$dico,$idxmenu_petit,$idxmenu_moyen,$baseurl,$tab_menu;
    reset($tab_menu);
    $chaine = html_debut_tableau("100%","","","","","center","top");
    $chaine .= html_debut_ligne();
    $texte = "";
    $query_url = $_SERVER['QUERY_STRING'];
    $chaine .= html_colonne("","","center","","","","2",$texte);
    $chaine .= html_fin_ligne();
    while (list($key,$val) = each($tab_menu))
    {
        $chaine .= html_debut_ligne();
        if ($val["type"] == "princ")
        {
            $style = (strtolower($choixmenu) == $key ? "boutonmoyensel" : "boutonmoyen");
            $chaine .= html_colonne("","10","","","","","2");
            $chaine .= html_fin_ligne();
            $chaine .= html_debut_ligne();
            $chaine .= html_colonne("","","left","","","","",html_image($idxmenu_moyen),"",$style);
            $chaine .= html_colonne("","","left","","","","",($val["lien"] == "" ? stripslashes($val["libelle"]) : html_lien($val["lien"],"_top",stripslashes($val["libelle"]))),"",$style);
        }
        else
        {
            $style = (strtolower($choixmenu) == $key ? "boutonpetitsel" : "boutonpetit");
            $chaine .= html_colonne("","","left","","","","",(strtolower($choixmenu) == $key ? html_image($idxmenu_petit) : "&nbsp;"),"","$style");
            $chaine .= html_colonne("","","left","","","","",($val["lien"] == "" ? stripslashes($val["libelle"]) : html_lien($val["lien"],"_top",stripslashes($val["libelle"]))),"",$style);
        }
        $chaine .= html_fin_ligne();
    }
    $chaine .= html_debut_ligne();
    $chaine .= html_colonne("","10","","","","","2");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
    return("$chaine");
}

function selectionner_utilisateur($nomvariable,$defaut="") {
    global $base_utilisateurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nomutil from $base_utilisateurs where 1");
    $texte = "";
    if (mysqli_num_rows($rep) != 0)
    {
        $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
        while (list($nom) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $nom . "\"";
            if ($id == $defaut) $texte .= " selected";
            $texte .= ">" . $nom . "</option>\n";
        }
        $texte .= "</select>\n";
    }
    return($texte);
}

?>