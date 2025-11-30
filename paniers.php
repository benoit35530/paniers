<?php
/*
 Plugin Name: Gestion de Paniers
 Description: Gestion des commandes de vente directe producteurs/consommateurs
 Version: 0.9
 Author: Benoit
 Author URI:
 */

define('paniers_plugin_dir', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) );
define('paniers_plugin_url', plugins_url('.', __FILE__));
define('paniers_dir', paniers_plugin_dir . '/html');

require_once(paniers_dir . "/include/dbconnect.php");
require_once(paniers_dir . "/include/parametres.php");
require_once(paniers_dir . "/options.php");
require_once(paniers_dir . "/compte.php");
require_once(paniers_dir . "/commandes.php");
require_once(paniers_dir . "/livraisons.php");
require_once(paniers_dir . "/produits.php");
require_once(paniers_dir . "/permanences.php");

function paniers_install()
{
    global $paniers_dbprefix;

    $schema = array("CREATE TABLE " . $paniers_dbprefix . "paniers_absences (
                    iddate int(11) NOT NULL,
                    idproducteur int(11) NOT NULL,
                    PRIMARY KEY  (iddate,idproducteur)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_bons_cde (
                    id int(11) NOT NULL auto_increment,
                    idboncde text NOT NULL,
                    idperiode int(11) NOT NULL,
                    idclient int(11) NOT NULL,
                    iddepot int(11) NOT NULL,
                    etat enum('encours','valide') NOT NULL default 'encours',
                    datemodif datetime NOT NULL,
                    PRIMARY KEY  (idperiode,idclient),
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_commandes (
                    id int(11) NOT NULL auto_increment,
                    idboncommande int(11) NOT NULL,
                    idclient int(11) NOT NULL,
                    idperiode int(11) NOT NULL,
                    idproducteur int(11) NOT NULL,
                    idproduit int(11) NOT NULL,
                    iddatelivraison int(11) NOT NULL,
                    quantite int(11) NOT NULL,
                    prix float NOT NULL default '0',
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_avoirs (
                    id int(11) NOT NULL auto_increment,
                    idboncommande int(11) NOT NULL,
                    idclient int(11) NOT NULL,
                    idproducteur int(11) NOT NULL,
                    montant float NOT NULL default '0',
                    description text NOT NULL,
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_dates (
                    id int(11) NOT NULL auto_increment,
                    idperiode int(11) NOT NULL,
                    datelivraison date NOT NULL,
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_journal (
                    id int(11) NOT NULL auto_increment,
                    date datetime NOT NULL default '0000-00-00 00:00:00',
                    auteur text NOT NULL,
                    commentaire text NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_periodes (
                    id int(11) NOT NULL auto_increment,
                    libelle text NOT NULL,
                    etat enum('Preparation','Active','Close') NOT NULL default 'Close',
                    datedebut date NOT NULL default '0000-00-00',
                    datefin date NOT NULL default '0000-00-00',
                    datecommande date NOT NULL default '0000-00-00',
                    relancemail enum('oui','non') NOT NULL default 'non',
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_permanences (
                    id int(11) NOT NULL auto_increment,
                    date date NOT NULL,
                    heuredebut time NOT NULL,
                    heurefin time NOT NULL,
                    nbparticipants int(11) NOT NULL,
                    nbinscrits int(11) NOT NULL default '0',
                    typepermanence enum('reception','livraison','prisecommande','miseensachets') NOT NULL,
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_permanenciers (
                    id int(11) NOT NULL auto_increment,
                    idpermanence int(11) NOT NULL,
                    idclient int(11) NOT NULL,
                    commentaire text NOT NULL,
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_producteurs (
                    id int(11) NOT NULL auto_increment,
                    nom text NOT NULL,
                    email text NOT NULL,
                    envoyerrecap bool NOT NULL,
                    telephone text NOT NULL,
                    paiement text,
                    produits text NOT NULL,
                    datemodif datetime NOT NULL,
                    etat enum('Actif','Inactif') NOT NULL default 'Actif',
                    ordre int(11) NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_depots (
                    id int(11) NOT NULL auto_increment,
                    nom text NOT NULL,
                    adresse text NOT NULL,
                    telephone text NOT NULL,
                    email text NOT NULL,
                    etat enum('Actif','Inactif') NOT NULL default 'Actif',
                    datemodif datetime NOT NULL,
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_produits (
                    id int(11) NOT NULL auto_increment,
                    nom text NOT NULL,
                    description text NOT NULL,
                    prix float NOT NULL,
                    idproducteur int(11) NOT NULL,
                    image int(11),
                    datemodif datetime NOT NULL,
                    etat enum('Inactif','Actif') NOT NULL default 'Actif',
                    KEY id (id)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_baseutils (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    nomutil varchar(16) NOT NULL,
                    motpasse text NOT NULL,
                    nom text NOT NULL,
                    prenom text NOT NULL,
                    derncnx datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    email text NOT NULL,
                    fonctions set('clients','commandes','dates','producteurs','produits','utilisateurs','journal','periodes','actualites','permanences','permanenciers','exports','depots','avoirs','stats') NOT NULL,
                    date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    iddepot int(11) NOT NULL DEFAULT '-1',
                    idproducteur int(11) NOT NULL DEFAULT '-1',
                    KEY id (id),
                    KEY nomutil (nomutil)
                    );",
               "CREATE TABLE " . $paniers_dbprefix . "paniers_clients (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    codeclient varchar(16) NOT NULL,
                    motpasse text NOT NULL,
                    nom text NOT NULL,
                    prenom text NOT NULL,
                    email text NOT NULL,
                    telephone text NOT NULL,
                    ville text NOT NULL,
                    iddepot int(11) NOT NULL DEFAULT '0',
                    etat enum('Inactif','Actif') NOT NULL DEFAULT 'Inactif',
                    derncnx datetime NOT NULL,
                    cotisation float NOT NULL DEFAULT '0',
                    datemodif datetime NOT NULL,
                    KEY id (id),
                    KEY codeclient (codeclient)
                    );"
                    );

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($schema);

    add_option("paniers_db_version", "1.0");

    $paniers_data = get_option('paniers_data');
    if(!$paniers_data || $paniers_data == "") {
        $paniers_data["pageconsommateurs"] = "/adherents";
        $paniers_data["pagegestionnaires"] = "/administrateurs";
        $paniers_data["pageinscription"] = "";
        $paniers_data["adressegestionnaires"] = get_option("admin_email");
        $paniers_data["villes"] = "Ville1;Ville2;etc";
        $paniers_data["deltaverrouillage"] = "1";
        $paniers_data["envoyerrelance"] = "0";
        $paniers_data["adresserelance"] = "";
        $paniers_data["deltarelance"] = "3";
        $paniers_data["commandeverouille"] = False;
        $paniers_data["commandenondisponible"] = "Le bon de commande n'est pas encore disponible, merci de revenir dans quelques jours.";
        $paniers_data["periodicite"] = "mensuel";
        $paniers_data["jourcommande"] = "Wednesday";
        $paniers_data["permanences"] = "reception,Réception des produits;
livraison,Livraison des produits,18:30,19:30,1,1;
prisecommande,Prise de commandes,18:30,19:30,1,0;
misensachets,Mise en sachets des pommes,14:00,14:30,2,0";
        $paniers_data["smtpuser"] = "";
        $paniers_data["smtppassword"] = "";
        $paniers_data["smtpserver"] = "";

        foreach($paniers_data as $key => $value) {
            if(substr($key, 0, 8) == 'paniers_') {
                if($value != '') {
                    $paniers_data[substr($key, 8)] = stripslashes($value);
                }
            }
        }
        update_option('paniers_data', $paniers_data);
    }

    paniers_rewriteURL();
    flush_rewrite_rules();
}

function paniers_uninstall() {
    flush_rewrite_rules();
}

function paniers_rewriteURL() {
    add_rewrite_rule('paniers/(.*)$', substr(paniers_dir, 1) . '/$1','top');
}

function paniers_queryvars($qvars) {
    $qvars[] = 'action';
    $qvars[] = 'id';
    $qvars[] = 'idperiode';
    $qvars[] = 'iddepot';
    $qvars[] = 'idclient';
    $qvars[] = 'iddate';
    $qvars[] = 'filtre_etat';
    $qvars[] = 'etat';
    $qvars[] = 'filtre_depot';
    $qvars[] = 'tri';
    $qvars[] = 'export';
    return $qvars;
}

function paniers_add_plugin_stylesheet() {
    wp_register_style('paniers_stylesheet', paniers_plugin_url . '/paniers.css');
    wp_enqueue_style('paniers_stylesheet');
}

function paniers_media_library_script($mediaId) {
    return <<<HTML
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        var mediaBox = $('#mediabox-$mediaId');
        var uploadLink = mediaBox.find(".uploadLink");
        var changeLink = mediaBox.find(".changeLink");
        var imageContainer = mediaBox.find(".imageContainer");
        var imageInput = mediaBox.find(".imageInput");
        var frame;

        function openFrame() {
            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Télécharger un image',
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                imageContainer.html('');
                imageContainer.append('<img src="'+ attachment.url +'" alt="" style="max-width:150px; max-height:150px;"/>');
                imageInput.val(attachment.id);
                uploadLink.addClass('hidden');
                changeLink.removeClass('hidden');
            });

            frame.open();
        }

        uploadLink.on('click', function(event) {
            event.preventDefault();
            openFrame();
        });

        changeLink.on('click', function(event){
            event.preventDefault();
            uploadLink.removeClass('hidden');
            changeLink.addClass('hidden');
            openFrame();
        });
    });
    </script>
    HTML;
}

register_activation_hook(__FILE__,'paniers_install');
register_deactivation_hook(__FILE__,'paniers_uninstall');

add_action('admin_menu', 'paniers_plugin_menu' );
add_action('wp_print_styles', 'paniers_add_plugin_stylesheet');
add_action('init', 'paniers_rewriteURL');
add_filter('query_vars', 'paniers_queryvars' );


