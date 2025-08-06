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
require_once(paniers_dir . "/hooks.php");
require_once(paniers_dir . "/options.php");

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
        $paniers_data["pageconnextion"] = "";
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

function paniers_checkIfLoggedIn() {
    global $url_page_connexion;
    if (is_user_logged_in())
    {
        $userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);
        if ($userid > 0) {
            return $userid;
        }
    }
    $loginurl = $url_page_connexion == "" ? wp_login_url($_SERVER['REQUEST_URI']) : $url_page_connexion;
    wp_redirect($loginurl);
    exit;
}

function paniers_check_login($user, $username, $password) {
    require_once(paniers_dir . "/include/fonctions/fonctions_communes.php");

    global $base_utilisateurs, $base_clients;
    if(!$user && $username == "") {
        return $user;
    }

    if ($user) {
        ecrire_log_public("Identification de " . $user->ID . " " . $user->get('user_firstname'));
    } else {
        ecrire_log_public("Identification de $username");
    }

    $mysql_password = encode_password($password);

    //
    // Est ce un administrateur des paniers?
    //
    $authenticated = false;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select email,motpasse from $base_utilisateurs where nomutil='$username' or email=lower('$username') limit 1");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($email,$motpasse) = mysqli_fetch_row($rep);
        if($motpasse == $mysql_password) {
            $authenticated = true;
        }
        else {
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            ecrire_log_public("Echec de connection de $username / $email: mot de passe invalide");
            $user =  new WP_Error('invalidpassword', __("Mot de passe invalide"));
            return $user;
        }
    }

    //
    // Est-ce un consommateur des paniers?
    //
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,email,motpasse,etat from $base_clients where lower(codeclient)=lower('$username') or email=lower('$username')");
    if($rep && mysqli_num_rows($rep) != 0) {
        // Fetch all the rows until we find an active consummers. This is required in case multiple consummers
        // were registered with different addresses.
        while (list($client_id,$email,$motpasse,$etat) = mysqli_fetch_row($rep)) {
            if ($etat == "Actif") {
                break;
            }
        }
        if($etat != "Actif") {
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            ecrire_log_public("Echec de connection de $username / $email: identifiant inactif");
            $user =  new WP_Error('nouserid', __("Identifiant inactif"));
            return $user;
        }

        if(empty($motpasse)) {
            $user = get_user_by('login', $username);
            if($user) {
                $authenticated = wp_check_password($password, $user->user_pass);
                if($authenticated) {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set motpasse='" . encode_password($password) .
                                       "' where id='" . $client_id . "'");
                }
            }
        } else if($motpasse == $mysql_password) {
            $authenticated = true;
        }

        if(!$authenticated) {
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            ecrire_log_public("Echec de connection de $username / $email: mot de passe invalide");
            $user =  new WP_Error('invalidpassword', __("Mot de passe invalide"));
            return $user;
        }
    }

    // If not authenticated, let other filters authenticate the user.
    if(!$authenticated) {
        return null;
    }

    $codeclient = '';

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, nom, prenom, email from $base_utilisateurs where email='$email' limit 1");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($gestionnaireId,$nom, $prenom, $email) = mysqli_fetch_row($rep);
    }

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, nom, prenom, email, codeclient from $base_clients where email='$email' and etat='Actif' limit 1");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($consommateurId,$nom, $prenom, $email, $codeclient) = mysqli_fetch_row($rep);
    }

    $userarray['first_name'] = $prenom;
    $userarray['last_name'] = $nom;
    if ($codeclient == '') {
        $userarray['user_login'] = $username;
    } else {
        $userarray['user_login'] = strtoupper($codeclient);
    }
    $userarray['user_pass'] = $password;
    $userarray['display_name'] = "$prenom $nom";
    $userarray['user_email'] = $email;

    $user = get_user_by('login', $username);
    if(!$user) {
        $user = get_user_by('email', $email);
    }
    if(!$user) {
        $user = get_user_by('email', $username);
    }
    if(!$user) {
        $id = wp_insert_user($userarray);
        if(is_wp_error($id)) {
            ecrire_log_public("Echec de connection de $username / $email: " . $id->get_error_message());
            remove_action('authenticate', 'wp_authenticate_username_password', 20);
            return $id;
        }
        $user = new WP_User($id);
        add_user_meta($id, 'show_admin_bar_front', false, true);
        if(isset($gestionnaireId)) {
            $user->set_role("editor");
        }
    } else {
        $id = $user->ID;
    }

    if(isset($consommateurId) && (!$user->has_cap("consommateur") ||
                                  !get_user_meta(get_current_user_id(), 'paniers_consommateurId', true))) {
        $user->add_cap("consommateur");
        add_user_meta($id, 'paniers_consommateurId', $consommateurId, true);
    }
    if(isset($gestionnaireId) && (!$user->has_cap("gestionnaire") ||
                                  !get_user_meta(get_current_user_id(), 'paniers_gestionnaireId', true))) {
        $user->add_cap("gestionnaire");
        add_user_meta($id, 'paniers_gestionnaireId', $gestionnaireId, true);
    }
    if(isset($consommateurId)) {
        ecrire_log_public("Connexion client n° $consommateurId - $prenom $nom");
        mysqli_query($GLOBALS["___mysqli_ston"],
                     "update $base_clients set derncnx=now() where id=$consommateurId");
    }
    if(isset($gestionnaireId)) {
        ecrire_log_admin("Connexion administrateur n° $gestionnaireId - $prenom $nom");
        mysqli_query($GLOBALS["___mysqli_ston"],
                     "update $base_utilisateurs set derncnx=now() where id=$gestionnaireId");
    }
    return $user;
}

