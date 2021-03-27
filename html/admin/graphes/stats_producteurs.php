<?
Header("Content-Type: image/png");

require_once("include/fonctions_include.php");
require_once("include/panachart.php");

global $g_col_fond,$g_col_text,$g_col_champ,$g_col_bar,$g_col_grille,$base_commandes;

$nb_points = 0;
$nb_total_commandes = 0;
$min = 999999999999999;
$max = 0;

$rep1 = mysql_query("select iddatelivraison,count(*) from $base_commandes where idproducteur='$id' group by iddatelivraison");

while (list($iddate,$nbcommandes) = mysql_fetch_row($rep1))
{

    $min = min( $nbcommandes, $min );
    $max = max( $nbcommandes, $max );
    $vCht4[] = $nbcommandes;
    $vLabels[] = retrouver_date($iddate);
    $nb_points++;
    $nb_total_commandes += $nbcommandes;

}

$moyenne = $nb_total_commandes / $nb_points;

for ($i = 0; $i < count($vCht4); $i++)
{

    $vCht5[$i] = 0;
    $vmoy[] = $moyenne;
    for ($j = 0; $j < 3; $j++)
    {

        if ($i-$j >= 0) { $vCht5[$i] += $vCht4[$i-$j]; } else { break; }

    }

    if ($j > 0) $vCht5[$i] = $vCht5[$i]/$j;

}

$ochart = new chart(800,400,5,$g_col_fond);
$ochart->setTitle(sprintf("Commandes par période (moyenne : %2.2f, total : %2.2f, min : %2.2f, max : %2.2f)",$moyenne,$nb_total_commandes,$min,$max),$g_col_text,3);
$ochart->setPlotArea(SOLID,$g_col_fond,$g_col_champ);
$ochart->setFormat(0,'','.');
$ochart->addSeries($vCht4,'bar','Commandes',SOLID,$g_coltext,$g_col_bar);
$ochart->addSeries($vCht5,'line','Commandes',LARGE_SOLID,$g_col_text,$g_col_bar);
$ochart->addSeries($vmoy,'line','Moyenne',LARGE_SOLID,"#0000FF",$g_col_bar);
$ochart->setXAxis($g_col_text,SOLID,2,"Période");
$ochart->setYAxis($g_col_text,SOLID, 2,"Commandes");
$ochart->setLabels($vLabels,$g_col_text,2,VERTICAL);
$ochart->setGrid($g_col_grille, SOLID,$g_col_grille,SOLID);
$ochart->plot('');

?>
