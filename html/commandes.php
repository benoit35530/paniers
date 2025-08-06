<?php

require_once("common.php");

function formulaire_bon_commande_frontend($idclient, $idcommande, $idperiode, $qteproduit=array())
{
    global $base_dates,$base_producteurs,$base_produits,$base_dates,$g_lib_somme;

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,datelivraison from $base_dates where idperiode = '$idperiode' order by datelivraison");
    $nbdates = 0;
    while(list($iddate,$datelivraison) = mysqli_fetch_row($rep0)) {
        $dates[$nbdates]['id'] = $iddate;
        $dates[$nbdates]['datelivraison'] = $datelivraison;
        $nbdates++;
    }

    if($nbdates == 0) {
        return afficher_erreur("Aucune date de disponible pour cette commande...");
    }

    $absences = retrouver_absences($idperiode);
    $avoirs = retrouver_avoirs($idclient, $idcommande);
    $colspan = $nbdates + 2;

    $affiche_avoir = function($montant, $description, $consumed, $idproducteur, $colspan) {
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
        $chaineavoir .= '  <td class="table-light" colspan=' . $colspan . '>'. $desc . '</td>';
        if($consumed) {
            $chaineavoir .= '  <td class="table-light" id="avoirproducteur' . $idproducteur . '" data-value="' . $montant . '" style="text-align: right; white-space:nowrap;">' . $m . '</td>';
        } else {
            $chaineavoir .= '  <td class="table-light" id="avoirproducteur' . $idproducteur . '" data-value="' . $montant . '" style="text-align: right; white-space:nowrap;"></td>';
        }
        $chaineavoir .= '</tr>';
        return $chaineavoir;
    };

    $total_commande = 0.0;
    $chaine = <<<HTML
        <table class="table table-bordered" id="commande">
            <thead class="table" style="position: sticky; top:0;">
                <tr class="table-dark">
                    <th scope="col">Producteurs</th>
                    <th scope="col" style="text-align:right;"><a role="button" href="#" onclick="jQuery('#accordionProducteur .collapse').collapse('toggle');" class="btn btn-primary">Tout déplier</a></th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="p-0" colspan="2">
    HTML;
    $chaine .= '<div class="accordion" id="accordionProducteur">';

    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,produits,paiement from $base_producteurs where etat = 'Actif' order by ordre,produits");
    while(list($idproducteur, $nom, $produits,$paiement) = mysqli_fetch_row($rep0))
    {
        $total_producteur = 0.0;
        $chaine2 = "";
        $rep1 = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,description,prix,image,UNIX_TIMESTAMP(curdate()) - UNIX_TIMESTAMP(datemodif) from $base_produits where idproducteur = '$idproducteur' and etat = 'Actif' order by description");
        while(list($idproduit, $nomProduit, $description, $prix, $image, $sincemodif) = mysqli_fetch_row($rep1))
        {
            $total_qte_produit = 0;
            $image = wp_get_attachment_image_src($image, array(300, 300))[0];
            $produit = $sincemodif < 24 * 3600 * 30 ? "&#11088; " . $nomProduit : $nomProduit;
            $chaine2 .= '<tr>';
            $chaine2 .= '  <td><button class="btn btn-lg btn-link" type="button" data-bs-toggle="popover" data-image="' . $image . '" data-description="' . $description . '" title="' . $nomProduit . '">' . $produit . '</button></td>';
            $chaine2 .= '  <td class="table-light" id="prix" data-value="' . $prix . '" style="text-align: right; white-space:nowrap;">' . sprintf($g_lib_somme,$prix) . '</td>';
            reset($dates);
            foreach($dates as $k => $v)
            {
                $iddate = $v["id"];

                $quantite =
                    array_key_exists($idproducteur, $qteproduit) &&
                    array_key_exists($idproduit, $qteproduit[$idproducteur]) &&
                    array_key_exists($iddate, $qteproduit[$idproducteur][$idproduit]) &&
                    is_numeric($qteproduit[$idproducteur][$idproduit][$iddate]) ?
                        $qteproduit[$idproducteur][$idproduit][$iddate] : 0;
                if ($quantite == 0) {
                    $quantite = "";
                }
                $name = "qteproduit[$idproducteur][$idproduit][$iddate]";
                if(array_key_exists($iddate, $absences) &&
                   array_key_exists($idproducteur, $absences[$iddate]) &&
                   $absences[$iddate][$idproducteur])
                {
                    $chaine2 .= '  <td><input type="hidden" value="' . $quantite . '" name="' . $name . '"></input></td>';
                } else {
                    $chaine2 .= '  <td style="vertical-align: middle;"><input type="number" size="2" min="0" max="99" value="' . $quantite . '" name="' . $name . '"></input></td>';
                }
                if ($quantite != "") {
                    $total_qte_produit += $quantite;
                }
            }

            $totalproduit = $total_qte_produit * $prix;
            $chaine2 .= '  <td class="table-secondary" id="totalproduit' . $idproducteur . '" data-value="' . $totalproduit . '" style="text-align: right; white-space:nowrap;">' . sprintf($g_lib_somme,$totalproduit) . '</td>';
            $chaine2 .= '</tr>';

            $total_producteur += $total_qte_produit * $prix;
        }

        $producteur = "$produits - $nom";
        if ($total_producteur > 0) {
            $producteur = "<b>$producteur</b>";
        }

        if(isset($avoirs[$idproducteur])) {
            $avoirProducteur = $avoirs[$idproducteur];
            foreach($avoirProducteur["montant"] as $id => $montant) {
                if ($montant < 0 || $montant < $total_producteur) {
                    $chaine2 .= $affiche_avoir($montant, $avoirProducteur["description"][$id], true, $idproducteur, $colspan);
                    $total_producteur -= $montant;
                } else {
                    $chaine2 .= $affiche_avoir($montant, $avoirProducteur["description"][$id], false, $idproducteur, $colspan);
                }
            }
        }

        if ($paiement != '') {
            $paiementHTML = '<div class="row"><div class="col" style="text-align: center"><b>&#9888; ' . $paiement . '</b></div></div>';
        } else {
            $paiementHTML = '';
        }

        $chaine .= <<<HTML
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" id="producteur$idproducteur" data-producteur="$produits - $nom" style="font-weight: normal; font-size: 1.5rem" type="button" data-bs-toggle="collapse" data-bs-target="#collapse$idproducteur" aria-expanded="false" aria-controls="collapse$idproducteur">
                    $producteur
                </button>
            </h2>
            <div id="collapse$idproducteur" class="accordion-collapse collapse">
                <div class="accordion-body p-3">
                    $paiementHTML
                    <table class="table table-bordered m-0">
                        <thead class="table-secondary" style="position: sticky; top:0;">
                            <tr>
                                <th scope="col"></th>
                                <th scope="col">Prix</th>
        HTML;

        reset($dates);
        foreach($dates as $key => $val)
        {
            $chaine .= '      <th>' . dateexterne($val["datelivraison"], false) . '</th>';
        }
        $chaine .= <<<HTML
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
        HTML;

        $chaine .= $chaine2;

        $chaine .= '    <tr class="table-secondary">';
        $chaine .= '      <td colspan=' . $colspan . ' style="text-align: right">Total</td>';
        $chaine .= '      <td style="text-align: right; white-space:nowrap" id="totalproducteur' . $idproducteur . '" data-value="'. $total_producteur .'">' . sprintf($g_lib_somme,$total_producteur) . '</td>';
        $chaine .= '    </tr>';
        $chaine .= '  </tbody>';
        $chaine .= '</table>';

        $chaine .= <<<HTML
                    </div>
                </div>
            </div>
        HTML;
        $total_commande += $total_producteur;
    }
    $chaine .= "</div></td></tr>";
    if(isset($avoirs[0])) {
        foreach($avoirs[0]["montant"] as $id => $montant) {
            if ($montant < 0 || $montant < $total_commande) {
                $total_commande -= $montant;
                $chaine .= $affiche_avoir($montant, $avoirs[0]["description"][$id], true, 0, 1);
            } else {
                $chaine .= $affiche_avoir($montant, $avoirs[0]["description"][$id], false, 0, 1);
            }
        }
    }
    $chaine .= '    <tr class="table-dark">';
    $chaine .= '      <td style="text-align: right">Total</td>';
    $chaine .= '      <td id="totalcommande" style="text-align: right; white-space:nowrap">' . sprintf($g_lib_somme,$total_commande) . '</td>';
    $chaine .= '    </tr>';
    $chaine .= '  </tbody>';
    $chaine .= '</table>';

    return $chaine;
}

