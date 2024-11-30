<?php

function nombre_lignes_commandes() {
    global $base_commandes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_commandes where 1");
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function nombre_bons_commandes($idclient = 0) {
    global $base_bons_cde;
    if($idclient == 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_bons_cde where 1");
    } else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from $base_bons_cde where idclient = '$idclient'");
    }
    list($nb) = mysqli_fetch_row($rep);
    return($nb);
}

function formulaire_bon_commande($idperiode, $champs, $qteproduit=array(),$avoirs=array())
{
    global $base_dates,$base_producteurs,$base_produits,$base_dates,$g_lib_somme;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode = '$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep0))
    {
        $dates[$nbdates]['id'] = $iddate;
        $dates[$nbdates]['datelivraison'] = $datelivraison;
        $nbdates++;
    }
    if ($nbdates == 0)
    {
        return afficher_message_erreur("Aucune date de disponible pour cette commande...");
    }

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits from $base_producteurs where etat = 'Actif' order by ordre,produits");
    $total_commande = 0.0;

    $absences = retrouver_absences($idperiode);

    while(list($idproducteur,$nom,$produits) = mysqli_fetch_row($rep0))
    {
        $total_producteur = 0.0;
        $champs["libelle"][] = "$produits";
        $champs["type"][] = "afftextfull";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = "<b>$produits - $nom</b>";
        $champs["aide"][] = "";

        $tableau_produits = html_debut_tableau("100%","0");
        $tableau_produits .= html_debut_ligne("","","","top");
        $tableau_produits .= html_colonne("30%","","center","top","","","","Produits","","thliste");
        $tableau_produits .= html_colonne("10%","","center","top","","","","Prix<br>unitaire","","thliste");
        $largeur_colonne = ( 40 / $nbdates ) . "%";
        for ($i = 0; $i < $nbdates; $i++)
        {
            $tableau_produits .= html_colonne($largeur_colonne,"","center","top","","","",datelitterale($dates[$i]['datelivraison'],false),"","thliste");
        }
        $tableau_produits .= html_colonne("10%","","center","top","","","","Total<br>Quantité","","thliste");
        $tableau_produits .= html_colonne("10%","","center","top","","","","Prix<br>total","","thliste");
        $tableau_produits .= html_fin_ligne();

        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,description,prix,UNIX_TIMESTAMP(curdate()) - UNIX_TIMESTAMP(datemodif) from $base_produits where idproducteur = '$idproducteur' and etat = 'Actif' order by description");
        while(list($idproduit,$description,$prix,$sincemodif) = mysqli_fetch_row($rep1))
        {
            if ($sincemodif < 24 * 3600 * 30) { // 30 days
                $description = "&#11088; " . $description;
            }
            $total_qte_produit = 0;
            $tableau_produits .= html_debut_ligne("","","","top");
            $tableau_produits .= html_colonne("","","left","top","","","","$description","","tdliste");
            $tableau_produits .= html_colonne("","","right","top","","","",sprintf($g_lib_somme,$prix),"","tdliste");

            for($i = 0; $i < $nbdates; $i++)
            {
                if(array_key_exists($idproducteur, $qteproduit) &&
                    array_key_exists($idproduit, $qteproduit[$idproducteur]) &&
                    array_key_exists($dates[$i]['id'], $qteproduit[$idproducteur][$idproduit]) &&
                    is_numeric($qteproduit[$idproducteur][$idproduit][$dates[$i]['id']])) {
                    $val = $qteproduit[$idproducteur][$idproduit][$dates[$i]['id']];
                } else {
                    $val = "";
                }

                if(array_key_exists($dates[$i]['id'], $absences) &&
                   array_key_exists($idproducteur, $absences[$dates[$i]['id']]) &&
                   $absences[$dates[$i]['id']][$idproducteur])
                {
                    $tableau_produits .= html_colonne("","","center","top","","","",html_hidden("qteproduit[" . $idproducteur . "][" . $idproduit . "][" . $dates[$i]['id'] . "]",$val,"4","4"),"","tdliste-grise");
                }
                else
                {
                    $tableau_produits .= html_colonne("","","center","top","","","",html_text_input("qteproduit[" . $idproducteur . "][" . $idproduit . "][" . $dates[$i]['id'] . "]",$val,"4","4"),"","tdliste");
                    if($val != "") {
                        $total_qte_produit += $val;
                    }
                }
            }

            $tableau_produits .= html_colonne("","","center","top","","","",$total_qte_produit,"","tdliste");
            $tableau_produits .= html_colonne("","","right","top","","","",sprintf($g_lib_somme,$total_qte_produit * $prix),"","thliste");
            $tableau_produits .= html_fin_ligne();
            $total_producteur += $total_qte_produit * $prix;
        }

        if(isset($avoirs[$idproducteur])) {
            foreach($avoirs[$idproducteur]["montant"] as $id => $montant) {
                if($montant < 0.0) {
                    $m = sprintf($g_lib_somme,-$montant);
                    $desc = "Dette de " . $m;
                    $m = '+' . $m;
                } else {
                    $m = sprintf($g_lib_somme,$montant);
                    $desc = "Avoir de " . $m;
                    $m = '-' . $m;
                }
                if($avoirs[$idproducteur]["description"][$id] != "") {
                    $desc .= " (" . $avoirs[$idproducteur]["description"][$id] . ")";
                }
                if($montant < 0.0 || $montant <= $total_producteur) {
                    $total_producteur -= $montant;
                } else {
                    $m = '';
                }
                $tableau_produits .= html_debut_ligne("","","","top");
                $tableau_produits .= html_colonne("","","right","top","","",$nbdates + 3, $desc, "","tdliste");
                $tableau_produits .= html_colonne("","","right","top","","","", $m,"","tdliste");
                $tableau_produits .= html_fin_ligne();
            }
        }

        $tableau_produits .= html_debut_ligne("","","","top");
        $tableau_produits .= html_colonne("","","right","top","","",$nbdates + 3,"Total","","thliste");
        $tableau_produits .= html_colonne("","","right","top","","","",sprintf($g_lib_somme,$total_producteur),"","thliste");
        $tableau_produits .= html_fin_ligne();
        $tableau_produits .= html_fin_tableau();

        $total_commande += $total_producteur;

        $champs["libelle"][] = "";
        $champs["type"][] = "afftextfull";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = $tableau_produits;
        $champs["aide"][] = "";

        $champs["libelle"][] = "";
        $champs["type"][] = "separateur";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = "";
        $champs["aide"][] = "";
    }

    if(isset($avoirs[0])) {
        $champs["libelle"][] = "Avoirs";
        $champs["type"][] = "afftext";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = "";
        $champs["aide"][] = "";

        $tableau_total = html_debut_tableau("100%","0");
        $tableau_total .= html_debut_ligne("","","","top");
        $tableau_total .= html_colonne("90%","","center","top","","","","Description","","thliste");
        $tableau_total .= html_colonne("10%","","center","top","","","","Montant","","thliste");
        $tableau_total .= html_fin_ligne();
        $total_avoirs = 0.0;
        foreach($avoirs[0]["montant"] as $id => $montant) {
            if($montant < 0.0) {
                $m = sprintf($g_lib_somme,-$montant);
                $desc = "Dette de " . $m;
                $m = '+' . $m;
            } else {
                $m = sprintf($g_lib_somme,$montant);
                $desc = "Avoir de " . $m;
                $m = '-' . $m;
            }
            if($avoirs[0]["description"][$id] != "") {
                $desc .= " (" . $avoirs[0]["description"][$id] . ")";
            }
            if($montant < 0.0 || $montant <= $total_commande) {
                $total_commande -= $montant;
                $total_avoirs -= $montant;
            } else {
                $m = '';
            }
            $tableau_total .= html_debut_ligne("","","","top");
            $tableau_total .= html_colonne("","","right","top","","","", $desc, "","tdliste");
            $tableau_total .= html_colonne("","","right","top","","","",$m,"","tdliste");
            $tableau_total .= html_fin_ligne();
        }

        $tableau_total .= html_debut_ligne("","","","top");
        $tableau_total .= html_colonne("","","right","top","","","","Total","","thliste");
        $tableau_total .= html_colonne("","","right","top","","","",sprintf($g_lib_somme,$total_avoirs),"","thliste");
        $tableau_total .= html_fin_ligne();
        $tableau_total .= html_fin_tableau();

        $champs["libelle"][] = "";
        $champs["type"][] = "afftext";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = $tableau_total;
        $champs["aide"][] = "";

        $champs["libelle"][] = "";
        $champs["type"][] = "separateur";
        $champs["lgmax"][] = "";
        $champs["taille"][] = "";
        $champs["nomvar"][] = "";
        $champs["valeur"][] = "";
        $champs["aide"][] = "";
    }

    $champs["libelle"][] = "Montant total";
    $champs["type"][] = "afftext";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = sprintf($g_lib_somme,$total_commande);
    $champs["aide"][] = "";
    return $champs;
}

