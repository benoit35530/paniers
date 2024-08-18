<?php

function nombre_permanenciers() {

    global $base_permanenciers;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_permanenciers where 1");
    list($nb) = mysqli_fetch_row($rep);

    return($nb);

}

function formulaire_permanencier($cde="ajout",$id=0,$idpermanence=0,$idclient=0,$commentaire="") {
    $champs["libelle"] = array(($cde == "ajout" ? "Ajout ": ($cde == "modif" ? "Modification " : "Suppression ")) . "d'un permanencier","*Permanence","*Client","Commentaire","");
    $champs["type"] = array("",($cde == "modif" || $cde == "ajout" ? "libre" : "afftext"),($cde == "modif" || $cde == "ajout" ? "libre" : "afftext"),($cde == "modif" || $cde == "ajout" ? "textarea" : "affarea"),"submit");
    $champs["lgmax"] = array("","","","4","");
    $champs["taille"] = array("","","","70","");
    $champs["nomvar"] = array("","","","commentaire","");
    $champs["valeur"] = array("",($cde == "modif" || $cde == "ajout" ? afficher_liste_permanences("idpermanence",$idpermanence) : retrouver_permanence($idpermanence)),($cde == "modif" || $cde == "ajout" ? afficher_liste_clients("idclient",$idclient) : retrouver_client($idclient,true)),$commentaire,($cde == "ajout" ? " Ajouter ": ($cde == "modif" ? " Modifier " : " Supprimer ")));
    $champs["aide"] = array("","Choisissez la permanence à laquelle vous inscrivez ce client","Choisissez le client concerné","Commentaire (visible sur le site public)","");
    return(saisir_enregistrement($champs,"?action=conf" . $cde . "&id=$id","formpermanencier","70","20"));
}

function gerer_liste_permanenciers() {

    global $base_permanenciers;

    $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,idpermanence,idclient,commentaire,datemodif from $base_permanenciers where 1 order by idpermanence desc");

    if (mysqli_num_rows($rep1) != 0)
    {

        $chaine = html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Permanence","","thliste");
        $chaine .= html_colonne("","","center","","","","","Client","","thliste");
        $chaine .= html_colonne("","","center","","","","","Modifié le","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();

        while (list($id,$idpermanence,$idclient,$commentaire,$datemodif) = mysqli_fetch_row($rep1))
        {

            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","",retrouver_permanence($idpermanence),"","tdliste");
            if ($idclient <= 0) {
                $chaine .= html_colonne("","","left","","","","","Erreur: client inconnu","","tdliste");
            } else {
                $chaine .= html_colonne("","","left","","","","",retrouver_client($idclient,true),"","tdliste");
            }
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $action = html_lien("?action=modif&id=$id","_top","Modifier");
            $action .= " | " . html_lien("?action=suppr&id=$id","_top","Supprimer");
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();

        }

        $chaine .= html_fin_tableau();

    }
    else
    {
        $chaine = afficher_message_erreur("Aucun permanencier dans la base...");
    }

    return($chaine);

}

function retrouver_permanenciers($idpermanence,$affcodeclient=true) {
    global $base_permanenciers;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idclient from $base_permanenciers where idpermanence='$idpermanence'");
    if (mysqli_num_rows($rep) != 0)
    {
        while(list($idclient) = mysqli_fetch_row($rep))
        {
            if ($idclient <= 0) {
                $tab_permanenciers[] = ("Erreur: permanencier inconnu");
            } else {
                $tab_permanenciers[] = retrouver_client($idclient,$affcodeclient);
            }
        }
    }
    else
    {
        $tab_permanenciers = array();
    }
    return($tab_permanenciers);
}

function verifier_non_inscription($idpermanence,$idclient) {
    global $base_permanenciers;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(id) from $base_permanenciers where idpermanence='$idpermanence' and idclient='$idclient'");
    list($count) = mysqli_fetch_row($rep);
    return($count == 0);

}

?>
