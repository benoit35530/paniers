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

?>