function afficher_formulaire_bon_commande($idperiode=0,$iddepot=0,$qteproduit=array(),$action="enregistrercde",
                                          $idcommande=0,$idclient=0) {

    $champs["libelle"][] = "Bon de commande";
    $champs["type"][] = "";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = "";
    $champs["aide"][] = "";

    $champs["libelle"][] = "Période";
    $champs["type"][] = "afftext";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = retrouver_periode($idperiode);
    $champs["aide"][] = "";

    $champs["libelle"][] = "Dépôt";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["type"][] = "libre";
    $champs["nomvar"][] = "iddepot";
    $champs["valeur"][] = afficher_liste_depots_actifs("iddepot", $iddepot);
    if(retrouver_etat_depot($iddepot) != "Actif") {
        $champs["aide"][] = "<b>Votre dépôt habituel est fermé pour cette commande, merci de selectionner un autre dépôt.</b>";
    }

    $champs["libelle"][] = "";
    $champs["type"][] = "separateur";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = "";
    $champs["aide"][] = "";

    $avoirs = retrouver_avoirs($idclient,$idcommande);
    $champs = formulaire_bon_commande($idperiode, $champs, $qteproduit,$avoirs);
    if(is_string($champs)) {
        return $champs;
    }

    $champs["libelle"][] = "";
    $champs["type"][] = "separateur";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = "";
    $champs["aide"][] = "";

    $champs["libelle"][] = "";
    $champs["type"][] = "submit";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "valider";
    $champs["valeur"][] = " Enregistrer ";
    $champs["aide"][] = "";

    return saisir_enregistrement($champs,"?action=$action&idperiode=$idperiode&id=$idcommande" . ($idclient != 0 ? "&idclient=$idclient" : ""),"formcde",95,15,2,2,false)
     . "<br/><center>&#11088; = Produit récemment ajouté ou modifié</center><br/>";
}

function afficher_formulaire_bon_commande_nouveau_client(
    $idperiode=0, $qteproduit, $nom, $prenom, $email, $telephone, $ville,$iddepot = 0)
{
    $champs["libelle"] = array("Bon de commande","Nom","Prenom","Email","Téléphone","Ville","Dépôt");
    $champs["type"] = array("","text","text","text","text","libre","libre");
    $champs["lgmax"] = array("","40","40","100","40","40","40");
    $champs["taille"] = array("","40","40","50","30","40","40");
    $champs["nomvar"] = array("","nom","prenom","email","telephone","ville","iddepot");
    $champs["valeur"] = array("","$nom","$prenom","$email","$telephone",afficher_villes_client("ville", $ville),
                              afficher_liste_depots_actifs("iddepot", $iddepot));

    $champs["libelle"][] = "";
    $champs["type"][] = "separateur";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = "";
    $champs["aide"][] = "";

    $champs = formulaire_bon_commande($idperiode, $champs, $qteproduit);

    $champs["libelle"][] = "";
    $champs["type"][] = "separateur";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "";
    $champs["valeur"][] = "";
    $champs["aide"][] = "";

    $champs["libelle"][] = "";
    $champs["type"][] = "submit";
    $champs["lgmax"][] = "";
    $champs["taille"][] = "";
    $champs["nomvar"][] = "valider";
    $champs["valeur"][] = " Imprimer ";
    $champs["aide"][] = "";
    $champs["action"][count($champs["libelle"]) - 1] = "/paniers/nouveau.php?idperiode=$idperiode&action=imprimercde";

    return saisir_enregistrement($champs,"","formcde",95,15,2,2,false, "_blank");
}

function afficher_recapitulatif_livraisons($idclient, $iddate = 0) {
    global $base_bons_cde,$base_commandes, $base_bons_cde, $base_dates, $jour_commande, $base_clients;

    $chaine = "";

    $chaine = <<<HTML
<script>
function datechange() {
const urlParams = new URLSearchParams(window.location.search);
urlParams.set('iddate', document.getElementById("date").value);
window.location.search = urlParams;
}

function clientchange() {
const urlParams = new URLSearchParams(window.location.search);
urlParams.set('idclient', document.getElementById("client").value);
window.location.search = urlParams;
}
</script>
HTML;

    $chaine .= '<div class="container-fluid">';
    $chaine .= '<div class="row">';

    $chaine .= "<div class=\"col\"><select id=\"date\" onchange=\"datechange()\">";
    $datenextlivraison = date("Y-m-d", strtotime("$jour_commande"));
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"],
        "select id,datelivraison from $base_dates " .
        "where 1 order by datelivraison desc limit 12");
    while(list($iddatebase,$datelivraison) = mysqli_fetch_row($rep0))
    {
        $chaine .= "<option value=\"" . $iddatebase . "\"";
        if(($iddate == 0 && $datelivraison == $datenextlivraison) || $iddate == $iddatebase)
        {
            $chaine .= " selected";
            if($iddate == 0)
            {
                $iddate = $iddatebase;
            }
        }
        $chaine .= ">" . datelitterale($datelivraison) . "</option>";
    }
    $chaine .= "</select></div>";

    if(current_user_can('gestionnaire')) {
        $chaine .= "<div class=\"col\"><select id=\"client\" onchange=\"clientchange()\">\n";
        $rep = mysqli_query($GLOBALS["___mysqli_ston"],
            "select distinct $base_clients.id,$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
            "from $base_commandes " .
            "inner join $base_clients on $base_clients.id=$base_commandes.idclient " .
            "where $base_commandes.iddatelivraison=$iddate order by $base_clients.nom");
        while(list($idclientbase, $nom, $prenom,$codeclient) = mysqli_fetch_row($rep))
        {
            $chaine .= "<option value=\"" . $idclientbase . "\"";
            if ($idclientbase == $idclient) $chaine .= " selected";
            $chaine .= ">$nom $prenom ($codeclient)</option>\n";
        }
        $chaine .= "</select></div>";
    }

    $chaine .= "</div>";
    $chaine .= "</div>";
    $qteproduit = array();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"],
                        "select quantite,idproducteur,idproduit " .
                        "from $base_commandes " .
                        "where iddatelivraison=\"$iddate\" and idclient=\"$idclient\"");
    if(mysqli_num_rows($rep) == 0) {
        echo afficher_message_erreur("Pas de commande pour cette personne à cette date...");
        return;
    }

    while(list($quantite,$idproducteur,$idproduit) = mysqli_fetch_row($rep)) {
        $qteproduit[$idproducteur][$idproduit] = $quantite;
    }

    $chaine .= '<table class="table table-bordered mt-5">';
    $chaine .= '  <thead class="table-dark" style="position: sticky; top:0;">';
    $chaine .= '    <tr>';
    $chaine .= '      <th scope="col"></th>';
    $chaine .= '      <th scope="col">Quantité</th>';
    $chaine .= '    </tr>';
    $chaine .= '  </thead>';
    $chaine .= '  <tbody>';

    foreach($qteproduit as $key_producteur => $val_producteur)
    {
        $param_producteur = retrouver_parametres_producteur($key_producteur);
        $total_qte_producteur = 0;
        $chaine2 = "";
        $chaine2 .= '    <tr class="table-secondary">';
        $chaine2 .= '      <th colspan="2"><b>' . $param_producteur['produits'] . " (" . $param_producteur['nom'] . ")</b></th>";
        $chaine2 .= '    </tr>';

        foreach($val_producteur as $key_produit => $quantite)
        {
            $param_produit = retrouver_parametres_produit($key_produit);
            $total_qte_produit = 0;

            $chaine3 = '<tr>';
            $chaine3 .= '  <td>' . $param_produit['description'] . '</td>';
            $chaine3 .= '  <td style="text-align: center">' .  $quantite . '</td>';
            $total_qte_produit += $quantite;
            $chaine3 .= '</tr>';

            $total_qte_producteur += $total_qte_produit;

            if($total_qte_produit != 0) {
                $chaine2 .= $chaine3;
            }
        }

        if ($total_qte_producteur > 0) {
            $chaine .= $chaine2;
        }
    }

    $chaine .= '  </tbody>';
    $chaine .= '</table>';

    return '<div class="table-responsive" style="max-height: 600px;">' . $chaine . '</div>';
}

