<?php

/**
 *
 * Generate a random 4 digit code
 *
 * @param void
 * @return str a 4 digit code
 **/

function bdpwr_generate_4_digit_code() {

	// Always generate a 4-digit numeric code
	$length = 4;
	$selection_string = '0123456789';

	$max_index = strlen($selection_string) - 1;
	$code = '';

	for ($i = 0; $i < $length; $i++) {
		$index = random_int(0, $max_index);
		$code .= $selection_string[$index];
	}

	return $code;
}



/**
 *
 * Get new code expiration time
 *
 * @param void
 * @return int the unix timestamp for a code expiry
 **/

function bdpwr_get_new_code_expiration_time() {

	/**
	*
	* Filter the number of seconds codes should be valid for
	* Set -1 for no expiry
	*
	* @param $seconds int the number of seconds the code will be valid for
	*/

	$valid_seconds = apply_filters( 'bdpwr_code_expiration_seconds', 900 );
	$time_string   = '+' . $valid_seconds . ' seconds';
	return strtotime( $time_string );
}


/**
 *
 * Get date from unix timestamp
 *
 * @param $time str the unix timestamp
 * @return str the formatted date
 **/

function bdpwr_get_formatted_date( $time = false ) {

	if ( ! $time ) {
		$time = strtotime( 'now' );
	}

	/**
	*
	* Filter the date format used in this plugin
	*
	* @param $format str the php date format string
	*/

	$format = apply_filters( 'bdpwd_date_format', 'd M Y h:i A' );

	$date = new DateTime();
	$date->setTimestamp( $time );
	$date->setTimezone( wp_timezone() );

	return date_format( $date, $format );
}


/**
 *
 * Get a list of the roles allowed to reset their password with this plugin
 *
 * @param void
 * @return arr an array of role slugs
 **/

function bdpwr_get_allowed_roles() {

	$all_roles   = wp_roles()->roles;
	$roles_array = array();

	foreach ( $all_roles as $slug => $role ) {

		if( $slug === 'administrator' ) {
			continue;
		}

		$roles_array[] = $slug;

	}

	/**
	*
	* Filter the roles allowed to use this plugin to reset a password
	*
	* @param $roles arr the array of allowed roles
	*/

	return apply_filters( 'bdpwr_allowed_roles', $roles_array );
}


/**
 *
 * Get a user
 *
 * @param $user_id int the ID of the WP User
 * @return obj a BDPWR_User user object
 **/

function bdpwr_get_user( $user_id = false ) {
	return new BDPWR_User( $user_id );
}


/**
 *
 * Send a password reset code email
 *
 * @param $email str the email address to send to
 * @param $code the code to send
 * @param $expiry int the time that the code will expire
 * @param $user_name str the user's display name
 * @return bool true on success false on failure
 **/

function bdpwr_send_password_reset_code_email( $email = false, $code = false, $expiry = 0, $user_name = '' ) {

	if ( ! $email ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		throw new Exception( __( 'An email address is required for the reset code email.', 'bdvs-password-reset' ) );
	}

	if ( ! $code ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		throw new Exception( __( 'No code was provided for the password reset email.', 'bdvs-password-reset' ) );
	}

	// Format the expiry date as Y-m-d H:i:s
	$expiry_date = '';
	if ( $expiry !== 0 ) {
		$expiry_datetime = new DateTime();
		$expiry_datetime->setTimestamp( $expiry );
		$expiry_datetime->setTimezone( wp_timezone() );
		$expiry_date = $expiry_datetime->format( 'Y-m-d H:i:s' );
	}

	// Get frontend URL
	$frontend_url = defined( 'FRONTEND_URL' ) ? FRONTEND_URL : '';

	ob_start(); ?>

Hi <?php echo htmlspecialchars( $user_name, ENT_QUOTES, 'UTF-8' ); ?>, Anda meminta untuk reset kata sandi.

Gunakan kode reset berikut:

<?php echo htmlspecialchars( $code, ENT_QUOTES, 'UTF-8' ); ?>

Kode tersebut akan expired pada <?php echo htmlspecialchars( $expiry_date, ENT_QUOTES, 'UTF-8' ); ?>

Untuk melanjutkan reset kata sandi, kunjungi link: <?php echo htmlspecialchars( $frontend_url, ENT_QUOTES, 'UTF-8' ); ?>/account/reset-password?<?php echo urlencode( $email ); ?>

Masukkan alamat email Anda dan kode di atas untuk mengatur kata sandi baru. Jika Anda tidak meminta pengaturan ulang kata sandi ini, silakan abaikan email ini.

Hisensepromo.id

	<?php
	$text = ob_get_contents();
	if ( $text ) {
		ob_end_clean(); }

	/**
	*
	* Filter the subject of the email
	*
	* @param $subject str the subject of the email
	*/

	$subject = apply_filters( 'bdpwr_code_email_subject', 'Password Reset' );

	/**
	*
	* Filter the body of the email
	*
	* @param $text str the content of the email
	* @param $email str the email address being sent to
	* @param $code the code being sent
	* @param $expiry int the unix timestamp for the code's expiry
	* @param $user_name str the user's display name
	*/

	$text = apply_filters( 'bdpwr_code_email_text', $text, $email, $code, $expiry, $user_name );

	return wp_mail( $email, $subject, $text );
}


/**
*
* BACKWARDS COMPATIBILITY FILLS
*
* The following declares new functions available from WP 5.3.0
* in the case that these have not already been declared, i.e. WP is < 5.3.0
*/

/**
*
* Retrieves the timezone from site settings as a string.
*
* Uses the `timezone_string` option to get a proper timezone if available,
* otherwise falls back to an offset.
*
* @since 5.3.0
*
* @return string PHP timezone string or a ±HH:MM offset.
*/

if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

/**
*
* Retrieves the timezone from site settings as a `DateTimeZone` object.
*
* Timezone can be based on a PHP timezone string or a ±HH:MM offset.
*
* @return DateTimeZone Timezone object.
*/

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}
