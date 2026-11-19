<?php
/**
 * Staging mode (Settings › Alpha Preclinical): keeps the staging site out of search,
 * optionally behind a login, shows a banner, and enables the Content review page.
 * Turn it off at launch.
 *
 * Also defines the "Client reviewer" role: people at Alpha who answer the open
 * questions and upload files, without access to the rest of the admin.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether staging mode is on.
 *
 * @return bool
 */
function alpha_is_staging() {
	return '1' === alpha_site( 'staging_mode' ) || ( defined( 'ALPHA_STAGING' ) && ALPHA_STAGING );
}

/**
 * Role and capability. Runs on theme switch and whenever the role is missing.
 */
function alpha_register_reviewer_role() {
	if ( ! get_role( 'alpha_reviewer' ) ) {
		add_role(
			'alpha_reviewer',
			__( 'Client reviewer', 'alpha-preclinical' ),
			array(
				'read'         => true,
				'upload_files' => true,
				'alpha_review' => true,
			)
		);
	}
	foreach ( array( 'administrator', 'editor' ) as $name ) {
		$role = get_role( $name );
		if ( $role && ! $role->has_cap( 'alpha_review' ) ) {
			$role->add_cap( 'alpha_review' );
		}
	}
}
add_action( 'init', 'alpha_register_reviewer_role' );

/**
 * Reviewers land on the Content review page after logging in, not in the admin.
 *
 * @param string           $redirect Default destination.
 * @param string           $request  Requested destination.
 * @param WP_User|WP_Error $user     User.
 * @return string
 */
function alpha_reviewer_login_redirect( $redirect, $request, $user ) {
	if ( $user instanceof WP_User && in_array( 'alpha_reviewer', (array) $user->roles, true ) && empty( $request ) ) {
		return alpha_review_url();
	}
	return $redirect;
}
add_filter( 'login_redirect', 'alpha_reviewer_login_redirect', 10, 3 );

/**
 * Keep reviewers out of the dashboard (they can still use the media modal and profile).
 */
function alpha_reviewer_admin_redirect() {
	if ( wp_doing_ajax() || ! is_user_logged_in() ) {
		return;
	}
	$user = wp_get_current_user();
	if ( array( 'alpha_reviewer' ) === array_values( (array) $user->roles ) ) {
		global $pagenow;
		if ( in_array( $pagenow, array( 'index.php', 'edit.php', 'upload.php' ), true ) ) {
			wp_safe_redirect( alpha_review_url() );
			exit;
		}
	}
}
add_action( 'admin_init', 'alpha_reviewer_admin_redirect' );

/**
 * No indexing while staging.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function alpha_staging_robots( $robots ) {
	if ( alpha_is_staging() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'alpha_staging_robots' );

/**
 * Private staging: send visitors who are not logged in to the login screen.
 */
function alpha_staging_private() {
	if ( ! alpha_is_staging() || '1' !== alpha_site( 'staging_private' ) || is_user_logged_in() ) {
		return;
	}
	if ( wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	auth_redirect();
}
add_action( 'template_redirect', 'alpha_staging_private', 0 );

/**
 * Staging banner for logged-in users, with a link to the review page.
 */
function alpha_staging_banner() {
	if ( ! alpha_is_staging() || ! is_user_logged_in() ) {
		return;
	}
	$link = current_user_can( 'alpha_review' ) ? ' <a href="' . esc_url( alpha_review_url() ) . '">' . esc_html__( 'Open the content review', 'alpha-preclinical' ) . '</a>' : '';
	echo '<div class="alpha-staging-banner" role="note"><strong>' . esc_html__( 'Staging site.', 'alpha-preclinical' ) . '</strong> ' . esc_html__( 'Not public. Content is still being confirmed.', 'alpha-preclinical' ) . wp_kses_post( $link ) . '</div>';
}
add_action( 'wp_body_open', 'alpha_staging_banner', 2 );

/**
 * Discourage search engines at the WordPress level too, while staging.
 *
 * @param mixed $value blog_public option.
 * @return mixed
 */
function alpha_staging_blog_public( $value ) {
	return alpha_is_staging() ? '0' : $value;
}
add_filter( 'pre_option_blog_public', 'alpha_staging_blog_public' );
