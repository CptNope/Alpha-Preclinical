<?php
/**
 * Content review: reviewers can invite colleagues.
 *
 * A Client reviewer (or an administrator) enters a colleague's name and work email on
 * the Content review page. The theme creates a Client reviewer account and WordPress
 * emails the colleague a link to set their own password, so nobody shares credentials.
 *
 * Limits: staging only; only the Client reviewer role is ever created; reviewers can
 * only invite addresses on the allowed domains (Settings › Alpha Preclinical); existing
 * accounts are never changed; ten invitations per person per day; the site admin is
 * told about every invitation.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email domains reviewers may invite (Settings › Alpha Preclinical).
 *
 * @return string[]
 */
function alpha_review_invite_domains() {
	$raw = (string) alpha_site( 'reviewer_domains' );
	$out = array();
	foreach ( preg_split( '/[\s,]+/', strtolower( $raw ) ) as $d ) {
		$d = ltrim( trim( $d ), '@' );
		if ( '' !== $d && preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/', $d ) ) {
			$out[] = $d;
		}
	}
	return $out;
}

/**
 * Can this person invite this address?
 *
 * @param string $email Email.
 * @return bool
 */
function alpha_review_invite_allowed_email( $email ) {
	if ( current_user_can( 'create_users' ) ) {
		return true; // Administrators may add anyone (still only as a Client reviewer).
	}
	$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
	return in_array( $domain, alpha_review_invite_domains(), true );
}

/**
 * People who can answer the review (Client reviewers).
 *
 * @return WP_User[]
 */
function alpha_review_reviewers() {
	return get_users(
		array(
			'role'    => 'alpha_reviewer',
			'orderby' => 'display_name',
			'number'  => 100,
		)
	);
}

/**
 * The invite panel, added under the review.
 *
 * @return string
 */
