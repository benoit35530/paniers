<?php

function paniers_plugin_menu() {
    add_options_page( 'Paniers Options', 'Paniers', 'manage_options', 'paniers-id', 'paniers_plugin_options' );
}

function paniers_plugin_options() {
    if (!current_user_can('manage_options'))  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    add_option('paniers_data');
    $paniers_data = array();
    if(is_admin() && !empty($_POST['panierssubmitted'])) {
        //Build the array of options here
        foreach ($_POST as $postKey => $postValue){
            if(substr($postKey, 0, 8) == 'paniers_') {
                // For now, no validation, since this is in admin area.
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
          value="<?php echo $paniers_data['pageconsommateurs']; ?>" class="regular-text"/></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="pagegestionnaires"><?php _e('Page Gestionnaires') ?> </label></th>
        <td><input name="paniers_pagegestionnaires" type="text" id="pagegestionnaires"
          value="<?php echo $paniers_data['pagegestionnaires']; ?>" class="regular-text"/></td>
      </tr>
      <tr valign="top">
        <th scope="row"><label for="pageconnexion"><?php _e('Page Connexion') ?> </label></th>
        <td><input name="paniers_pageconnexion" type="text" id="pageconnexion"
          value="<?php echo $paniers_data['pageconnexion']; ?>" class="regular-text"/></td>
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
        <th scope="row"><label for="smtppassword"><?php _e('Mot de passe SMTP') ?> </label></th>
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
            <?php _e("Courrier récapitulatif paiement pour dépôt", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Sujet", 'paniers'); ?> </label></td>
        <td><?php
        $subject = $paniers_data['exportpaiementssujet'];
        if(empty($subject)) {
            $subject = __('%BLOGNAME%: Récapitulatif montants des commandes - %PERIODE%', 'paniers');
		}
		?> <input type="text" name="paniers_exportpaiementssujet" value='<?php echo $subject; ?>' style="width: 100%"
          class='wide'
        />
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['exportpaiementsmessage'];
        if(empty($message))
        {
            $message = __("Bonjour,

Voici le récapitulatif des montants de commandes pour la période de %PERIODE%.

Cordialement,
--
mailto: %EMAIL_GESTIONNAIRES%
%BLOGURL%", 'paniers');
        }
        ?> <textarea name="paniers_exportpaiementsmessage" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
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
      <tr valign="top">
        <td colspan="2">
          <h4>
            <?php _e("Message confirmation de sauvegarde de commande", 'paniers'); ?>
          </h4>
        </td>
      </tr>
      <tr valign="top">
        <td><label><?php _e("Message", 'message'); ?> </label></td>
        <td><?php
        $message = $paniers_data['messagesauvegardecommande'];
        if(empty($message))
        {
            $message = __("N'oubliez pas pas de faire votre virement pour le %DATECOMMANDE%", 'paniers');
        }
        ?> <textarea name="paniers_messagesauvegardecommande" class='wide' style="width: 100%; height: 250px;"><?php echo esc_textarea($message) ?></textarea>
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

?>