function afficher_recapitulatif_commande_admin($id) {
    global $base_bons_cde,$base_commandes;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode from $base_bons_cde where id='$id'");
    if (mysqli_num_rows($rep) == 0) {
        echo afficher_message_erreur("Commande introuvable !!!");
        return;
    }

    list($idperiode) = mysqli_fetch_row($rep);
    $qteproduit = array();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select quantite,prix,idproducteur,idproduit,iddatelivraison " .
                       "from $base_commandes " .
                       "where idboncommande = '$id'");
    while(list($quantite,$prix,$idproducteur,$idproduit,$iddate) = mysqli_fetch_row($rep)) {
        $qteproduit[$idproducteur][$idproduit][$iddate]["quantite"] = $quantite;
        $qteproduit[$idproducteur][$idproduit]["prix"] = $prix;
    }
    $avoirs = retrouver_avoirs(0, $id);
    return afficher_recapitulatif_commande_interne($idperiode, $qteproduit,$avoirs);
}

function afficher_recapitulatif_commande($id) {
    global $base_bons_cde,$base_commandes, $base_dates, $g_lib_somme;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode from $base_bons_cde where id='$id'");
    if (mysqli_num_rows($rep) == 0) {
        echo afficher_message_erreur("Commande introuvable !!!");
        return;
    }

    list($idperiode) = mysqli_fetch_row($rep);
    $qteproduit = array();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select quantite,prix,idproducteur,idproduit,iddatelivraison " .
                       "from $base_commandes " .
                       "where idboncommande = '$id'");
    while(list($quantite,$prix,$idproducteur,$idproduit,$iddate) = mysqli_fetch_row($rep)) {
        $qteproduit[$idproducteur][$idproduit][$iddate]["quantite"] = $quantite;
        $qteproduit[$idproducteur][$idproduit]["prix"] = $prix;
    }

    $avoirs = retrouver_avoirs(0, $id);

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode='$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep0))
    {
        $dates[$nbdates]["id"] = $iddate;
        $dates[$nbdates]["livraison"] = $datelivraison;
        $nbdates++;
    }

    $absences = retrouver_absences($idperiode);

    if($avoirs != null) {
        foreach($avoirs as $key_producteur => $avoir) {
            if($key_producteur != 0 && !isset($qteproduit[$key_producteur])) {
                foreach($avoir["montant"] as $idavoir => $montant) {
                    if($montant < 0.0) {
                        $qteproduit[$key_producteur] = array();
                        break;
                    }
                }
            }
        }
    }

    $affiche_avoir = function($montant, $description, $nbdates) {
        global $g_lib_somme;
        $chaineavoir = "";
        if($montant < 0.0) {
            $m = sprintf($g_lib_somme,-$montant);
            $desc = "Dette de " . $m;
            $m = '+' . $m;
        } else {
            $m = sprintf($g_lib_somme,$montant);
            $desc = "Avoir de " . $m;
            $m = '-' . $m;
        }
        if($description != "") {
            $desc .= " (" . $description . ")";
        }
        $chaineavoir .= '<tr>';
        $chaineavoir .= '  <td colspan=' . ($nbdates + 3) . '>'. $desc . '</td>';
        $chaineavoir .= '  <td style="text-align: right; white-space:nowrap;">' . $m . '</td>';
        $chaineavoir .= '</tr>';
        return $chaineavoir;
    };

    $total_commande = 0.0;
    $chaine = "";

    $chaine .= '<table class="table table-bordered mt-5">';
    $chaine .= '  <thead class="table-dark" style="position: sticky; top:0;">';
    $chaine .= '    <tr>';
    $chaine .= '      <th scope="col"></th>';
    $chaine .= '      <th scope="col">Prix</th>';
    reset($dates);
    foreach($dates as $key => $val)
    {
        $chaine .= '      <th>' . dateexterne($val["livraison"], false) . '</th>';
    }
    $chaine .= '      <th scope="col">Quantité</th>';
    $chaine .= '      <th scope="col">Total</th>';
    $chaine .= '    </tr>';
    $chaine .= '  </thead>';
    $chaine .= '  <tbody>';

    foreach($qteproduit as $key_producteur => $val_producteur)
    {
        $param_producteur = retrouver_parametres_producteur($key_producteur);
        $total_prix_producteur = 0;
        $total_qte_producteur = 0;
        $chaine2 = "";
        $chaine2 .= '    <tr class="table-secondary">';
        $chaine2 .= '      <th colspan="' . ($nbdates + 4) . '"><b>' . $param_producteur['produits'] . " (" . $param_producteur['nom'] . ")</b></th>";
        $chaine2 .= '    </tr>';

        foreach($val_producteur as $key_produit => $val_produit)
        {
            $param_produit = retrouver_parametres_produit($key_produit);
            $total_qte_produit = 0;
            $total_prix_produit = 0.0;

            $chaine3 = '<tr>';
            $chaine3 .= '  <td>' . $param_produit['description'] . '</td>';
            $chaine3 .= '  <td class="table-light" style="text-align: right; white-space:nowrap;">' . sprintf($g_lib_somme,$qteproduit[$key_producteur][$key_produit]["prix"]) . '</td>';
            reset($dates);
            foreach($dates as $k => $v)
            {
                $key_date = $v["id"];
                if(isset($absences[$key_date][$key_producteur]) && $absences[$key_date][$key_producteur])
                {
                    $chaine3 .= '  <td></td>';
                }
                else
                {
                    $quantite =
                        array_key_exists($key_producteur, $qteproduit) &&
                        array_key_exists($key_produit, $qteproduit[$key_producteur]) &&
                        array_key_exists($key_date, $qteproduit[$key_producteur][$key_produit]) ?
                            $qteproduit[$key_producteur][$key_produit][$key_date]["quantite"] : 0;
                    if($quantite == 0) {
                        $chaine3 .= '  <td></td>';
                    } else {
                        $chaine3 .= '  <td style="text-align: center">' .  $quantite . '</td>';
                        $total_qte_produit += $quantite;
                        $total_prix_produit += $quantite * $qteproduit[$key_producteur][$key_produit]["prix"];
                    }
                }
            }
            $chaine3 .= '  <td class="table-light" style="text-align: center">' . $total_qte_produit . '</td>';
            $chaine3 .= '  <td class="table-light" style="text-align: right; white-space:nowrap;">' . sprintf($g_lib_somme,$total_prix_produit) . '</td>';
            $chaine3 .= '</tr>';

            $total_prix_producteur += $total_prix_produit;
            $total_qte_producteur += $total_qte_produit;

            if($total_qte_produit != 0) {
                $chaine2 .= $chaine3;
            }
        }

        if(isset($avoirs[$key_producteur])) {
            $avoirProducteur = $avoirs[$key_producteur];
            foreach($avoirProducteur["montant"] as $id => $montant) {
                $chaine2 .= $affiche_avoir($montant, $avoirProducteur["description"][$id], $nbdates);
                $total_prix_producteur -= $montant;
            }
        }

        if($total_qte_producteur > 0 || $total_prix_producteur > 0.0) {
            $chaine2 .= '<tr class="table-light">';
            $chaine2 .= '  <td colspan=' . ($nbdates + 3) . ' style="text-align: right">Sous Total</td>';
            $chaine2 .= '  <td style="text-align: right; white-space:nowrap">' . sprintf($g_lib_somme,$total_prix_producteur) . '</td>';
            $chaine2 .= '</tr>';
            $chaine .= $chaine2;
        }
        $total_commande += $total_prix_producteur;
    }

    if(isset($avoirs[0])) {
        foreach($avoirs[0]["montant"] as $id => $montant) {
            $chaine2 .= $affiche_avoir($montant, $avoirs[0]["description"][$id], $nbdates);
            $total_commande -= $montant;
        }
    }

    $chaine .= '    <tr class="table-dark">';
    $chaine .= '      <td colspan=' . ($nbdates + 3) . ' style="text-align: right">Total</td>';
    $chaine .= '      <td style="text-align: right; white-space:nowrap">' . sprintf($g_lib_somme,$total_commande) . '</td>';
    $chaine .= '    </tr>';
    $chaine .= '  </tbody>';
    $chaine .= '</table>';

    return '<div class="table-responsive" style="max-height: 600px;">' . $chaine . '</div>';
}

