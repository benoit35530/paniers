<?php

function afficher_planning_permanences_frontend($idclient) {
    global $base_permanences,$tab_types_permanences;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,date,heuredebut,heurefin,nbparticipants,nbinscrits,typepermanence from $base_permanences where date >= curdate() order by date, heuredebut");
    $chaine = "";
    if(mysqli_num_rows($rep) != 0)
    {
        $chaine .= html_debut_tableau("70%","","","");
        $chaine .= html_debut_ligne("","","","top");
        $chaine .= html_colonne("","","center","","","","","Date","","thliste");
        $chaine .= html_colonne("","","center","","","","","Type","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de début","","thliste");
        $chaine .= html_colonne("","","center","","","","","Heure de fin","","thliste");
        $chaine .= html_colonne("","","center","","","","","Participants","","thliste");
        $chaine .= html_colonne("","","center","","","","","Action","","thliste");
        $chaine .= html_fin_ligne();
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
            $chaine .= html_debut_ligne("","","","top");
            $chaine .= html_colonne("","","center","","","","",dateexterne($date),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",$tab_types_permanences[$typepermanence],"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heuredebut),"","tdliste");
            $chaine .= html_colonne("","","center","","","","",heures_minutes($heurefin),"","tdliste");
            $chaine .= html_colonne("","","left","","","","",$inscrits,"","tdliste");
            $action = "";
            $pas_deja_inscrit = verifier_non_inscription($id,$idclient);
            if(($nbinscrits < $nbparticipants) && $pas_deja_inscrit)
            {
                $action = html_lien("?action=inscrire&id=$id","_top","S'inscrire");
            }
            if(!$pas_deja_inscrit)
            {
                $action = html_lien("?action=desinscrire&id=$id","_top","Se désinscrire");
            }
            $chaine .= html_colonne("","","center","","","","",$action,"","tdliste");
            $chaine .= html_fin_ligne();
        }
        $chaine .= html_fin_tableau();
    }
    else
    {
        return afficher_erreur("Il n'y a aucune permanence pour le moment");
    }
    return $chaine;
}

?>