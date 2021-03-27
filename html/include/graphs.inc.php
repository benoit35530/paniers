<?
/*
 +-------------------------------------------------------------------+
 |                   H T M L - G R A P H S   (v1.3)                  |
 |                                                                   |
 | Copyright Gerd Tentler               info@gerd-tentler.de         |
 | Created: Sep. 17, 2002               Last modified: Dec. 18, 2003 |
 +-------------------------------------------------------------------+
 | This program may be used and hosted free of charge by anyone for  |
 | personal purpose as long as this copyright notice remains intact. |
 |                                                                   |
 | Obtain permission before selling the code for this program or     |
 | hosting this software on a commercial website or redistributing   |
 | this software over the Internet or in any other medium. In all    |
 | cases copyright must remain intact.                               |
 +-------------------------------------------------------------------+
 */
//======================================================================================================
// Parameters:
//
// - graph type ("hBar", "vBar", "pBar")
// - values (string with comma-separated values or array)
// - labels (string with comma-separated values or array)
// - bar color (string with comma-separated values or array)
// - hBar/vBar: label color, bar background color; pBar: background color (string)
// - show values (1 = yes, 0 = no)
// - hBar/vBar: legend items (string with comma-separated values or array)
// - number of graphs
//
// Returns HTML code
//======================================================================================================