function afficher_boncommande_vierge($idperiode) {
    global $base_dates,$base_producteurs,$base_produits,$base_dates;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_dates where idperiode = '$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate) = mysqli_fetch_row($rep0)) {
        $dates[$nbdates] = $iddate;
        $nbdates++;
    }
    if($nbdates == 0) {
        return afficher_message_erreur("Aucune date de disponible pour cette commande...");
    }

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_producteurs where etat = 'Actif' order by produits");

    $newqteproduits = array();
    while(list($idproducteur) = mysqli_fetch_row($rep0))
    {
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_produits where idproducteur = '$idproducteur' and etat = 'Actif' order by description");
        while(list($idproduit) = mysqli_fetch_row($rep1)) {
            $params = retrouver_parametres_produit($idproduit);
            if(!$params) {
                continue;
            }
            $newqteproduits[$idproducteur][$idproduit]["prix"] = $params['prix'];
            foreach($dates as $i => $iddate) {
                $newqteproduits[$idproducteur][$idproduit][$iddate]["quantite"] = -1;
            }
        }
    }
    return afficher_recapitulatif_commande_interne($idperiode, $newqteproduits,null, True);
}

function afficher_recapitulatif_commande_interne($idperiode,$qteproduit,$avoirs,$forceprint=False) {
    global $g_lib_somme,$base_dates;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode='$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep0))
    {
        $dates[$nbdates]["id"] = $iddate;
        $dates[$nbdates]["livraison"] = $datelivraison;
        $nbdates++;
    }

    $absences = retrouver_absences($idperiode);

    if($avoirs != null) {
        foreach($avoirs as $key_producteur => $avoir) {
            if($key_producteur != 0 && !isset($qteproduit[$key_producteur])) {
                foreach($avoir["montant"] as $idavoir => $montant) {
                    if($montant < 0.0) {
                        $qteproduit[$key_producteur] = array();
                        break;
                    }
                }
            }
        }
    }

    $largeur_colonne = ( 40 / $nbdates ) . "%";
    $total_commande = 0.0;
    $chaine = "";
    foreach($qteproduit as $key_producteur => $val_producteur)
    {
        $param_producteur = retrouver_parametres_producteur($key_producteur);
        $total_prix_producteur = 0;
        $total_qte_producteur = 0;
        $chaine2 = "";
        $chaine2 .= html_debut_tableau("95%","0");
        $chaine2 .= html_debut_ligne("","","","top");
        $chaine2 .= html_colonne("30%","","left","","","","",$param_producteur['produits'] . "<br>(" . $param_producteur['nom'] . ")","","thliste");
        $chaine2 .= html_colonne("10%","","center","","","","","Prix unitaire","","thliste");

        reset($dates);
        foreach($dates as $key => $val)
        {
            $chaine2 .= html_colonne("$largeur_colonne","","center","top","","","",
                                     datelitterale($val["livraison"],false),"","thliste");
        }

        $chaine2 .= html_colonne("10%","","center","","","","","Total<br>quantité","","thliste");
        $chaine2 .= html_colonne("10%","","center","","","","","Prix<br>total","","thliste");
        $chaine2 .= html_fin_ligne();

        foreach($val_producteur as $key_produit => $val_produit)
        {
            $param_produit = retrouver_parametres_produit($key_produit);
            $total_qte_produit = 0;
            $total_prix_produit = 0.0;

            $chaine3 = html_debut_ligne("","","","top");
            $chaine3 .= html_colonne("","","left","","","","",$param_produit['description'],"","tdliste");
            $chaine3 .= html_colonne("","","right","","","","",
                                     sprintf($g_lib_somme,$qteproduit[$key_producteur][$key_produit]["prix"]),"",
                                     "tdliste");
            reset($dates);
            foreach($dates as $k => $v)
            {
                $key_date = $v["id"];
                if(isset($absences[$key_date][$key_producteur]) && $absences[$key_date][$key_producteur])
                {
                    $chaine3 .= html_colonne("","","center","","","","","","",  "tdliste-grise");
                }
                else
                {
                    $quantite =
                        array_key_exists($key_producteur, $qteproduit) &&
                        array_key_exists($key_produit, $qteproduit[$key_producteur]) &&
                        array_key_exists($key_date, $qteproduit[$key_producteur][$key_produit]) ?
                            $qteproduit[$key_producteur][$key_produit][$key_date]["quantite"] : 0;
                    if($quantite == -1) {
                        $chaine3 .= html_colonne("","","center","","","","", "", "","tdliste");
                    } else {
                        $chaine3 .= html_colonne("","","center","","","","", $quantite, "","tdliste");
                        $total_qte_produit += $quantite;
                        $total_prix_produit += $quantite * $qteproduit[$key_producteur][$key_produit]["prix"];
                    }
                }
            }

            $chaine3 .= html_colonne("","","center","","","","",$total_qte_produit,"","thliste");
            $chaine3 .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$total_prix_produit),"","thliste");
            $chaine3 .= html_fin_ligne();

            $total_prix_producteur += $total_prix_produit;

            if($total_qte_produit != 0 || $forceprint) $chaine2 .= $chaine3;
            $total_qte_producteur += $total_qte_produit;
        }

        if(isset($avoirs[$key_producteur])) {
            foreach($avoirs[$key_producteur]["montant"] as $id => $montant) {
                $chaine2 .= html_debut_ligne("","","","top");
                if($montant < 0.0) {
                    $m = sprintf($g_lib_somme,-$montant);
                    $desc = "Dette de " . $m;
                    $m = '+' . $m;
                } else {
                    $m = sprintf($g_lib_somme,$montant);
                    $desc = "Avoir de " . $m;
                    $m = '-' . $m;
                }
                if($avoirs[$key_producteur]["description"][$id] != "") {
                    $desc .= " (" . $avoirs[$key_producteur]["description"][$id] . ")";
                }
                $chaine2 .= html_colonne("","","right","","","",$nbdates + 3,$desc, "","tdliste");
                $chaine2 .= html_colonne("","","right","","","","", $m,"","tdliste");
                $chaine2 .= html_fin_ligne();
                $total_prix_producteur -= $montant;
            }
        }

        if($total_qte_producteur > 0 || $total_prix_producteur > 0.0 || $forceprint) {
            if($total_prix_producteur > 0.0 || $forceprint) {
                $chaine2 .= html_debut_ligne("","","","top");
                $chaine2 .= html_colonne("","","right","","","",$nbdates + 3,"Sous Total","","thliste");
                $chaine2 .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$total_prix_producteur),"","thliste");
                $chaine2 .= html_fin_ligne();
                $chaine2 .= html_fin_tableau() . "<br><br>";
            } else {
                $chaine2 .= html_fin_tableau() . "<br><br>";
            }
            $chaine .= $chaine2;
        }
        $total_commande += $total_prix_producteur;
    }

    $chaine2 = "";
    $chaine2 .= html_debut_tableau("95%","0");
    $chaine2 .= html_debut_ligne("","","","top");
    $chaine2 .= html_colonne("90%","","left","","","","","Total","","thliste");
    $chaine2 .= html_colonne("10%","","center","","","","","","","thliste");
    $chaine2 .= html_fin_ligne();

    if(isset($avoirs[0])) {
        foreach($avoirs[0]["montant"] as $id => $montant) {
            $chaine2 .= html_debut_ligne("","","","top");
            if($montant < 0.0) {
                $m = sprintf($g_lib_somme,-$montant);
                $desc = "Dette de " . $m;
                $m = '+' . $m;
            } else {
                $m = sprintf($g_lib_somme,$montant);
                $desc = "Avoir de " . $m;
                $m = '-' . $m;
            }
            if($avoirs[0]["description"][$id] != "") {
                $desc .= " (" . $avoirs[0]["description"][$id] . ")";
            }
            $chaine2 .= html_colonne("","","right","","","","",$desc, "","tdliste");
            $chaine2 .= html_colonne("","","right","","","","",$m,"","tdliste");
            $chaine2 .= html_fin_ligne();
            $total_commande -= $montant;
        }
    }

    $chaine2 .= html_colonne("","","right","","","","","Total commande","","thliste");
    $chaine2 .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$total_commande),"","thliste");
    $chaine2 .= html_fin_ligne();
    $chaine2 .= html_fin_tableau() . "<br><br>";
    $chaine .= $chaine2;
    return("$chaine");
}

