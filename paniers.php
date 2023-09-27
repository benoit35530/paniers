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
require_once(paniers_dir . "/include/fonctions/fonctions_periodes.php");

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
                    telephone text NOT NULL,
                    ordrecheque text NOT NULL,
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
        $paniers_data["pageconsommateurs"] = "/index.php";
        $paniers_data["pagegestionnaires"] = "/index.php";
        $paniers_data["adressegestionnaires"] = get_option("admin_email");
        $paniers_data["villes"] = "Ville1;Ville2;etc";
        $paniers_data["deltaverrouillage"] = "1";
        $paniers_data["envoyerrelance"] = "0";
        $paniers_data["adresserelance"] = "";
        $paniers_data["deltarelance"] = "3";
        $paniers_data["ordrecheque"] = "";
        $paniers_data["commandeverouille"] = False;
        $paniers_data["commandenondisponible"] = "Le bon de commande n'est pas encore disponible, merci de revenir dans quelques jours.";
        $paniers_data["ordrecheque"] = "";
        $paniers_data["periodicite"] = "mensuel";
        $paniers_data["jourcommande"] = "Wednesday";
        $paniers_data["permanences"] = "reception,Réception des produits;
livraison,Livraison des produits,18:30,19:30,1,1;
prisecommande,Prise de commandes,18:30,19:30,1,0;
misensachets,Mise en sachets des pommes,14:00,14:30,2,0";
        $paniers_data["smtpuser"] = "";
        $paniers_data["smtppassword"] = "";
        $paniers_data["smtpserver"] = "";

        foreach ($paniers_data as $key => $value) {
            if( substr($key, 0, 8) == 'paniers_') {
                if($value != '') {
                    $paniers_data[substr($key, 8)] = stripslashes($value);
                }
            }
        }
        update_option('paniers_data', $paniers_data);
    }

    paniers_rewriteURL();
    flush_rewrite_rules();

    if(!wp_next_scheduled('controler_date_fin_commande_event'))
    {
        wp_schedule_event(time(), 'hourly', 'controler_date_fin_commande_event');
    }
}

function paniers_rewriteURL()
{
    add_rewrite_rule('paniers/(.*)$', paniers_dir . '/$1','top');
}

function paniers_uninstall()
{
    flush_rewrite_rules();
    wp_clear_scheduled_hook('controler_date_fin_commande_event');
}