function bar_graph($type, $values, $labels = '', $bColor = '', $lColor = '', $showVal = 0, $legend = '', $graphcnt = 1) {
    error_reporting(E_WARNING);

    $colors = array('#0000FF', '#FF0000', '#00E000', '#A0A0FF', '#FFA0A0', '#00A000');
    $graph = '';
    $d = (is_array($values)) ? $values : explode(',', $values);
    if(is_array($labels)) $r = $labels;
    else $r = (strlen($labels) > 1) ? explode(',', $labels) : array();
    $lc = explode(',', $lColor);
    $lc[0] = (strlen($lc[0]) < 3) ? '#C0E0FF' : trim($lc[0]);
    $lc[1] = trim($lc[1]);
    $drf = (is_array($bColor)) ? $bColor : explode(',', $bColor);

    if($legend && $type != 'pbar')
    $graph .= '<table border=0 cellspacing=0 cellpadding=0><tr valign=top><td>';

    if($graphcnt > 1) {
        $divide = ceil(count($d) / $graphcnt);
        $graph .= '<table border=0 cellspacing=0 cellpadding=6><tr valign=top><td>';
    }
    else $divide = 0;

    $min = 9999999999;
    for($i = $sum = $max = $ccnt = $lcnt = $chart = 0; $i < count($d); $i++) {
        if($divide && $i && !($i % $divide)) {
            $lcnt = 0;
            $chart++;
        }
        $drw = explode(';', $d[$i]);
        for($j = 0; $j < count($drw); $j++) {
            $val[$chart][$lcnt][$j] = trim($drw[$j]);
            //        $sum += $val[$chart][$lcnt][$j];
            if($val[$chart][$lcnt][$j] > $max) $max = $val[$chart][$lcnt][$j];
            if($val[$chart][$lcnt][$j] < $min) $min = $val[$chart][$lcnt][$j];
            if(!$bc[$j]) {
                if($ccnt >= count($colors)) $ccnt = 0;
                $bc[$j] = (strlen($drf[$j]) < 3) ? $colors[$ccnt++] : trim($drf[$j]);
            }
            for($j = 0; $j < count($drw); $j++) {
                $sum += $val[$chart][$lcnt][$j]-$min;
            }
        }
        $lcnt++;
    }

    //    $mPercent = $sum ? round($max * 100 / $sum) : 0;
    $mPercent = $sum ? round(($max-$min) * 100 / $sum) : 0;
    //    $mul = $mPercent ? 100 / $mPercent : 1;
    $mul = $mPercent ? 200 / $mPercent : 2;
    $type = strtolower($type);

    for($chart = $lcnt = 0; $chart < count($val); $chart++) {
        $graph .= '<table border=0 cellspacing=4 cellpadding=0>';

        if($type == 'hbar') {
            for($i = 0; $i < count($val[$chart]); $i++, $lcnt++) {
                $label = ($lcnt < count($r)) ? trim($r[$lcnt]) : $lcnt+1;
                $graph .= '<tr><td rowspan=' . count($val[$chart][$i]) . " bgcolor=$lc[0] align=left><font face=verdana size=-2>$label</font></td>";

                for($j = 0; $j < count($val[$chart][$i]); $j++) {
                    //            $percent = $sum ? round($val[$chart][$i][$j] * 100 / $sum) : 0;
                    $percent = $sum ? round(($val[$chart][$i][$j]-$min) * 100 / $sum) : 0;
                    if($j) $graph .= '<tr>';
                    if($showVal) $graph .= '<td bgcolor=' . $lc[0] . ' align=right>' . $val[$chart][$i][$j] . '</td>';

                    $graph .= '<td' . ($lc[1] ? " bgcolor=$lc[1]" : '') . '>';
                    $graph .= '<table border=0 cellspacing=0 cellpadding=0><tr>';

                    if($percent) {
                        $graph .= '<td bgcolor=' . $bc[$j] . ' width=' . round($percent * $mul) . '>&nbsp;</td>';
                    }
                    $graph .= '<td width=' . round(($mPercent - $percent) * $mul + 20) . '>';
                    //            $graph .= "&nbsp;$percent%</td>";
                    $graph .= "</td>";
                    $graph .= '</tr></table></td>';
                    $graph .= '</tr>';
                }
            }
        }
        else if($type == 'vbar') {
            $graph .= '<tr align=center valign=bottom>';
            for($i = 0; $i < count($val[$chart]); $i++) {

                for($j = 0; $j < count($val[$chart][$i]); $j++) {
                    //            $percent = $sum ? round($val[$chart][$i][$j] * 100 / $sum) : 0;
                    $percent = $sum ? round(($val[$chart][$i][$j]-$min) * 100 / $sum) : 0;
                    $graph .= '<td' . ($lc[1] ? " bgcolor=$lc[1]" : '') . '>';
                    $graph .= '<table border=0 cellspacing=0 cellpadding=0 width=20 align=center>';
                    $graph .= '<tr align=center valign=bottom>';
                    $graph .= '<td height=' . round(($mPercent - $percent) * $mul + 15) . '>';
                    $graph .= '<font face="Verdana" size="-2">' . $val[$chart][$i][$j] . '</font></td>';
                    if($percent) {
                        $graph .= '</tr><tr align=center valign=bottom>';
                        $graph .= '<td style="font-size:1px" bgcolor=' . $bc[$j] . ' height=' . round($percent * $mul) . '>&nbsp;</td>';
                    }
                    $graph .= '</tr></table></td>';
                }
            }
            if($showVal) {
                $graph .= '</tr><tr align=center>';
                for($i = 0; $i < count($val[$chart]); $i++) {
                    for($j = 0; $j < count($val[$chart][$i]); $j++) {
                        $graph .= "<td bgcolor=$lc[0]><font face=\"Verdana\" size=\"-2\">" . $val[$chart][$i][$j] . '</font></td>';
                    }
                }
            }
            $graph .= '</tr><tr align=center>';
            for($i = 0; $i < count($val[$chart]); $i++, $lcnt++) {
                $label = ($lcnt < count($r)) ? trim($r[$lcnt]) : $lcnt+1;
                $graph .= '<td colspan=' . count($val[$chart][$i]) . " bgcolor=$lc[0]><font face=\"Verdana\" size=\"-2\">$label</font></td>";
            }
            $graph .= '</tr>';
        }
        else if($type == 'pbar') {
            for($i = 0; $i < count($val[$chart]); $i++, $lcnt++) {
                $label = ($lcnt < count($r)) ? trim($r[$lcnt]) : '';
                $graph .= '<tr><td align=right>' . $label . '</td>';

                $sum = $val[$chart][$i][1];
                $percent = $sum ? round($val[$chart][$i][0] * 100 / $sum) : 0;
                if($showVal) $graph .= '<td bgcolor=' . $lc[0] . ' align=right>' . $val[$chart][$i][0] . ' / ' . $sum . '</td>';

                $graph .= "<td width=200 bgcolor=$lc[0]>";
                $graph .= '<table border=0 cellspacing=0 cellpadding=0><tr>';

                if($percent) {
                    $bColor = $drf[$i] ? trim($drf[$i]) : $colors[0];
                    $graph .= '<td bgcolor=' . $bColor . ' width=' . round($percent * 2) . '>&nbsp;</td>';
                }
                $graph .= '</tr></table></td>';
                //          $graph .= "<td>&nbsp;$percent%</td>";
                $graph .= "<td></td>";
                $graph .= '</tr>';
            }
        }
        $graph .= '</table>';

        if($chart < $graphcnt - 1 && count($val[$chart+1])) {
            $graph .= '</td>';
            if($type == 'vbar') $graph .= '</tr><tr valign=top>';
            $graph .= '<td>';
        }
    }

    if($graphcnt > 1) $graph .= '</td></tr></table>';

    if($legend && $type != 'pbar') {
        $graph .= '</td><td width=10>&nbsp;</td><td>';
        $graph .= '<table border=0 cellspacing=0 cellpadding=1><tr><td bgcolor=#808080>';
        $graph .= '<table border=0 cellspacing=0 cellpadding=0><tr><td bgcolor=#F0F0F0>';
        $graph .= '<table border=0 cellspacing=4 cellpadding=0>';
        $l = (is_array($legend)) ? $legend : explode(',', $legend);
        for($i = 0; $i < count($bc); $i++) {
            $graph .= '<tr>';
            $graph .= '<td bgcolor=' . $bc[$i] . ' nowrap>&nbsp;&nbsp;&nbsp;</td>';
            $graph .= '<td nowrap>' . trim($l[$i]) . '</td>';
            $graph .= '</tr>';
        }
        $graph .= '</table></td></tr></table></td></tr></table>';
        $graph .= '</td></tr></table>';
    }
    return $graph;
}
?>
