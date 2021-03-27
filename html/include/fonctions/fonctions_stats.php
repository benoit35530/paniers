<?php

function stats_producteurs() {

    global $base_producteurs,$base_produits,$base_commandes;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom from $base_producteurs where 1");
    $chaine = "";

    while(list($idproducteur,$nomproducteur) = mysqli_fetch_row($rep0))
    {

        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode,count(*) from $base_commandes where idproducteur='$idproducteur' group by idperiode");
        $chaine .= html_debut_tableau("50%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","2","$nomproducteur","","thliste");
        $chaine .= html_fin_ligne();
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Période","","thliste");
        $chaine .= html_colonne("","","center","","","","","Commandes","","thliste");
        $chaine .= html_fin_ligne();

        while(list($idperiode,$nbcommandes) = mysqli_fetch_row($rep1))
        {

            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","",retrouver_periode($idperiode,true),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",$nbcommandes,"","tdliste");
            $chaine .= html_fin_ligne();

        }

        $chaine .= html_fin_tableau() . "<br>";

        $chaine .= html_debut_tableau("95%","0","5","5","","center");
        $chaine .= html_debut_ligne("","","","","");
        $chaine .= html_colonne("","","center","top","","","","<img src=\"./graphes/stats_producteurs.php?id=$idproducteur\" border=\"0\">");
        $chaine .= html_fin_ligne();
        $chaine .= html_fin_tableau() . "<br>";

    }

    return($chaine);

}

function stats_permanences() {

    global $base_permanenciers,$base_clients;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,codeclient,nom,prenom from $base_clients where 1 order by nom, prenom");
    $chaine = html_debut_tableau("50%","0","2","0");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("","","center","","","","","Nom, prénom (code client)","","thliste");
    $chaine .= html_colonne("","","center","","","","","Nb. permanences","","thliste");
    $chaine .= html_fin_ligne();

    while(list($idclient,$codeclient,$nom,$prenom) = mysqli_fetch_row($rep0))
    {

        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_permanenciers where idclient='$idclient'");

        while(list($nb) = mysqli_fetch_row($rep1))
        {

            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","",strtoupper($nom) . " $prenom ($codeclient)","",($nb != 0 ? "tdliste" : "tdlisteinv"));
            $chaine .= html_colonne("","","center","","","","",$nb,"",($nb != 0 ? "tdliste" : "tdlisteinv"));
            $chaine .= html_fin_ligne();

        }

    }

    $chaine .= html_fin_tableau();
    return($chaine);

}

function stats_clients() {

    global $base_bons_cde,$base_clients;

    $chaine = "";
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,codeclient,nom,prenom from $base_clients where 1 order by nom, prenom");
    $chaine = html_debut_tableau("50%","0","2","0");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("","","center","","","","","Nom, prénom (code client)","","thliste");
    $chaine .= html_colonne("","","center","","","","","Nb. commandes","","thliste");
    $chaine .= html_fin_ligne();

    while(list($idclient,$codeclient,$nom,$prenom) = mysqli_fetch_row($rep0))
    {

        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_bons_cde where idclient='$idclient'");

        while(list($nb) = mysqli_fetch_row($rep1))
        {

            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","",strtoupper($nom) . " $prenom ($codeclient)","",($nb != 0 ? "tdliste" : "tdlisteinv"));
            $chaine .= html_colonne("","","center","","","","",$nb,"",($nb != 0 ? "tdliste" : "tdlisteinv"));
            $chaine .= html_fin_ligne();

        }

    }

    $chaine .= html_fin_tableau();
    return($chaine);

}

?>