function paniers_add_plugin_stylesheet() {
    wp_register_style('paniers_stylesheet', paniers_plugin_url . '/paniers.css');
    wp_enqueue_style('paniers_stylesheet');
    if(!str_starts_with($_SERVER['REQUEST_URI'], "/paniers/admin")) {
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    }
}

function paniers_add_plugin_scripts() {
    if(!str_starts_with($_SERVER['REQUEST_URI'], "/paniers/admin")) {
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    }
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
add_action('wp_enqueue_scripts', 'paniers_add_plugin_scripts');
add_action('init', 'paniers_rewriteURL');
add_action('password_reset', 'paniers_password_reset', 10, 2);
add_filter('query_vars', 'paniers_queryvars' );
add_filter('authenticate', 'paniers_check_login', 10, 3);

add_action('get_header', function () {
    global $url_page_consommateur, $url_page_gestionnaire;
    if (str_starts_with($_SERVER['REQUEST_URI'], $url_page_consommateur) ||
        str_starts_with($_SERVER['REQUEST_URI'], $url_page_gestionnaire)) {
        paniers_checkIfLoggedIn();
    }
});

add_shortcode('paniers-updateprofile', function() {
    require_once(paniers_dir . "/include/fonctions/fonctions_communes.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_depots.php");
    require_once(paniers_dir . "/common.php");
    require_once(ABSPATH . "wp-admin/includes/user.php");

    global $base_utilisateurs, $base_clients, $tab_villes_clients;

    $user = wp_get_current_user();
    if ($user->ID == 0) {
        echo "Vous n'êtes pas connecté.";
        return;
    }

    $userdata = $user->data;
    $username = $userdata->user_login;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,prenom,email from $base_utilisateurs where nomutil='" . $username . "' limit 1");
    list($nom, $prenom, $email, $ville) = '';
    $admin_id = 0;
    if($rep && mysqli_num_rows($rep) != 0) {
        list($admin_id,$nom,$prenom,$email) = mysqli_fetch_row($rep);
    }

    $client_id = 0;
    if($email != '') {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,prenom,ville,telephone,iddepot from $base_clients where email = '$email' and etat='Actif' limit 1");
        if($rep && mysqli_num_rows($rep) != 0) {
            list($client_id, $nom,$prenom, $ville, $telephone,$iddepot) = mysqli_fetch_row($rep);
        }
    } else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,nom,prenom,ville,telephone,email,iddepot from $base_clients where lower(codeclient)=lower('$username') and etat='Actif' limit 1");
        if($rep && mysqli_num_rows($rep) != 0)
        {
            list($client_id, $nom,$prenom,$ville, $telephone, $email,$iddepot) = mysqli_fetch_row($rep);
        }
    }

    $update = false;
    $updateerror = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['action']) && $_POST['action'] == 'updateprofile' &&
        wp_verify_nonce($_POST['edit_nonce_field'], 'verify_edit_user') ) {
        $update = true;
        $nom = $_POST['last_name'];
        $prenom = $_POST['first_name'];
        $email = $_POST['email'];
        $ville = $_POST['ville'];
        $telephone = $_POST['telephone'];
        $iddepot = $_POST['iddepot'];

        $motdepasse = $_POST['mot_de_passe'];
        $confmotdepasse = $_POST['conf_mot_de_passe'];
        $updatemotdepasse = '';
        if($motdepasse != '') {
            if($motdepasse != $confmotdepasse) {
                ob_start();
                echo "Le mot de passe saisi est invalide: le mot de passe de confirmation n'est pas identique au mot de passe saisi.";
                echo "<br><br><a href=\"";
                the_permalink();
                echo "\">Retour au formulaire</a>";
                return apply_filters ('wppb_edit_profile', ob_get_clean());;
            }
            $updatemotdepasse = ", motpasse='" . encode_password($motdepasse) . "'";
        }

        if($updateerror == '') {
            if($client_id != 0) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set nom='$nom',prenom='$prenom',email='$email',telephone='$telephone',ville='$ville',iddepot='$iddepot',datemodif=now() $updatemotdepasse where id='" . $client_id . "'");
            }

            if($admin_id != 0) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_utilisateurs set email='$email',date=now() $updatemotdepasse where id='" . $admin_id . "'");
            }

            $userarray['ID'] = $user->ID;
            $userarray['first_name'] = $prenom;
            $userarray['last_name'] = $nom;
            $userarray['user_email'] = $email;
            wp_update_user($userarray);
        }
    }

    ob_start();

    echo "<div class=\"paniers_holder\" id=\"paniers_updateprofile\">";

    if($update) {
        echo afficher_info("", "Votre profil a été mis à jour avec succès");
    }

    echo "<form method=\"post\" id=\"updateprofile\" action=\"";
    the_permalink();
    echo  "\">";

    echo "<p class=\"last_name\">";
    echo "   <label for=\"last_name\">Nom</label>";
    echo "   <input class=\"text-input\" name=\"last_name\" type=\"text\" id=\"last_name\" value=\"". $nom . "\" />";
    echo "</p>";

    echo "<p class=\"first_name\">";
    echo "   <label for=\"first_name\">Prénom</label>";
    echo "   <input class=\"text-input\" name=\"first_name\" type=\"text\" id=\"first_name\" value=\"". $prenom . "\" />";
    echo "</p>";

    echo "<p class=\"email\">";
    echo "   <label for=\"email\">Email</label>";
    echo "   <input class=\"text-input\" name=\"email\" type=\"text\" id=\"email\" value=\"" . $email .  "\" />";
    echo "</p>";

    if($client_id != 0) {
        echo "<p class=\"ville\">";
        echo "   <label for=\"ville\">Ville</label>";
        echo "   <select name=\"ville\" id=\"ville\">";
        foreach($tab_villes_clients as $key => $value) {
            if($value != $ville) {
                $selected = '';
            } else {
                $selected = ' selected=1';
            }
            echo "	   <option id=\"" . $value . "\" value=\"". $value . "\"$selected>". $value . "</option>";
        }
        echo "	</select>";
        echo "</p>";

        echo "<p class=\"telephone\">";
        echo "   <label for=\"telephone\">Telephone</label>";
        echo "   <input class=\"text-input\" name=\"telephone\" type=\"text\" id=\"telephone\" value=\"". $telephone . "\" />";
        echo "</p>";

        echo "<p class=\"iddepot\">";
        echo "   <label for=\"iddepot\">Dépôt</label>";
        echo afficher_liste_depots("iddepot", $iddepot);
        echo "</p>";
    }

    echo "<p class=\"mot_de_passe\">";
    echo "   <label for=\"mot_de_passe\">Nouveau mot de passe</label>";
    echo "   <input class=\"text-input\" name=\"mot_de_passe\" type=\"password\" id=\"mot_de_passe\" value=\"\" />";
    echo "</p>";

    echo "<p class=\"conf_mot_de_passe\">";
    echo "   <label for=\"conf_mot_de_passe\">Confirmation mot de passe</label>";
    echo "   <input class=\"text-input\" name=\"conf_mot_de_passe\" type=\"password\" id=\"conf_mot_de_passe\" value=\"\" />";
    echo "</p>";

    echo "<p>";
    echo "<input name=\"updateprofile\" type=\"submit\" id=\"updateprofile\" class=\"submit button\" value=\"Sauvegarder\" />";
    echo "<input name=\"action\" type=\"hidden\" id=\"action\" value=\"updateprofile\" />";
    echo "<input name=\"client_id\" type=\"hidden\" id=\"client_id\" value=\"$client_id\" />";
    echo "<input name=\"admin_id\" type=\"hidden\" id=\"admin_id\" value=\"$admin_id\" />";
    wp_nonce_field('verify_edit_user','edit_nonce_field');
    echo "</form>";
    echo "</div>";
    $output = ob_get_contents();
    ob_end_clean();
    $output = apply_filters('wppb_edit_profile', $output);
    return $output;
});