function paniers_queryvars( $qvars )
{
    $qvars[] = 'action';
    $qvars[] = 'id';
    $qvars[] = 'idperiode';
    $qvars[] = 'iddepot';
    $qvars[] = 'idclient';
    $qvars[] = 'filtre_etat';
    $qvars[] = 'etat';
    $qvars[] = 'filtre_depot';
    $qvars[] = 'tri';
    $qvars[] = 'export';
    return $qvars;
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
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,email,motpasse,etat from $base_clients where lower(codeclient)=lower('$username') or email=lower('$username') limit 1");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($client_id,$email,$motpasse,$etat) = mysqli_fetch_row($rep);
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

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id, nom, prenom, email, codeclient from $base_clients where email='$email' limit 1");
    if($rep && mysqli_num_rows($rep) != 0) {
        list($consommateurId,$nom, $prenom, $email, $codeclient) = mysqli_fetch_row($rep);
    }

    $userarray['first_name'] = $prenom;
    $userarray['last_name'] = $nom;
    if ($codeclient == '') {
        $userarray['user_login'] = upper($username);
    } else {
        $userarray['user_login'] = $codeclient;
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

function paniers_register_form() {
    global $base_clients;
    require_once(paniers_dir . "/include/fonctions/fonctions_depots.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_clients.php");

    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select max(cast(substr(codeclient,2) as unsigned))+1 from $base_clients where 1");
    if($rep) {
        list($codeclient) = mysqli_fetch_row($rep);
    }
    if($codeclient == "") {
        $codeclient = "1";
    }
    $codeclient = "C" . $codeclient;

    $first_name = ( isset( $_POST['first_name'] ) ) ? $_POST['first_name']: '';
    $last_name = ( isset( $_POST['last_name'] ) ) ? $_POST['last_name']: '';
    $telephone = ( isset( $_POST['telephone'] ) ) ? $_POST['telephone']: '';
    $depot = ( isset( $_POST['depot'] ) ) ? $_POST['depot']: '';
    $ville = ( isset( $_POST['ville'] ) ) ? $_POST['ville']: '';
    ?>
    <p>
         <label for="last_name"><?php _e('Nom','mydomain') ?><br />
         <input type="text" name="last_name" id="last_name" class="input" value="<?php echo esc_attr(stripslashes($last_name)); ?>" size="25" /></label>
    </p>
    <p>
         <label for="first_name"><?php _e('Prénom','mydomain') ?><br />
         <input type="text" name="first_name" id="first_name" class="input" value="<?php echo esc_attr(stripslashes($first_name)); ?>" size="25" /></label>
    </p>
    <p>
         <label for="telephone"><?php _e('Télephone','mydomain') ?><br />
         <input type="text" name="telephone" id="telephone" class="input" value="<?php echo esc_attr(stripslashes($telephone)); ?>" size="25" /></label>
    </p>
    <p>
        <label for="ville"><?php _e('Ville','mydomain') ?></label>
        <?php echo afficher_villes_client("ville", $ville); ?>
    </p>
    <p>
        <label for="depot"><?php _e('Dépôt','mydomain') ?></label>
        <?php echo afficher_liste_depots("depot", $depot); ?>
    </p>
   <?php

   $content = ob_get_contents();
   $content = preg_replace('/\<label for="user_login"\>(.*?)\<\/label\>/',
                           'Identifiant: ' . $codeclient . '<br/>',
                           $content);
   $content = preg_replace('/\<input type="text" name="user_login" .* \/\>/',
                           '<input type="hidden" name="user_login" value="' . $codeclient . '"/> ',$content);
   ob_get_clean();
   echo $content;
}

function paniers_registration_errors ($errors, $sanitized_user_login, $user_email) {
    if(empty( $_POST['first_name']))
        $errors->add( 'first_name_error', __('<strong>ERROR</strong>: le prénom est obligatoire.','mydomain') );
    if(empty( $_POST['last_name']))
        $errors->add( 'last_name_error', __('<strong>ERROR</strong>: le nom est obligatoire.','mydomain') );
    if(empty( $_POST['telephone']))
        $errors->add( 'telephone_error', __('<strong>ERROR</strong>: le téléphone est obligatoire.','mydomain') );
    if(empty($_POST['ville']))
        $errors->add( 'ville_error', __('<strong>ERROR</strong>: la ville est obligatoire.','mydomain') );
    if($_POST['depot'] <= 0)
        $errors->add( 'depot_error', __('<strong>ERROR</strong>: le dépôt est obligatoire.','mydomain') );
    return $errors;
}

function paniers_insertclient() {
    require_once(ABSPATH . "wp-admin/includes/user.php");

    if(username_exists($_POST["codeclient"])) {
        return "Ce code client est déja utilisé.";
    }

    if($id = email_exists($_POST["email"])) {
        global $base_utilisateurs;
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_utilisateurs where email='" . $_POST["email"] . "' limit 1");
        if(mysqli_num_rows($rep) != 0) {
            $userarray['first_name'] = $_POST['prenom'];
            $userarray['last_name'] = $_POST['nom'];
            $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
            $userarray['ID'] = $id;
            $id = wp_update_user($userarray);
            if(is_wp_error($id))
            {
                return "La mise à jour a échoué: " . $id->get_error_message();
            }

            add_user_meta($id, 'show_admin_bar_front', false, true);

            $user = new WP_User($id);
            $user->add_cap("consommateur");
        }
        return "";
    }

    $userarray['user_login'] =  $_POST["codeclient"];
    $userarray['user_pass'] = $_POST['motpasse'];
    $userarray['first_name'] = $_POST['prenom'];
    $userarray['last_name'] = $_POST['nom'];
    $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
    $userarray['user_email'] = $_POST["email"];
    $userarray['show_admin_bar_front'] = false;
    $id = wp_insert_user($userarray);
    if(is_wp_error($id))
    {
        return "L'ajout a échoué: " . $id->get_error_message();
    }

    $user = new WP_User($id);
    $user->add_cap("consommateur");
    add_user_meta($id, 'show_admin_bar_front', false, true);

    wp_new_user_notification($id, '', 'both');
    return "";
}

function paniers_updateclient() {
    require_once(ABSPATH . "wp-admin/includes/user.php");

    global $base_clients;
    if(!($id = username_exists($_POST["codeclient"])) && !($id = email_exists($_POST['email'])))
    {
        return paniers_insertclient();
    }

    if(username_exists($_POST["codeclient"])) {
        $userarray['user_login'] =  $_POST["codeclient"];
        $userarray['user_pass'] = $_POST['motpasse'];
    }
    $userarray['first_name'] = $_POST['prenom'];
    $userarray['last_name'] = $_POST['nom'];
    $userarray['user_email'] = $_POST['email'];
    $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
    $userarray['ID'] = $id;
    $id = wp_update_user($userarray);
    if(is_wp_error($id))
    {
        return "La mise à jour a échoué: " . $id->get_error_message();
    }
    return "";
}

function paniers_removeclient($idclient) {
    require_once(ABSPATH . "wp-admin/includes/user.php");
    global $base_clients;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select email from $base_clients where id='" . $idclient . "' limit 1");
    if (mysqli_num_rows($rep) != 0) {
        list($email) = mysqli_fetch_row($rep);
        if($id = email_exists($email)) {
            $user = new WP_User($id);
            if(!$user->has_cap("gestionnaire")) {
                wp_delete_user($id);
            } else {
                $user->remove_cap("consommateur");
            }
        }
    }
}

function paniers_insertadmin() {
    require_once(ABSPATH . "wp-admin/includes/user.php");
    global $base_clients;

    if(username_exists($_POST["nomutil"])) {
        return "Ce code utilisateur est déja utilisé.";
    }

    if($id = email_exists($_POST["email"])) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_clients where email='" . $_POST["email"] . "' limit 1");
        if (mysqli_num_rows($rep) != 0) {
            $userarray['user_login'] =  $_POST["nomutil"];
            $userarray['first_name'] = $_POST['prenom'];
            $userarray['last_name'] = $_POST['nom'];
            $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
            $userarray['ID'] = $id;
            $id = wp_update_user($userarray);
            if(is_wp_error($id))
            {
                return "La mise à jour a échoué: " . $id->get_error_message();
            }

            $user = new WP_User($id);
            $user->set_role("editor");
            $user->add_cap("gestionnaire");
        }
        return "";
    }

    $userarray['user_login'] =  $_POST["nomutil"];
    $userarray['user_pass'] = $_POST['motpasse'];
    $userarray['first_name'] = $_POST['prenom'];
    $userarray['last_name'] = $_POST['nom'];
    $userarray['user_email'] = $_POST["email"];
    $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
    $id = wp_insert_user($userarray);
    if(is_wp_error($id))
    {
        return "L'ajout a échoué: " . $id->get_error_message();
    }

    $user = new WP_User($id);
    $user->set_role("editor");
    $user->add_cap("gestionnaire");

    wp_new_user_notification($id, $_POST['motpasse']);
    return "";
}

function paniers_updateadmin() {
    require_once(ABSPATH . "wp-admin/includes/user.php");

    if(!($id = username_exists($_POST["nomutil"])) && !($id = email_exists($_POST["email"])))
    {
        return "Cet utilisateur est inconnu de wordpress.";
    }

    $userarray['user_login'] =  $_POST["nomutil"];
    $userarray['user_pass'] = $_POST['motpasse'];
    $userarray['user_email'] = $_POST["email"];
    $userarray['first_name'] = $_POST['prenom'];
    $userarray['last_name'] = $_POST['nom'];
    $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
    $userarray['ID'] = $id;
    $id = wp_update_user($userarray);
    if(is_wp_error($id))
    {
        return "La mise à jour a échoué: " . $id->get_error_message();
    }
    return "";
}

function paniers_removeadmin() {
    require_once(ABSPATH . "wp-admin/includes/user.php");
    global $base_utilisateurs;
    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select email from $base_utilisateurs where id='" . $_POST["id"] . "' limit 1");
    if (mysqli_num_rows($rep) != 0) {
        list($email) = mysqli_fetch_row($rep);
        if($id = email_exists($email)) {
            $user = new WP_User($id);
            if(!$user->has_cap("consommateur")) {
                wp_delete_user($id);
            } else {
                $user->remove_cap("gestionnaire");
            }
        }
    }
}

function paniers_updateprofile() {
    require_once(paniers_dir . "/include/fonctions/fonctions_communes.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_depots.php");
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
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty( $_POST['action'] ) && $_POST['action'] == 'updateprofile' &&
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
                $output = ob_get_contents();
                ob_end_clean();
                $output = apply_filters ('wppb_edit_profile', $output);
                return $output;
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
        echo "<b>Votre profil a été mis à jour avec succès.</b><br><br> ";
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
    $output = apply_filters ('wppb_edit_profile', $output);
    return $output;
}

function paniers_datecommande() {
    require_once(paniers_dir . "/include/fonctions/fonctions_generales.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_periodes.php");
    $txt = afficher_date_prochaine_commande();
    if($txt == "") {
        return "&lt;la date n'est pas encore connue&gt;";
    } else {
        return datelitterale($txt, true);
    }
}

function paniers_produits($atts) {
    extract( shortcode_atts( array(
		'idproducteur' => '0',
		'table' => '0'
    ), $atts ) );

    require_once(paniers_dir . "/include/fonctions/fonctions_produits.php");
    require_once(paniers_dir . "/include/fonctions/fonctions_producteurs.php");
    $producteur = retrouver_producteur_info($idproducteur);
    if(!$producteur) {
        return;
    }

    $produits = liste_produits($idproducteur);
    if($table > 0) {
        $txt = "<table>";
        $txt .= "<tr><th colspan=\"2\">" . $producteur["produits"] . ": " . $producteur["nom"] . "</th></tr>";
        foreach($produits as $desc => $prix) {
            $txt .= "<tr><td>". $desc . "</td><td>" . $prix . "&#8364;</td></tr>";
        }
        $txt .= "</table>";
    }
    else {
        $txt = "<ul>";
        foreach($produits as $desc => $prix) {
            $txt .= "<li>". $desc . "</li>";
        }
        $txt .= "</ul>";
    }
    return $txt;
}

function paniers_add_plugin_stylesheet() {
    wp_register_style('paniers_stylesheet', paniers_plugin_url . '/paniers.css');
    wp_enqueue_style( 'paniers_stylesheet');
}

function paniers_password_reset($user, $password) {
    require_once(paniers_dir . "/include/fonctions/fonctions_communes.php");

    global $base_utilisateurs, $base_clients, $tab_villes_clients;

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

function paniers_plugin_menu() {
    add_options_page( 'Paniers Options', 'Paniers', 'manage_options', 'paniers-id', 'paniers_plugin_options' );
}

function paniers_plugin_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    add_option('paniers_data');
    $paniers_data = array();
    if( is_admin() && !empty($_POST['panierssubmitted']) ){
        //Build the array of options here
        foreach ($_POST as $postKey => $postValue){
            if( substr($postKey, 0, 8) == 'paniers_' ){
                //For now, no validation, since this is in admin area.
                $paniers_data[substr($postKey, 8)] = stripslashes($postValue);
            }
        }
        update_option('paniers_data', $paniers_data);
        ?>
<div class="updated">
  <p>
    <strong><?php _e('Changes saved.'); ?> </strong>
  </p>
</div>
<?php
    } else {
	    $paniers_data = get_option('paniers_data');
    }
    ?>
<div class="wrap">
  <h2>Paniers Options</h2>
  <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" class="form-table">
    <!-- Ajoute 2 champs cachés pour savoir comment rediriger l'utilisateur -->
    <table width="90%">
      <tr valign="top">
        <th scope="row"><label for="pageconsommateurs"><?php _e('Page Consommateurs') ?> </label></th>
        <td><input name="paniers_pageconsommateurs" type="text" id="pageconsommateurs"
          value="<?php echo $paniers_data['pageconsommateurs']; ?>" class="regular-text"
        /></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="pagegestionnaires"><?php _e('Page Gestionnaires') ?> </label></th>
        <td><input name="paniers_pagegestionnaires" type="text" id="pagegestionnaires"
          value="<?php echo $paniers_data['pagegestionnaires']; ?>" class="regular-text"
        /></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="adressegestionnaires"><?php _e('Adresse gestionnaires') ?> </label></th>
        <td><input name="paniers_adressegestionnaires" type="text" id="adressegestionnaires"
          value="<?php echo $paniers_data['adressegestionnaires']; ?>" class="regular-text"
        /> <span class="adressegestionnaires"><?php _e("Adresse email des gestionnaires, utilisée pour l'envoie de courriers aux producteurs et dépôts.") ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="smtpserver"><?php _e('Serveur SMTP') ?> </label></th>
        <td><input name="paniers_smtpserver" type="text" id="smtpserver"
          value="<?php echo $paniers_data['smtpserver']; ?>" class="regular-text"
        /> <span class="smtpserver"><?php _e("Serveur SMTP.") ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="smtpuser"><?php _e('Utilisateur SMTP') ?> </label></th>
        <td><input name="paniers_smtpuser" type="text" id="smtpuser"
          value="<?php echo $paniers_data['smtpuser']; ?>" class="regular-text"
        /> <span class="smtpuser"><?php _e("Utilisateur SMTP.") ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="smtppassword"><?php _e('Serveur SMTP') ?> </label></th>
        <td><input name="paniers_smtppassword" type="password" id="smtppassword"
          value="<?php echo $paniers_data['smtppassword']; ?>" class="password"
        /> <span class="smtppassword"><?php _e("Mot de passe SMTP.") ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Villes", 'villes'); ?> </label></td>
        <td><?php
        $villes = $paniers_data['villes'];
        if(empty($villes))
        {
            $villes = __("Ville1;Ville2;Autre", 'villes');
        }
        ?><input name="paniers_villes" type="text" id="villes"
          value="<?php echo $villes; ?>" style="width: 40%;" class="wide"/><span class="villes"><?php _e('Liste des villes des consommateurs (séparés par des points virgules)') ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h3>
            <?php _e("Commandes commandes", 'paniers'); ?>
          </h3>
          <h4>
            <?php _e("Vérouillage des commandes", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="deltaverrouillage"><?php _e('Delta verouillage') ?> </label></th>
        <td><input name="paniers_deltaverrouillage" type="text" id="deltaverrouillage"
          value="<?php echo $paniers_data['deltaverrouillage']; ?>" class="regular-text"
        /> <span class="deltaverrouillage"><?php _e('Le nombre de jours avant la date de commande pour verouiller les commandes.') ?>
        </span></td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Configuration bon de commandes", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="ordrecheque"><?php _e('Ordre du Chèque') ?> </label></th>
        <td><input name="paniers_ordrecheque" type="text" id="ordrecheque"
          value="<?php echo $paniers_data['ordrecheque']; ?>" class="regular-text"
        /> <span class="ordrecheque"><?php _e('L\'ordre pour le chèque consommateur si chèque unique. Laissez ce champ vide si les consommateurs règlent chaque producteur par chèque (l\'ordre pour chaque producteur sera indiqué sur le bon de commande).') ?>
        </span></td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Permanences", 'permanences'); ?> </label></td>
        <td><textarea name="paniers_permanences" type="text" id="permanences" class="wide"
           style="width: 40%; height: 100px;"><?php echo $paniers_data['permanences']; ?></textarea><span class="permanences"><?php _e('Types de permanences consommateurs (liste de \"id,libellé,heure début,heure fin,nombre de participants,défaut\" séparés par des points virgules)') ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Periodicité", 'periodicite'); ?> </label></td>
        <td><select size="1" name="paniers_periodicite">
<option value="hebdomadaire" <?php if($paniers_data['periodicite'] == "hebdomadaire") echo "selected"; ?>>Hebdomadaire</option>
<option value="mensuel" <?php if($paniers_data['periodicite'] == "mensuel") echo "selected"; ?>>Mensuel</option>
</select><span class="periodicite">  <?php _e('Périodicité des commandes, une fois par semaine ou bien une fois par mois.') ?>
        </span>
        </td>
      </tr>
      <tr>
        <td/>
        <td><select size="1" name="paniers_jourcommande">
<?php
    global $liste_jours;
    foreach($liste_jours as $id => $jour) {
        $selected = $id == $paniers_data['jourcommande'] ? "selected" : "";
?>
        <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $jour; ?></option>
<?php
    }
?>
</select><span class="periodicite">  <?php _e('Jour de la commande.') ?>
        </span>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h3>
            <?php _e("Réglages de notifications", 'paniers'); ?>
          </h3>
          <h4>
            <?php _e("Notification de relance", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Envoyer un email de relance ?", 'paniers'); ?> </label>
        </td>
        <td><input style="margin: 0px; padding: 0px; width: auto;" type="checkbox" name="paniers_envoyerrelance"
          value="1" <?php echo $paniers_data["envoyerrelance"] == "1" ? 'checked="checked"' : ''; ?>
        /> <span><?php _e('Le courrier de relance est envoyé avant la date de commande.') ?> </span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="adresserelance"><?php _e('Adresse relance') ?> </label></th>
        <td><input name="paniers_adresserelance" type="text" id="adresserelance"
          value='<?php echo $paniers_data['adresserelance']; ?>' class="regular-text"
        /> <span><?php _e('L\'adresse où envoyer le mail de relance, laisser vide pour envoyer la relance individuellement à chaque consommateur.') ?> </span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="deltarelance"><?php _e('Delta relance') ?> </label></th>
        <td><input name="paniers_deltarelance" type="text" id="deltarelance"
          value='<?php echo $paniers_data['deltarelance']; ?>' class="regular-text"
        /> <span><?php _e('Le nombre de jours avant la date de commande pour envoyer le mail de relance.') ?> </span>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['relancesujet'];
        if(empty($subject)) {
            $subject = __('Pensez à faire votre commande au %BLOGNAME%', 'paniers');
		}
		?> <input type="text" name="paniers_relancesujet" value='<?php echo $subject; ?>' style="width: 100%" class='wide' />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['relancemessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Il est temps de penser à faire votre commande pour les Paniers d'Eden !

Les commandes seront closes le %DATE_VERROUILLAGE% à minuit.

Pour passer votre commande, connectez-vous ici :
   %BLOGURL%

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_relancemessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Courrier commandes producteurs", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['exportcommandessujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Commandes - %PERIODE%', 'paniers');
		}
		?> <input type="text" name="paniers_exportcommandessujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['exportcommandesmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici les commandes au format Excel et PDF pour la période de %PERIODE%.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_exportcommandesmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Courrier récapitulatif commandes pour dépôt", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['exportrecapcommandessujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Récapitulatif des commandes - %PERIODE%', 'paniers');
		}
		?> <input type="text" name="paniers_exportrecapcommandessujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['exportrecapcommandesmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici le récapitulatif des commandes consommateurs pour votre dépôt et la période de %PERIODE%.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_exportrecapcommandesmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Courrier récapitulatif chèques pour dépôt", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['exportchequessujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Récapitulatif montants des commandes - %PERIODE%', 'paniers');
		}
		?> <input type="text" name="paniers_exportchequessujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['exportchequesmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici le récapitulatif des montants de commandes pour la période de %PERIODE%.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_exportchequesmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Courrier liste clients pour dépôt", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['exportclientssujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Liste des consommateurs', 'paniers');
		}
		?> <input type="text" name="paniers_exportclientssujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['exportclientsmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici la liste des consommateurs pour votre dépôt.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_exportclientsmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Notification dates de livraisons producteurs", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['notificationproducteurssujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Dates de livraisons pour %PERIODE%', 'paniers');
		}
		?> <input type="text" name="paniers_notificationproducteurssujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['notificationproducteursmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici les dates de livraisons prévues pour la commande de la période %PERIODE%:

