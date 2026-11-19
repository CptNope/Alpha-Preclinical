<?php
/**
 * Connects the design's quote and careers forms to the theme's form handler
 * (inc/forms.php: nonce, honeypot, fill-time check, rate limit, saved inquiries).
 *
 * The markup stays the design's; this adds what a real submission needs: method,
 * action, field names, the nonce, honeypot and start time. After a submission the
 * page shows the design's own confirmation or an error message.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Forms in section blocks: block name => form type.
 *
 * @return array<string,string>
 */
function alpha_section_forms() {
	return array(
		'alpha/contact-form'  => 'quote',
		'alpha/careers-apply' => 'careers',
	);
}

/**
 * Wire a section's form to the handler.
 *
 * @param string   $html       Section markup.
 * @param array    $attributes Attributes.
 * @param WP_Block $block      Block.
 * @return string
 */
function alpha_section_form_html( $html, $attributes, $block ) {
	$forms = alpha_section_forms();
	if ( ! isset( $forms[ $block->name ] ) || false === strpos( $html, '<form data-demo-form' ) ) {
		return $html;
	}
	$type = $forms[ $block->name ];

	// Field names the handler reads (the careers form in the design only named the role).
	if ( 'careers' === $type ) {
		$names = array(
			'a-fn'   => 'first_name',
			'a-ln'   => 'last_name',
			'a-em'   => 'email',
			'a-ph'   => 'phone',
			'a-cv'   => 'resume',
			'a-note' => 'message',
		);
		foreach ( $names as $id => $name ) {
			$html = preg_replace( '/(<(?:input|textarea) id="' . preg_quote( $id, '/' ) . '")(?![^>]*\sname=)/', '$1 name="' . $name . '"', $html, 1 );
		}
		$html = alpha_careers_role_options( $html );
	}

	$return = get_permalink();
	$hidden = '<input type="hidden" name="action" value="alpha_form">'
		. '<input type="hidden" name="form_type" value="' . esc_attr( $type ) . '">'
		. '<input type="hidden" name="return_to" value="' . esc_url( $return ) . '">'
		. '<input type="hidden" name="started" value="' . esc_attr( (string) time() ) . '">'
		. wp_nonce_field( 'alpha_form_' . $type, 'alpha_form_nonce', false, false )
		. '<div class="sr-only" aria-hidden="true"><label for="alpha-website-' . esc_attr( $type ) . '">Website</label><input type="text" id="alpha-website-' . esc_attr( $type ) . '" name="website" tabindex="-1" autocomplete="off"></div>';
	$open   = '<form data-demo-form data-wp-form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"'
		. ( 'careers' === $type ? ' enctype="multipart/form-data"' : '' ) . ' novalidate>' . $hidden;
	$html   = str_replace( '<form data-demo-form novalidate>', $open, $html );

	// Result of the last submission (?alpha_form=sent|error), shown in the design's own
	// panel. The handler adds a nonce to that redirect; display only, no capability needed
	// (visitors submit these forms).
	$state = '';
	if ( isset( $_GET['alpha_form'], $_GET['alpha_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['alpha_nonce'] ) ), 'alpha_form_result' ) ) {
		$state = sanitize_key( wp_unslash( $_GET['alpha_form'] ) );
	}
	if ( 'sent' === $state ) {
		$done  = alpha_form_done_details();
		$html  = str_replace( $open, str_replace( '<form ', '<form hidden ', $open ), $html );
		$html  = str_replace( '<div class="done" role="status" data-done hidden>', '<div class="done" role="status" data-done tabindex="-1">', $html );
		$html  = preg_replace( '/<span data-email-out(?:="")?><\/span>/', '<span data-email-out>' . esc_html( $done['email'] ? $done['email'] : __( 'you', 'alpha-preclinical' ) ) . '</span>', $html );
		$html  = preg_replace( '/<span data-role-out(?:="")?><\/span>/', '<span data-role-out>' . esc_html( $done['role'] ? $done['role'] : __( 'this role', 'alpha-preclinical' ) ) . '</span>', $html );
	} elseif ( 'error' === $state ) {
		$msg  = '<p class="form-error" role="alert">' . esc_html__( 'We could not send that. Please check the required fields and try again, or email us directly.', 'alpha-preclinical' ) . '</p>';
		$html = str_replace( $open, $msg . $open, $html );
	}
	return $html;
}
add_filter( 'alpha_section_html', 'alpha_section_form_html', 10, 3 );

/**
 * The role dropdown lists the open roles from Careers › Open roles (falls back to the
 * design's options when none are published).
 *
 * @param string $html Section markup.
 * @return string
 */
function alpha_careers_role_options( $html ) {
	$jobs = get_posts(
		array(
			'post_type'      => 'job',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);
	if ( ! $jobs ) {
		return $html;
	}
	return preg_replace_callback(
		'/(<select id="a-role" name="role"[^>]*>)(.*?)(<\/select>)/s',
		function ( $m ) use ( $jobs ) {
			$opts = '';
			foreach ( $jobs as $job ) {
				$opts .= '<option>' . esc_html( get_the_title( $job ) ) . '</option>';
			}
			// Keep the design's final "general application" option if it has one.
			if ( preg_match( '/<option[^>]*>[^<]*(?:general|other|not listed)[^<]*<\/option>/i', $m[2], $g ) ) {
				$opts .= $g[0];
			}
			return $m[1] . $opts . $m[3];
		},
		$html,
		1
	);
}

/**
 * Details for the confirmation panel, kept for ten minutes under a random token so
 * no personal data goes into the URL.
 *
 * @return array{email:string,role:string}
 */
function alpha_form_done_details() {
	$out   = array(
		'email' => '',
		'role'  => '',
	);
	// Called only after alpha_section_form_html() verified the result nonce.
	$token = isset( $_GET['alpha_ref'] ) ? sanitize_key( wp_unslash( $_GET['alpha_ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified by the caller.
	if ( $token ) {
		$saved = get_transient( 'alpha_done_' . $token );
		if ( is_array( $saved ) ) {
			$out = array_merge( $out, array_intersect_key( $saved, $out ) );
		}
	}
	return $out;
}

/**
 * Store the confirmation details for a successful submission and return the token.
 *
 * @param string $email Email.
 * @param string $role  Role (careers).
 * @return string
 */
function alpha_form_remember_done( $email, $role ) {
	$token = strtolower( wp_generate_password( 20, false ) );
	set_transient(
		'alpha_done_' . $token,
		array(
			'email' => $email,
			'role'  => $role,
		),
		10 * MINUTE_IN_SECONDS
	);
	return $token;
}