function afficher_formulaire_bon_commande_frontend($idcommande, $idclient, $idperiode, $qteproduit, $action) {

    $iddepot = retrouver_depot_client($idclient);

    $chaine = '<div class="container-fluid">';
    $chaine .= '<form method="post">';

    $chaine .= '<div class="row px-3 py-2">';
    $chaine .= '  <div class="col-3"></div>';
    $chaine .= '  <div class="col-sm">';
    $chaine .= afficher_liste_depots_actifs("iddepot", $iddepot);
    // if(retrouver_etat_depot($iddepot) != "Actif") {
    //     $champs["aide"][] = "<b>Votre dépôt habituel est fermé pour cette commande, merci de selectionner un autre dépôt.</b>";
    // }
    $chaine .= '  </div>';
    $chaine .= '  <div class="col-3"></div>';
    $chaine .= '</div>';

    $chaine .= '<div class="row py-2">';
    $chaine .= '  <div class="col"><center>&#11088; = Produit récemment ajouté ou modifié</center></div>';
    $chaine .= '</div>';

    $chaine .= '<div class="row">';
    $chaine .= '  <div class="col">';
    $chaine .= formulaire_bon_commande_frontend($idclient, $idcommande, $idperiode, $qteproduit);
    $chaine .= '  </div>';
    $chaine .= '</div>';

    $formaction = "?action=$action&idperiode=$idperiode&id=$idcommande" . ($idclient != 0 ? "&idclient=$idclient" : "");

    $chaine .= '<div class="row">';
    $chaine .= '  <div class="col">';
    $chaine .= '    <input type="submit" id="save" value="Sauvegarder" formaction="' . $formaction . '">';
    $chaine .= '  </div>';
    $chaine .= '</div>';

    $chaine .= '</form>';
    $chaine .= '</div>';

    $chaine .= <<<HTML
    <script type="module">

    jQuery('[data-bs-toggle="popover"]').each(function () {
        return new bootstrap.Popover(this, {
            trigger: 'focus hover',
            content:
            this.dataset.image == '' ?
                this.dataset.description :
                '<div class="container-fluid"' +
                '  <div class="row">' +
                '    <div class="col"><img src="' + this.dataset.image + '"></div>' +
                '    <div class="col">' + this.dataset.description + '</div>' +
                '  </div>' +
                '</div>',
            html: true
        });
    });

    var totalProducteurs = jQuery('table#commande td[id^="totalproducteur"]');
    var totalAvoirs = jQuery('table#commande td[id=avoirproducteur0]');
    var totalCommande = jQuery('table#commande td[id="totalcommande"]')[0];
    var updated = false;

    jQuery(window).bind('beforeunload', function()
    {
        if (updated) {
            return true;
        }
    });

    jQuery('input[id=save]').on('click', function() {
        jQuery(window).off('beforeunload');
    });

    jQuery('table#commande input[name^=qteproduit').change(function(event)
    {
        updated = true;

        var producteurId = event.target.name.substr(11);
        producteurId = producteurId.substr(0, producteurId.indexOf("]"));
        var row = event.target.parentElement.parentElement;

        // Total produit
        var count = 0;
        var quantites = row.querySelectorAll('input[name^=qteproduit').forEach(function (element)
        {
            var c = parseInt(element.value);
            if(!isNaN(c)) {
                count += c;
            }
        });
        var totalProduit = row.querySelector('#totalproduit' + producteurId);
        var prixProduit = row.querySelector("#prix")
        var prixTotalProduit = parseFloat(prixProduit.dataset.value) * count;
        totalProduit.dataset.value = prixTotalProduit;
        totalProduit.innerHTML = prixTotalProduit.toFixed(2) + " €";

        // Total producteur
        var totalProduitsProducteur = jQuery('table#commande td[id=totalproduit' + producteurId + ']');
        var avoirsProducteur = jQuery('table#commande td[id=avoirproducteur' + producteurId + ']');
        var prixTotalProducteur = 0.0;
        totalProduitsProducteur.each(function ()
        {
            prixTotalProducteur += parseFloat(this.dataset.value);
        });

        var button = jQuery('table#commande button[id=producteur' + producteurId + ']')[0];
        if(prixTotalProducteur > 0) {
            button.innerHTML = "<b>" + button.dataset.producteur + "</b>";
        } else {
            button.innerHTML = button.dataset.producteur;
        }

        avoirsProducteur.each(function()
        {
            var montant = parseFloat(this.dataset.value);
            if(montant < 0.0 || montant <= prixTotalProducteur) {
                prixTotalProducteur -= montant;
                this.innerHTML = (-montant).toFixed(2) + " €";
            } else {
                this.innerHTML = "";
            }
        });
        var totalProducteur = jQuery('table#commande td[id=totalproducteur' + producteurId + ']')[0];
        totalProducteur.dataset.value = prixTotalProducteur;
        totalProducteur.innerHTML = prixTotalProducteur.toFixed(2) + " €";

        // Prix total de la commande
        var prixTotalCommande = 0.0;
        totalProducteurs.each(function ()
        {
            prixTotalCommande += parseFloat(this.dataset.value);
        });
        totalAvoirs.each(function()
        {
            var montant = parseFloat(this.dataset.value);
            if(montant < 0.0 || montant < prixTotalCommande) {
                prixTotalCommande -= montant;
                this.innerHTML = (-montant).toFixed(2) + " €";
            } else {
                this.innerHTML = "";
            }
        });
        totalCommande.innerHTML = prixTotalCommande.toFixed(2) + " €";
    });
    </script>
    HTML;

    return $chaine;
}