function alpha_review_invite_panel() {
	$domains = alpha_review_invite_domains();
	$admin   = current_user_can( 'create_users' );
	$state   = '';
	$who     = '';
	if ( isset( $_GET['alpha_invite'], $_GET['alpha_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['alpha_nonce'] ) ), 'alpha_review_invited' ) ) {
		$state = sanitize_key( wp_unslash( $_GET['alpha_invite'] ) );
		$who   = isset( $_GET['alpha_who'] ) ? sanitize_text_field( wp_unslash( $_GET['alpha_who'] ) ) : '';
	}
	$messages = array(
		/* translators: %s: name. */
		'sent'    => array( 'ok', sprintf( __( 'Invitation sent. %s will get an email with a link to set a password, then can answer the review.', 'alpha-preclinical' ), $who ) ),
		/* translators: %s: name. */
		'nomail'  => array( 'warn', sprintf( __( 'The account for %s was created, but the email could not be sent from this site. Ask Jeremy to resend the login link.', 'alpha-preclinical' ), $who ) ),
		'exists'  => array( 'warn', __( 'That email already has an account. If they can’t answer the review, ask Jeremy to check their access.', 'alpha-preclinical' ) ),
		'domain'  => array( 'err', __( 'Only work email addresses on the allowed domain can be invited.', 'alpha-preclinical' ) ),
		'invalid' => array( 'err', __( 'Please enter a first name, last name and a valid email address.', 'alpha-preclinical' ) ),
		'limit'   => array( 'err', __( 'You have sent the most invitations allowed for today. Try again tomorrow or ask Jeremy.', 'alpha-preclinical' ) ),
	);

	$html  = '<section class="alpha-invite" id="invite" aria-labelledby="alpha-invite-h">';
	$html .= '<h2 id="alpha-invite-h">' . esc_html__( 'Invite a colleague', 'alpha-preclinical' ) . '</h2>';
	$html .= '<p class="alpha-invite__intro">' . esc_html__( 'Someone else should answer part of the review, or approve their own bio? Add them here. They get their own login by email; no passwords are shared.', 'alpha-preclinical' );
	if ( $domains && ! $admin ) {
		/* translators: %s: allowed email domains. */
		$html .= ' ' . esc_html( sprintf( __( 'Use their work address (%s).', 'alpha-preclinical' ), '@' . implode( ', @', $domains ) ) );
	}
	$html .= '</p>';
	if ( $state && isset( $messages[ $state ] ) ) {
		$html .= '<p class="alpha-invite__msg alpha-invite__msg--' . esc_attr( $messages[ $state ][0] ) . '" role="status">' . esc_html( $messages[ $state ][1] ) . '</p>';
	}
	if ( ! $domains && ! $admin ) {
		$html .= '<p class="alpha-invite__msg alpha-invite__msg--warn">' . esc_html__( 'Invitations are turned off. Ask Jeremy to add a colleague.', 'alpha-preclinical' ) . '</p>';
	} else {
		$html .= '<form class="alpha-invite__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="alpha_review_invite">'
			. wp_nonce_field( 'alpha_review_invite', 'alpha_invite_nonce', true, false )
			. '<p><label for="alpha-inv-fn">' . esc_html__( 'First name', 'alpha-preclinical' ) . '</label><input id="alpha-inv-fn" name="first_name" autocomplete="off" required maxlength="60"></p>'
			. '<p><label for="alpha-inv-ln">' . esc_html__( 'Last name', 'alpha-preclinical' ) . '</label><input id="alpha-inv-ln" name="last_name" autocomplete="off" required maxlength="60"></p>'
			. '<p class="alpha-invite__email"><label for="alpha-inv-em">' . esc_html__( 'Work email', 'alpha-preclinical' ) . '</label><input id="alpha-inv-em" name="email" type="email" autocomplete="off" required maxlength="100"></p>'
			. '<p><button type="submit" class="alpha-btn alpha-btn--primary">' . esc_html__( 'Send invitation', 'alpha-preclinical' ) . '</button></p>'
			. '</form>';
	}
	$people = alpha_review_reviewers();
	if ( $people ) {
		$html .= '<h3>' . esc_html__( 'Who can answer the review', 'alpha-preclinical' ) . '</h3><ul class="alpha-invite__people">';
		foreach ( $people as $u ) {
			$html .= '<li><b>' . esc_html( $u->display_name ) . '</b> <span>' . esc_html( $u->user_email ) . '</span></li>';
		}
		$html .= '</ul>';
	}
	return $html . '</section>';
}

/**
 * Add the panel under the review for people who can answer it.
 *
 * @param string $html  Block output.
 * @param array  $block Parsed block.
 * @return string
 */
function alpha_review_invite_append( $html, $block ) {
	if ( 'alpha/content-review' !== ( $block['blockName'] ?? '' ) || ! alpha_is_staging() || ! current_user_can( 'alpha_review' ) ) {
		return $html;
	}
	return $html . alpha_review_invite_panel();
}
add_filter( 'render_block', 'alpha_review_invite_append', 9, 2 );

/**
 * Invitation handler: nonce, capability, staging, domain, daily limit.
 */
function alpha_review_invite_handle() {
	if ( ! is_user_logged_in() || ! current_user_can( 'alpha_review' ) ) {
		wp_die( esc_html__( 'You do not have permission to invite reviewers.', 'alpha-preclinical' ), 403 );
	}
	check_admin_referer( 'alpha_review_invite', 'alpha_invite_nonce' );

	$back = static function ( $state, $who = '' ) {
		wp_safe_redirect(
			add_query_arg(
				array_filter(
					array(
						'alpha_invite' => $state,
						'alpha_who'    => $who,
						'alpha_nonce'  => wp_create_nonce( 'alpha_review_invited' ),
					)
				),
				alpha_review_url()
			) . '#invite'
		);
		exit;
	};

	if ( ! alpha_is_staging() ) {
		wp_die( esc_html__( 'The content review is closed.', 'alpha-preclinical' ), 403 );
	}

	$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( '' === $first || '' === $last || ! is_email( $email ) ) {
		$back( 'invalid' );
	}
	if ( ! alpha_review_invite_allowed_email( $email ) ) {
		$back( 'domain' );
	}
	if ( email_exists( $email ) ) {
		$back( 'exists' );
	}

	$me    = get_current_user_id();
	$count = (int) get_transient( 'alpha_invites_' . $me );
	if ( $count >= 10 && ! current_user_can( 'create_users' ) ) {
		$back( 'limit' );
	}

	// Unique login from the address ("chris" or "chris2").
	$base  = sanitize_user( strtolower( strtok( $email, '@' ) ), true );
	$base  = '' !== $base ? $base : 'reviewer';
	$login = $base;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}

	$id = wp_insert_user(
		array(
			'user_login'           => $login,
			'user_email'           => $email,
			'user_pass'            => wp_generate_password( 32, true, true ),
			'first_name'           => $first,
			'last_name'            => $last,
			'display_name'         => $first . ' ' . $last,
			'role'                 => 'alpha_reviewer',
			'show_admin_bar_front' => 'false',
		)
	);
	if ( is_wp_error( $id ) ) {
		$back( 'invalid' );
	}
	set_transient( 'alpha_invites_' . $me, $count + 1, DAY_IN_SECONDS );
	update_user_meta( $id, 'alpha_invited_by', $me );

	// The colleague gets WordPress's own "set your password" email.
	$failed = false;
	$watch  = static function () use ( &$failed ) {
		$failed = true;
	};
	add_action( 'wp_mail_failed', $watch );
	wp_new_user_notification( $id, null, 'user' );
	remove_action( 'wp_mail_failed', $watch );

	// Tell the site admin who invited whom.
	$inviter = wp_get_current_user();
	$notify  = alpha_site( 'review_notify' ) ? alpha_site( 'review_notify' ) : get_option( 'admin_email' );
	wp_mail(
		$notify,
		/* translators: %s: new reviewer's name. */
		sprintf( __( 'Content review: %s was invited', 'alpha-preclinical' ), $first . ' ' . $last ),
		/* translators: 1: inviter, 2: new reviewer, 3: email, 4: users screen. */
		sprintf( __( "%1\$s invited %2\$s (%3\$s) to answer the content review.\n\nThey have the Client reviewer role. Manage reviewers: %4\$s", 'alpha-preclinical' ), $inviter->display_name, $first . ' ' . $last, $email, admin_url( 'users.php?role=alpha_reviewer' ) )
	);

	$back( $failed ? 'nomail' : 'sent', $first . ' ' . $last );
}
add_action( 'admin_post_alpha_review_invite', 'alpha_review_invite_handle' );
