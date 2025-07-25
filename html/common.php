<?php

function afficher_info($titre="", $message="", $contenu="") {
    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    if ($titre != "") {
        echo "<h2>$titre</h2><br>";
    }
    if ($message != "") {
        echo "<div class=\"textemsginfo\">$message</div><br>";
    }
    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    return ob_get_clean();
}

function afficher_erreur($titre="", $message="", $contenu="") {
    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    if ($titre != "") {
        echo "<h2>$titre</h2><br>";
    }
    if ($message != "") {
        echo "<div class=\"textemsgerreur\">$message</div><br>";
    }
    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    return ob_get_clean();
}

?>
