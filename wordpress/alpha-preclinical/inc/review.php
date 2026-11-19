<?php
/**
 * Content review: the open questions and approvals before launch, answered on the
 * staging site by logged-in Client reviewers (alpha/content-review block).
 *
 * Each question saves its answer with who and when. Where an answer maps to site
 * data, saving it also updates the site ("applies"), so staging always shows the
 * latest confirmed content: job titles, profile links, phone, quote inboxes and
 * routing, accessibility response time, which roles are open, which papers show.
 * Everything else (approvals, notes, uploads) is recorded for Jeremy to act on.
 *
 * Admin: Tools › Content review answers (table, CSV export, reset).
 *
 * @package AlphaPreclinical
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of the Content review page.
 *
 * @return string
 */
function alpha_review_url() {
	$page = get_page_by_path( 'content-review' );
	return $page ? get_permalink( $page ) : home_url( '/content-review/' );
}

/**
 * Stored answers: id => [ value, by, at, applied ].
 *
 * @return array
 */
function alpha_review_answers() {
	$a = get_option( 'alpha_review', array() );
	return is_array( $a ) ? $a : array();
}

/**
 * Team member by slug.
 *
 * @param string $slug Slug.
 * @return WP_Post|null
 */
function alpha_review_person( $slug ) {
	$p = alpha_get_team( array( $slug ) );
	return $p ? $p[0] : null;
}

/**
 * Update one site setting.
 *
 * @param string $key   Setting.
 * @param string $value Value.
 */
function alpha_review_set_site( $key, $value ) {
	$opts         = wp_parse_args( (array) get_option( 'alpha_site', array() ), alpha_site_defaults() );
	$opts[ $key ] = $value;
	update_option( 'alpha_site', alpha_sanitize_site( wp_slash( $opts ) ) );
}

/**
 * All sections and questions. Built at request time from the current content.
 *
 * Question keys: id, label, type, help?, options? (value => label), link?, current?
 * (callable → string), apply? (callable(value) → note), accept? (files).
 * Types: text, email, url, textarea, choice (radio), person, approval, files.
 *
 * @return array
 */