function enregistrer_bon_commande($idperiode,$idclient,$iddepot) {
    global $base_bons_cde;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_bons_cde (idperiode,idclient,iddepot,etat,datemodif) values ('$idperiode','$idclient','$iddepot', 'encours',now())");
    if (!$rep) {
        return -1;
    }
    $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
    $new_id = retrouver_code_client($idclient) . "-" . $last_id;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_bons_cde set idboncde='$new_id' where id='$last_id'");
    return $last_id;
}

function enregistrer_commande($idperiode,$qteproduit,$idboncommande,$idclient) {
    global $base_commandes,$base_avoirs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_commandes where idboncommande = '$idboncommande'");
    $total = 0.0;
    $avoirs = retrouver_avoirs($idclient,$idboncommande);
    $absences = retrouver_absences($idperiode);
    foreach($qteproduit as $key_producteur => $val_producteur)
    {
        $total_producteur = 0.0;
        foreach($val_producteur as $key_produit => $val_produit)
        {
            foreach($val_produit as $key_date => $val_date)
            {
                if(array_key_exists($key_date, $absences) &&
                   array_key_exists($key_producteur, $absences[$key_date]) &&
                   $absences[$key_date][$key_producteur]) {
                    continue;
                }

                if ($qteproduit[$key_producteur][$key_produit][$key_date] != "" &&
                    $qteproduit[$key_producteur][$key_produit][$key_date] != 0)
                {
                    $param = retrouver_parametres_produit($key_produit);
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_commandes (idboncommande,idclient,idperiode,idproducteur,idproduit,iddatelivraison,quantite,prix,datemodif) values ('$idboncommande','$idclient','$idperiode','$key_producteur','$key_produit','$key_date','" . $qteproduit[$key_producteur][$key_produit][$key_date] . "','" . $param["prix"] . "',now())");

                    $total_producteur += $qteproduit[$key_producteur][$key_produit][$key_date] * $param["prix"];
                }
            }
        }

        if(isset($avoirs[$key_producteur])) {
            foreach($avoirs[$key_producteur]["montant"] as $idavoir => $montant) {
                if($montant < 0.0 || $montant <= $total_producteur) {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande='$idboncommande' where id='$idavoir'");
                    $total_producteur -= $montant;
                } else {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where id='$idavoir'");
                }
            }
        }

        $total += $total_producteur;
    }

    foreach($avoirs as $key_producteur => $avoir) {
        if(!isset($qteproduit[$key_producteur])) {
            foreach($avoir["montant"] as $idavoir => $montant) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where id='$idavoir'");
            }
        }
    }

    if(isset($avoirs[0])) {
        foreach($avoirs[0]["montant"] as $idavoir => $montant) {
            if($montant < 0.0 || $montant <= $total) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande='$idboncommande' where id='$idavoir'");
                $total -= $montant;
            } else {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where id='$idavoir'");
            }
        }
    }
}

function lister_commandes($idclient = 0, $path) {
    global $base_bons_cde,$base_periodes,$g_periode_libelle;
    $chaine = "";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_bons_cde.id,idboncde,idperiode,$base_bons_cde.datemodif," .
                       "$base_periodes.etat,UNIX_TIMESTAMP($base_periodes.datecommande) - UNIX_TIMESTAMP(curdate())," .
                       $g_periode_libelle .
                       "from $base_bons_cde " .
                       "inner join $base_periodes on $base_periodes.id=$base_bons_cde.idperiode " .
                       "where $base_bons_cde.idclient = '$idclient' " .
                       "order by $base_bons_cde.datemodif desc");
    if (mysqli_num_rows($rep) != 0) {
        $chaine .= '<table id="liste-des-commandes" class="table table-bordered mt-5">';
        $chaine .= '  <thead class="table-dark" style="position: sticky; top:0;">';
        $chaine .= '    <tr>';
        $chaine .= '      <th scope="col">Commande</th>';
        $chaine .= '      <th scope="col">Période</th>';
        $chaine .= '      <th scope="col">Date</th>';
        $chaine .= '    </tr>';
        $chaine .= '  </thead>';
        $chaine .= '  <tbody>';
        while(list($id,$idboncde,$idperiode,$datemodif,$etat,$restant,$periode) = mysqli_fetch_row($rep))
        {
            $chaine .= "    <tr>";
            $chaine .= "      <td><a href='$path?action=affichercde&id=$id'>$idboncde</a></td>";
            $chaine .= "      <td>$periode</td>";
            $chaine .= "      <td>$datemodif</td>";
            $chaine .= "    </tr>";
        }
        $chaine .= "  </tbody>";
        $chaine .= "</table>";
    }
    else {
        $chaine .= "Vous n'avez encore fait aucune commande...";
    }
    return("$chaine");
}

function retrouver_quantites_commande($idcommande,$idperiode) {
    global $base_commandes;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select quantite,idproducteur,idproduit,iddatelivraison from $base_commandes " .
                       "where idboncommande = '$idcommande'");
    while(list($quantite,$idproducteur,$idproduit,$iddate) = mysqli_fetch_row($rep)) {
        $qteproduit[$idproducteur][$idproduit][$iddate] = $quantite;
    }
    return $qteproduit;
}

