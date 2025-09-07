<?php

function get_produit_image($image) {
    if ($image) {
        $image = wp_get_attachment_image_src($image, 'medium');
        if ($image) {
            $image = $image[0];
        }
    }
    return $image ? $image : paniers_plugin_url . '/placeholder.png';
}

add_shortcode('paniers-produits-producteur', function($atts) {
    extract(shortcode_atts(array('idproducteur' => '0'), $atts));

    require_once(paniers_dir . "/include/fonctions/fonctions_produits.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_producteurs.php");
    $producteur = retrouver_producteur_info($idproducteur);
    if(!$producteur) {
        return;
    }

    $produits = liste_produits($idproducteur);
    $chaine = "<div class=\"container-fluid\">";
    $i = 0;
    foreach($produits as $nom => list($prix, $image, $description)) {
        $image = get_produit_image($image);
        if ($i == 0) {
            $chaine .= "<div class=\"row\">";
        }
        $chaine .= <<<HTML
        <div class="col-sm p-4">
            <div class="container-fluid">
                <div class="row"><div class="col"><img src="$image"/></div></div>
                <div class="row"><div class="col"><center><b>$nom</b></center></div></div>
                <div class="row"><div class="col"><center>$prix €</center></div></div>
                <div class="row"><div class="col"><center><i>$description</i></center></div></div>
            </div>
        </div>
        HTML;

        $i++;
        if ($i == 4) {
            $chaine .= "</div>";
            $i = 0;
        }
    }
    if ($i > 0 && $i < 4) {
        for (; $i < 4; $i++) {
            $chaine .= "<div class=\"col\"></div>";
        }
        $chaine .= "</div>";
    }
    $chaine .= "</div>";
    return $chaine;
});

?>