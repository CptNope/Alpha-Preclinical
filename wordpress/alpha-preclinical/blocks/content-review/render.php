<?php
/**
 * Content review page (staging only). Logged-in users with the alpha_review
 * capability answer the open questions section by section.
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

$alpha_wrap = get_block_wrapper_attributes( array( 'class' => 'alpha-review' ) );

if ( ! alpha_is_staging() ) {
	echo '<div ' . $alpha_wrap . '><p>' . esc_html__( 'The content review is closed: the site is live.', 'alpha-preclinical' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
if ( ! is_user_logged_in() ) {
	echo '<div ' . $alpha_wrap . '><p>' . esc_html__( 'Please log in to answer the content review.', 'alpha-preclinical' ) . '</p><p><a class="alpha-btn alpha-btn--primary" href="' . esc_url( wp_login_url( alpha_review_url() ) ) . '">' . esc_html__( 'Log in', 'alpha-preclinical' ) . '</a></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
if ( ! current_user_can( 'alpha_review' ) ) {
	echo '<div ' . $alpha_wrap . '><p>' . esc_html__( 'Your account can\'t answer the content review. Ask Jeremy to give you the Client reviewer role.', 'alpha-preclinical' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$alpha_sections = alpha_review_sections();
$alpha_answers  = alpha_review_answers();
$alpha_saved    = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only status after redirect.
$alpha_total    = 0;
$alpha_done     = 0;
$alpha_counts   = array();
foreach ( $alpha_sections as $alpha_key => $alpha_sec ) {
	$alpha_counts[ $alpha_key ] = array( 0, count( $alpha_sec['q'] ) );
	foreach ( $alpha_sec['q'] as $alpha_q ) {
		$alpha_a = isset( $alpha_answers[ $alpha_q['id'] ] ) ? $alpha_answers[ $alpha_q['id'] ] : null;
		if ( alpha_review_done( $alpha_q, $alpha_a ) ) {
			++$alpha_counts[ $alpha_key ][0];
		}
	}
	$alpha_total += $alpha_counts[ $alpha_key ][1];
	$alpha_done  += $alpha_counts[ $alpha_key ][0];
}
$alpha_pct = $alpha_total ? (int) round( 100 * $alpha_done / $alpha_total ) : 0;
?>
<div <?php echo $alpha_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="alpha-review__top">
		<p class="alpha-review__hello">
			<?php
			/* translators: %s: user's display name. */
			echo esc_html( sprintf( __( 'Hi %s. Answer what you can and save each section; you can come back any time. Answers marked "Updates the site" change the staging site as soon as you save.', 'alpha-preclinical' ), wp_get_current_user()->display_name ) );
			?>
		</p>
		<div class="alpha-review__progress">
			<span class="alpha-review__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $alpha_pct; ?>" aria-label="<?php esc_attr_e( 'Answered so far', 'alpha-preclinical' ); ?>"><span style="width:<?php echo (int) $alpha_pct; ?>%"></span></span>
			<b>
				<?php
				/* translators: 1: answered, 2: total. */
				echo esc_html( sprintf( __( '%1$d of %2$d answered', 'alpha-preclinical' ), $alpha_done, $alpha_total ) );
				?>
			</b>
		</div>
		<nav class="alpha-review__nav" aria-label="<?php esc_attr_e( 'Sections', 'alpha-preclinical' ); ?>">
			<?php foreach ( $alpha_sections as $alpha_key => $alpha_sec ) : ?>
				<a href="#review-<?php echo esc_attr( $alpha_key ); ?>"><?php echo esc_html( $alpha_sec['title'] ); ?> <small><?php echo (int) $alpha_counts[ $alpha_key ][0]; ?>/<?php echo (int) $alpha_counts[ $alpha_key ][1]; ?></small></a>
			<?php endforeach; ?>
		</nav>
	</div>

	<?php foreach ( $alpha_sections as $alpha_key => $alpha_sec ) : ?>
		<section class="alpha-review__sec" id="review-<?php echo esc_attr( $alpha_key ); ?>" aria-labelledby="review-<?php echo esc_attr( $alpha_key ); ?>-h">
			<h2 id="review-<?php echo esc_attr( $alpha_key ); ?>-h"><?php echo esc_html( $alpha_sec['title'] ); ?></h2>
			<p class="alpha-review__intro"><?php echo esc_html( $alpha_sec['intro'] ); ?></p>
			<?php if ( $alpha_saved === $alpha_key ) : ?>
				<p class="alpha-review__saved" role="status"><?php esc_html_e( 'Saved. Thank you.', 'alpha-preclinical' ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="alpha_review_save">
				<input type="hidden" name="section" value="<?php echo esc_attr( $alpha_key ); ?>">
				<?php wp_nonce_field( 'alpha_review_' . $alpha_key, 'alpha_review_nonce' ); ?>
				<?php
				foreach ( $alpha_sec['q'] as $alpha_q ) :
					$alpha_id    = 'q_' . $alpha_q['id'];
					$alpha_a     = isset( $alpha_answers[ $alpha_q['id'] ] ) ? $alpha_answers[ $alpha_q['id'] ] : null;
					$alpha_value = $alpha_a ? $alpha_a['value'] : ( isset( $alpha_q['current'] ) ? (string) call_user_func( $alpha_q['current'] ) : '' );
					$alpha_notes = $alpha_a && isset( $alpha_a['notes'] ) ? $alpha_a['notes'] : '';
					$alpha_isdone = alpha_review_done( $alpha_q, $alpha_a );
					$alpha_help  = isset( $alpha_q['help'] ) ? $alpha_q['help'] : '';
					$alpha_multi = in_array( $alpha_q['type'], array( 'choice', 'person', 'approval' ), true ) || 'files' === $alpha_q['type'];
					?>
					<div class="alpha-review__q<?php echo $alpha_isdone ? ' is-done' : ''; ?>">
						<?php if ( in_array( $alpha_q['type'], array( 'choice', 'approval' ), true ) ) : ?>
							<fieldset>
								<legend><?php echo esc_html( $alpha_q['label'] ); ?></legend>
						<?php else : ?>
							<label class="alpha-review__label" for="<?php echo esc_attr( $alpha_id ); ?>"><?php echo esc_html( $alpha_q['label'] ); ?></label>
						<?php endif; ?>
						<?php if ( isset( $alpha_q['apply'] ) ) : ?>
							<span class="alpha-review__tag"><?php esc_html_e( 'Updates the site', 'alpha-preclinical' ); ?></span>
						<?php endif; ?>
						<?php if ( $alpha_help ) : ?>
							<p class="alpha-review__help" id="<?php echo esc_attr( $alpha_id ); ?>-help"><?php echo esc_html( $alpha_help ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $alpha_q['link'] ) ) : ?>
							<p class="alpha-review__link"><a href="<?php echo esc_url( $alpha_q['link'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See it on the site (opens in a new tab)', 'alpha-preclinical' ); ?></a></p>
						<?php endif; ?>
						<?php
						if ( ! empty( $alpha_q['post'] ) ) :
							$alpha_post = get_post( (int) $alpha_q['post'] );
							?>
							<details class="alpha-review__draft">
								<summary><?php esc_html_e( 'Read the draft', 'alpha-preclinical' ); ?></summary>
								<div class="alpha-review__draft-body"><?php echo wp_kses_post( apply_filters( 'the_content', $alpha_post->post_content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllThemeGlobals.NonPrefixedHooknameFound -- core filter. ?></div>
							</details>
						<?php endif; ?>

						<?php
						switch ( $alpha_q['type'] ) :
							case 'textarea':
								?>
								<textarea id="<?php echo esc_attr( $alpha_id ); ?>" name="<?php echo esc_attr( $alpha_id ); ?>" rows="4"<?php echo $alpha_help ? ' aria-describedby="' . esc_attr( $alpha_id ) . '-help"' : ''; ?>><?php echo esc_textarea( (string) $alpha_value ); ?></textarea>
								<?php
								break;
							case 'person':
								?>
								<select id="<?php echo esc_attr( $alpha_id ); ?>" name="<?php echo esc_attr( $alpha_id ); ?>">
									<?php foreach ( $alpha_q['options'] as $alpha_v => $alpha_l ) : ?>
										<option value="<?php echo esc_attr( $alpha_v ); ?>"<?php selected( $alpha_value, $alpha_v ); ?>><?php echo esc_html( $alpha_l ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php
								break;
							case 'choice':
							case 'approval':
								$alpha_opts = 'approval' === $alpha_q['type'] ? array( 'approved' => __( 'Approved as written', 'alpha-preclinical' ), 'changes' => __( 'Needs changes (see notes)', 'alpha-preclinical' ) ) : $alpha_q['options'];
								foreach ( $alpha_opts as $alpha_v => $alpha_l ) :
									?>
									<label class="alpha-review__opt"><input type="radio" name="<?php echo esc_attr( $alpha_id ); ?>" value="<?php echo esc_attr( $alpha_v ); ?>"<?php checked( $alpha_value, $alpha_v ); ?>> <?php echo esc_html( $alpha_l ); ?></label>
									<?php
								endforeach;
								break;
							case 'files':
								$alpha_mimes = alpha_review_mimes( isset( $alpha_q['accept'] ) ? $alpha_q['accept'] : 'docs' );
								$alpha_exts  = array();
								foreach ( array_keys( $alpha_mimes ) as $alpha_e ) {
									foreach ( explode( '|', $alpha_e ) as $alpha_x ) {
										$alpha_exts[] = '.' . $alpha_x;
									}
								}
								?>
								<input id="<?php echo esc_attr( $alpha_id ); ?>" type="file" name="<?php echo esc_attr( $alpha_id ); ?>[]" multiple accept="<?php echo esc_attr( implode( ',', $alpha_exts ) ); ?>">
								<?php if ( ! empty( $alpha_value ) && is_array( $alpha_value ) ) : ?>
									<ul class="alpha-review__files">
										<?php foreach ( $alpha_value as $alpha_att ) : ?>
											<li><a href="<?php echo esc_url( wp_get_attachment_url( (int) $alpha_att ) ); ?>"><?php echo esc_html( get_the_title( (int) $alpha_att ) . ' (' . basename( (string) get_attached_file( (int) $alpha_att ) ) . ')' ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php
								break;
							default:
								$alpha_type = in_array( $alpha_q['type'], array( 'email', 'url' ), true ) ? $alpha_q['type'] : 'text';
								?>
								<input id="<?php echo esc_attr( $alpha_id ); ?>" type="<?php echo esc_attr( $alpha_type ); ?>" name="<?php echo esc_attr( $alpha_id ); ?>" value="<?php echo esc_attr( (string) $alpha_value ); ?>"<?php echo $alpha_help ? ' aria-describedby="' . esc_attr( $alpha_id ) . '-help"' : ''; ?>>
								<?php
						endswitch;
						?>

						<?php if ( 'approval' === $alpha_q['type'] || ! empty( $alpha_q['notes'] ) || 'files' === $alpha_q['type'] ) : ?>
							<label class="alpha-review__notes-label" for="<?php echo esc_attr( $alpha_id ); ?>_notes"><?php esc_html_e( 'Notes', 'alpha-preclinical' ); ?></label>
							<textarea id="<?php echo esc_attr( $alpha_id ); ?>_notes" name="<?php echo esc_attr( $alpha_id ); ?>_notes" rows="3"><?php echo esc_textarea( $alpha_notes ); ?></textarea>
						<?php endif; ?>
						<?php if ( in_array( $alpha_q['type'], array( 'choice', 'approval' ), true ) ) : ?>
							</fieldset>
						<?php endif; ?>
						<?php
						if ( $alpha_a ) :
							$alpha_by = get_userdata( (int) $alpha_a['by'] );
							?>
							<p class="alpha-review__meta">
								<?php
								/* translators: 1: name, 2: date. */
								echo esc_html( sprintf( __( 'Answered by %1$s, %2$s.', 'alpha-preclinical' ), $alpha_by ? $alpha_by->display_name : __( 'someone', 'alpha-preclinical' ), wp_date( 'M j, g:ia', (int) $alpha_a['at'] ) ) );
								echo $alpha_a['applied'] ? ' ' . esc_html( $alpha_a['applied'] ) : '';
								?>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<p class="alpha-review__actions"><button class="alpha-btn alpha-btn--primary" type="submit">
					<?php
					/* translators: %s: section title. */
					echo esc_html( sprintf( __( 'Save %s', 'alpha-preclinical' ), $alpha_sec['title'] ) );
					?>
				</button></p>
			</form>
		</section>
	<?php endforeach; ?>
</div>
