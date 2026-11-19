<?php
/**
 * Form handling for the alpha/quote-form block.
 *
 * Every submission: nonce verified, honeypot and minimum fill time checked, rate
 * limited per visitor, fields sanitized, then emailed to the inbox for its study type
 * (Settings > Alpha Preclinical) and kept as a private "Inquiry" so nothing is lost
 * if mail fails. Resumes are type- and size-checked, attached, then deleted.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private store of submissions (admins only).
 */
function alpha_register_inquiries() {
	register_post_type(
		'alpha_inquiry',
		array(
			'labels'          => array(
				'name'          => __( 'Inquiries', 'alpha-preclinical' ),
				'singular_name' => __( 'Inquiry', 'alpha-preclinical' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 25,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array(
				'create_posts' => 'do_not_allow',
				'edit_posts'   => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'delete_posts' => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts'  => 'manage_options',
				'publish_posts' => 'manage_options',
			),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'alpha_register_inquiries' );

/**
 * Handle a submission. Public endpoint: logged-in and logged-out visitors.
 */
function alpha_handle_form() {
	$type   = isset( $_POST['form_type'] ) && 'careers' === $_POST['form_type'] ? 'careers' : 'quote'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read only to pick which nonce to verify, next line.
	$nonce  = isset( $_POST['alpha_form_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['alpha_form_nonce'] ) ) : '';
	$return = isset( $_POST['return_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return_to'] ) ), home_url( '/' ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated redirect target only.
	$anchor = 'careers' === $type ? '#apply' : '#form';

	$fail = static function ( $code ) use ( $return, $anchor ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'alpha_form'  => $code,
					'alpha_nonce' => wp_create_nonce( 'alpha_form_result' ),
				),
				$return
			) . $anchor
		);
		exit;
	};

	// Permission: these are public forms (visitors are not logged in), so the checks are
	// the nonce, the honeypot and fill time, and the rate limit below.
	if ( ! wp_verify_nonce( $nonce, 'alpha_form_' . $type ) ) {
		$fail( 'error' );
	}

	// Bots: honeypot filled, or the form submitted in under three seconds. Pretend success.
	$started = isset( $_POST['started'] ) ? absint( $_POST['started'] ) : 0;
	if ( ! empty( $_POST['website'] ) || ( $started && time() - $started < 3 ) ) {
		$fail( 'sent' );
	}

	// Rate limit: 5 submissions per visitor per 10 minutes.
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rl_key  = 'alpha_rl_' . md5( $ip . wp_salt( 'nonce' ) );
	$attempt = (int) get_transient( $rl_key );
	if ( $attempt >= 5 ) {
		$fail( 'error' );
	}
	set_transient( $rl_key, $attempt + 1, 10 * MINUTE_IN_SECONDS );

	$f = array();
	foreach ( array( 'first_name', 'last_name', 'company', 'position', 'phone', 'study_type', 'role' ) as $key ) {
		$f[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}
	$f['email']   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$f['message'] = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $f['first_name'] || '' === $f['last_name'] || ! is_email( $f['email'] ) ) {
		$fail( 'error' );
	}
	if ( 'quote' === $type && ( '' === $f['message'] || '' === $f['company'] ) ) {
		$fail( 'error' );
	}
	if ( 'quote' === $type && ! in_array( $f['study_type'], alpha_study_types(), true ) ) {
		$f['study_type'] = 'Something else';
	}

	$attachments = array();
	if ( 'careers' === $type ) {
		if ( empty( $_FILES['resume']['name'] ) ) {
			$fail( 'error' );
		}
		$attachments = alpha_handle_resume_upload();
		if ( ! $attachments ) {
			$fail( 'error' );
		}
	}

	$name    = $f['first_name'] . ' ' . $f['last_name'];
	$to      = 'careers' === $type ? ( alpha_site( 'careers_inbox' ) ? alpha_site( 'careers_inbox' ) : alpha_route_inbox( '' ) ) : alpha_route_inbox( $f['study_type'] );
	$subject = 'careers' === $type
		/* translators: 1: role, 2: applicant name. */
		? sprintf( __( 'Application: %1$s from %2$s', 'alpha-preclinical' ), $f['role'], $name )
		/* translators: 1: study type, 2: name, 3: company. */
		: sprintf( __( 'Quote request: %1$s from %2$s, %3$s', 'alpha-preclinical' ), $f['study_type'], $name, $f['company'] );

	$lines = array();
	$labels = array(
		'first_name' => __( 'First name', 'alpha-preclinical' ),
		'last_name'  => __( 'Last name', 'alpha-preclinical' ),
		'company'    => __( 'Company', 'alpha-preclinical' ),
		'position'   => __( 'Position', 'alpha-preclinical' ),
		'email'      => __( 'Email', 'alpha-preclinical' ),
		'phone'      => __( 'Phone', 'alpha-preclinical' ),
		'study_type' => __( 'Study type', 'alpha-preclinical' ),
		'role'       => __( 'Role', 'alpha-preclinical' ),
		'message'    => __( 'Message', 'alpha-preclinical' ),
	);
	foreach ( $labels as $key => $label ) {
		if ( '' !== $f[ $key ] ) {
			$lines[] = $label . ': ' . $f[ $key ];
		}
	}
	$lines[] = '';
	$lines[] = __( 'Sent from', 'alpha-preclinical' ) . ': ' . $return;
	$body    = implode( "\n", $lines );

	$sent = wp_mail(
		$to,
		$subject,
		$body,
		array( 'Reply-To: ' . str_replace( array( "\r", "\n" ), '', $name ) . ' <' . $f['email'] . '>' ),
		$attachments
	);

	wp_insert_post(
		array(
			'post_type'    => 'alpha_inquiry',
			'post_status'  => 'private',
			'post_title'   => $subject,
			'post_content' => esc_html( $body ) . ( $sent ? '' : "\n\n" . __( '[Email delivery failed: follow up from here.]', 'alpha-preclinical' ) ),
		)
	);

	foreach ( $attachments as $file ) {
		wp_delete_file( $file );
	}

	// Success: the confirmation panel can show the visitor's email and role.
	$token = function_exists( 'alpha_form_remember_done' ) ? alpha_form_remember_done( $f['email'], $f['role'] ) : '';
	wp_safe_redirect(
		add_query_arg(
			array_filter(
				array(
					'alpha_form'  => 'sent',
					'alpha_ref'   => $token,
					'alpha_nonce' => wp_create_nonce( 'alpha_form_result' ),
				)
			),
			$return
		) . $anchor
	);
	exit;
}
add_action( 'admin_post_alpha_form', 'alpha_handle_form' );
add_action( 'admin_post_nopriv_alpha_form', 'alpha_handle_form' );

/**
 * Validate and store an uploaded resume temporarily.
 *
 * @return string[] Paths of files to attach; empty on failure.
 */
function alpha_handle_resume_upload() {
	// Called only from alpha_handle_form() after its nonce check.
	if ( ! isset( $_FILES['resume'] ) || ! is_array( $_FILES['resume'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return array();
	}
	$file = $_FILES['resume']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked by wp_handle_upload below.
	if ( ! empty( $file['error'] ) || empty( $file['size'] ) || (int) $file['size'] > 5 * MB_IN_BYTES ) {
		return array();
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$mimes = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
		return array();
	}
	$moved = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => $mimes,
		)
	);
	return empty( $moved['file'] ) ? array() : array( $moved['file'] );
}