function gerer_liste_commandes($tri=0,$idperiode="-2",$iddepot="-1",$action="") {
    global $base_bons_cde, $base_depots, $base_periodes,$base_clients,$g_periode_libelle;
    switch($tri)
    {
        case 1:
            $order = "idperiode desc";
            break;
        case 2:
            $order = "$base_clients.nom,$base_clients.prenom asc";
            break;
        case 3:
            $order = "datemodif desc";
            break;
        case 4:
            $order = "depot asc";
            break;
        default:
            $order = "idperiode,$base_clients.nom,$base_clients.prenom asc";
            break;
    }

    $condition = "";
    if($idperiode > 0) {
        $condition .= "idperiode='$idperiode'";
    } else if($idperiode == "-2") {
        $condition .= "$base_periodes.etat != 'Close'";
    }
    if($iddepot > 0) {
        if($condition != "") {
            $condition .= " and ";
        }
        $condition .= "$base_bons_cde.iddepot='$iddepot'";
    }
    if($condition == "") {
        $condition = "1";
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_bons_cde.id,idboncde,idperiode,idclient,$base_bons_cde.datemodif," .
                       $g_periode_libelle . ",$base_periodes.etat,$base_depots.nom as depot," .
                       "$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
                       "from $base_bons_cde ".
                       "inner join $base_depots on $base_depots.id=$base_bons_cde.iddepot ".
                       "inner join $base_periodes on $base_periodes.id=$base_bons_cde.idperiode " .
                       "inner join $base_clients on $base_clients.id=$base_bons_cde.idclient " .
                       "where $condition order by $order");
    $chaine = "";
    if($action == "filtrer") {
        $action = "&action=filtrer&idperiode=$idperiode&iddepot=$iddepot";
    }

    if($rep && mysqli_num_rows($rep) != 0) {
        $chaine .= html_debut_tableau("90%","0","2","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Actions","","thliste");
        $chaine .= html_colonne("","","center","","","","",html_lien("?tri=0" . $action,"_top","N° commande"),"","thliste");
        $chaine .= html_colonne("","","center","","","","",html_lien("?tri=2" . $action,"_top","Client"),"","thliste");
        $chaine .= html_colonne("","","center","","","","",html_lien("?tri=1" . $action,"_top","Période"),"","thliste");
        $chaine .= html_colonne("","","center","","","","",html_lien("?tri=4" . $action,"_top","Dépôt"),"","thliste");
        $chaine .= html_colonne("","","center","","","","",html_lien("?tri=3" . $action,"_top","Modifiée le"),"","thliste");
        $chaine .= html_fin_ligne();
        while (list($id,$idboncde,$idperiode,$idclient,$datemodif,$periode,$etat,$depot,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep))
        {
            $chaine .= html_debut_ligne("","","","top");
            $actionlien = "";
            if($etat != "Close" && utilisateurIsAdmin()) {
                $actionlien .= html_lien("?action=modifier&id=$id&tri=$tri","_top","Modifier") . " | ";
                $actionlien .= html_lien("?action=supprimer&id=$id&tri=$tri","_top","Supprimer") . " | ";
            }
            $actionlien .= html_lien("?action=detail&id=$id&tri=$tri","_top","Détails");
            $chaine .= html_colonne("","","center","","","","",$actionlien,"","tdliste");
            $chaine .= html_colonne("","","left","","","","","$idboncde","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$prenom $nom ($codeclient)","","tdliste");
            $chaine .= html_colonne("","","left","","","","","$periode","","tdliste");
            $chaine .= html_colonne("","","left","","","","",$depot,"","tdliste");
            $chaine .= html_colonne("","","center","","","","",dateheureexterne($datemodif),"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else {
        $chaine .= afficher_message_erreur("Aucune commande dans la base...");
    }
    return($chaine);

}

function recapitulatif_commandes_clients($idperiode, $iddate, $iddepot) {
    global $base_commandes,$base_bons_cde,$base_clients,$base_produits,$base_producteurs;
    $absences = retrouver_absences($idperiode);
    $tab_producteurs = retrouver_produits_producteurs();
    $recap_produits_producteurs = array();
    $rep2 = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_commandes.idproducteur,$base_produits.nom,sum(quantite) " .
                        "from $base_commandes " .
                        "inner join $base_bons_cde on $base_bons_cde.id=$base_commandes.idboncommande " .
                        "inner join $base_produits on $base_produits.id=$base_commandes.idproduit " .
                        "where iddatelivraison='$iddate' and $base_bons_cde.iddepot='$iddepot' " .
                        "group by idproduit");
    while (list($idproducteur,$produit,$quantite) = mysqli_fetch_row($rep2))
    {
        if(!array_key_exists($iddate, $absences) ||
           !array_key_exists($idproducteur, $absences[$iddate]) ||
           !$absences[$iddate][$idproducteur]) {
            $recap_produits_producteurs[$idproducteur][$produit] = $quantite;
        }
    }

    if(empty($recap_produits_producteurs)) {
        return "Pas de commandes le " . retrouver_date($iddate);
    }

    $chaine = afficher_titre(retrouver_depot($iddepot) .
                             ", commandes pour la livraison du : " . retrouver_date($iddate));
    $chaine .= "<br/>";
    $chaine .= html_debut_tableau("95%","0","1","0");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("20%","","center","","","","","Producteur (nombre de commandes)","","thliste");
    $chaine .= html_colonne("80%", "", "center", "", "", "", "", "Produits", "", "thliste");
    $chaine .= html_fin_ligne();

    reset($tab_producteurs);
    foreach($tab_producteurs as $key_producteur => $val_producteur)
    {
        if(isset($recap_produits_producteurs[$key_producteur]))
        {
            $rep2 = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncommande from $base_commandes ".
                                "inner join $base_bons_cde on $base_bons_cde.id=$base_commandes.idboncommande " .
                                "where iddatelivraison='$iddate' and idproducteur='$key_producteur' " .
                                "and $base_bons_cde.iddepot='$iddepot'".
                                "group by idboncommande");
            $nombrecommandes = mysqli_num_rows($rep2);
            $recap = "";
            foreach($recap_produits_producteurs[$key_producteur] as $produit => $quantite) {
                if($recap != "") $recap .= " / ";
                $recap .= "<b>$quantite</b> " . strtolower($produit);
            }
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","","$val_producteur - $nombrecommandes commande(s)","","tdliste");
            $chaine .= html_colonne("", "", "left", "", "", "", "", $recap,"", "tdliste");
            $chaine .= html_fin_ligne();
        }
    }
    $chaine .= html_fin_tableau();

    $chaine .= "<br>";

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproduit,quantite,idproducteur,$base_commandes.idclient," .
                        "$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
                        "from $base_commandes " .
                        "inner join $base_bons_cde on $base_bons_cde.id = $base_commandes.idboncommande " .
                        "inner join $base_clients on $base_clients.id = $base_commandes.idclient " .
                        "inner join $base_producteurs on idproducteur = $base_producteurs.id " .
                        "where $base_bons_cde.iddepot='$iddepot' and $base_commandes.iddatelivraison='$iddate' ".
                        "order by $base_clients.nom,$base_clients.prenom,$base_producteurs.ordre,idproduit");
    if(mysqli_num_rows($rep0) != 0)
    {
        $chaine .= html_debut_tableau("100%","0","1","0");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("15%","","center","","","","","Client","","thliste");
        $chaine .= html_colonne("85%", "", "left", "", "", "", 2, "Produits", "", "thliste");
        $chaine .= html_fin_ligne();
        $clients = array();
        while(list($idproduit, $quantite, $idproducteur, $idclient,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep0))
        {
            if(!array_key_exists($iddate, $absences) ||
               !array_key_exists($idproducteur, $absences[$iddate]) ||
               !$absences[$iddate][$idproducteur]) {
                $clients[$idclient]["client"] = array($idclient,$nom,$prenom,$codeclient);
                $clients[$idclient][$idproducteur][] = array($idproduit, $quantite);
            }
        }

        foreach($clients as $idclient => $producteurs)
        {
            $client = $producteurs["client"];
            unset($producteurs["client"]);

            $afficheClient = True;
            $chaine2 = "";
            foreach($producteurs as $idproducteur => $produits) {
                $chaine2 .= html_debut_ligne("","","","top");
                if($afficheClient) {
                    $chaine2 .= html_colonne("10%","","left","","","","", "<b>" . $client[2] . ' ' . $client[1] . " (" .
                                             $client[3] . ")</b>",
                                             count($producteurs),"tdliste");
                    $afficheClient = False;
                }
                $liste = "";
                foreach($produits as $produit) {
                    list($idproduit,$quantite) = $produit;
                    if($liste != "") {
                        $liste .= " / ";
                    } else {
                        $liste = "&nbsp;";
                    }
                    $liste .= "<b>" . $quantite . "</b> " . strtolower(retrouver_nom_produit($idproduit));
                }
                $chaine2 .= html_colonne("25%", "", "left", "", "", "", "", $tab_producteurs[$idproducteur], "","tdliste");
                $chaine2 .= html_colonne("65%","","left","","","","", $liste, "", "tdliste");
                $chaine2 .= html_fin_ligne();
            }
            $chaine2 .= html_debut_ligne("","","","top");
            $chaine2 .= html_colonne("10%", "", "left", "", "", "", "", "", "","thlisteseparateur");
            $chaine2 .= html_colonne("25%", "", "left", "", "", "", "", "", "","thlisteseparateur");
            $chaine2 .= html_colonne("65%", "", "left", "", "", "", "", "", "","thlisteseparateur");
            $chaine2 .= html_fin_ligne();

            $chaine .= $chaine2;
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        $chaine .= afficher_message_erreur("Aucun client dans la base...");
    }
    return($chaine);
}

