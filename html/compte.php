<?php

add_filter('authenticate', 'paniers_check_login', 10, 3);
add_filter('login_url', 'paniers_login_url', 10, 3);
add_filter('register_url', 'paniers_register_url', 10, 1);
add_filter('login_redirect', 'paniers_login_redirect', 10);
add_action('password_reset', 'paniers_password_reset', 10, 2);

function paniers_checkIfLoggedIn() {
    if (is_user_logged_in()) {
        $userid = get_user_meta(get_current_user_id(), 'paniers_consommateurId', true);
        if ($userid > 0) {
            return $userid;
        }
    }
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
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

function paniers_login_url($login_url, $redirect, $force_reauth) {
    global $url_page_connexion;
    if ($url_page_connexion != "") {
        $login_url = site_url($url_page_connexion);
    }

	if (!empty($redirect)) {
		$login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
	}
	if ($force_reauth) {
		$login_url = add_query_arg('reauth', '1', $login_url);
	}
	return $login_url;
}

function paniers_register_url($register_url) {
    global $url_page_inscription;
    if ($url_page_inscription != "") {
        $register_url = site_url($url_page_inscription);
    }
    return $register_url;
}

function paniers_login_redirect() {
    if(isset($_REQUEST['redirect_to'])){
        return $_REQUEST['redirect_to'];
    }
    return '/';
}

add_shortcode('paniers-register-form', function() {
    require_once(paniers_dir . "/include/fonctions_include.php");
    require_once(paniers_dir . "/common.php");

    if (is_user_logged_in()) {
        return afficher_erreur("Vous êtes connecté et déjà inscrit !");
    }

    global $wp_query;
    $action = $wp_query->get("action");
    if ($action == "register") {
        $error = new WP_Error();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $error->add(0, "Requête invalide");
        }
        else if (email_exists($_POST["email"])) {
            $error->add(0, "Vous avez déjà un compte associé à cette adresse email !");
        }
        else {
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
            } else {
                $error = $user;
            }
        }
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

        echo afficher_erreur("Erreur d'inscription", $errors);
	}
    ?>
<div class="card">
    <div class="card-body">
        <form name="registerform" action="?action=register" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" id="email" required="required" value="<?php echo esc_attr($email); ?>" size="20"/>
            </div>
            <div class="mb-3">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" class="form-control" id="prenom" required="required" value="<?php echo esc_attr($prenom); ?>" size="20"/>
            </div>
            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" class="form-control" id="nom" required="required" value="<?php echo esc_attr($nom); ?>" size="20"/>
            </div>
            <div class="mb-3">
                <label for="telephone" class="form-label">Téléphone</label>
                <input type="text" name="telephone" class="form-control" id="telephone" required="required" value="<?php echo esc_attr($telephone); ?>" size="20"/>
            </div>
            <div class="mb-3">
                <label for="ville">Ville</label><?php echo afficher_villes_client("ville", $ville); ?>
            </div>
            <div class="mb-3">
                <label for="depot">Dépôt</label><?php echo afficher_liste_depots("depot", $depot); ?>
            </div>
            <div class="mb-3">
                <input type="submit" name="wp-submit" id="wp-submit" class="btn btn-primary" value="S'inscrire" />
            </div>
        </form>
    </div>
</div>
    <?php
    return ob_get_clean();
});

add_filter('wp_new_user_notification_email_admin', function($email, $user, $blogname) {
    $email['subject'] = '[%s] Nouvelle inscription sur le site';
    $email['message'] = <<< HTML
Bonjour,

Un nouvel utilisateur s'est inscrit sur le site.

Identifiant: $user->user_login
Nom: $user->last_name
Prénom: $user->first_name
Email: $user->user_email
Code client: $user->user_login

$blogname
HTML;
    return $email;
}, 10, 3);

add_filter('wp_new_user_notification_email', function($email, $user, $blogname) {
    global $email_gestionnaires;
    $key = get_password_reset_key($user);
    if (is_wp_error($key)) {
        return;
    }
    $url = network_site_url('wp-login.php?login=' . rawurlencode($user->user_login) . "&key=$key&action=rp", 'login' );
    $email['subject'] = '[%s] Confirmez votre inscription';
    $email['message'] = <<< HTML
Bonjour $user->first_name,

Vous avez été ou vous vous êtes inscrit sur le site $blogname.

Votre code consommateur est $user->user_login.

Afin de finaliser votre inscription, vous devez créer un nouveau mot de passe à ce lien:
$url

$blogname
$email_gestionnaires
HTML;
    return $email;
}, 10, 3);

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
    echo "<p>";
    echo "<input name=\"updateprofile\" class=\"btn btn-primary\" type=\"submit\" id=\"updateprofile\" value=\"Sauvegarder\" />";
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

function paniers_insertclient() {
    require_once(ABSPATH . "wp-admin/includes/user.php");

    if(isset($_POST["codeclient"])) {
        $codeclient = $_POST["codeclient"];
    } else {
        global $base_clients;
        $rep = mysqli_query($GLOBALS["___mysqli_ston"], "select max(cast(substr(codeclient,2) as unsigned))+1 from $base_clients where 1");
        if($rep) {
            list($codeclient) = mysqli_fetch_row($rep);
            $codeclient = "C" . $codeclient;
        } else {
            $codeclient = "C1";
        }
    }

    $errors = new WP_Error();

    if(username_exists($codeclient)) {
		$errors->add( 'username_exists', __( '<strong>Error:</strong> This username is already registered. Please choose another one.' ) );
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
            if(is_wp_error($id)) {
                return $id;
            }

            $user = new WP_User($id);
            $user->add_cap("consommateur");
            add_user_meta($id, 'show_admin_bar_front', false, true);
            return $user;
        } else {
            $errors->add(
                'email_exists',
                sprintf(
                    /* translators: %s: Link to the login page. */
                    __( '<strong>Error:</strong> This email address is already registered. <a href="%s">Log in</a> with this address or choose another one.' ),
                    wp_login_url()
                )
            );
        }
    }

    if ($errors->has_errors()) {
        return $errors;
    }

    $userarray['user_login'] = $codeclient;
    if (isset($_POST['motpasse'])) {
        $userarray['user_pass'] = $_POST['motpasse'];
    } else {
    	$userarray['user_pass'] = wp_generate_password(12, false);
    }
    $userarray['first_name'] = $_POST['prenom'];
    $userarray['last_name'] = $_POST['nom'];
    $userarray['display_name'] = $_POST["prenom"] . " " . $_POST["nom"];
    $userarray['user_email'] = $_POST["email"];
    $userarray['show_admin_bar_front'] = 'false';
    $id = wp_insert_user($userarray);
    if (is_wp_error($id)) {
        return $id;
    }

    $user = new WP_User($id);
    $user->add_cap("consommateur");
    add_user_meta($id, 'show_admin_bar_front', 'false', true);
    if (!isset($_POST['motpasse'])) {
    	update_user_meta($id, 'default_password_nag', true ); // Set up the password change nag.
    }
    wp_send_new_user_notifications($id);
    return $user;
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
    wp_send_new_user_notifications($id);
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

?>