add_shortcode('paniers-date-commande', function() {
    require_once(paniers_dir . "/include/fonctions/fonctions_generales.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_periodes.php");
    $txt = afficher_date_prochaine_commande();
    if($txt == "") {
        return "&lt;la date n'est pas encore connue&gt;";
    } else {
        return datelitterale($txt, true);
    }
});

add_shortcode('paniers-produits-producteur', function($atts) {
    extract(shortcode_atts(array('idproducteur' => '0'), $atts));

    require_once(paniers_dir . "/include/fonctions/fonctions_produits.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_producteurs.php");
    $producteur = retrouver_producteur_info($idproducteur);
    if(!$producteur) {
        return;
    }

    $produits = liste_produits($idproducteur);
    $chaine = "<div class=\"container-fluid\">";
    $i = 0;
    $placeholder = paniers_plugin_url . '/placeholder.png';
    foreach($produits as $nom => list($prix, $image, $description)) {
        if($image != 0) {
            $image = wp_get_attachment_image_src($image, array(300, 300))[0];
        } else {
            $image = $placeholder;
        }

        if ($i == 0) {
            $chaine .= "<div class=\"row\">";
        }
        $chaine .= <<<HTML
        <div class="col-sm p-4">
            <div class="container-fluid">
                <div class="row"><div class="col"><img src="$image"/></div></div>
                <div class="row"><div class="col"><center><b>$nom</b></center></div></div>
                <div class="row"><div class="col"><center>$prix €</center></div></div>
                <div class="row"><div class="col"><center><i>$description</i></center></div></div>
            </div>
        </div>
        HTML;

        $i++;
        if ($i == 4) {
            $chaine .= "</div>";
            $i = 0;
        }
    }
    if ($i < 4) {
        for (; $i < 4; $i++) {
            $chaine .= "<div class=\"col\"></div>";
        }
        $chaine .= "</div>";
    }
    $chaine .= "</div>";
    return $chaine;
});

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

