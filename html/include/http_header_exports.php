<?php
if (isset($export) && $export == "excel")
{
    $filename = "export_" . gmdate("Ymd_His") . ".xls";
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
}
<<<<<<< HEAD
elseif (!isset($export) || $export != "pdf")
=======
elseif (isset($export) && $export == "pdf")
{
    ob_clean();
    flush();
    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=export_" . $action . "_" . gmdate("md") . ".pdf");
    header("Content-Transfer-Encoding: binary");
    header("Accept-Ranges: bytes");
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
}
else
>>>>>>> origin/main
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
}
?>