<?php

function afficher_info($titre="", $message="", $contenu="") {
    ob_start();
    echo '<div class="alert alert-primary">';
    if ($titre != "") {
        echo "<center><h4>$titre</h4></center>";
    }
    if ($message != "") {
        echo "<div><center>$message</center></div>";
    }
    echo '</div>';
    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    return ob_get_clean();
}

function afficher_erreur($message="", $contenu="") {
    ob_start();
    echo '<div class="alert alert-danger">';

    if ($message == "") {
        $message = "Erreur";
    }
    echo "<div><h4><center>$message</center><h4></div>";

    if ($contenu != "") {
        echo "<div>$contenu</div>";
    }
    echo '</div>';
    return ob_get_clean();
}

?>
