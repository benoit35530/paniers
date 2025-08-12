<?php

function afficher_info($titre="", $message="", $contenu="") {
    ob_start();
    echo '<div>';
    if ($titre != "") {
        echo "<center><h4>$titre</h4></center>";
    }
    if ($message != "") {
        echo "<div class=\"alert alert-primary\"><center>$message</center></div>";
    }
    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    echo '</div>';
    return ob_get_clean();
}

function afficher_erreur($message="", $contenu="") {
    ob_start();
    echo '<div>';
    echo "<center><h4>Erreur</h4><center>";
    if ($message != "") {
        echo "<div class=\"alert alert-danger\"><center>$message</center></div>";
    }
    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    echo '</div>';
    return ob_get_clean();
}

?>
