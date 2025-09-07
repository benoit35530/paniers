<?php

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
    $rep0 = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_dates where 1 order by id desc limit 12");
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
    $nrows = 0;
    if ($rep) {
        $nrows = mysqli_num_rows($rep);
    }
    if ($nrows > 0) {
        $chaine .= "<div class=\"col-sm\"><select id=\"date\" onchange=\"datechange()\">";
        $datenextlivraisontime = strtotime(date("Y-m-d", strtotime("$jour_commande")));
        $selected = false;
        while(list($iddatebase,$datelivraison) = mysqli_fetch_row($rep)) {
            $chaine .= "<option value=\"" . $iddatebase . "\"";
            $datelivraisontime = strtotime($datelivraison);
            if((!$iddate && $datelivraisontime >= $datenextlivraisontime && !$selected) || $iddate == $iddatebase) {
                $selected = true;
                $chaine .= " selected";
                if(!$iddate) {
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
                    $image = get_produit_image($param_produit["image"]);
                    $chaine3 .= '  <td><button class="btn text-decoration-popup-link" type="button" data-bs-toggle="popover" data-image="' . $image . '" data-description="' . $param_produit["description"] . '" title="' . $param_produit["nom"] . '">' . $param_produit["nom"] . '</button></td>';
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

add_shortcode('paniers-livraisons',  function () {
    $userid = paniers_checkIfLoggedIn();
    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/commandes.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    global $wp_query;
    $iddate = $wp_query->get("iddate");
    $idclient = $wp_query->get("idclient");
    return afficher_recapitulatif_livraisons_frontend(!$idclient ? $userid : $idclient, $iddate);
});

?>