%LISTE_DATES%

Merci de nous signaler si vous ne pourrez pas assurer l'une de ces livraisons. Le bon de commande sera mis en ligne dans quelques jours.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_notificationproducteursmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
        </td>
      </tr>
    </table>
    <!-- Mise à jour des valeurs -->
    <input type="hidden" name="panierssubmitted" value="1" />
    <!-- Bouton de sauvegarde -->
    <p>
      <input type="submit" value="<?php _e('Save Changes'); ?>" />
    </p>
  </form>
</div>
<?php
}

add_action( 'admin_menu', 'paniers_plugin_menu' );

register_activation_hook(__FILE__,'paniers_install');
register_deactivation_hook(__FILE__,'paniers_uninstall');

add_action('controler_date_fin_commande_event', 'controler_date_fin_commande');

add_action('wp_print_styles', 'paniers_add_plugin_stylesheet');
add_action('init', 'paniers_rewriteURL');

add_action('register_form','paniers_register_form');
add_filter('registration_errors', 'paniers_registration_errors', 10, 3);
add_action('password_reset', 'paniers_password_reset', 10, 2);
add_filter('query_vars', 'paniers_queryvars' );
add_filter('authenticate', 'paniers_check_login', 10, 3);

add_shortcode('paniers-updateprofile', 'paniers_updateprofile');
add_shortcode('paniers-date-commande', 'paniers_datecommande');
add_shortcode('paniers-produits', 'paniers_produits');

