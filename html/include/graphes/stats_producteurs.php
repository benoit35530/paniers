<?
Header("Content-Type: image/png");

require_once ("paniers/panachart.php");

global $g_tab_valeurs_stats;

$nb_points = 0;
$nb_total_commandes = 0;
$min = 999999999999999;
$max = 0;

while (list($periode,$nbcommandes) = each($g_tab_valeurs_stats))
{

    $min = min( $nbcommandes, $min );
    $max = max( $nbcommandes, $max );
    $vCht4[] = $nbcommandes;
    $vLabels[] = $periode;
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

$ochart = new chart(800,400,5,$colfond);
$ochart->setTitle(sprintf("Commandes par période (moyenne : %2.2f, total : %2.2f, min : %2.2f, max : %2.2f)",$moyenne,$nb_total_commandes,$min,$max),$coltext,3);
$ochart->setPlotArea(SOLID,$colfond,$colchamp);
$ochart->setFormat(0,'','.');
$ochart->addSeries($vCht4,'bar','Commandes',SOLID,$coltext,$col_bar);
$ochart->addSeries($vCht5,'line','Commandes',LARGE_SOLID,$coltext,$col_bar);
$ochart->addSeries($vmoy,'line','Moyenne',LARGE_SOLID,"#0000FF",$col_bar);
$ochart->setXAxis($coltext,SOLID,2,"Période");
$ochart->setYAxis($coltext,SOLID, 2,"Commandes");
$ochart->setLabels($vLabels,$coltext,2,VERTICAL);
$ochart->setGrid($col_grille, SOLID,$col_grille,SOLID);
$ochart->plot('');

?>
