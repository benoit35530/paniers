<?php

function afficher_planning_permanences_frontend($idclient) {
    global $base_permanences,$tab_types_permanences;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where date >= curdate() order by date, heuredebut");
    $chaine = "";
    if($rep && mysqli_num_rows($rep) > 0)
    {
        $chaine .= '<table id="liste-des-permanences" class="table table-bordered mt-5">';
        $chaine .= '  <thead class="table-dark" style="position: sticky; top:0;">';
        $chaine .= '    <tr>';
        $chaine .= '      <th scope="col">Date</th>';
        $chaine .= '      <th scope="col">Type</th>';
        $chaine .= '      <th scope="col">Heure de début</th>';
        $chaine .= '      <th scope="col">Heure de fin</th>';
        $chaine .= '      <th scope="col">Participants</th>';
        $chaine .= '      <th scope="col">Action</th>';
        $chaine .= '    </tr>';
        $chaine .= '  </thead>';
        $chaine .= '  <tbody>';
        while (list($id,$date,$heuredebut,$heurefin,$nbparticipants,$nbinscrits,$typepermanence) = mysqli_fetch_row($rep))
        {
            $tab_permanenciers = retrouver_permanenciers($id,false);
            $inscrits = "";
            if(count($tab_permanenciers) != 0)
            {
                foreach($tab_permanenciers as $key => $val)
                {
                    $inscrits .= $val . "<br>";
                }
            }

            $chaine .= "    <tr>";
            $chaine .= "      <td>" . dateexterne($date) . "</td>";
            $chaine .= "      <td>$tab_types_permanences[$typepermanence]</td>";
            $chaine .= "      <td>" . heures_minutes($heuredebut) . "</td>";
            $chaine .= "      <td>" . heures_minutes($heurefin) . "</td>";
            $chaine .= "      <td>" . $inscrits . "</td>";
            $action = "";
            $pas_deja_inscrit = verifier_non_inscription($id,$idclient);
            if(($nbinscrits < $nbparticipants) && $pas_deja_inscrit)
            {
                $chaine .= "      <td><a href='?action=inscrire&id=$id'>S'inscrire</a></td>";
            }
            if(!$pas_deja_inscrit)
            {
                $chaine .= "      <td><a href='?action=desinscrire&id=$id'>Se désinscrire</a></td>";
            }
            $chaine .= "    </tr>";
        }
        $chaine .= "  </tbody>";
        $chaine .= "</table>";
    }
    else
    {
        return afficher_erreur("Il n'y a aucune permanence pour le moment");
    }
    return $chaine;
}

add_shortcode('paniers-permanences', function () {
    $userid = paniers_checkIfLoggedIn();

    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/common.php");
    require_once(paniers_dir . "/permanences.php");

    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    global $wp_query;
    global $base_permanences;
    global $base_permanenciers;

    $action = $wp_query->get("action");
    $id = $wp_query->get("id");

    ob_start();

    if ($action == "") {
        echo afficher_planning_permanences_frontend($userid);
    } else if ($action == "inscrire") {
        mysqli_begin_transaction($GLOBALS["___mysqli_ston"], MYSQLI_TRANS_START_READ_WRITE);
        try {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nbparticipants,nbinscrits from $base_permanences where id='$id' and date >= curdate()");
            if (mysqli_num_rows($rep) != 0) {
                list($nbparticipants,$nbinscrits) = mysqli_fetch_row($rep);
            }
            if ($userid > 0 && $nbinscrits < $nbparticipants && verifier_non_inscription($id,$userid))
            {
                if (!mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanenciers (id,idpermanence,idclient,commentaire,datemodif) values ('','$id','$userid','',now())")) {
                    echo afficher_erreur(
                        "Vous êtes déjà inscrit à la permanence.",
                        afficher_planning_permanences_frontend($userid));
                    mysqli_rollback($GLOBALS["___mysqli_ston"]);
                } else {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits+1 where id='$id'");
                    echo afficher_info(
                        "",
                        "Merci de vous être inscrit à cette permanence",
                        afficher_planning_permanences_frontend($userid));
                    ecrire_log_public("Inscription à la permanence : " . retrouver_permanence($id));
                    mysqli_commit($GLOBALS["___mysqli_ston"]);
                }
            } else {
                echo afficher_erreur(
                    "Numéro d'utilisateur inconnu, déjà inscrit ou trop d'inscrits",
                    afficher_planning_permanences_frontend($userid));
                mysqli_rollback($GLOBALS["___mysqli_ston"]);
            }
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS["___mysqli_ston"]);
            throw $e;
        }
    } else if ($action == "desinscrire") {
        mysqli_begin_transaction($GLOBALS["___mysqli_ston"], MYSQLI_TRANS_START_READ_WRITE);
        try {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where id='$id' and date >= curdate()");
            if ($userid > 0 && mysqli_num_rows($rep) != 0 && !verifier_non_inscription($id,$userid)) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanenciers where idpermanence='$id' and idclient='" . $userid . "' limit 1");
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits-1 where id='$id'");
                echo afficher_info(
                    "",
                    "Vous êtes désinscrit de cette permanence",
                    afficher_planning_permanences_frontend($userid));
                ecrire_log_public("Désinscription de la permanence : " . retrouver_permanence($id));
                mysqli_commit($GLOBALS["___mysqli_ston"]);
            }
            else
            {
                echo afficher_erreur(
                    "Vous êtes déja désinscrit de la permanence ou votre numéro d'utilisateur est inconnu",
                    afficher_planning_permanences_frontend($userid));
                mysqli_rollback($GLOBALS["___mysqli_ston"]);
            }
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS["___mysqli_ston"]);
            throw $e;
        }
    }

    $content = ob_get_contents();
    ob_clean();
    return $content;
});

?>