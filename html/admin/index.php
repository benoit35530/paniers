<?php
require_once("../include/fonctions_include_admin.php");

$titre_page = "Administration des Paniers d'Eden";

echo html_debut_tableau("100%","0","","");
echo html_debut_ligne("","","","top");

$nbitems = 0;
$nbitemsparligne = 3;
$chaine = "";

$id = get_user_meta(get_current_user_id(), 'paniers_gestionnaireId', true);
$rep = mysqli_query($GLOBALS["___mysqli_ston"], "select fonctions from $base_utilisateurs where id='$id'");
if($rep && mysqli_num_rows($rep) != 0)
{
    $chaine .= html_debut_tableau("80%","0","30","30");
    list($fonctions) = mysqli_fetch_row($rep);
    $ses_fonctions = explode(",",$fonctions);
    for ($i = 0; $i < count($ses_fonctions); $i++)
    {
        if ($nbitems == 0) $chaine .= html_debut_ligne();
        $chaine .= html_colonne("","","left","","","","",html_lien("./" . $ses_fonctions[$i] . ".php?","_top",$tab_fonctions[$ses_fonctions[$i]]),"","textegrandgras");
        $nbitems++;
        if ($nbitems > $nbitemsparligne)
        {
            $chaine .= html_fin_ligne();
            $nbitems = 0;
        }
    }
    if ($nbitems != 0)
    {
        for ($i = $nbitems; $i <= $nbitemsparligne; $i++)
        {
            $chaine .= html_colonne("","","center","","","","","");
        }
    }
    $chaine .= html_fin_ligne();
    $chaine .= html_fin_tableau();
}

echo html_colonne("","","center","","","","",$chaine,"","textenormalgras");
echo html_fin_ligne();
echo html_fin_tableau();

require_once("../include/admin/admin_footer.php");

?>