add_shortcode('paniers-livraisons',  function () {
    $userid = paniers_checkIfLoggedIn();
    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/commandes.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    global $wp_query;
    $iddate = $wp_query->get("iddate");
    $idclient = $wp_query->get("idclient");

    return afficher_recapitulatif_livraisons_frontend($idclient == 0 ? $userid : $idclient, $iddate);
});

add_shortcode('paniers-commande-adherent', function($atts) {
    $userid = paniers_checkIfLoggedIn();

    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/commandes.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    extract(shortcode_atts(array(
        'page_commande_non_disponible' => '',
        'page_commande_verrouille' => ''
    ), $atts));

    global $wp_query;

    $action = $wp_query->get("action");
    $id = $wp_query->get("id");
    $idperiode = $wp_query->get("idperiode");

    if ($action == "editercde" || ($action=="" && !str_starts_with($_SERVER['REQUEST_URI'], "/wp-admin"))) {
        $idperiode = retrouver_periode_courante(true);
        if ($idperiode == 0) {
            wp_redirect($page_commande_non_disponible);
            exit;
        } else if ($idperiode == -1) {
            wp_redirect($page_commande_verouille == "" ? $page_commande_non_disponible : $page_commande_verouille);
        }

        if (!isset($id) || $id == "" || $id == 0) {
            $id = retrouver_commande($userid, $idperiode);
        }

        if ($id == 0) {
            $qteproduit = array();
        } else {
            $qteproduit = retrouver_quantites_commande($id);
        }

        return afficher_info(
            afficher_periode($idperiode),
            "",
            afficher_formulaire_bon_commande_frontend(
                $id,
                $userid,
                $idperiode,
                $qteproduit,
                "enregistrercde"));
    } else if ($action == "affichercde" && $id != "" && $id != 0) {
        $idperiode = retrouver_periode_commande_client($id, $userid);
        if ($idperiode == 0) {
            return afficher_erreur("Commande introuvable");
        }

        return afficher_info(
            afficher_periode($idperiode),
            "",
            afficher_recapitulatif_bon_commande_frontend($id, $idperiode));
    } else if ($action == "enregistrercde") {
        $iddepot = $_POST["iddepot"];
        $qteproduit = $_POST['qteproduit'];
        if (!isset($idperiode) || $idperiode == "" || $idperiode == 0) {
            return afficher_erreur("Pas de période définie");
        }
        else if (!isset($iddepot) || $iddepot == "" || $iddepot == 0) {
            return afficher_erreur("Pas de dépot selectionné");
        }
        else if (isset($idclient) && $idclient != $userid) {
            return afficher_erreur("Identifiant client invalide");
        }

        $total = 0.0;
        mysqli_begin_transaction($GLOBALS["___mysqli_ston"], MYSQLI_TRANS_START_READ_WRITE);
        try {
            $nouvellecommande = false;
            if (!isset($id) || $id == "" || $id == 0) {
                $id = enregistrer_bon_commande($idperiode, $userid, $iddepot);
                if($id == 0) {
                    mysqli_rollback($GLOBALS["___mysqli_ston"]);
                    return afficher_erreur("La commande est déjà enregistrée");
                }
                $nouvellecommande = true;
            }

            $total = enregistrer_commande($idperiode, $qteproduit, $id, $userid);

            if ($total == 0.0) {
                if ($nouvellecommande) {
                    mysqli_rollback($GLOBALS["___mysqli_ston"]);
                    return afficher_info("Commande non sauvegardée", "Votre commande étant vide, elle n'a pas été enregistrée");
                } else {
                    $boncde = supprimer_bon_commande($id);
                    ecrire_log_public("Commande supprimé sous le n° $boncde");
                    mysqli_commit($GLOBALS["___mysqli_ston"]);
                    return afficher_info("Commande n° $boncde supprimée", "Votre commande étant vide, elle a été supprimée");
                }
            }

            mysqli_commit($GLOBALS["___mysqli_ston"]);
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS["___mysqli_ston"]);
            return afficher_erreur($e->getMessage());;
        }

        $boncde = "C$userid-$id";
        ecrire_log_public("Commande n° $boncde entregistrée");
        $vars = array(
            "%PERIODE%" => afficher_periode($idperiode),
            "%DATECOMMANDE%" => datelitterale(afficher_date_prochaine_commande())
        );
        return afficher_info(
            "Commande n° $boncde entregistrée",
            message_courrier("messagesauvegardecommande", $vars),
            afficher_recapitulatif_bon_commande_frontend($id, $idperiode));
    } else {
        return afficher_erreur("Action invalide");
    }
});