function alpha_review_sections() {
	$team   = alpha_get_team();
	$people = array( '' => __( 'Choose…', 'alpha-preclinical' ) );
	foreach ( $team as $p ) {
		$people[ $p->post_name ] = alpha_person_name( $p );
	}
	$approval = array(
		'approved' => __( 'Approved as written', 'alpha-preclinical' ),
		'changes'  => __( 'Needs changes (see notes)', 'alpha-preclinical' ),
	);
	$s = array();

	// ---------------------------------------------------------------- Team.
	$q = array();
	foreach ( array( 'ashley-kolofsky', 'cindy-hopper', 'gil-chacon' ) as $slug ) {
		$p = alpha_review_person( $slug );
		if ( ! $p ) {
			continue;
		}
		$q[] = array(
			'id'      => 'title_' . $slug,
			/* translators: %s: person's name. */
			'label'   => sprintf( __( 'Job title for %s', 'alpha-preclinical' ), $p->post_title ),
			'type'    => 'text',
			'help'    => __( 'Written from the old site. Correct it if needed; it updates the Team page, cards and schema.', 'alpha-preclinical' ),
			'current' => static function () use ( $p ) {
				return (string) get_post_meta( $p->ID, 'job_title', true );
			},
			'apply'   => static function ( $v ) use ( $p ) {
				update_post_meta( $p->ID, 'job_title', $v );
				return __( 'Updated on the site.', 'alpha-preclinical' );
			},
		);
	}
	foreach ( $team as $p ) {
		$q[] = array(
			'id'    => 'bio_' . $p->post_name,
			/* translators: %s: person's name. */
			'label' => sprintf( __( 'Bio: %s', 'alpha-preclinical' ), alpha_person_name( $p ) ),
			'type'  => 'approval',
			'link'  => get_permalink( $p ),
			'help'  => 'cindy-hopper' === $p->post_name
				? __( 'Please check the line that now reads "preventive approaches to medicine" (it replaced a reference to homeopathy).', 'alpha-preclinical' )
				: ( 'joan-flanagan' === $p->post_name ? __( 'Please check that the SWI/SNF papers sit correctly with your UMass Medical School training.', 'alpha-preclinical' ) : __( 'Read the profile and approve it, or note what to change.', 'alpha-preclinical' ) ),
		);
	}
	foreach ( array( 'barak-yahalom' => array( 'orcid', 'scholar' ), 'joan-flanagan' => array( 'orcid', 'scholar' ), 'cindy-hopper' => array( 'linkedin' ), 'gil-chacon' => array( 'linkedin' ) ) as $slug => $keys ) {
		$p = alpha_review_person( $slug );
		if ( ! $p ) {
			continue;
		}
		foreach ( $keys as $key ) {
			$names = array(
				'orcid'    => 'ORCID',
				'scholar'  => 'Google Scholar',
				'linkedin' => 'LinkedIn',
			);
			$q[]   = array(
				'id'      => 'link_' . $slug . '_' . $key,
				/* translators: 1: profile site, 2: person's name. */
				'label'   => sprintf( __( '%1$s profile link for %2$s', 'alpha-preclinical' ), $names[ $key ], $p->post_title ),
				'type'    => 'url',
				'help'    => __( 'Optional. Leave empty if there is none.', 'alpha-preclinical' ),
				'current' => static function () use ( $p, $key ) {
					return (string) get_post_meta( $p->ID, $key, true );
				},
				'apply'   => static function ( $v ) use ( $p, $key ) {
					update_post_meta( $p->ID, $key, $v );
					return __( 'Updated on the profile.', 'alpha-preclinical' );
				},
			);
		}
	}
	foreach ( $team as $p ) {
		$q[] = array(
			'id'    => 'card_' . $p->post_name,
			/* translators: %s: person's name. */
			'label' => sprintf( __( 'Business card and email signature details for %s', 'alpha-preclinical' ), $p->post_title ),
			'type'  => 'textarea',
			'help'  => __( 'Work email and direct phone, as they should print. Leave empty if they don\'t need cards.', 'alpha-preclinical' ),
		);
	}
	$s['team'] = array(
		'title' => __( 'Team', 'alpha-preclinical' ),
		'intro' => __( 'Titles, bios and profile links. Each person should approve their own bio.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	// ---------------------------------------------------------------- Expertise and papers.
	$q = array();
	foreach ( array(
		'lead_fibrosis' => __( 'Who leads inflammation and fibrosis studies?', 'alpha-preclinical' ),
		'lead_tumor'    => __( 'Who leads tumor model studies?', 'alpha-preclinical' ),
		'lead_ivis'     => __( 'Who leads IVIS imaging?', 'alpha-preclinical' ),
	) as $id => $label ) {
		$q[] = array(
			'id'      => $id,
			'label'   => $label,
			'type'    => 'person',
			'options' => $people,
			'help'    => __( 'Currently shown as Barak Yahalom. Jeremy updates the "Led by" on the page.', 'alpha-preclinical' ),
		);
	}
	$q[] = array(
		'id'      => 'models_current',
		'label'   => __( 'Are the model lists on the Expertise page current?', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'yes'     => __( 'Yes, all current', 'alpha-preclinical' ),
			'changes' => __( 'Some changes (see notes)', 'alpha-preclinical' ),
		),
		'notes'   => true,
		'link'    => home_url( '/expertise/' ),
		'help'    => __( 'They were copied from the old site. Note any models to add or remove, and anything new you offer.', 'alpha-preclinical' ),
	);
	$q[] = array(
		'id'      => 'lnp_papers',
		'label'   => __( 'The three Sanofi / Translate Bio LNP papers list no Alpha scientist as an author. Keep or remove them?', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'keep'   => __( 'Keep, as collaborator work', 'alpha-preclinical' ),
			'remove' => __( 'Remove from the site', 'alpha-preclinical' ),
		),
		'link'    => home_url( '/publications/' ),
		'apply'   => static function ( $v ) {
			$papers = get_posts(
				array(
					'post_type'      => 'publication',
					'post_status'    => array( 'publish', 'draft' ),
					'posts_per_page' => -1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 16 posts.
						array(
							'key'   => 'alpha_authors',
							'value' => '',
						),
					),
				)
			);
			foreach ( $papers as $paper ) {
				wp_update_post(
					array(
						'ID'          => $paper->ID,
						'post_status' => 'remove' === $v ? 'draft' : 'publish',
					)
				);
			}
			/* translators: %d: number of papers. */
			return sprintf( 'remove' === $v ? __( '%d papers hidden.', 'alpha-preclinical' ) : __( '%d papers shown.', 'alpha-preclinical' ), count( $papers ) );
		},
	);
	$q[] = array(
		'id'      => 'ilar_author',
		'label'   => __( 'Joan: is the 2004 ILAR Journal paper (BBZDR/Wor rat) yours? One database lists the author as "John F. Flanagan".', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'yes' => __( 'Yes, it is Joan\'s paper', 'alpha-preclinical' ),
			'no'  => __( 'No', 'alpha-preclinical' ),
		),
	);
	$q[] = array(
		'id'      => 'becker',
		'label'   => __( 'Joan\'s Becker College Industry Advisory Board line: Becker closed in 2021. Keep it as past service, or leave it off?', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'keep'   => __( 'Keep, as past service', 'alpha-preclinical' ),
			'remove' => __( 'Leave it off', 'alpha-preclinical' ),
		),
		'apply'   => static function ( $v ) {
			$joan = alpha_review_person( 'joan-flanagan' );
			if ( ! $joan ) {
				return '';
			}
			$line  = 'Becker College Industry Advisory Board (until 2021)';
			$items = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $joan->ID, 'highlights', true ) ) ) );
			$items = array_values( array_diff( $items, array( $line ) ) );
			if ( 'keep' === $v ) {
				$items[] = $line;
			}
			update_post_meta( $joan->ID, 'highlights', implode( "\n", $items ) );
			return 'keep' === $v ? __( 'Added to Joan\'s profile.', 'alpha-preclinical' ) : __( 'Left off Joan\'s profile.', 'alpha-preclinical' );
		},
	);
	$q[] = array(
		'id'      => 'pdf_rights',
		'label'   => __( 'May Alpha host the publisher PDF of each paper on its own site?', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'all'  => __( 'Yes, for all of them', 'alpha-preclinical' ),
			'some' => __( 'Only some (see notes)', 'alpha-preclinical' ),
			'none' => __( 'No, link to the journal instead', 'alpha-preclinical' ),
		),
		'notes'   => true,
		'help'    => __( 'Where we can\'t host the PDF, the paper links to its DOI page.', 'alpha-preclinical' ),
	);
	$s['science'] = array(
		'title' => __( 'Expertise and publications', 'alpha-preclinical' ),
		'intro' => __( 'Who leads what, and which papers we show.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	// ---------------------------------------------------------------- Contact and forms.
	$q   = array();
	$q[] = array(
		'id'      => 'phone',
		'label'   => __( 'Phone number to list on the site', 'alpha-preclinical' ),
		'type'    => 'text',
		'help'    => __( 'Leave empty to keep the phone number off the site. It shows in the footer and on the Contact page.', 'alpha-preclinical' ),
		'current' => static function () {
			return alpha_site( 'phone' );
		},
		'apply'   => static function ( $v ) {
			alpha_review_set_site( 'phone', $v );
			return '' === $v ? __( 'No phone shown.', 'alpha-preclinical' ) : __( 'Shown on the site.', 'alpha-preclinical' );
		},
	);
	$q[] = array(
		'id'      => 'quote_inbox',
		'label'   => __( 'Who receives quote requests from the website?', 'alpha-preclinical' ),
		'type'    => 'email',
		'help'    => __( 'The default inbox for the quote form.', 'alpha-preclinical' ),
		'current' => static function () {
			return alpha_site( 'quote_inbox' ) ? alpha_site( 'quote_inbox' ) : alpha_site( 'email' );
		},
		'apply'   => static function ( $v ) {
			alpha_review_set_site( 'quote_inbox', $v );
			return __( 'Quote form now sends here.', 'alpha-preclinical' );
		},
	);
	foreach ( alpha_study_types() as $type ) {
		if ( in_array( $type, array( 'A study quote', 'Something else' ), true ) ) {
			continue;
		}
		$q[] = array(
			'id'      => 'route_' . sanitize_key( $type ),
			/* translators: %s: study type. */
			'label'   => sprintf( __( 'Send "%s" requests to a different person?', 'alpha-preclinical' ), $type ),
			'type'    => 'email',
			'help'    => __( 'Optional. Leave empty to use the default inbox.', 'alpha-preclinical' ),
			'current' => static function () use ( $type ) {
				$inbox = alpha_route_inbox( $type );
				return $inbox === alpha_route_inbox( '' ) ? '' : $inbox;
			},
			'apply'   => static function ( $v ) use ( $type ) {
				$lines = array();
				foreach ( preg_split( '/\r\n|\r|\n/', alpha_site( 'routing' ) ) as $line ) {
					$parts = array_map( 'trim', explode( '=', $line, 2 ) );
					if ( 2 === count( $parts ) && 0 !== strcasecmp( $parts[0], $type ) ) {
						$lines[] = $parts[0] . ' = ' . $parts[1];
					}
				}
				if ( '' !== $v ) {
					$lines[] = $type . ' = ' . $v;
				}
				alpha_review_set_site( 'routing', implode( "\n", $lines ) );
				return '' === $v ? __( 'Uses the default inbox.', 'alpha-preclinical' ) : __( 'Routing updated.', 'alpha-preclinical' );
			},
		);
	}
	$q[] = array(
		'id'      => 'careers_inbox',
		'label'   => __( 'Who receives job applications?', 'alpha-preclinical' ),
		'type'    => 'email',
		'help'    => __( 'Optional. Empty = the quote inbox.', 'alpha-preclinical' ),
		'current' => static function () {
			return alpha_site( 'careers_inbox' );
		},
		'apply'   => static function ( $v ) {
			alpha_review_set_site( 'careers_inbox', $v );
			return __( 'Updated.', 'alpha-preclinical' );
		},
	);
	$q[] = array(
		'id'      => 'a11y_response',
		'label'   => __( 'How quickly can Alpha respond to an accessibility request?', 'alpha-preclinical' ),
		'type'    => 'text',
		'help'    => __( 'Shown on the Accessibility statement, e.g. "within 2 business days".', 'alpha-preclinical' ),
		'link'    => home_url( '/accessibility/' ),
		'current' => static function () {
			return alpha_site( 'a11y_response' );
		},
		'apply'   => static function ( $v ) {
			alpha_review_set_site( 'a11y_response', $v );
			return __( 'Shown on the Accessibility statement.', 'alpha-preclinical' );
		},
	);
	$s['contact'] = array(
		'title' => __( 'Contact and forms', 'alpha-preclinical' ),
		'intro' => __( 'Where website enquiries go, and what we publish.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	// ---------------------------------------------------------------- Careers.
	$q    = array();
	$jobs = get_posts(
		array(
			'post_type'      => 'job',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	foreach ( $jobs as $job ) {
		$q[] = array(
			'id'      => 'job_' . $job->ID,
			/* translators: %s: role title. */
			'label'   => sprintf( __( 'Is "%s" still open?', 'alpha-preclinical' ), $job->post_title ),
			'type'    => 'choice',
			'options' => array(
				'open'   => __( 'Yes, keep it listed', 'alpha-preclinical' ),
				'closed' => __( 'No, take it down', 'alpha-preclinical' ),
			),
			'link'    => get_permalink( $job ),
			'current' => static function () use ( $job ) {
				return 'publish' === get_post_status( $job ) ? 'open' : 'closed';
			},
			'apply'   => static function ( $v ) use ( $job ) {
				wp_update_post(
					array(
						'ID'          => $job->ID,
						'post_status' => 'closed' === $v ? 'draft' : 'publish',
					)
				);
				return 'closed' === $v ? __( 'Taken down.', 'alpha-preclinical' ) : __( 'Listed.', 'alpha-preclinical' );
			},
		);
	}
	$q[] = array(
		'id'    => 'new_roles',
		'label' => __( 'Any new roles to add?', 'alpha-preclinical' ),
		'type'  => 'textarea',
		'help'  => __( 'Title, team, and a few lines on the role. Jeremy adds them.', 'alpha-preclinical' ),
	);
	$s['careers'] = array(
		'title' => __( 'Careers', 'alpha-preclinical' ),
		'intro' => __( 'Postings show Worcester, MA as the location.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	// ---------------------------------------------------------------- Blog.
	$q   = array();
	$q[] = array(
		'id'      => 'editorial_policy',
		'label'   => __( 'Is every blog post reviewed by a second PhD or DVM before it is published?', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'yes' => __( 'Yes (name the reviewer in notes)', 'alpha-preclinical' ),
			'no'  => __( 'No, drop that line', 'alpha-preclinical' ),
		),
		'notes'   => true,
	);
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'draft', 'pending', 'publish' ),
			'posts_per_page' => 20,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	foreach ( $posts as $post ) {
		$author = (string) get_post_meta( $post->ID, 'author_member', true );
		$q[]    = array(
			'id'      => 'post_' . $post->ID,
			/* translators: 1: post title, 2: author name. */
			'label'   => sprintf( __( '%1$s (by %2$s)', 'alpha-preclinical' ), $post->post_title, $author && isset( $people[ $author ] ) ? $people[ $author ] : __( 'Alpha', 'alpha-preclinical' ) ),
			'type'    => 'approval',
			'post'    => $post->ID,
			'help'    => __( 'Read the draft, approve it or note changes, and add one detail from your own lab work in the notes: a lesson learned, a typical number, a mistake you often see.', 'alpha-preclinical' ),
		);
	}
	$s['blog'] = array(
		'title' => __( 'Blog posts', 'alpha-preclinical' ),
		'intro' => __( 'Seven drafts, each in its author\'s voice. Posts go live one every one to two weeks after launch.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	// ---------------------------------------------------------------- Legal, brand and files.
	$q   = array();
	$q[] = array(
		'id'      => 'legal',
		'label'   => __( 'Attorney review of the Privacy policy and Terms of use', 'alpha-preclinical' ),
		'type'    => 'choice',
		'options' => array(
			'not_started' => __( 'Not started', 'alpha-preclinical' ),
			'in_review'   => __( 'With our attorney', 'alpha-preclinical' ),
			'approved'    => __( 'Approved (changes in notes or uploaded)', 'alpha-preclinical' ),
		),
		'notes'   => true,
		'link'    => home_url( '/privacy-policy/' ),
	);
	$q[] = array(
		'id'    => 'brand',
		'label' => __( 'Brand book recommendations: minimum logo size, colour proportions and line-icon style', 'alpha-preclinical' ),
		'type'  => 'approval',
	);
	$q[] = array(
		'id'     => 'files_photos',
		'label'  => __( 'Original, full-resolution photos (team, building, lab)', 'alpha-preclinical' ),
		'type'   => 'files',
		'accept' => 'image',
		'help'   => __( 'The photos on the site now are small copies from the old website. JPG, PNG, WebP or TIFF.', 'alpha-preclinical' ),
	);
	$q[] = array(
		'id'     => 'files_lab',
		'label'  => __( 'Lab photos for each therapeutic area', 'alpha-preclinical' ),
		'type'   => 'files',
		'accept' => 'image',
		'help'   => __( 'Benches, instruments and the vivarium work well. No animals need to be shown.', 'alpha-preclinical' ),
	);
	$q[] = array(
		'id'     => 'files_logo',
		'label'  => __( 'Vector logo files and the sign maker\'s colour spec', 'alpha-preclinical' ),
		'type'   => 'files',
		'accept' => 'docs',
		'help'   => __( 'AI, EPS or PDF, plus any document with the Pantone or CMYK values. Needed before business cards are printed.', 'alpha-preclinical' ),
	);
	$q[] = array(
		'id'     => 'files_other',
		'label'  => __( 'Anything else (legal edits, new paper PDFs, documents)', 'alpha-preclinical' ),
		'type'   => 'files',
		'accept' => 'docs',
		'notes'  => true,
	);
	$s['launch'] = array(
		'title' => __( 'Legal, brand and files', 'alpha-preclinical' ),
		'intro' => __( 'Last items before launch.', 'alpha-preclinical' ),
		'q'     => $q,
	);

	/**
	 * Filters the Content review questions.
	 *
	 * @param array $s Sections.
	 */
	return apply_filters( 'alpha_review_sections', $s );
}

/**
 * Whether a question has a complete answer.
 *
 * @param array $q Question.
 * @param array $a Stored answer or null.
 * @return bool
 */
function alpha_review_done( $q, $a ) {
	if ( ! $a ) {
		return false;
	}
	if ( 'files' === $q['type'] ) {
		return ! empty( $a['value'] ) || ! empty( $a['notes'] );
	}
	return '' !== (string) $a['value'] || ! empty( $a['notes'] ) || in_array( $q['type'], array( 'text', 'url', 'email' ), true );
}

/**
 * Allowed upload types for a files question.
 *
 * @param string $accept image|docs.
 * @return array ext => mime
 */
function alpha_review_mimes( $accept ) {
	$images = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'tif|tiff'     => 'image/tiff',
		'heic'         => 'image/heic',
	);
	$docs   = array(
		'pdf'  => 'application/pdf',
		'ai'   => 'application/postscript',
		'eps'  => 'application/postscript',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'zip'  => 'application/zip',
	);
	return 'image' === $accept ? $images : $images + $docs;
}

/**
 * Save one section. admin-post.php?action=alpha_review_save, logged-in users only.
 */
function alpha_review_save() {
	if ( ! current_user_can( 'alpha_review' ) ) {
		wp_die( esc_html__( 'You do not have permission to answer the content review.', 'alpha-preclinical' ), 403 );
	}
	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
	check_admin_referer( 'alpha_review_' . $section, 'alpha_review_nonce' );

	$sections = alpha_review_sections();
	if ( ! isset( $sections[ $section ] ) ) {
		wp_die( esc_html__( 'Unknown section.', 'alpha-preclinical' ), 400 );
	}
	$answers = alpha_review_answers();
	$changed = array();
	$user    = get_current_user_id();

	foreach ( $sections[ $section ]['q'] as $q ) {
		$id    = $q['id'];
		$field = 'q_' . $id;
		$prev  = isset( $answers[ $id ] ) ? $answers[ $id ] : null;
		// A field the form didn't send (an unanswered radio group, a stale form) is left as it is.
		if ( ! isset( $_POST[ $field ] ) && ! isset( $_POST[ $field . '_notes' ] ) && ( 'files' !== $q['type'] || empty( $_FILES[ $field ]['name'][0] ) ) ) {
			continue;
		}
		$value = '';
		$notes = isset( $_POST[ $field . '_notes' ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field . '_notes' ] ) ) : '';

		switch ( $q['type'] ) {
			case 'email':
				$value = isset( $_POST[ $field ] ) ? sanitize_email( wp_unslash( $_POST[ $field ] ) ) : '';
				break;
			case 'url':
				$value = isset( $_POST[ $field ] ) ? esc_url_raw( wp_unslash( $_POST[ $field ] ) ) : '';
				break;
			case 'textarea':
				$value = isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '';
				break;
			case 'choice':
			case 'person':
			case 'approval':
				$raw     = isset( $_POST[ $field ] ) ? sanitize_key( wp_unslash( $_POST[ $field ] ) ) : '';
				$options = 'approval' === $q['type'] ? array( 'approved' => 1, 'changes' => 1 ) : $q['options'];
				$value   = isset( $options[ $raw ] ) ? $raw : '';
				break;
			case 'files':
				$value = $prev && is_array( $prev['value'] ) ? $prev['value'] : array();
				$value = array_merge( $value, alpha_review_handle_files( $field, $q ) );
				break;
			default:
				$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		}

		// Compare with the last answer, or with what the site shows now: unchanged fields are not answers.
		$baseline       = $prev ? $prev['value'] : ( isset( $q['current'] ) ? (string) call_user_func( $q['current'] ) : ( 'files' === $q['type'] ? array() : '' ) );
		$baseline_notes = $prev && isset( $prev['notes'] ) ? $prev['notes'] : '';
		if ( $value === $baseline && $notes === $baseline_notes ) {
			continue;
		}
		$applied = '';
		if ( isset( $q['apply'] ) && ! is_array( $value ) && $value !== $baseline ) {
			$applied = (string) call_user_func( $q['apply'], $value );
		} elseif ( $prev && isset( $prev['applied'] ) ) {
			$applied = $prev['applied'];
		}
		$answers[ $id ] = array(
			'value'   => $value,
			'notes'   => $notes,
			'by'      => $user,
			'at'      => time(),
			'applied' => $applied,
		);
		$changed[]      = $q['label'];
	}

	update_option( 'alpha_review', $answers, false );

	if ( $changed ) {
		$to = alpha_site( 'review_notify' ) ? alpha_site( 'review_notify' ) : get_option( 'admin_email' );
		$me = wp_get_current_user();
		wp_mail(
			$to,
			/* translators: 1: person, 2: section. */
			sprintf( __( 'Content review: %1$s answered %2$s', 'alpha-preclinical' ), $me->display_name, $sections[ $section ]['title'] ),
			implode( "\n", array_map( static function ( $l ) {
				return '- ' . $l;
			}, $changed ) ) . "\n\n" . admin_url( 'tools.php?page=alpha-review' )
		);
	}

	$back = add_query_arg( 'saved', $section, alpha_review_url() ) . '#review-' . $section;
	wp_safe_redirect( $back );
	exit;
}
add_action( 'admin_post_alpha_review_save', 'alpha_review_save' );

/**
 * Store uploaded files for a question in the media library.
 * Called only from alpha_review_save() after its nonce and capability checks.
 *
 * @param string $field Field name.
 * @param array  $q     Question.
 * @return int[] Attachment IDs.
 */
function alpha_review_handle_files( $field, $q ) {
	if ( empty( $_FILES[ $field ]['name'] ) || ! is_array( $_FILES[ $field ]['name'] ) || ! current_user_can( 'upload_files' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the caller.
		return array();
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$files = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified by the caller; each file is checked by wp_handle_upload.
	$mimes = alpha_review_mimes( isset( $q['accept'] ) ? $q['accept'] : 'docs' );
	$ids   = array();
	$count = min( 20, count( $files['name'] ) );
	for ( $i = 0; $i < $count; $i++ ) {
		if ( empty( $files['name'][ $i ] ) || ! empty( $files['error'][ $i ] ) ) {
			continue;
		}
		$one   = array(
			'name'     => sanitize_file_name( $files['name'][ $i ] ),
			'type'     => $files['type'][ $i ],
			'tmp_name' => $files['tmp_name'][ $i ],
			'error'    => $files['error'][ $i ],
			'size'     => $files['size'][ $i ],
		);
		$check = wp_check_filetype_and_ext( $one['tmp_name'], $one['name'], $mimes );
		if ( empty( $check['ext'] ) ) {
			continue;
		}
		$id = media_handle_sideload(
			$one,
			0,
			/* translators: %s: question label. */
			sprintf( __( 'Content review: %s', 'alpha-preclinical' ), $q['label'] ),
			array( 'post_author' => get_current_user_id() )
		);
		if ( ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_alpha_review_question', $q['id'] );
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

/**
 * Allow AI/EPS/TIFF/HEIC uploads only inside the review handler (WordPress blocks them by default).
 *
 * @param array $mimes Allowed mimes.
 * @return array
 */
function alpha_review_upload_mimes( $mimes ) {
	if ( doing_action( 'admin_post_alpha_review_save' ) && current_user_can( 'alpha_review' ) ) {
		$mimes += alpha_review_mimes( 'docs' );
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'alpha_review_upload_mimes' );

/**
 * Admin: Tools › Content review answers.
 */
function alpha_review_admin_menu() {
	add_management_page( __( 'Content review answers', 'alpha-preclinical' ), __( 'Content review', 'alpha-preclinical' ), 'manage_options', 'alpha-review', 'alpha_review_admin_page' );
}
add_action( 'admin_menu', 'alpha_review_admin_menu' );

/**
 * Format an answer for display.
 *
 * @param array $q Question.
 * @param array $a Answer.
 * @return string Plain text.
 */
function alpha_review_value_text( $q, $a ) {
	$v = $a['value'];
	if ( is_array( $v ) ) {
		return implode( ', ', array_map( 'wp_get_attachment_url', $v ) );
	}
	if ( 'approval' === $q['type'] ) {
		return 'approved' === $v ? __( 'Approved', 'alpha-preclinical' ) : ( 'changes' === $v ? __( 'Needs changes', 'alpha-preclinical' ) : '' );
	}
	if ( isset( $q['options'][ $v ] ) ) {
		return $q['options'][ $v ];
	}
	return (string) $v;
}

/**
 * Admin page.
 */
function alpha_review_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$answers = alpha_review_answers();
	$total   = 0;
	$done    = 0;
	echo '<div class="wrap"><h1>' . esc_html__( 'Content review answers', 'alpha-preclinical' ) . '</h1>';
	echo '<p>' . esc_html__( 'Answers from the Content review page on the staging site. Applied answers have already updated the site.', 'alpha-preclinical' ) . ' <a href="' . esc_url( alpha_review_url() ) . '">' . esc_html__( 'Open the review page', 'alpha-preclinical' ) . '</a> · <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=alpha_review_csv' ), 'alpha_review_csv' ) ) . '">' . esc_html__( 'Download CSV', 'alpha-preclinical' ) . '</a></p>';
	foreach ( alpha_review_sections() as $section ) {
		echo '<h2>' . esc_html( $section['title'] ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Question', 'alpha-preclinical' ) . '</th><th>' . esc_html__( 'Answer', 'alpha-preclinical' ) . '</th><th>' . esc_html__( 'Notes', 'alpha-preclinical' ) . '</th><th>' . esc_html__( 'By', 'alpha-preclinical' ) . '</th><th>' . esc_html__( 'Applied', 'alpha-preclinical' ) . '</th></tr></thead><tbody>';
		foreach ( $section['q'] as $q ) {
			++$total;
			$a = isset( $answers[ $q['id'] ] ) ? $answers[ $q['id'] ] : null;
			if ( alpha_review_done( $q, $a ) ) {
				++$done;
			}
			$by = $a ? get_userdata( (int) $a['by'] ) : null;
			echo '<tr><td>' . esc_html( $q['label'] ) . '</td><td>' . ( $a ? esc_html( alpha_review_value_text( $q, $a ) ) : '<em>' . esc_html__( 'Waiting', 'alpha-preclinical' ) . '</em>' ) . '</td><td>' . ( $a ? nl2br( esc_html( $a['notes'] ) ) : '' ) . '</td><td>' . ( $by ? esc_html( $by->display_name . ', ' . wp_date( 'M j, g:ia', (int) $a['at'] ) ) : '' ) . '</td><td>' . ( $a ? esc_html( $a['applied'] ) : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	/* translators: 1: answered, 2: total. */
	echo '<p><strong>' . esc_html( sprintf( __( '%1$d of %2$d answered.', 'alpha-preclinical' ), $done, $total ) ) . '</strong></p></div>';
}

/**
 * CSV export: nonce + capability.
 */
function alpha_review_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'alpha-preclinical' ), 403 );
	}
	check_admin_referer( 'alpha_review_csv' );
	$answers = alpha_review_answers();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=alpha-content-review-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming the download.
	fputcsv( $out, array( 'Section', 'Question', 'Answer', 'Notes', 'By', 'When', 'Applied' ) );
	foreach ( alpha_review_sections() as $section ) {
		foreach ( $section['q'] as $q ) {
			$a  = isset( $answers[ $q['id'] ] ) ? $answers[ $q['id'] ] : null;
			$by = $a ? get_userdata( (int) $a['by'] ) : null;
			$row = array( $section['title'], $q['label'], $a ? alpha_review_value_text( $q, $a ) : '', $a ? $a['notes'] : '', $by ? $by->display_name : '', $a ? wp_date( 'Y-m-d H:i', (int) $a['at'] ) : '', $a ? $a['applied'] : '' );
			// Neutralise spreadsheet formulas in user-entered cells.
			$row = array_map(
				static function ( $c ) {
					return preg_match( '/^[=+\-@]/', (string) $c ) ? "'" . $c : $c;
				},
				$row
			);
			fputcsv( $out, $row );
		}
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_alpha_review_csv', 'alpha_review_csv' );