function paniers_commande_nouveau($atts) {
    require_once(paniers_dir . "/include/fonctions_include.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    extract( shortcode_atts( array(
		'page_commande_non_disponible' => '',
    ), $atts ) );

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        foreach($_POST as $k=>$v) $$k=$v;
    }
    $idperiode = retrouver_periode_courante();

    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    if ($idperiode == 0 || !periode_active($idperiode)) {
        wp_redirect($page_commande_non_disponible);
    } else {
        echo afficher_formulaire_bon_commande_nouveau_client($idperiode,
                                                             $qteproduit,
                                                             $nom,
                                                             $prenom,
                                                             $email,
                                                             $telephone,
                                                             $ville);
    }
    $content = ob_get_contents();
    ob_clean();
    return $content;
}
add_shortcode('paniers-commande-nouveau', 'paniers_commande_nouveau');

function paniers_permanences() {
    if(!is_user_logged_in()) {
        wp_redirect( wp_login_url() );
    }

    require_once(paniers_dir . "/include/fonctions_include.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    global $wp_query;
    global $base_permanences;
    global $base_permanenciers;

    $action = $wp_query->get("action");
    $id = $wp_query->get("id");
    $userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);

    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    if ($action == "") {
        echo afficher_planning_permanences(false, $userid);
    } else if ($action == "inscrire") {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select nbparticipants,nbinscrits from $base_permanences where id='$id' and date >= curdate()");
        if (mysqli_num_rows($rep) != 0) {
            list($nbparticipants,$nbinscrits) = mysqli_fetch_row($rep);
        }
        if ($nbinscrits < $nbparticipants && verifier_non_inscription($id,$userid))
        {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $base_permanenciers (id,idpermanence,idclient,commentaire,datemodif) values ('','$id','" . $userid . "','',now())");
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits+1 where id='$id'");
            afficher_corps_page(
                "Merci de vous être inscrit à cette permanence",
                "",
                afficher_planning_permanences(false,$userid));
            ecrire_log_public("Inscription à la permanence : " . retrouver_permanence($id));
        } else {
            afficher_corps_page(
                "Une erreur est survenue",
                "",
                afficher_planning_permanences(false,$userid));
        }
    } else if ($action == "desinscrire") {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_permanences where id='$id' and date >= curdate()");
        if (mysqli_num_rows($rep) != 0 && !verifier_non_inscription($id,$userid)) {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_permanenciers where idpermanence='$id' and idclient='" . $userid . "' limit 1");
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_permanences set nbinscrits=nbinscrits-1 where id='$id'");
            afficher_corps_page(
                "Vous êtes désinscrit de cette permanence",
                "",
                afficher_planning_permanences(false,$userid));
            ecrire_log_public("Désinscription de la permanence : " . retrouver_permanence($id));
        }
        else
        {
            afficher_corps_page(
                "Une erreur est survenue",
                "Vous êtes déja désinscrit de la permanence",
                afficher_planning_permanences(false,$userid));
        }
    }
    $content = ob_get_contents();
    ob_clean();
    return $content;
}
add_shortcode('paniers-permanences', 'paniers_permanences');