add_shortcode('paniers-liste-commandes-adherent', function() {
    $userid = paniers_checkIfLoggedIn();

    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/commandes.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    extract( shortcode_atts( array(
		'page_commande' => '/commande/',
    ), $atts ) );

    return afficher_liste_bon_commandes_frontend($userid, $page_commande);
});

add_shortcode('paniers-login-form', function() {
    return wp_login_form(array(
        'echo' => false,
        'value_remember' => true,
        'required_username' => true,
        'required_password' => true,
        'redirect' => home_url()
    ));
});

add_shortcode('paniers-register-form', function() {
    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/common.php");

    global $wp_query;
    $action = $wp_query->get("action");
    if ($action == "register") {
        $user = paniers_insertclient();
        if (!is_wp_error($user)) {
            global $base_clients;
            $telephone = $_POST["telephone"];
            $ville = $_POST["ville"];
            $iddepot = $_POST["depot"];
            $etat = 'Actif';
            $cotisation = 0;
            mysqli_query(
                $GLOBALS["___mysqli_ston"],
                "insert into $base_clients (codeclient,motpasse,nom,prenom,email,telephone,ville,iddepot,etat,derncnx,datemodif,cotisation) values ('$user->user_login','" . encode_password($user->user_pass) . "','$user->last_name','$user->first_name','$user->user_email','$telephone','$ville','$iddepot','$etat',now(),now(),'$cotisation')");
            $last_id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
            add_user_meta($user->ID, 'paniers_consommateurId', $last_id, true);

            return afficher_info(
                "Succès de votre inscription",
                "Vous allez recevoir un message pour confirmer votre inscription et créer un mot de passe");
        }
        $error = $user;
    } else {
        $error = new WP_Error();
    }

    $email = (isset( $_POST['email'])) ? wp_unslash($_POST['email']) : '';
    $prenom = (isset( $_POST['prenom'])) ? wp_unslash($_POST['prenom']) : '';
    $nom = (isset( $_POST['nom'])) ? wp_unslash($_POST['nom']) : '';
    $telephone = (isset( $_POST['telephone'])) ? wp_unslash($_POST['telephone']) : '';
    $depot = (isset( $_POST['depot'])) ? wp_unslash($_POST['depot']) : '';
    $ville = (isset( $_POST['ville'])) ? wp_unslash($_POST['ville']) : '';
    ob_start();
	if ($error->has_errors()) {
        $error_messages = $error->get_error_messages();
        if (sizeof($error_messages) > 1) {
            $errors = '<ul>';
            foreach ($error_messages as $error_message) {
                $errors .= '<li>' . $error_message . '</li>';
            }
            $errors .= '</ul>';
        }
        else {
            $errors = '<p>' . $error_messages[0] . '</p>';
        }

        wp_admin_notice(
            $errors,
            array(
                'type' => 'error',
                'id' => 'login_error',
                'paragraph_wrap' => false,
            )
        );
	}
    ?>
<form name="registerform" action="?action=register" method="post">
    <p>
        <label for="email"><?php _e( 'Email' ); ?></label>
        <input type="email" name="email" id="email" class="input" value="<?php echo esc_attr($email); ?>" autocomplete="email" required="required"/>
    </p>
    <p>
        <label for="nom"><?php _e('Nom','mydomain') ?></label>
        <input type="text" name="nom" id="nom" class="input" value="<?php echo esc_attr($nom); ?>" required="required"/></label>
    </p>
    <p>
        <label for="prenom"><?php _e('Prénom','mydomain') ?></label>
        <input type="text" name="prenom" id="prenom" class="input" value="<?php echo esc_attr($prenom); ?>" required="required"/></label>
    </p>
    <p>
        <label for="telephone"><?php _e('Télephone','mydomain') ?></label>
        <input type="text" name="telephone" id="telephone" class="input" value="<?php echo esc_attr($telephone); ?>" required="required"/></label>
    </p>
    <p>
        <label for="ville"><?php _e('Ville&nbsp','mydomain') ?></label>
        <?php echo afficher_villes_client("ville", $ville); ?>
    </p>
    <p>
        <label for="depot"><?php _e('Dépôt&nbsp','mydomain') ?></label>
        <?php echo afficher_liste_depots("depot", $depot); ?>
    </p>
    <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Register' ); ?>" />
    </p>
</form>
    <?php
    return ob_get_clean();
});