function afficher_recapitulatif_bon_commande_frontend($id, $idperiode) {
    global $base_commandes, $base_dates, $g_lib_somme;

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
            $image = wp_get_attachment_image_src($param_produit["image"], array(300, 300))[0];
            $chaine3 .= '  <td><button class="btn btn-lg btn-link" type="button" data-bs-toggle="popover" data-image="' . $image . '" data-description="' . $param_produit["description"] . '" title="' . $param_produit["nom"] . '" style="font-size: 14px;">' . $param_produit["nom"] . '</button></td>';
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
            $chaine .= $affiche_avoir($montant, $avoirs[0]["description"][$id], $nbdates);
            $total_commande -= $montant;
        }
    }

    $chaine .= '    <tr class="table-dark">';
    $chaine .= '      <td colspan=' . ($nbdates + 3) . ' style="text-align: right">Total</td>';
    $chaine .= '      <td style="text-align: right; white-space:nowrap">' . sprintf($g_lib_somme,$total_commande) . '</td>';
    $chaine .= '    </tr>';
    $chaine .= '  </tbody>';
    $chaine .= '</table>';

    $chaine .= <<<HTML
    <script type="module">

    jQuery('[data-bs-toggle="popover"]').each(function () {
        return new bootstrap.Popover(this, {
            trigger: 'focus hover',
            content:
            this.dataset.image == '' ?
                this.dataset.description :
                '<div class="container-fluid"' +
                '  <div class="row">' +
                '    <div class="col"><img src="' + this.dataset.image + '"></div>' +
                '    <div class="col">' + this.dataset.description + '</div>' +
                '  </div>' +
                '</div>',
            html: true
        });
    });
    </script>
    HTML;

    return $chaine;
}

