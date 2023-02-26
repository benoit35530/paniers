<?php

function nombre_avoirs() {
    global $base_avoirs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_avoirs where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_avoir($cde,$id,$idclient,$idproducteur,$montant,$description) {
    global $g_lib_somme_admin, $base_producteurs, $base_avoirs;

    $champs["libelle"] = array(($cde == "modif" ? "Modification " : "Suppression ") . "d'un avoir","*Client","Producteur","*Montant","Description","");
    $fieldtype = $cde == "modif" ? "text" : "afftext";
    $fieldtypelibre = $cde == "modif" ? "libre" : "afftext";
    $champs["type"] = array("","afftext",$fieldtypelibre,$fieldtype,$cde == "modif" ? "textarea" : "afftext","submit");
    $champs["lgmax"] = array("","20","20","20","4","");
    $champs["taille"] = array("","20","20","20","60","");
    $champs["nomvar"] = array("","idclient","idproducteur","montant","description");
    if($cde == "modif") {
        $champs["valeur"] = array("",
                                  retrouver_client($idclient),
                                  afficher_liste_producteurs("idproducteur",$idproducteur,True),
                                  $montant,
                                  $description,
                                  " Modifier ");
    } else {
        $champs["valeur"] = array("",
                                  retrouver_client($idclient),
                                  retrouver_producteur($idproducteur),
                                  $montant,
                                  $description,
                                  " Supprimer ");
    }
    $champs["aide"] = array("","","Choisissez le producteur sur lequel sera déduit l'avoir. Ne choisissez pas de producteur pour affecter l'avoir sur le compte des paniers.", "", "","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formavoir","70","20"));
}

function gerer_liste_avoirs($actifsOnly=True) {
    global $base_avoirs,$base_clients,$base_producteurs,$base_bons_cde;
    $condition =( $actifsOnly ? "idboncommande = 0" : "1");
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_avoirs.id,$base_clients.nom,$base_clients.prenom,$base_clients.codeclient," . 
                       "$base_avoirs.datemodif,idboncommande,idproducteur,montant,description" . 
                       ($actifsOnly ? " " : ",$base_bons_cde.idboncde ") .
                       "from $base_avoirs " . 
                       ($actifsOnly ? "" :
                        "inner join $base_bons_cde on $base_avoirs.idboncommande = $base_bons_cde.id " ).
                       "inner join $base_clients on $base_avoirs.idclient = $base_clients.id " .
                       "where $condition order by $base_avoirs.datemodif desc");

    $chaine = "";
    if(mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("70%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","left","","","","","Date","","thliste");
        $chaine .= html_colonne("","","center","","","","","Client","","thliste");
        $chaine .= html_colonne("","","center","","","","","Producteur","","thliste");
        $chaine .= html_colonne("","","center","","","","","Montant","","thliste");
        $chaine .= html_colonne("","","center","","","","","Description","","thliste");
        if(!$actifsOnly) {
            $chaine .= html_colonne("","","center","","","","","Commande","","thliste");
        } else {
            $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        }
        $chaine .= html_fin_ligne();
        while (list($id,$nom,$prenom,$codeclient,$datemodif,$idboncommande,$idproducteur,$montant,$description, $idboncde) = 
               mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","",$datemodif,"","tdliste");
            $chaine .= html_colonne("","","left","","","","","$nom $prenom ($codeclient)","","tdliste");
            if($idproducteur > 0) {
                $chaine .= html_colonne("","","left","","","","",retrouver_producteur($idproducteur),"","tdliste");
            } else {
                $chaine .= html_colonne("","","left","","","","","","","tdliste");
            }
            $chaine .= html_colonne("","","center","","","","",sprintf("%.02f",$montant),"","tdliste");
            $chaine .= html_colonne("","","left","","","","",$description,"","tdliste");
            if(!$actifsOnly) {
                $chaine .= html_colonne("","","center","","","","",html_lien("commandes.php?action=detail&id=$idboncommande","_top", $idboncde),"","tdliste");
            } else {
                $action = html_lien("?action=modif&id=$id","_top","Modifier");
                if($idboncommande == 0) {
                    $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
                }
                $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            }
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        if($actifsOnly) {
            $chaine .= afficher_message_erreur("Aucun avoir en cours dans la base...");
        } else {
            $chaine .= afficher_message_erreur("Aucun avoir dans la base...");
        }
    }
    return($chaine);
}

function gerer_liste_avoirs_periode($idperiode) {
    global $base_avoirs,$base_clients,$base_producteurs,$base_bons_cde;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_avoirs.id,$base_clients.nom,$base_clients.prenom,$base_clients.codeclient," .
                       "datemodif,idboncommande,idproducteur,montant,description,$base_bons_cde.idboncde " .
                       "from $base_avoirs " . 
                       "inner join $base_bons_cde on $base_avoirs.idboncommande = $base_bons_cde.id " .
                       "inner join $base_clients on $base_avoirs.idclient = $base_clients.id " .
                       "where $base_bons_cde.idperiode='$idperiode' " . 
                       "order by $base_clients.nom, $base_clients.prenom desc");

    $chaine = "";
    if(mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("70%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Date","","thliste");
        $chaine .= html_colonne("","","center","","","","","Client","","thliste");
        $chaine .= html_colonne("","","center","","","","","Producteur","","thliste");
        $chaine .= html_colonne("","","center","","","","","Montant","","thliste");
        $chaine .= html_colonne("","","center","","","","","Description","","thliste");
        $chaine .= html_colonne("","","center","","","","","Commande","","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$nom,$prenom,$codeclient,$datemodif,$idboncommande,$idproducteur,$montant,$description, $idboncde) =
               mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","","$nom $prenom ($codeclient)","","tdliste");
            $chaine .= html_colonne("","","left","","","","",dateheureexterne($datemodif),"","tdliste");
            if($idproducteur > 0) {
                $chaine .= html_colonne("","","left","","","","",retrouver_producteur($idproducteur),"","tdliste");
            } else {
                $chaine .= html_colonne("","","left","","","","","","","tdliste");
            }
            $chaine .= html_colonne("","","center","","","","",sprintf("%.02f",$montant),"","tdliste");
            $chaine .= html_colonne("","","left","","","","",$description,"","tdliste");
            $chaine .= html_colonne("","","center","","","","",html_lien("commandes.php?action=detail&id=$idboncommande","_top", $idboncde),"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucun avoir dans la base...");
    }
    return $chaine;
}

function retrouver_avoirs($idclient,$idboncommande) {
    global $base_avoirs,$base_clients,$base_producteurs,$base_bons_cde;
    $avoirs = array();
    if($idclient > 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idproducteur,montant,description " . 
                           "from $base_avoirs where idclient='$idclient' and idboncommande=0");
        while(list($id,$idproducteur,$montant,$description) = mysqli_fetch_row($rep))
        {
            $avoirs[$idproducteur]["description"][$id] = $description;
            $avoirs[$idproducteur]["montant"][$id] = $montant;
        }
    }

    if($idboncommande > 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idproducteur,montant,description " . 
                           "from $base_avoirs where idboncommande='$idboncommande'");
        while(list($id,$idproducteur,$montant,$description) = mysqli_fetch_row($rep)) {
            $avoirs[$idproducteur]["description"][$id] = $description;
            $avoirs[$idproducteur]["montant"][$id] = $montant;
        }
    }
    return $avoirs;
}

?>