function paniers_password_reset($user, $password) {
    require_once(paniers_dir . "/include/fonctions/fonctions_communes.php");

    global $base_utilisateurs, $base_clients;

    $user = new WP_User($user->ID);
    if ( $user->ID == 0 ) {
        return;
    }
    $userdata = $user->data;
    $username = $userdata->user_login;

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, email from $base_utilisateurs where nomutil='" . $username . "' limit 1");
    $email = '';
    $admin_id = 0;
    if (mysqli_num_rows($rep) != 0)
    {
        list($admin_id, $email) = mysqli_fetch_row($rep);
    }

    $client_id = 0;
    if($email != '') {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_clients where email = '$email' and etat='Actif' limit 1");
        if (mysqli_num_rows($rep) != 0)
        {
            list($client_id) = mysqli_fetch_row($rep);
        }
    }  else {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_clients where lower(codeclient)=lower('$username') and etat='Actif' limit 1");
        if (mysqli_num_rows($rep) != 0)
        {
            list($client_id) = mysqli_fetch_row($rep);
        }
    }

    $updatemotdepasse = "motpasse='" . encode_password($password) . "'";
    if($client_id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_clients set $updatemotdepasse where id='" . $client_id . "'");
    }
    if($admin_id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_utilisateurs set $updatemotdepasse where id='" . $admin_id . "'");
    }
}
