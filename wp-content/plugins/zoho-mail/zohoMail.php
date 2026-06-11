<?php
//require_once('wp-load.php');
/*
Plugin Name: Zoho Mail
Version: 1.6.2
Plugin URI: http://mail.zoho.com
Author: Zoho Mail
Author URI: https://www.zoho.com/mail/
Description: Configure your zoho account to send email from your WordPress site
Text Domain: Zoho Mail
Domain Path: /languages
 */
/*
    Copyright (c) 2015, ZOHO CORPORATION
    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
if (!defined('ABSPATH')) {
  wp_die(__('Direct access not allowed', 'zoho-mail'), '', array('response' => 403));
} else {
  define("ZM4WP", "ZM4WP_PLUGIN_ACTIVATED");
  define("ZM4WP_VERSION", "1.0");
  define("ZM4WP_ZM_PLUGIN_HOME_DIR", plugin_dir_path(__FILE__));
}

function zm_get_domain_name()
{
  $domain_name = get_option('zmail_integ_domain_name', 'zoho.com');

  if (strpos($domain_name, 'zoho') === false) {
    $domain_name = 'zoho.' . $domain_name;
  }

  $allowed_domains = array(
    'zoho.com',
    'zoho.eu',
    'zoho.in',
    'zoho.com.cn',
    'zoho.com.au',
    'zoho.jp',
    'zohocloud.ca',
    'zoho.sa'
  );

  return in_array($domain_name, $allowed_domains, true) ? $domain_name : 'zoho.com';
}

function zmail_admin_notice__success()
{
  if (!current_user_can('manage_options')) {
    return;
  }
  if (! get_option('zmail_plugin_installed')) {
?>
    <div class="notice notice-info is-dismissible">
      <p>
        <?php echo wp_kses_post(
          __('Frequently faced with sending limit issues? Use Zoho ZeptoMail—a dedicated transactional email sending service for your WordPress site. <a href="https://www.zoho.com/blog/zeptomail/?src=zmwp" target="_blank" rel="noopener">Learn more</a>', 'zoho-mail')
        );
        ?>
      </p>
    </div>
  <?php
    update_option('zmail_plugin_installed', true, false);
  }
}

add_action('admin_notices', 'zmail_admin_notice__success');


function zm_zmplugin_script()
{
  if (!is_admin()) {
    return;
  }
  wp_enqueue_style(
    'zm_zohomail_style',
    plugin_dir_url(__FILE__) . 'assets/css/style.css',
    array(),
    ZM4WP_VERSION
  );
}

add_action('admin_enqueue_scripts', 'zm_zmplugin_script');


function onMailError($wp_error)
{
  if (!current_user_can('manage_options')) {
    return;
  }
  $error_message = $wp_error->get_error_message('wp_mail_failed');
  echo '<div class="error"><p><strong>Error --> ' . wp_kses_post($error_message) . '</strong></p></div>';
}

add_action('wp_mail_failed', 'onMailError', 10, 1);


function zohomail_activate() {}
register_activation_hook(__FILE__, 'zohomail_activate');


function zohomail_uninstall()
{
  $options = array(
    'zmail_integ_client_id',
    'zmail_integ_client_secret',
    'zmail_integ_from_email_id',
    'zmail_integ_domain_name',
    'zmail_access_token',
    'zmail_refresh_token',
    'zmail_account_id',
    'zmail_integ_from_name',
    'zmail_auth_code',
    'zmail_content_type',
    'zmail_integ_timestamp',
    'zmail_plugin_installed'
  );

  foreach ($options as $option) {
    delete_option($option);
  }
}
register_uninstall_hook(__FILE__, 'zohomail_uninstall');



function zmail_integ_settings()
{
  add_menu_page(
    'Welcome to Zoho mail',
    'Zoho Mail',
    'manage_options',
    'zmail-integ-settings',
    'zmail_integ_settings_callback',
    'dashicons-email'
  );
  add_submenu_page(
    'zmail-integ-settings',
    'Welcome to Zoho mail',
    'Configure Account',
    'manage_options',
    'zmail-integ-settings',
    'zmail_integ_settings_callback'
  );
  add_submenu_page(
    'zmail-integ-settings',
    'Send Mail - Zoho',
    'Test Mail',
    'manage_options',
    'zmail-send-mail',
    'zmail_send_mail_callback'
  );
  add_submenu_page(
    'zmail-integ-settings',
    'Troubleshoot - Zoho Mail',
    'Troubleshoot',
    'manage_options',
    'zmail-troubleshoot',
    'zmail_troubleshoot_callback'
  );
}

add_action('admin_menu', 'zmail_integ_settings');

function zmail_troubleshoot_callback()
{

  if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized access', 'zoho-mail'), 403);
  }

  // Verify nonce for ALL POST requests
  if (!empty($_POST)) {
    if (
      !isset($_POST['zmail_troubleshoot_nonce']) ||
      !wp_verify_nonce($_POST['zmail_troubleshoot_nonce'], 'zmail_troubleshoot_action')
    ) {
      wp_die(__('Security check failed. Please refresh the page and try again.', 'zoho-mail'), 403);
    }
  }

  // Process troubleshooting forms with proper sanitization
  if (isset($_POST['zmail_invalid_secret']) && sanitize_text_field($_POST['zmail_invalid_secret']) === 'true') {
    $auth_code = get_option('zmail_auth_code');
    $refresh_token = get_option('zmail_refresh_token');
    $account_id = get_option('zmail_account_id');

    if ($auth_code !== false && $refresh_token !== false) {
      echo '<div class="notice notice-error"><p><strong>' .
        esc_html__('Your configuration setup looks good. Try sending an email from the', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-send-mail')) . '">' .
        esc_html__('Test mail', 'zoho-mail') . '</a> ' .
        esc_html__('page. If the test email sending fails, capture a screenshot of', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-integ-settings')) . '">' .
        esc_html__('Configure Account', 'zoho-mail') . '</a> ' .
        esc_html__('page and write to', 'zoho-mail') .
        ' <a href="mailto:support@zohomail.com">' . esc_html__('our support', 'zoho-mail') . '</a>' .
        '</strong></p></div>';
    }

    if ($refresh_token === false) {
      delete_option('zmail_auth_code');
      delete_option('zmail_account_id');
      echo '<div class="notice notice-error"><p><strong>' .
        esc_html__('This error occurs because of mismatch in Client ID, Client Secret and Domain field in the account', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-integ-settings')) . '">' .
        esc_html__('Configure page', 'zoho-mail') . '</a>. ' .
        esc_html__('Please click on the Authorize button in the configuration page', 'zoho-mail') .
        '</strong></p></div>';
    }

    if ($refresh_token !== false && $account_id === false) {
      echo '<div class="notice notice-error"><p><strong>' .
        esc_html__('Please capture a screenshot of', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-integ-settings')) . '">' .
        esc_html__('Configure Account', 'zoho-mail') . '</a> ' .
        esc_html__('page and write to', 'zoho-mail') .
        ' <a href="mailto:support@zohomail.com">' . esc_html__('our support', 'zoho-mail') . '</a>' .
        '</strong></p></div>';
    }
  }

  if (isset($_POST['zmail_invalid_from']) && sanitize_text_field($_POST['zmail_invalid_from']) === 'true') {
    $account_id = get_option('zmail_account_id');
    $from_email = get_option('zmail_integ_from_email_id');

    if ($account_id === false) {
      echo '<div class="notice notice-error"><p><strong>' .
        esc_html__("The 'From' address mismatches with the 'From' address of the authorized Zoho Mail account. Try to reauthorize in the account", 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-integ-settings')) . '">' .
        esc_html__('configuration page', 'zoho-mail') . '</a> ' .
        esc_html__('with the', 'zoho-mail') . ' ' .
        esc_html(sanitize_email($from_email)) . ' ' .
        esc_html__('account', 'zoho-mail') .
        '</strong></p></div>';
    } else {
      echo '<div class="notice notice-error"><p><strong>' .
        esc_html__('Your configuration setup looks good. Try sending an email from the', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-send-mail')) . '">' .
        esc_html__('Test mail', 'zoho-mail') . '</a> ' .
        esc_html__('page. If the test email sending fails, capture a screenshot of', 'zoho-mail') .
        ' <a href="' . esc_url(admin_url('admin.php?page=zmail-integ-settings')) . '">' .
        esc_html__('Configure Account', 'zoho-mail') . '</a> ' .
        esc_html__('page and write to', 'zoho-mail') .
        ' <a href="mailto:support@zohomail.com">' . esc_html__('our support', 'zoho-mail') . '</a>' .
        '</strong></p></div>';
    }
  }
  ?>

  <head>
    <title>Troubleshoot- Zoho Mail - Wordpress</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  </head>

  <body>
    <div class="page">
      <div class="zmwpContent">
        <h1>Troubleshoot Zoho Mail </h1>
        <p class="zmwpTagline">Having trouble downloading the products? Try these solutions to resolve common browser, system, and connectivity problems that can interfere with sucessful downloads of products. </p>

        <div class="zmwpContainer">
          <h3>Common Errors</h3>
          <p>Explore the most common errors for your resoruce. Select Trouble shoot to run an automated troubleshooter, follow do-it-yoursself troubleshooting steps, or explore a wide range of troubleshooting tools.</p>
          <div class="zmwpCEBoxWra">
            <div class="zmwpCEBox">
              <h5>Invalid Client Secret</h5>
              <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin.php?page=zmail-troubleshoot')); ?>">
                <?php wp_nonce_field('zmail_troubleshoot_action', 'zmail_troubleshoot_nonce'); ?>
                <input type="hidden" id="zmail_invalid_secret" name="zmail_invalid_secret" value="true" />
                <input type="submit" name="zmail_troubleshoot_invalid_cs" id="zmail_troubleshoot_invalid_cs" class="tbtn" value="Troubleshoot" />
              </form>
              <!--<a href="" target="_blank" class="zmwpLink"><b>Troubleshoot</b></a>-->
            </div>
            <div class="zmwpCEBox">
              <h5>Invalid from Address</h5>
              <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin.php?page=zmail-troubleshoot')); ?>">
                <?php wp_nonce_field('zmail_troubleshoot_action', 'zmail_troubleshoot_nonce'); ?>
                <input type="hidden" id="zmail_invalid_from" name="zmail_invalid_from" value="true" />
                <input type="submit" name="zmail_troubleshoot_invalid_fn" id="zmail_troubleshoot_invalid_fn" class="tbtn" value="Troubleshoot" />
              </form>
              <!--<a href="" target="_blank" class="zmwpLink"><b>Troubleshoot</b></a>-->
            </div>
          </div>

        </div>
        <div class="zmwpContainer">
          <h3>More like this</h3>
          <ul>
            <li><a href="https://www.zoho.com/mail/help/zohomail-plugin-for-wordpress.html#alink9" target="_blank" class="zmwpLink">Mod Security issue</a></li>
            <li><a href="https://www.zoho.com/mail/help/zohomail-plugin-for-wordpress.html#alink8" target="_blank" class="zmwpLink">Mail not send in HTML format</a></li>
            <li><a href="https://www.zoho.com/mail/help/zohomail-plugin-for-wordpress.html#alink5" target="_blank" class="zmwpLink">Reply To address not set in mail</a></li>
          </ul>

        </div>
        <div class="zmwpContainer">
          <h3>Still need help?</h3>
          <p>Visit the <a href="https://wordpress.org/support/plugin/zoho-mail/" target="_blank" class="zmwpLink">ZohoMail Wordpress plugin page</a> to see if other users have found solutions for similar issues or refer our help documentation. If you still need assistance from us, write to our <a href="mailto: support@zohomail.com" class="zmwpLink">Support</a> with details of your issue</p>
        </div>
      </div>
    </div>
  </body>
  <?php

}



function zmail_integ_settings_callback()
{
    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized'));
    }

    if (isset($_GET['granted']) && check_admin_referer('redirect_uri', 'granted')) {
      $option = get_option('zmail_access_token');

      if (empty($option)) {
        echo '<div class="error"><p><strong><a href="' . esc_url(admin_url('admin.php?page=zmail-troubleshoot')) . '">' . esc_html__('Invalid Client Secret') . '</a></strong></p></div>';
      } else {
        $accId = get_option('zmail_account_id');
        if (empty($accId)) {
          echo '<div class="error"><p><strong>' . esc_html__('Invalid From Address.') . '</strong></p></div>';
        } else {
          echo '<div class="updated"><p><strong>' . esc_html__('Access Granted.') . '</strong></p></div>';
        }
      }
    }

    if (isset($_GET['code'])) {
      if (!isset($_GET['state']) || $_GET['state'] !== get_option('redirect_url')) {
        wp_die(__('Invalid OAuth state'));
      }

      ?>
      <head> <meta http-equiv="refresh" content="0; url=<?php echo wp_nonce_url(esc_url(admin_url().'admin.php?page=zmail-integ-settings&action=zmail_integ_oauth_grant'),'redirect_uri','granted');?>"/> </head>
      <?php

    if (empty(get_option('zmail_auth_code'))) {
      $auth_code = sanitize_text_field($_GET['code']);
      update_option('zmail_auth_code', $auth_code, false);

      $state = wp_create_nonce('redirect_url');

      $url = "https://accounts." . zm_get_domain_name() . "/oauth/v2/token?code=" . $auth_code . "&client_id=" . get_option('zmail_integ_client_id') . "&client_secret=" . get_option('zmail_integ_client_secret') . "&redirect_uri=" . admin_url() . "admin.php?page=zmail-integ-settings&scope=VirtualOffice.messages.CREATE,VirtualOffice.accounts.READ&grant_type=authorization_code&state=" . $state;
      $bodyAccessTokandRefresh = wp_remote_retrieve_body(wp_remote_post($url));
      $respoAtJs = json_decode($bodyAccessTokandRefresh);


      update_option('zmail_access_token', $respoAtJs->access_token, false);
      update_option('zmail_refresh_token', $respoAtJs->refresh_token, false);
      $accId = get_option('zmail_account_id');

      if (!empty($accId)) {
        delete_option('zmail_account_id');
      }

      $urlAccounts = 'https://mail.' . zm_get_domain_name() . '/api/accounts';
      $headr = array();
      $accesstoken = get_option('zmail_access_token');
      $headr[] = 'Authorization: Zoho-oauthtoken ' . $accesstoken;
      $args = array(
        'headers' => array(
          'Authorization' => 'Zoho-oauthtoken ' . $accesstoken,
          'User-Agent' => 'zm_wordpress'
        )
      );
      $bodyAccounts = wp_remote_retrieve_body(wp_remote_get($urlAccounts, $args));
      $jsonbodyAccounts = json_decode($bodyAccounts);


      for ($i = 0; $i < count($jsonbodyAccounts->data); $i++) {
        for ($j = 0; $j < count($jsonbodyAccounts->data[$i]->sendMailDetails); $j++) {
          if (strcmp($jsonbodyAccounts->data[$i]->sendMailDetails[$j]->fromAddress, get_option('zmail_integ_from_email_id')) === 0) {
            update_option('zmail_account_id', $jsonbodyAccounts->data[0]->accountId, false);
          }
        }
      }
    } else {
      if (!empty(get_option('zmail_access_token'))) {

        $urlAccounts = 'https://mail.' . zm_get_domain_name() . '/api/accounts';
        $headr = array();

        update_access_token();
        $accesstoken = get_option('zmail_access_token');
        $headr[] = 'Authorization: Zoho-oauthtoken ' . $accesstoken;
        $args = array(
          'headers' => array(
            'Authorization' => 'Zoho-oauthtoken ' . $accesstoken,
            'User-Agent' => 'zm_wordpress'

          )
        );
        $bodyAccounts = wp_remote_retrieve_body(wp_remote_get($urlAccounts, $args));
        $jsonbodyAccounts = json_decode($bodyAccounts);


        if (
          isset($jsonbodyAccounts->data) &&
          is_array($jsonbodyAccounts->data)
        ) {
          foreach ($jsonbodyAccounts->data as $element) {

            if (
              !isset($element->sendMailDetails) ||
              !is_array($element->sendMailDetails)
            ) {
              continue;
            }

            foreach ($element->sendMailDetails as $smd) {
              if (
                isset($smd->fromAddress) &&
                $smd->fromAddress === get_option('zmail_integ_from_email_id')
              ) {
                update_option('zmail_account_id', $element->accountId, false);
                break 2;
              }
            }
          }
        }
      }
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
      !isset($_POST['_wpnonce']) ||
      !wp_verify_nonce($_POST['_wpnonce'], 'zmail_integ_settings_nonce')
    ) {
      wp_die(__('Invalid request'));
    }

    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized'));
    }

    if (isset($_POST['zmail_content_type'])) {
      $selectedValue = zmail_validate_content_type(
        sanitize_text_field($_POST['zmail_content_type'])
      );
      update_option('zmail_content_type', $selectedValue, false);
    }

    if (isset($_POST['zmail_integ_from_email_id'])) {
      $selectedValue = zmail_validate_email(
        sanitize_email($_POST['zmail_integ_from_email_id'])
      );
      update_option('zmail_integ_from_email_id', $selectedValue, false);
    }

    if (isset($_POST['zmail_integ_from_name'])) {
      $selectedValue = zmail_validate_from_name(
        sanitize_text_field($_POST['zmail_integ_from_name'])
      );
      update_option('zmail_integ_from_name', $selectedValue, false);
    }
  }

  if (isset($_POST['zmail_integ_submit']) && !empty($_POST)) {
    $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
    if (!wp_verify_nonce($nonce, 'zmail_integ_settings_nonce')) {
      wp_die(__('Invalid request'));
    }

    $zmail_integ_client_id = zmail_validate_client_id(sanitize_text_field($_POST['zmail_integ_client_id']));
    $zmail_integ_client_secret = zmail_validate_client_secret(sanitize_text_field($_POST['zmail_integ_client_secret']));

    update_option('zmail_integ_client_id', $zmail_integ_client_id, false);
    update_option('zmail_integ_client_secret', $zmail_integ_client_secret, false);


    if (isset($_POST['zmail_integ_from_email_id'])) {
      $zmail_integ_from_email_id = zmail_validate_email(sanitize_email($_POST['zmail_integ_from_email_id']));
      update_option('zmail_integ_from_email_id', $zmail_integ_from_email_id, false);
    }
    if (isset($_POST['zmail_integ_domain_name'])) {
      $zmail_integ_domain_name = zmail_validate_domain(sanitize_text_field($_POST['zmail_integ_domain_name']));
      update_option('zmail_integ_domain_name', $zmail_integ_domain_name, false);
    }
    if (isset($_POST['zmail_integ_from_name'])) {
      $zmail_integ_from_name = zmail_validate_from_name(sanitize_text_field($_POST['zmail_integ_from_name']));
      update_option('zmail_integ_from_name', stripslashes($zmail_integ_from_name), false);
    }
    if (isset($_POST['zmail_content_type'])) {
      $zmail_content_type = zmail_validate_content_type(sanitize_text_field($_POST['zmail_content_type']));
      update_option('zmail_content_type', $zmail_content_type, false);
    }

    echo '<div class="updated"><p><strong>' . esc_html__('Settings saved.') . '</strong></p></div>' . "\n";
    ?>

      <head>
        <meta http-equiv="refresh" content="0; url=<?php $completeRedirectUrl = esc_url(admin_url() . 'admin.php?page=zmail-integ-settings');
              $state = wp_create_nonce('redirect_url');
              update_option('redirect_url', $state, false);
              $test = esc_url("https://accounts." . zm_get_domain_name() . "/oauth/v2/auth?response_type=code&client_id=" . get_option('zmail_integ_client_id') . "&scope=VirtualOffice.messages.CREATE,VirtualOffice.accounts.READ&redirect_uri=" . $completeRedirectUrl . "&prompt=consent&access_type=offline&state=" . $state);
              echo esc_attr($test); ?>" />
      </head>
    <?php
  }

  ?>

  <head>
    <meta charset="UTF-8">
    <title>Zoho Mail</title>
    <script>
      function copyRedirecturi() {
        var copyText = document.getElementById('zmail_integ_authorization_uri');
        copyText.select();
        copyText.setSelectionRange(0, copyText.value.length);
        document.execCommand('copy');
      }
    </script>
  </head>

  <body>
    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=zmail-integ-settings')); ?>">
      <?php wp_nonce_field('zmail_integ_settings_nonce'); ?>
      <div class="page">
        <div class="page__content">
          <div class="page__header">
            <h2 style="display: flex; align-items: center;"><img src=<?php echo esc_url(plugins_url('assets/images/icon.png', __FILE__)); ?> title="Zoho" alt="Zoho" width="60" style="margin-right: 15px;"> Welcome to Zoho Mail</h2>
            <p>Please visit the <a class="zm_a" href=<?php echo esc_url("https://accounts.zoho.com/developerconsole"); ?> target="_blank">Zoho OAuth Creation</a> documentation page for usage instructions.</p>
          </div>
          <div class="form__row">
            <label class="form--label">Where is your account hosted?</label>
            <div class="form__domain">
              <select class="form__domain-value" name="zmail_integ_domain_name">
                <option value="zoho.com" <?php if (get_option('zmail_integ_domain_name') === "zoho.com") { ?> selected="true" <?php } ?>>mail.zoho.com</option>
                <option value="zoho.eu" <?php if (get_option('zmail_integ_domain_name') === "zoho.eu") { ?> selected="true" <?php } ?>>mail.zoho.eu</option>
                <option value="zoho.in" <?php if (get_option('zmail_integ_domain_name') === "zoho.in") { ?> selected="true" <?php } ?>>mail.zoho.in</option>
                <option value="zoho.com.cn" <?php if (get_option('zmail_integ_domain_name') === "zoho.com.cn") { ?>selected="true" <?php } ?>>mail.zoho.com.cn</option>
                <option value="zoho.com.au" <?php if (get_option('zmail_integ_domain_name') === "zoho.com.au") { ?>selected="true" <?php } ?>>mail.zoho.com.au</option>
                <option value="zoho.jp" <?php if (get_option('zmail_integ_domain_name') === "zoho.jp") { ?>selected="true" <?php } ?>>mail.zoho.jp</option>
                <option value="zohocloud.ca" <?php if (get_option('zmail_integ_domain_name') === "zohocloud.ca") { ?>selected="true" <?php } ?>>mail.zohocloud.ca</option>
                <option value="zoho.sa" <?php if (get_option('zmail_integ_domain_name') === "zoho.sa") { ?>selected="true" <?php } ?>>mail.zoho.sa</option>
              </select>

            </div>
            <div>
              <i class="form__row-info">The name of the region the account is configured</i>
            </div>
          </div>


          <div class="form__row">
            <label class="form--label">Client Id</label>
            <input type="text" value="<?php echo esc_attr(get_option('zmail_integ_client_id')) ?>" name="zmail_integ_client_id" class="form--input" id="zmail_integ_client_id" required /> <i class="form__row-info">Created in the developer console</i>
          </div>
          <div class="form__row">
            <label class="form--label">Client Secret</label>
            <input type="text" value="<?php echo esc_attr(get_option('zmail_integ_client_secret')) ?>" name="zmail_integ_client_secret" class="form--input" id="zmail_integ_client_secret" required /> <i class="form__row-info">Created in the developer console</i>
          </div>
          <div class="form__row">
            <label class="form--label">Authorization Redirect URI</label>
            <input type="text" id="zmail_integ_authorization_uri" readonly="readonly" name="zmail_integ_authorization_uri" class="form--input" value="<?php echo esc_url(admin_url() . 'admin.php?page=zmail-integ-settings'); ?>" class="regular-text" readonly="readonly" required /> <i class="form__row-info">Copy this URL into Redirect URI field of your Client Id creation </i><i class="tib-copy" onclick="copyRedirecturi();">Copy text</i>
          </div>


          <div class="form__row form__row-btn">
            <input type="submit" name="zmail_integ_submit" id="zmail_integ_submit" class="btn" value="Authorize" />
          </div>
          <br>
          <?php
          if (!empty(get_option('zmail_access_token'))) {
          ?>
            <form method="post" action="">
              <?php wp_nonce_field('zmail_integ_settings_nonce'); ?>
              <div class="page__second-form">
                <div class="form__row">
                  <label class="form--label">Mail Format</label>
                  <select class="form--input form--input--select" name="zmail_content_type" onchange="this.form.submit()">
                    <option value="plaintext" <?php if (get_option('zmail_content_type') === "plaintext") { ?> selected="selected" <?php } ?>>Plaintext</option>
                    <option value="html" <?php if (get_option('zmail_content_type') === "html") { ?> selected="selected" <?php } ?>>HTML</option>
                  </select>
                </div>
                <div class="form__row">
                  <label class="form--label">From Email Address</label>
                  <?php
                  $urlAccounts = 'https://mail.' . zm_get_domain_name() . '/api/accounts';
                  $headr = array();

                  update_access_token();

                  $accesstoken = get_option('zmail_access_token');
                  $headr[] = 'Authorization: Zoho-oauthtoken ' . $accesstoken;
                  $args = array(
                    'headers' => array(
                      'Authorization' => 'Zoho-oauthtoken ' . $accesstoken,
                      'User-Agent' => 'zm_wordpress'
                    )
                  );
                  $bodyAccounts = wp_remote_retrieve_body(wp_remote_get($urlAccounts, $args));
                  $jsonbodyAccounts = json_decode($bodyAccounts);
                  if (!empty($jsonbodyAccounts->data[0]->accountId)) {
                    update_option('zmail_account_id', $jsonbodyAccounts->data[0]->accountId, false);
                  }

                  ?>

                  <select class="form--input form--input--select" name="zmail_integ_from_email_id" onchange="this.form.submit()">
                    <option value="none" <?php if (get_option('zmail_integ_from_email_id') === "none") { ?> selected="selected" <?php } ?>>Please select From Email Address</option>

                    <?php
                    foreach ($jsonbodyAccounts->data as $account) {
                      foreach ($account->sendMailDetails as $mailDetail) {
                        $fromAddress = $mailDetail->fromAddress;
                        $isSelected = ($fromAddress === get_option('zmail_integ_from_email_id')) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr($fromAddress) . '" ' . $isSelected . '>' . esc_html($fromAddress) . '</option>';
                      }
                    ?>
                    <?php
                    }
                    ?>

                  </select>
                </div>
                <div class="form__row">
                  <label class="form--label">From Name</label>
                  <input type="text" name="zmail_integ_from_name" value="<?php echo esc_attr(get_option('zmail_integ_from_name')) ?>" id="zmail_integ_from_name" required onchange="this.form.submit()" /> <br><i class="form__row-info">The name which will be used as the from name when sending an email</i>
                </div>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>
      </div>
    </form>
  </body>
  <?php

}


function update_access_token()
{
  if (empty(get_option('zmail_integ_timestamp')) || time() - get_option('zmail_integ_timestamp') > 3000) {
    if (!base64_decode(get_option('zmail_refresh_token'), true)) {
      update_option('zmail_refresh_token', base64_encode(get_option('zmail_refresh_token')), false);
    }

    update_option('zmail_integ_timestamp', time(), false);

    $urlUsingRefreshToken = 'https://accounts.' . zm_get_domain_name() . '/oauth/v2/token?refresh_token=' . base64_decode(get_option('zmail_refresh_token')) . '&grant_type=refresh_token&client_id=' . get_option('zmail_integ_client_id') . '&client_secret=' . get_option('zmail_integ_client_secret') . '&redirect_uri=' . admin_url() . 'admin.php?page=zmail-integ-settings&scope=VirtualOffice.messages.CREATE,VirtualOffice.accounts.READ';
    $bodyAccessTok = wp_remote_retrieve_body(wp_remote_post($urlUsingRefreshToken));
    $respoJs = json_decode($bodyAccessTok);
    update_option('zmail_access_token', $respoJs->access_token, false);
  }
}



function zmail_validate_email($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

function zmail_validate_domain($domain)
{
  return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $domain : false;
}

function zmail_validate_client_id($input)
{
  return preg_match('/^[A-Z0-9.]+$/', $input) ? $input : false;
}

function zmail_validate_client_secret($secret)
{
  return preg_match('/^[a-z0-9]+$/', $secret) ? $secret : false;
}

function zmail_validate_url($url)
{
  return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function zmail_validate_content_type($input)
{
  $allowed_values = ['html', 'plaintext'];
  return in_array(strtolower($input), $allowed_values, true) ? strtolower($input) : false;
}

function zmail_validate_from_name($input)
{
  $input = trim($input);
  if (preg_match('/^[\p{L}\p{N} ._-]{1,50}$/u', $input)) {
    return $input;
  }
  return false;
}

function zmail_send_mail_callback()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized access', 'zoho-mail'), '', array('response' => 403));
  }
  $option = get_option('zmail_account_id');
  if (!empty($option)) {
    if (isset($_POST['zmail_integ_send_mail_submit']) && !empty($_POST)) {
      $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
      if (!wp_verify_nonce($nonce, 'zmail_send_mail_nonce')) {
        wp_die(
          esc_html__('Security check failed. Please reload the page.', 'zoho-mail'),
          esc_html__('Error', 'zoho-mail'),
          ['response' => 403]
        );
      } else {
        $toAddressTest = zmail_validate_email(sanitize_email($_POST['zmail_integ_to_address']));
        $subjectTest = isset($_POST['zmail_integ_subject']) ? sanitize_text_field($_POST['zmail_integ_subject']) : '';
        $subjectTest = str_replace(["\r", "\n", "%0a", "%0d"], '', $subjectTest);

        $contentTest = isset($_POST['zmail_integ_content']) ? sanitize_textarea_field($_POST['zmail_integ_content']) : '';

        if ($toAddressTest === false || empty($toAddressTest)) {
          echo '<div class="error"><p><strong>' .
            esc_html__('Invalid email address provided.', 'zoho-mail') .
            '</strong></p></div>' . "\n";
        } else if (wp_mail($toAddressTest, $subjectTest, $contentTest, '', array())) {
          echo '<div class="updated"><p><strong>' . esc_html__('Mail Sent Successfully') . '</strong></p></div>' . "\n";
        } else {
          echo '<div class="error"><p><strong>' . esc_html__('Mail Sending Failed.') . '</strong></p></div>' . "\n";
        }
      }
    }
  ?>

    <head>
      <meta charset="UTF-8">
      <title>Zoho Mail</title>
    </head>

    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin.php?page=zmail-send-mail')); ?>">
      <?php wp_nonce_field('zmail_send_mail_nonce'); ?>

      <body>
        <div class="page">
          <div class="page__content">
            <div class="page__header">
              <h1>Send Mail <span class="ico-send"></span></h1>
            </div>
            <div class="form">
              <div class="form__row">
                <label class="form--label">To</label>
                <input type="email" class="form--input" name="zmail_integ_to_address" required="required" />
              </div>
              <div class="form__row">
                <label class="form--label">Subject</label>
                <input type="text" class="form--input" name="zmail_integ_subject" required="required" />
              </div>
              <div class="form__row">
                <label class="form--label">Content</label>
                <input type="text" class="form--input" name="zmail_integ_content" />
              </div>
              <div class="form__row form__row-btn"> <input type="submit" class="btn" name="zmail_integ_send_mail_submit" id="zmail_integ_send_mail_submit" value="<?php esc_attr_e('Send Mail'); ?>">

              </div>
            </div>
          </div>
        </div>
      </body>
    </form>
<?php

  } else {
    echo '<div class="error"><p><strong>' . __('Configure Your Account.') . '</strong></p></div>' . "\n";
  }
}


if (!function_exists('wp_mail')) {
  function wp_mail($to, $subject, $message, $headers = '', $attachments = array())
  {

    $atts = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));

    if (isset($atts['to'])) {
      $to = $atts['to'];
    }
    if (!is_array($to)) {
      $to = explode(',', $to);
    }
    if (isset($atts['subject'])) {
      $subject = $atts['subject'];
    }
    if (isset($atts['message'])) {
      $message = $atts['message'];
    }
    if (isset($atts['headers'])) {
      $headers = $atts['headers'];
    }
    if (isset($atts['attachments'])) {
      $attachments = $atts['attachments'];
    }

    if (! is_array($attachments) && !empty($attachments)) {
      $attach[] = str_replace("\r\n", "\n", $attachments);
      $attachments = implode("\n", $attach);
    }

    $content_type = null;
    // Headers
    $cc = $bcc = $reply_to = array();
    $from_name = '';
    $from_email = '';

    if (empty($headers)) {
      $headers = array();
    } else {

      if (!is_array($headers) && !empty($headers)) {
        // Explode the headers out, so this function can take both
        // string headers and an array of headers.
        $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
      } else {
        $tempheaders = $headers;
      }
      $headers = array();
      // If it's actually got contents
      if (!empty($tempheaders)) {
        // Iterate through the raw headers
        foreach ((array) $tempheaders as $header) {
          if (strpos($header, ':') === false) {
            if (false !== stripos($header, 'boundary=')) {
              $parts = preg_split('/boundary=/i', trim($header));
              $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
            }
            continue;
          }
          // Explode them out
          list($name, $content) = explode(':', trim($header), 2);

          // Cleanup crew
          $name    = trim($name);
          $content = trim($content);
          $content_type = null;
          switch (strtolower($name)) {
            case 'content-type':
              if (strpos($content, ';') !== false) {
                list($type, $charset_content) = explode(';', $content);
                $content_type = trim($type);
                if (false !== stripos($charset_content, 'charset=')) {
                  $charset = trim(str_replace(array('charset=', '"'), '', $charset_content));
                } elseif (false !== stripos($charset_content, 'boundary=')) {
                  $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset_content));
                  $charset = '';
                }

                // Avoid setting an empty $content_type.
              } elseif ('' !== trim($content)) {
                $content_type = trim($content);
              }
              break;
            case 'cc':
              $cc = array_merge((array) $cc, explode(',', $content));
              break;
            case 'bcc':
              $bcc = array_merge((array) $bcc, explode(',', $content));
              break;
            case 'reply-to':
              $reply_to = array_merge((array) $reply_to, explode(',', $content));
              break;
            case 'from':
              if (preg_match('/^(.+?)\s*<(.+?)>$/', $content, $matches)) {
                $from_name = trim($matches[1]);
                $from_email = trim($matches[2]);
              } elseif (preg_match('/^(.+?)$/', $content, $matches)) {
                $from_email = trim($matches[1]);
              }
              break;
            default:
              $headers[trim($name)] = trim($content);
              break;
          }
        }
      }
    }
    $content_type = apply_filters('wp_mail_content_type', $content_type);
    $data = array();
    if (!empty($from_name)) {
      $from_name = sanitize_text_field($from_name);
      $from_name = str_replace(["\r", "\n", "<", ">", "\0"], '', $from_name);
      $data['fromAddress'] = $from_name . ' <' . get_option('zmail_integ_from_email_id') . '>';
    } else {
      $data['fromAddress'] = get_option('zmail_integ_from_name') . ' <' . get_option('zmail_integ_from_email_id') . '>';
    }

    if (sizeof($bcc) > 0) {
      $bcc = array_filter(array_map('sanitize_email', $bcc));
      if (!empty($bcc)) {
        $data['bccAddress'] = implode(',', $bcc);
      }
    }
    if (sizeof($cc) > 0) {
      $cc = array_filter(array_map('sanitize_email', $cc));
      if (!empty($cc)) {
        $data['ccAddress'] = implode(',', $cc);
      }
    }

    if (!empty($reply_to)) {
      if (get_option('zmail_integ_from_email_id') === $to[0] && sizeof($to) === 1) {
        $start = stripos($reply_to[0], '<');
        $length = strlen($reply_to[0]) - 1 - $start;
        if ($start > 1) {
          $shortString = substr($reply_to[0], $start + 1, $length - 1);
        } else {
          $shortString = $reply_to[0];
        }
        $data['replyTo'] = sanitize_email($shortString);
      }
    }
    if (!base64_decode(get_option('zmail_refresh_token'), true)) {
      update_option('zmail_refresh_token', base64_encode(get_option('zmail_refresh_token')), false);
    }
    if (!empty(get_option('zmail_auth_code'))) {
      delete_option('zmail_auth_code');
    }

    $allowed_upload_dir = wp_upload_dir();
    $allowed_dir = realpath($allowed_upload_dir['basedir']);

    $data['subject'] = str_replace(["\r", "\n", "%0a", "%0d"], '', $subject);
    $data['content'] = $message;
    $to = array_filter(array_map('sanitize_email', $to));

    if (empty($to)) {
      do_action('wp_mail_failed', new WP_Error('wp_mail_failed', 'No valid recipient email addresses.', array()));
      return false;
    }

    $toAddresses = implode(',', $to);
    $data['toAddress'] = $toAddresses;

    update_access_token();

    if (!empty($attachments)) {
      $attachmentJSONArr = array();
      $data['attachments'] = $attachments;
      $headers1 = array(
        'Authorization' => 'Zoho-oauthtoken ' . get_option('zmail_access_token'),
        'Content-Type' => 'application/octet-stream',
        'User-Agent' => 'zm_wordpress'
      );
      $count = 0;
      $flag = true;
      $max_file_size = 10 * 1024 * 1024;

      foreach ($attachments as $attfile) {
        // Normalize and sanitize path
        $attfile = wp_normalize_path($attfile);

        // Check for null bytes
        if (strpos($attfile, "\0") !== false) {
          continue;
        }

        $real_path = realpath($attfile);

        // Verify path is valid and within allowed directory
        if (!$real_path || strpos($real_path, $allowed_dir) !== 0) {
          continue;
        }

        // Don't follow symlinks
        if (is_link($attfile)) {
          continue;
        }

        // Verify file exists, is readable, and is a regular file
        if (!file_exists($real_path) || !is_readable($real_path) || !is_file($real_path)) {
          continue;
        }

        // Check file size
        $file_size = filesize($real_path);
        if ($file_size === false || $file_size > $max_file_size) {
          continue;
        }

        $fileName = sanitize_file_name(basename($real_path));
        $attachurl = 'https://mail.' . zm_get_domain_name() . '/api/accounts/' .
          get_option('zmail_account_id') . '/messages/attachments' .
          '?fileName=' . urlencode($fileName);

        $file_contents = file_get_contents($real_path);
        if ($file_contents === false) {
          continue;
        }

        $args = array(
          'body' => $file_contents,
          'headers' => $headers1,
          'method' => 'POST',
          'timeout' => 30
        );

        $resultatt = wp_remote_post($attachurl, $args);

        if (is_wp_error($resultatt)) {
          $flag = false;
          continue;
        }

        $responseSending = wp_remote_retrieve_body($resultatt);
        $http_code = wp_remote_retrieve_response_code($resultatt);

        if ($http_code === 200) {
          $responseattachjson = json_decode($responseSending);

          if (json_last_error() !== JSON_ERROR_NONE) {
            $flag = false;
            continue;
          }

          if (
            empty($responseattachjson->data) ||
            empty($responseattachjson->data->storeName) ||
            empty($responseattachjson->data->attachmentPath) ||
            empty($responseattachjson->data->attachmentName)
          ) {
            $flag = false;
            continue;
          }

          $attachmentupload = array(
            'storeName' => sanitize_text_field($responseattachjson->data->storeName),
            'attachmentPath' => sanitize_text_field($responseattachjson->data->attachmentPath),
            'attachmentName' => sanitize_file_name($responseattachjson->data->attachmentName)
          );

          $attachmentJSONArr[$count] = $attachmentupload;
          $count++;
        } else {
          $flag = false;
        }
      }

      if ($flag && !empty($attachmentJSONArr)) {
        $data['attachments'] = $attachmentJSONArr;
      }
    }
    if ($content_type === 'text/html' || get_option('zmail_content_type') === 'html') {
      $data['mailFormat'] = 'html';
    } else {
      $data['mailFormat'] = 'plaintext';
    }
    $headers1 = array(
      'Authorization' => 'Zoho-oauthtoken ' . get_option('zmail_access_token'),
      'Content-Type' => 'application/json',
      'User-Agent' => 'zm_wordpress'
    );

    $data_string = json_encode($data);
    $args = array(
      'body' => $data_string,
      'headers' => $headers1,
      'method' => 'POST',
      'timeout' => 30
    );

    // Define the mail_data variable
    $mail_data = array(
      'to' => $to,
      'subject' => $subject,
      'message' => $message,
      'headers' => $headers,
      'attachments' => $attachments
    );

    $account_id = get_option('zmail_account_id');

    if (empty($account_id)) {
      do_action('wp_mail_failed', new WP_Error('wp_mail_failed', 'Zoho Mail Account ID is missing.', $mail_data));
      return false;
    }


    $urlToSend = 'https://mail.' . zm_get_domain_name() . '/api/accounts/' . get_option('zmail_account_id') . '/messages';
    $responseSending = wp_remote_post($urlToSend, $args);

    if (is_wp_error($responseSending)) {
      do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $responseSending->get_error_message(), $mail_data));
      return false;
    }

    $http_code = wp_remote_retrieve_response_code($responseSending);

    if ($http_code === 200) {
      return true;
    } elseif ($http_code === 500) {
      $responseBody = wp_remote_retrieve_body($responseSending);
      $responseData = json_decode($responseBody);

      $moreInfo = 'Unknown error';
      if (
        json_last_error() === JSON_ERROR_NONE &&
        isset($responseData->data->moreInfo)
      ) {
        $moreInfo = $responseData->data->moreInfo;
      }

      do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $moreInfo, $mail_data));
      return false;
    }

    $responseBody = wp_remote_retrieve_body($responseSending);
    do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $responseBody, $mail_data));
    return false;
  }
}