function afficher_liste_bon_commandes_frontend($idclient, $path) {
    global $base_bons_cde,$base_periodes,$g_periode_libelle;
    $chaine = "";
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select " .
                       "$base_bons_cde.id," .
                       "idboncde," .
                       "$base_bons_cde.datemodif," .
                       $g_periode_libelle .
                       "from $base_bons_cde " .
                       "inner join $base_periodes on $base_periodes.id=$base_bons_cde.idperiode " .
                       "where $base_bons_cde.idclient = '$idclient' " .
                       "order by $base_bons_cde.datemodif desc");
    if ($rep && mysqli_num_rows($rep) != 0) {
        $chaine .= '<table id="liste-des-commandes" class="table table-bordered mt-5">';
        $chaine .= '  <thead class="table-dark" style="position: sticky; top:0;">';
        $chaine .= '    <tr>';
        $chaine .= '      <th scope="col">Commande</th>';
        $chaine .= '      <th scope="col">Période</th>';
        $chaine .= '      <th scope="col">Date</th>';
        $chaine .= '    </tr>';
        $chaine .= '  </thead>';
        $chaine .= '  <tbody>';
        while(list($id,$idboncde,$datemodif,$periode) = mysqli_fetch_row($rep))
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
        $chaine .= afficher_info("Commandes", "Vous n'avez encore aucune commande enregistrée");
    }
    return $chaine;
}