function recapituler_par_producteur($idproducteur,$idperiode,$iddepot) {
    global $base_commandes,$base_bons_cde,$export;

    $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select idproduit,sum(quantite),iddatelivraison from $base_commandes ".
                        "inner join $base_bons_cde on $base_bons_cde.id=$base_commandes.idboncommande " .
                        "where $base_bons_cde.idperiode='$idperiode' and idproducteur='$idproducteur' and ".
                        "iddepot='$iddepot' group by idproduit, iddatelivraison");
    if (mysqli_num_rows($rep1) == 0) {
        return afficher_message_info("Aucun produit commandé pour ce producteur sur cette période...");
    }
    $recap = array();
    while(list($idproduit,$quantite,$iddatelivraison) = mysqli_fetch_row($rep1))
    {
        $recap[$idproduit][$iddatelivraison] = $quantite;
    }

    $date = retrouver_dates_periode($idperiode,$idproducteur);

    $chaine = html_debut_tableau("90%","");

    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("40%","","left","","","","","Récapitulatif par semaine","","thliste");
    foreach($date as $key_date => $val_date)
    {
        $chaine .= html_colonne("","","center","","","","",dateexterne($val_date, $export == "excel"),"","thliste");
    }
    $chaine .= html_fin_ligne();

    foreach($recap as $key_produit => $val_produit)
    {
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","left","","","","",retrouver_nom_produit($key_produit),"","tdliste");
        reset($date);
        foreach($date as $key_date => $val_date)
        {
            $chaine .= html_colonne("","","center","","","","",array_key_exists($key_date, $val_produit) ? $val_produit[$key_date] : "","","tdliste");
        }
        $chaine .= html_fin_ligne();
    }

    $chaine .= html_fin_tableau();
    return($chaine);
}

function recapituler_par_producteur_client($idproducteur,$idperiode,$iddepot) {
    global $base_bons_cde,$base_commandes,$base_dates,$base_clients,$base_avoirs,$g_lib_somme,$base_produits,$export,
        $base_clients;

    $date = retrouver_dates_periode($idperiode, $idproducteur);
    $total_commande = 0.0;
    $chaine = html_debut_tableau("100%","");

    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("36%","","center","","","","","Produits","","thliste");
    $chaine .= html_colonne("","","center","","","","","Prix<br>unitaire","","thliste");
    reset($date);
    foreach($date as $key_date => $val_date)
    {
        $chaine .= html_colonne("","","center","","","","",dateexterne($val_date, $export == "excel"),"","thliste");
    }
    $chaine .= html_colonne("","","center","","","","","Montant","","thliste");
    $chaine .= html_fin_ligne();

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_commandes.idclient,idproduit,$base_commandes.iddatelivraison,quantite,prix," .
                        "$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
                        "from $base_commandes " .
                        "inner join $base_bons_cde on $base_bons_cde.id = $base_commandes.idboncommande " .
                        "inner join $base_clients on $base_clients.id = $base_commandes.idclient " .
                        "where $base_commandes.idperiode='$idperiode' and ".
                        "$base_commandes.idproducteur='$idproducteur' and $base_bons_cde.iddepot='$iddepot' " .
                        "order by $base_clients.nom,$base_clients.prenom");
    while(list($idclient,$idproduit,$iddatelivraison,$quantity,$prix,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep0))
    {
        $params = retrouver_parametres_produit($idproduit);
        $commandes[$idclient]["client"] = array($idclient,$nom,$prenom,$codeclient);
        $commandes[$idclient][$params["description"]]["prix"] = $prix;
        $commandes[$idclient][$params["description"]][$iddatelivraison] = array($quantity, $prix);
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_avoirs.id,$base_avoirs.idclient,montant,description,$base_clients.nom,$base_clients.prenom,$base_clients.codeclient from $base_avoirs " .
                       "inner join $base_bons_cde on $base_bons_cde.id = $base_avoirs.idboncommande " .
                       "inner join $base_clients on $base_clients.id = $base_avoirs.idclient " .
                       "where $base_bons_cde.idperiode='$idperiode' and $base_bons_cde.iddepot='$iddepot' " .
                       "and $base_avoirs.idproducteur = '$idproducteur' ");
    while(list($id,$idclient,$montant,$description,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep))
    {
        $avoirs[$idclient][$id]["montant"] = $montant;
        $avoirs[$idclient][$id]["description"] = htmlentities($description,ENT_QUOTES, 'UTF-8');
        if($montant < 0.0 && !isset($commandes[$idclient])) {
            $commandes[$idclient]["client"] = array($idclient,$nom,$prenom,$codeclient);
        }
    }

    if(!isset($commandes)) {
        return "";
    }

    reset($commandes);
    foreach($commandes as $nom => $produits) {
        $client = $produits["client"];
        unset($produits["client"]);
        $idclient = $client[0];
        $nom = $client[1];
        $prenom = $client[2];
        $codeclient = $client[3];

        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("36%","","left","","","", "","$nom $prenom ($codeclient)","","thliste");
        $chaine .= html_colonne("","","center","","","","","Prix","","thlistenormal");
        reset($date);
        foreach($date as $key_date => $val_date)
        {
            $chaine .= html_colonne("","","center","","","","",dateexterne($val_date, $export == "excel"),"",
                                    "thlistenormal");
        }
        $chaine .= html_colonne("","","center","","","","","Total","","thliste");
        $chaine .= html_fin_ligne();

        $grand_total_prix = 0.0;
        $numproduits = 0;
        foreach($produits as $produit => $dates) {
            $total_prix = 0.0;

            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","left","","","","",$produit,"","tdliste");
            $chaine .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$dates["prix"]),"","tdliste");
            reset($date);

            foreach($date as $key_date => $val_date)
            {
                if(isset($dates[$key_date])) {
                    list($quantite, $prix) = $dates[$key_date];
                    $chaine .= html_colonne("","","center","","","","",$quantite,"","tdliste");
                    $total_prix += ( $prix * $quantite );
                    $grand_total_prix += ( $prix * $quantite );
                    $total_commande += ( $prix * $quantite );
                }
                else
                {
                    $chaine .= html_colonne("","","center","","","","","","","tdliste");
                }
            }

            $chaine .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$total_prix),"","tdliste");
            $chaine .= html_fin_ligne();
            $numproduits += 1;
        }

        if(isset($avoirs[$idclient])) {
            foreach($avoirs[$idclient] as $id => $avoir) {
                $grand_total_prix -= $avoir["montant"];
                $total_commande -= $avoir["montant"];
                $chaine .= html_debut_ligne("","","","top");
                $montant = $avoir["montant"];
                if($montant < 0.0) {
                    $m = sprintf($g_lib_somme,-$montant);
                    $desc = "Dette de " . $m;
                    $m = '+' . $m;
                } else {
                    $m = sprintf($g_lib_somme,$montant);
                    $desc = "Avoir de " . $m;
                    $m = '-' . $m;
                }
                if($avoir["description"] != "") {
                    $desc .= " (" . $avoir["description"] . ")";
                }
                $chaine .= html_colonne("","","right","","","",count($date) + 2,$desc,"","tdliste");
                $chaine .= html_colonne("","","right","","","","",$m,"","thliste");
                $chaine .= html_fin_ligne();
                $numproduits += 1;
            }
        }

        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","","","","","","","","");
        $chaine .= html_colonne("","","","","","","","","","");
        reset($date);
        foreach($date as $key_date => $val_date)
        {
            $chaine .= html_colonne("","","","","","","","","","");
        }
        $chaine .= html_colonne("","","right","","","","",sprintf($g_lib_somme,$grand_total_prix),"","thliste");
        $chaine .= html_fin_ligne();
    }

    $chaine .= html_fin_tableau() . "<br><br>";

    $chaine .= html_debut_tableau("50%","");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("75%","","left","","","","","Montant total de la commande","","thliste");
    $chaine .= html_colonne("25%","","right","","","","",sprintf($g_lib_somme,$total_commande),"","thliste");
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();

    return $chaine;
}