function paniers_commande_adherent($atts) {

    if (!function_exists('message_erreur')) {
        function message_erreur($message) {
            echo afficher_message_erreur($message);
            $content = ob_get_contents();
            ob_clean();
            return $content;
        }
    }

    if(!is_user_logged_in()) {
        wp_redirect( wp_login_url() );
    }

    require_once(paniers_dir . "/include/fonctions_include.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    extract( shortcode_atts( array(
		'page_commande_non_disponible' => '',
    ), $atts ) );

    global $wp_query;
    global $base_bons_cde;

    $action = $wp_query->get("action");
    $id = $wp_query->get("id");
    $idperiode = $wp_query->get("idperiode");
    $userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);

    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    if ($action == "editercde" ||
	($action=="" && !str_starts_with($_SERVER['REQUEST_URI'],
"/wp-admin"))) {

        if (!isset($id) || $id == "" || $id == 0) {
            $idperiode = retrouver_periode_courante(true);
            if($idperiode == -1) {
                wp_redirect($page_commande_non_disponible);
            }
            else if($idperiode == 0 || !periode_active($idperiode)) {
                wp_redirect($page_commande_non_disponible);
            } else {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id from $base_bons_cde where idclient = '$userid' and idperiode = '$idperiode'");
                if (mysqli_num_rows($rep) > 0) {
                    list($id) = mysqli_fetch_row($rep);
                }
            }
        }

        if (isset($id) && $id != "" && $id != 0) {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode,iddepot from $base_bons_cde where id = '$id'");
            if(mysqli_num_rows($rep) == 0) {
                return message_erreur("Commande introuvable");
            }
            list($idperiode,$iddepot) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
        } else {
            $id = 0;
            $iddepot = retrouver_depot_client($userid);
            $qteproduit = array();
        }

        afficher_corps_page(
            "",
            "",
            afficher_formulaire_bon_commande(
                $idperiode,
                $iddepot,
                $qteproduit,
                "enregistrercde",
                $id,
                $userid));

    } else if ($action == "affichercde" && $id != "" && $id != 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode,iddepot from $base_bons_cde where id='$id'");
        if (mysqli_num_rows($rep) != 0) {
            list($idperiode,$iddepot) = mysqli_fetch_row($rep);
            $qteproduit = retrouver_quantites_commande($id,$idperiode);
            afficher_corps_page(
                "",
                "",
                afficher_recapitulatif_commande($id));
        }
        else {
            return message_erreur("Commande introuvable");
        }
    } else if ($action == "enregistrercde") {
        $iddepot = $_POST["iddepot"];
        if (!isset($idperiode) || $idperiode == "" || $idperiode == 0) {
            return message_erreur("Pas de période définie");
        }
        else if (!isset($iddepot) || $iddepot == "" || $iddepot == 0) {
            return message_erreur("Pas de dépot selectionné");
        }
        else {
            if (isset($id) && $id != "" && $id != 0) {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,iddepot from $base_bons_cde where id='$id'");
                if(mysqli_num_rows($rep) == 0) {
                    return message_erreur("Commande introuvable");
                }
            }
            else {
                $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select id,iddepot from $base_bons_cde where idperiode='$idperiode' and idclient='$userid'");
            }

            if(mysqli_num_rows($rep) == 0) {
                $id = enregistrer_bon_commande($idperiode,$userid,$iddepot);
            } else {
                list($id, $iddepotorig) = mysqli_fetch_row($rep);
                if($iddepotorig != $iddepot) {
                    $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_bons_cde set iddepot=$iddepot where id='$id'");
                }
            }

            enregistrer_commande($idperiode,$_POST['qteproduit'],$id,$userid);
            afficher_corps_page(
                "Commande enregistrée sous le n° C$userid-$id (" . html_lien("/paniers/imprimer.php?id=$id","_blank","l'imprimer") . ")",
                "",
                afficher_recapitulatif_commande($id));
            ecrire_log_public("Commande enregistrée sous le n° C$userid-$id");
        }
    } else if ($action == "supprimercde" && $id > 0) {
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select idperiode,etat,datemodif from $base_bons_cde where id='$id'");
        if(mysqli_num_rows($rep) != 0) {
            list($idperiode,$datemodif) = mysqli_fetch_row($rep);
            $champs["libelle"] = array("Commande n° C$userid-$id","Période","Créée le","","");
            $champs["type"] = array("","afftext","afftext","submit","submit");
            $champs["lgmax"] = array("","","","","");
            $champs["taille"] = array("","","","","");
            $champs["nomvar"] = array("","","","valider","valider");
            $champs["valeur"] = array("",retrouver_periode($idperiode),dateheureexterne($datemodif)," Annuler "," Valider ");
            $champs["aide"] = array("","","");
            afficher_corps_page(
                "Suppression de la commande n° C$userid-$id",
                "",
                saisir_enregistrement($champs,"?action=confsupprimercde&id=$id","formsupprimer",70,20,2,2,false));
        }
        else {
            return message_erreur("Commande introuvable");
        }
    } else if ($action == "confsupprimercde" && $id > 0) {
        if ($_POST["valider"] == " Valider ") {
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_bons_cde where id='$id'");
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $base_commandes where idboncommande='$id'");
            $rep = mysqli_query($GLOBALS["___mysqli_ston"], "update $base_avoirs set idboncommande=0 where idboncommande='$id'");
            ecrire_log_public("Commande n° C$userid-$id supprimée");
        }
        else {
            echo(lister_commandes($userid, $page_commande));
        }
    } else {
        return message_erreur("Action de commande invalide");
    }

    $content = ob_get_contents();
    ob_clean();
    return $content;
}
add_shortcode('paniers-commande-adherent', 'paniers_commande_adherent');

function paniers_liste_commandes_adherent($atts) {
    if(!is_user_logged_in()) {
        wp_redirect( wp_login_url() );
    }

    require_once(paniers_dir . "/include/fonctions_include.php");
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    extract( shortcode_atts( array(
		'page_commande' => '/commande/',
    ), $atts ) );

    $userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);

    ob_start();
    echo('<link rel="stylesheet" href="/paniers/styles/styles.css" type="text/css">');
    echo(lister_commandes($userid, $page_commande));
    $content = ob_get_contents();
    ob_clean();
    return $content;
}
add_shortcode('paniers-liste-commandes-adherent', 'paniers_liste_commandes_adherent');
