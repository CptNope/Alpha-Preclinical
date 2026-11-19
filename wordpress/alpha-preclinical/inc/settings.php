<?php
/**
 * Site details (Settings > Alpha Preclinical): contact facts used across the site,
 * quote-form routing and the accessibility response time. Everything here is
 * available to blocks through the `alpha/site` binding source.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults, from the approved design. Empty values are not shown on the site.
 *
 * @return array
 */
function alpha_site_defaults() {
	return array(
		'legal_name'        => 'Alpha Preclinical LLC',
		'email'             => 'info@alphapreclinical.com',
		'phone'             => '',
		'street'            => '722 Plantation Street',
		'locality'          => 'Worcester',
		'region'            => 'MA',
		'postal_code'       => '01605',
		'founded'           => '2020',
		'linkedin'          => 'https://www.linkedin.com/company/alpha-preclinical',
		'map_url'           => 'https://www.google.com/maps/dir/?api=1&destination=722+Plantation+Street+Worcester+MA+01605',
		'quote_inbox'       => '',
		'careers_inbox'     => '',
		'routing'           => '',
		'a11y_response'     => '',
		'tagline'           => 'Tailored in vivo research for your innovation.',
		'redirects'         => '',
	);
}

/**
 * One site detail.
 *
 * @param string $key Setting key.
 * @return string
 */
function alpha_site( $key ) {
	$opts = wp_parse_args( (array) get_option( 'alpha_site', array() ), alpha_site_defaults() );
	return isset( $opts[ $key ] ) ? (string) $opts[ $key ] : '';
}

/**
 * Register the setting.
 */
function alpha_register_settings() {
	register_setting(
		'alpha_site',
		'alpha_site',
		array(
			'type'              => 'object',
			'sanitize_callback' => 'alpha_sanitize_site',
			'default'           => alpha_site_defaults(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'alpha_register_settings' );
add_action( 'rest_api_init', 'alpha_register_settings' );

/**
 * Sanitize the settings array.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function alpha_sanitize_site( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( alpha_site_defaults() as $key => $default ) {
		$val = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
		switch ( $key ) {
			case 'email':
			case 'quote_inbox':
			case 'careers_inbox':
				$out[ $key ] = sanitize_email( $val );
				break;
			case 'linkedin':
			case 'map_url':
				$out[ $key ] = esc_url_raw( $val );
				break;
			case 'routing':
			case 'redirects':
				$out[ $key ] = sanitize_textarea_field( $val );
				break;
			default:
				$out[ $key ] = sanitize_text_field( $val );
		}
	}
	return $out;
}

/**
 * Settings page.
 */
function alpha_settings_menu() {
	add_options_page(
		__( 'Alpha Preclinical', 'alpha-preclinical' ),
		__( 'Alpha Preclinical', 'alpha-preclinical' ),
		'manage_options',
		'alpha-site',
		'alpha_render_settings_page'
	);
}
add_action( 'admin_menu', 'alpha_settings_menu' );

/**
 * Render the settings page. options.php checks the nonce and capability.
 */
function alpha_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opts   = wp_parse_args( (array) get_option( 'alpha_site', array() ), alpha_site_defaults() );
	$fields = array(
		'legal_name'    => array( __( 'Legal name', 'alpha-preclinical' ), 'text', '' ),
		'email'         => array( __( 'Public email', 'alpha-preclinical' ), 'email', '' ),
		'phone'         => array( __( 'Public phone', 'alpha-preclinical' ), 'text', __( 'Leave empty to keep the phone number off the site.', 'alpha-preclinical' ) ),
		'street'        => array( __( 'Street address', 'alpha-preclinical' ), 'text', '' ),
		'locality'      => array( __( 'City', 'alpha-preclinical' ), 'text', '' ),
		'region'        => array( __( 'State', 'alpha-preclinical' ), 'text', '' ),
		'postal_code'   => array( __( 'ZIP code', 'alpha-preclinical' ), 'text', '' ),
		'founded'       => array( __( 'Year founded', 'alpha-preclinical' ), 'text', '' ),
		'linkedin'      => array( __( 'Company LinkedIn URL', 'alpha-preclinical' ), 'url', '' ),
		'map_url'       => array( __( 'Map link', 'alpha-preclinical' ), 'url', '' ),
		'tagline'       => array( __( 'Tagline', 'alpha-preclinical' ), 'text', '' ),
		'quote_inbox'   => array( __( 'Quote requests go to', 'alpha-preclinical' ), 'email', __( 'Default inbox for the quote form. Empty = the public email.', 'alpha-preclinical' ) ),
		'routing'       => array( __( 'Route by study type', 'alpha-preclinical' ), 'textarea', __( 'One per line: Study type = email@example.com. Unlisted types go to the default inbox.', 'alpha-preclinical' ) ),
		'careers_inbox' => array( __( 'Job applications go to', 'alpha-preclinical' ), 'email', __( 'Empty = the default quote inbox.', 'alpha-preclinical' ) ),
		'a11y_response' => array( __( 'Accessibility response time', 'alpha-preclinical' ), 'text', __( 'Shown on the Accessibility statement, e.g. "within 2 business days".', 'alpha-preclinical' ) ),
		'redirects'     => array( __( 'Extra redirects', 'alpha-preclinical' ), 'textarea', __( 'One per line: /old-path = /new-path/. Added to the built-in Wix redirects (inc/redirects.php).', 'alpha-preclinical' ) ),
	);
	echo '<div class="wrap"><h1>' . esc_html__( 'Alpha Preclinical site details', 'alpha-preclinical' ) . '</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'alpha_site' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $key => $field ) {
		list( $label, $type, $help ) = $field;
		$id = 'alpha-site-' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea class="large-text code" rows="6" id="' . esc_attr( $id ) . '" name="alpha_site[' . esc_attr( $key ) . ']">' . esc_textarea( $opts[ $key ] ) . '</textarea>';
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="alpha_site[' . esc_attr( $key ) . ']" value="' . esc_attr( $opts[ $key ] ) . '">';
		}
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form></div>';
}

/**
 * Inbox for a study type, from the routing rules.
 *
 * @param string $study Study type.
 * @return string Email address.
 */
function alpha_route_inbox( $study ) {
	$default = alpha_site( 'quote_inbox' );
	if ( '' === $default ) {
		$default = alpha_site( 'email' );
	}
	if ( '' === $default ) {
		$default = get_option( 'admin_email' );
	}
	foreach ( preg_split( '/\r\n|\r|\n/', alpha_site( 'routing' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( 2 === count( $parts ) && 0 === strcasecmp( $parts[0], $study ) && is_email( $parts[1] ) ) {
			return $parts[1];
		}
	}
	return $default;
}