function recapitulatif_cheques_clients($idperiode,$iddepot) {
    global $base_commandes,$base_producteurs,$base_clients,$base_bons_cde,$base_avoirs,$g_lib_somme,$base_clients;

    $absences = retrouver_absences($idperiode);

    $producteurs = array();
    $tab_cheques = array();
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur,$base_commandes.idclient,iddatelivraison," .
                       "sum(prix * quantite)," .
                       "$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
                       "from $base_commandes " .
                       "inner join $base_bons_cde on $base_bons_cde.id = $base_commandes.idboncommande " .
                       "inner join $base_clients on $base_clients.id = $base_commandes.idclient " .
                       "where $base_commandes.idperiode='$idperiode' and $base_bons_cde.iddepot='$iddepot' ".
                       "group by $base_commandes.idclient,idproducteur,iddatelivraison " .
                       "order by $base_clients.nom,$base_clients.prenom");
    while(list($idproducteur,$idclient,$iddate,$prix,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep)) {
        if(!array_key_exists($iddate, $absences) ||
           !array_key_exists($idproducteur, $absences[$iddate]) ||
           !$absences[$iddate][$idproducteur]) {
            if(!array_key_exists($idproducteur, $producteurs)) {
                $producteurs[$idproducteur] = 0;
            }
            $producteurs[$idproducteur] += $prix;
            $tab_cheques[$idclient]["client"] = array($idclient, $nom, $prenom, $codeclient);
            if(!array_key_exists($idproducteur, $tab_cheques[$idclient])) {
                $tab_cheques[$idclient][$idproducteur] = 0;
            }
            $tab_cheques[$idclient][$idproducteur] += $prix;
        }
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idproducteur,$base_avoirs.idclient,sum(montant),".
                       "$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
                       "from $base_avoirs " .
                       "inner join $base_bons_cde on $base_bons_cde.id = $base_avoirs.idboncommande " .
                       "inner join $base_clients on $base_clients.id = $base_avoirs.idclient " .
                       "where $base_bons_cde.idperiode='$idperiode' and $base_bons_cde.iddepot='$iddepot' ".
                       "group by $base_avoirs.idclient, idproducteur");
    while(list($idproducteur,$idclient,$montant,$nom,$prenom,$codeclient) = mysqli_fetch_row($rep)) {
        if(!array_key_exists($idproducteur, $producteurs)) {
            $producteurs[$idproducteur] = 0;
        }
        $producteurs[$idproducteur] -= $montant;
        if(!isset($tab_cheques[$idclient]["client"])) {
            $tab_cheques[$idclient]["client"] = array($idclient, $nom, $prenom, $codeclient);
        }
        if(!array_key_exists($idproducteur, $tab_cheques[$idclient])) {
            $tab_cheques[$idclient][$idproducteur] = 0;
        }
        $tab_cheques[$idclient][$idproducteur] -= $montant;
    }

    $largeur = (80 / (count($producteurs) + 1)) . "%";
    $chaine = html_debut_tableau("95%","");
    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("20%","","center","","","","","Client","","thliste");

    $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,produits,nom,ordre from $base_producteurs where 1 order by ordre,nom");
    while (list($idproducteur,$produit,$nom) = mysqli_fetch_row($rep1))
    {
        if(isset($producteurs[$idproducteur])) {
            $chaine .= html_colonne("$largeur","","center","","","","", $nom . " - " . $produit,"","thliste");
        }
    }

    $chaine .= html_colonne("$largeur","","center","","","","","Total","","thliste");
    $chaine .= html_fin_ligne();

    $produits = retrouver_produits_producteurs();

    $total_avoirs = 0.0;
    reset($tab_cheques);
    foreach($tab_cheques as $nom => $prixParProducteurs) {
        $client = $prixParProducteurs["client"];
        $idclient = $client[0];
        $nom = $client[1];
        $prenom = $client[2];
        $codeclient = $client[3];

        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("20%","","left","","","","", "$nom $prenom ($codeclient)","","tdliste");
        $total = 0.0;
        foreach($produits as $idproducteur => $desc) {
            if(isset($producteurs[$idproducteur])) {
                if(array_key_exists($idproducteur, $prixParProducteurs) && $prixParProducteurs[$idproducteur] != 0.0) {
                    $v = round($prixParProducteurs[$idproducteur], 2);
                    $total += $v;
                    $chaine .= html_colonne("$largeur","","right","","","","",sprintf($g_lib_somme, $v),"","tdliste");
                }
                else {
                    $chaine .= html_colonne("$largeur","","right","","","","","","","tdliste");
                }
            }
        }
        if(isset($prixParProducteurs[0])) {
            $total += $prixParProducteurs[0];
            $total_avoirs += $prixParProducteurs[0];
            if($prixParProducteurs[0] > 0.0) {
                $t = "(dettes de ";
            } else {
                $t = "(avoirs de ";
            }
            $t .= sprintf($g_lib_somme, $prixParProducteurs[0]) . ") " . sprintf($g_lib_somme, $total);
        } else {
            $t = sprintf($g_lib_somme, $total);
        }
        $chaine .= html_colonne("$largeur","","right","","","","",$t,"","tdliste");
        $chaine .= html_fin_ligne();
    }

    $chaine .= html_debut_ligne("","","","top");
    $chaine .= html_colonne("20%","","left","","","","", "" . "Totaux" . "","","thliste");
    $total = 0.0;
    foreach($produits as $idproducteur => $desc) {
        if(isset($producteurs[$idproducteur])) {
            $total += $producteurs[$idproducteur];
            $chaine .= html_colonne("$largeur","","right","","","","",
                                    sprintf($g_lib_somme, $producteurs[$idproducteur]),"","thliste");
        }
    }

    if($total_avoirs != 0.0) {
        $total += $total_avoirs;
        if($total_avoirs > 0.0) {
            $t = "(dettes de ";
        } else {
            $t = "(avoirs de ";
        }
        $t .= sprintf($g_lib_somme, $total_avoirs) . ") " . sprintf($g_lib_somme, $total);
    } else {
        $t = sprintf($g_lib_somme, $total);
    }
    $chaine .= html_colonne("$largeur","","right","","","","",$t,"","thliste");
    $chaine .= html_fin_ligne();

    $chaine .= html_fin_tableau();
    return $chaine;
}

function afficher_liste_commandes($nomvariable, $defaut, $idclient) {
    global $base_bons_cde,$base_periodes,$g_periode_libelle;
    $texte = "<select size=\"1\" name=\"$nomvariable\">\n";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select $base_bons_cde.id,idboncde," . $g_periode_libelle . " from $base_bons_cde " .
                       "inner join $base_periodes on $base_periodes.id=$base_bons_cde.idperiode " .
                       "where idclient='$idclient' order by $base_bons_cde.id desc");
    if(mysqli_num_rows($rep) != 0)
    {
        if($defaut == 0) $texte .= "<option value=\"0\" selected>Choisissez une commande...</option>\n";
        while(list($id,$idboncde,$libelle) = mysqli_fetch_row($rep))
        {
            $texte .= "<option value=\"" . $id . "\"";
            if ($id == $defaut) $texte .= " selected";
            $texte .= ">" . $idboncde . " (" . $libelle . ")" . "</option>\n";
        }
    }
    else
    {
        $texte .= "<option value=\"\">Pas de commandes...</option>";
    }
    $texte .= "</select>\n";
    return($texte);
}

function retrouver_commande($id) {
    global $base_bons_cde;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idboncde from $base_bons_cde id='$id'");
    if(mysqli_num_rows($rep) != 0) {
        list($idboncde) = mysqli_fetch_row($rep);
        return $idboncde;
    }
    return "";
}

?>
