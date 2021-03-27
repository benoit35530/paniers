<?php

wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-style', plugins_url() . '/paniers/html/styles/jquery-ui-1.9.2.custom.min.css');

echo "<html>\n";
echo "<head>\n";
echo "<title>Administration</title>\n";
echo "<link rel=\"stylesheet\" href=\"../styles/styles_admin.css\" type=\"text/css\">\n";
wp_print_scripts();
wp_print_styles();
echo "<script type=\"text/javascript\">
jQuery(document).ready(function() {
    jQuery('.datepicker').datepicker({
        dateFormat : 'dd/mm/yy'
    });
});
</script>";
echo "</head>\n";
echo "<body>\n";
echo html_debut_tableau("100%");
echo html_debut_ligne();
echo html_colonne("100%","","center","","","","","<h2>Administration</h2>","","banniere");
echo html_fin_ligne();
echo html_fin_tableau();
?>