<?php

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
?>