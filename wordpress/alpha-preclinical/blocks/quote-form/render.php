<?php
/**
 * Quote form (or job application). Posts to admin-post.php, handled in inc/forms.php.
 * Works without JavaScript; theme.js adds inline validation and the ?study= preset.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_type    = isset( $attributes['formType'] ) && 'careers' === $attributes['formType'] ? 'careers' : 'quote';
$alpha_uid     = wp_unique_id( 'alpha-form-' );
$alpha_status = '';
if ( isset( $_GET['alpha_form'], $_GET['alpha_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['alpha_nonce'] ) ), 'alpha_form_result' ) ) {
	$alpha_status = sanitize_key( wp_unslash( $_GET['alpha_form'] ) ); // Result of the visitor's own submission (nonce added by alpha_handle_form).
}
$alpha_preset  = isset( $_GET['study'] ) ? sanitize_text_field( wp_unslash( $_GET['study'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- preselects an option; no state change.
$alpha_role    = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- preselects an option; no state change.
$alpha_privacy = get_privacy_policy_url();
$alpha_wrap    = get_block_wrapper_attributes( array( 'class' => 'alpha-form alpha-form--' . $alpha_type ) );
$alpha_field   = static function ( $name, $label, $type = 'text', $args = array() ) use ( $alpha_uid ) {
	$id       = $alpha_uid . '-' . $name;
	$required = ! empty( $args['required'] );
	$full     = ! empty( $args['full'] ) ? ' alpha-field--full' : '';
	$auto     = isset( $args['autocomplete'] ) ? ' autocomplete="' . esc_attr( $args['autocomplete'] ) . '"' : '';
	echo '<div class="alpha-field' . esc_attr( $full ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $label );
	if ( ! $required ) {
		echo ' <small>' . esc_html__( '(optional)', 'alpha-preclinical' ) . '</small>';
	}
	echo '</label>';
	if ( 'textarea' === $type ) {
		echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . ( isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '' ) . ' maxlength="5000"></textarea>';
	} elseif ( 'select' === $type ) {
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-preset="' . esc_attr( isset( $args['preset'] ) ? $args['preset'] : '' ) . '">';
		foreach ( $args['options'] as $opt ) {
			echo '<option' . selected( isset( $args['preset'] ) ? $args['preset'] : '', $opt, false ) . '>' . esc_html( $opt ) . '</option>';
		}
		echo '</select>';
	} elseif ( 'file' === $type ) {
		echo '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="file" accept=".pdf,.doc,.docx"' . ( $required ? ' required' : '' ) . '>';
		echo '<span class="alpha-field__hint">' . esc_html__( 'PDF or Word, up to 5 MB.', 'alpha-preclinical' ) . '</span>';
	} else {
		echo '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '"' . $auto . ( $required ? ' required' : '' ) . ' maxlength="200"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $auto is escaped above.
		if ( 'email' === $type ) {
			echo ' aria-describedby="' . esc_attr( $id ) . '-err"';
		}
		echo '>';
		if ( 'email' === $type ) {
			echo '<span class="alpha-field__err" id="' . esc_attr( $id ) . '-err" hidden>' . esc_html__( 'Enter an email address like name@company.com so we can reply.', 'alpha-preclinical' ) . '</span>';
		}
	}
	echo '</div>';
};
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> id="<?php echo 'careers' === $alpha_type ? 'apply' : 'form'; ?>">
	<?php if ( 'sent' === $alpha_status ) : ?>
		<div class="alpha-form__done" role="status" tabindex="-1" data-form-done>
			<h3><?php echo esc_html( isset( $attributes['successHeading'] ) ? $attributes['successHeading'] : __( 'Message sent', 'alpha-preclinical' ) ); ?></h3>
			<p><?php echo 'careers' === $alpha_type ? esc_html__( 'Thanks for applying. We review every application and will be in touch.', 'alpha-preclinical' ) : esc_html__( 'Thanks for reaching out. A scientist will reply by email.', 'alpha-preclinical' ); ?></p>
		</div>
	<?php elseif ( 'error' === $alpha_status ) : ?>
		<div class="alpha-form__error" role="alert">
			<p>
				<?php
				printf(
					/* translators: %s: email address. */
					esc_html__( 'Sorry, your message could not be sent. Please try again, or email us at %s.', 'alpha-preclinical' ),
					'<a href="mailto:' . esc_attr( antispambot( alpha_site( 'email' ) ) ) . '">' . esc_html( antispambot( alpha_site( 'email' ) ) ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped parts.
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	<?php if ( 'sent' !== $alpha_status ) : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate data-alpha-form>
		<input type="hidden" name="action" value="alpha_form">
		<input type="hidden" name="form_type" value="<?php echo esc_attr( $alpha_type ); ?>">
		<input type="hidden" name="return_to" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
		<input type="hidden" name="started" value="<?php echo esc_attr( (string) time() ); ?>">
		<?php wp_nonce_field( 'alpha_form_' . $alpha_type, 'alpha_form_nonce' ); ?>
		<div class="alpha-form__hp" aria-hidden="true"><label><?php esc_html_e( 'Leave this field empty', 'alpha-preclinical' ); ?><input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<div class="alpha-form__fields">
			<?php
			$alpha_field( 'first_name', __( 'First name', 'alpha-preclinical' ), 'text', array( 'required' => true, 'autocomplete' => 'given-name' ) );
			$alpha_field( 'last_name', __( 'Last name', 'alpha-preclinical' ), 'text', array( 'required' => true, 'autocomplete' => 'family-name' ) );
			if ( 'quote' === $alpha_type ) {
				$alpha_field( 'company', __( 'Company', 'alpha-preclinical' ), 'text', array( 'required' => true, 'autocomplete' => 'organization' ) );
				$alpha_field( 'position', __( 'Position', 'alpha-preclinical' ), 'text', array( 'autocomplete' => 'organization-title' ) );
			}
			$alpha_field( 'email', 'quote' === $alpha_type ? __( 'Work email', 'alpha-preclinical' ) : __( 'Email', 'alpha-preclinical' ), 'email', array( 'required' => true, 'autocomplete' => 'email' ) );
			$alpha_field( 'phone', __( 'Phone', 'alpha-preclinical' ), 'tel', array( 'autocomplete' => 'tel' ) );
			if ( 'quote' === $alpha_type ) {
				$alpha_field( 'study_type', __( 'What do you need help with?', 'alpha-preclinical' ), 'select', array( 'required' => true, 'full' => true, 'options' => alpha_study_types(), 'preset' => $alpha_preset ) );
				$alpha_field( 'message', __( 'Tell us about your program', 'alpha-preclinical' ), 'textarea', array( 'required' => true, 'full' => true, 'placeholder' => __( 'Compound or modality, target, the question you need answered, and your timeline', 'alpha-preclinical' ) ) );
			} else {
				$alpha_roles = get_posts( array( 'post_type' => 'job', 'posts_per_page' => 50, 'orderby' => 'menu_order', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true ) );
				$alpha_opts  = array_map( 'get_the_title', $alpha_roles );
				$alpha_opts[] = __( 'General application', 'alpha-preclinical' );
				$alpha_field( 'role', __( 'Role', 'alpha-preclinical' ), 'select', array( 'required' => true, 'full' => true, 'options' => $alpha_opts, 'preset' => $alpha_role ) );
				$alpha_field( 'resume', __( 'Resume', 'alpha-preclinical' ), 'file', array( 'required' => true, 'full' => true ) );
				$alpha_field( 'message', __( 'A few words about you', 'alpha-preclinical' ), 'textarea', array( 'full' => true ) );
			}
			?>
		</div>
		<div class="alpha-form__actions">
			<button class="alpha-btn alpha-btn--primary" type="submit"><?php echo esc_html( isset( $attributes['submitText'] ) && '' !== $attributes['submitText'] ? $attributes['submitText'] : __( 'Send message', 'alpha-preclinical' ) ); ?></button>
			<p>
				<?php
				if ( $alpha_privacy ) {
					printf(
						/* translators: %s: link to the privacy policy. */
						esc_html__( 'We use your details only to respond. See our %s.', 'alpha-preclinical' ),
						'<a href="' . esc_url( $alpha_privacy ) . '">' . esc_html__( 'privacy policy', 'alpha-preclinical' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped parts.
					);
				} else {
					esc_html_e( 'We use your details only to respond.', 'alpha-preclinical' );
				}
				?>
			</p>
		</div>
	</form>
	<?php endif; ?>
</div>