function afficher_recapitulatif_livraisons_frontend($idclient, $iddate = 0) {
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
urlParams.delete('iddate');
window.location.search = urlParams;
}
</script>
HTML;

    $chaine .= '<div class="container-fluid">';
    $chaine .= '<div class="row">';


    $idpremieredatelivraison = 0;
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"],
        "select id from $base_dates where 1 order by id desc limit 12");
    while(list($iddatebase) = mysqli_fetch_row($rep0)) {
        $idpremieredatelivraison = $iddatebase;
    }
    if ($idpremieredatelivraison == 0) {
        return afficher_info("Livraisons", "Il n'y a aucune livraison à venir");
    }

    if(current_user_can('gestionnaire')) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"],
            "select distinct $base_clients.id,$base_clients.nom,$base_clients.prenom,$base_clients.codeclient " .
            "from $base_commandes " .
            "inner join $base_clients on $base_clients.id=$base_commandes.idclient " .
            "inner join $base_dates on $base_commandes.iddatelivraison=$base_dates.id " .
            "where $base_commandes.iddatelivraison>=$idpremieredatelivraison order by $base_clients.nom");
        if($rep && mysqli_num_rows($rep) > 0) {
            $chaine .= "<div class=\"col-sm\"><select id=\"client\" onchange=\"clientchange()\">\n";
            while(list($idclientbase, $nom, $prenom,$codeclient) = mysqli_fetch_row($rep))
            {
                $chaine .= "<option value=\"" . $idclientbase . "\"";
                if ($idclientbase == $idclient) $chaine .= " selected";
                $chaine .= ">$nom $prenom ($codeclient)</option>\n";
            }
            $chaine .= "</select></div>";
        } else {
            return afficher_info("Livraisons", "Il n'y a aucune livraison à venir");
        }
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"],
        "select distinct $base_dates.id, $base_dates.datelivraison " .
        "from $base_dates " .
        "inner join $base_commandes on $base_commandes.iddatelivraison=$base_dates.id " .
        "where $base_dates.id>=$idpremieredatelivraison and $base_commandes.idclient=$idclient " .
        "order by $base_dates.id");
    $nrows = mysqli_num_rows($rep);
    if ($rep && $nrows > 0) {
        $chaine .= "<div class=\"col-sm\"><select id=\"date\" onchange=\"datechange()\">";
        $datenextlivraisontime = strtotime(date("Y-m-d", strtotime("$jour_commande")));
        $selected = false;
        while(list($iddatebase,$datelivraison) = mysqli_fetch_row($rep)) {
            $chaine .= "<option value=\"" . $iddatebase . "\"";
            $datelivraisontime = strtotime($datelivraison);
            if(($iddate == 0 && $datelivraisontime >= $datenextlivraisontime && !$selected) || $iddate == $iddatebase) {
                $selected = true;
                $chaine .= " selected";
                if($iddate == 0) {
                    $iddate = $iddatebase;
                }
            }
            if (!$selected && --$nrows == 0) {
                $chaine .= " selected";
                $iddate = $iddatebase;
            }
            $chaine .= ">" . datelitterale($datelivraison) . "</option>";
        }
        $chaine .= "</select></div>";
    } else {
        return afficher_info("Livraisons", "Il n'y a aucune livraison à venir");
    }

    $chaine .= "</div>";
    $chaine .= "</div>";

    if ($iddate > 0) {
        $qteproduit = array();
        $rep = mysqli_query($GLOBALS["___mysqli_ston"],
                            "select quantite,idproducteur,idproduit " .
                            "from $base_commandes " .
                            "where iddatelivraison=\"$iddate\" and idclient=\"$idclient\"");
        if ($rep && mysqli_num_rows($rep) > 0) {
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
                    $image = wp_get_attachment_image_src($param_produit["image"], array(300, 300))[0];
                    $chaine3 .= '  <td><button class="btn btn-lg btn-link" type="button" data-bs-toggle="popover" data-image="' . $image . '" data-description="' . $param_produit["description"] . '" title="' . $param_produit["nom"] . '" style="font-size: 14px;">' . $param_produit["nom"] . '</button></td>';
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
        } else {
            $chaine .= afficher_info("Vous n'avez pas de commandes");
        }
    }
    $chaine .= <<<HTML
    <script type="module">

    jQuery('[data-bs-toggle="popover"]').each(function () {
        return new bootstrap.Popover(this, {
            trigger: 'focus hover',
            content:
            this.dataset.image == '' ?
                this.dataset.description :
                '<div class="container-fluid"' +
                '  <div class="row">' +
                '    <div class="col"><img src="' + this.dataset.image + '"></div>' +
                '    <div class="col">' + this.dataset.description + '</div>' +
                '  </div>' +
                '</div>',
            html: true
        });
    });
    </script>
    HTML;

    return $chaine;
}

?>