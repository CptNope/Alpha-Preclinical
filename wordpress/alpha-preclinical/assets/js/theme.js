/**
 * Alpha Preclinical front end. No dependencies.
 * - Pause/play for the drifting strata (WCAG 2.2.2), paused by default under reduced motion
 * - Scroll reveals, contour line drawing, count-up numbers, alpha fill on scroll
 * - FAQ accordion, filter chips (with a live status), quote form preset + validation
 */
( function () {
	'use strict';

	var doc = document.documentElement;
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( ! reduce ) {
		doc.classList.add( 'has-motion' );
	}

	/* ---------- Strata pause buttons: one toggles them all ---------- */
	var toggles = document.querySelectorAll( '[data-motion-toggle]' );
	function setPaused( paused ) {
		doc.classList.toggle( 'motion-paused', paused );
		document.querySelectorAll( '[data-strata]' ).forEach( function ( s ) {
			s.classList.toggle( 'is-paused', paused );
		} );
		toggles.forEach( function ( t ) {
			t.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			t.textContent = paused ? t.dataset.playLabel || 'Play animation' : t.dataset.pauseLabel || 'Pause animation';
		} );
		try {
			window.localStorage.setItem( 'alphaMotionPaused', paused ? '1' : '0' );
		} catch ( e ) {}
	}
	var stored = null;
	try {
		stored = window.localStorage.getItem( 'alphaMotionPaused' );
	} catch ( e ) {}
	setPaused( reduce || stored === '1' );
	toggles.forEach( function ( t ) {
		t.addEventListener( 'click', function () {
			setPaused( t.getAttribute( 'aria-pressed' ) !== 'true' );
		} );
	} );

	/* ---------- Reveals ---------- */
	var revealSel = '.alpha-section .wp-block-columns, .alpha-model-row, .alpha-paper, .alpha-person, .alpha-stratum, .alpha-lead, .alpha-faq__item, .alpha-contour, .alpha-cta, .alpha-post-card, .alpha-stat';
	var items = document.querySelectorAll( revealSel );
	if ( ! reduce && 'IntersectionObserver' in window ) {
		items.forEach( function ( el ) {
			el.classList.add( 'alpha-reveal' );
			var sibs = el.parentElement ? Array.prototype.indexOf.call( el.parentElement.children, el ) : 0;
			el.style.setProperty( '--d', Math.min( sibs, 3 ) * 0.08 + 's' );
		} );
		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( e ) {
					if ( e.isIntersecting ) {
						e.target.classList.add( 'is-in' );
						e.target.removeAttribute( 'data-out' );
						count( e.target );
					} else {
						e.target.classList.remove( 'is-in' );
						e.target.setAttribute( 'data-out', e.boundingClientRect.top < 0 ? 'above' : 'below' );
					}
				} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
		);
		items.forEach( function ( el ) {
			io.observe( el );
		} );
		// Keyboard focus always reveals the section it lands in.
		document.addEventListener( 'focusin', function ( e ) {
			var r = e.target.closest && e.target.closest( '.alpha-reveal' );
			if ( r ) {
				r.classList.add( 'is-in' );
			}
		} );
	} else {
		items.forEach( function ( el ) {
			el.classList.add( 'is-in' );
		} );
	}

	/* ---------- Count-up (the real number is in aria-label) ---------- */
	function count( root ) {
		root.querySelectorAll( '[data-count]' ).forEach( function ( n ) {
			var to = parseFloat( n.dataset.count );
			if ( isNaN( to ) || reduce ) {
				return;
			}
			var dec = ( n.dataset.count.split( '.' )[ 1 ] || '' ).length;
			var start = null;
			function step( t ) {
				start = start || t;
				var p = Math.min( 1, ( t - start ) / 1200 );
				var eased = 1 - Math.pow( 1 - p, 3 );
				n.textContent = ( to * eased ).toFixed( dec );
				if ( p < 1 ) {
					window.requestAnimationFrame( step );
				}
			}
			window.requestAnimationFrame( step );
		} );
	}

	/* ---------- Homepage alpha fills further as you scroll ---------- */
	var lift = document.querySelector( '.alpha-hero-alpha .ap-a-lift' );
	if ( lift && ! reduce ) {
		var ticking = false;
		window.addEventListener(
			'scroll',
			function () {
				if ( ticking ) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame( function () {
					var y = Math.min( window.scrollY, 600 );
					lift.style.setProperty( '--alpha-fill', -( y / 600 ) * 30 + 'px' );
					ticking = false;
				} );
			},
			{ passive: true }
		);
	}

	/* ---------- FAQ ---------- */
	document.querySelectorAll( '[data-accordion]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var open = b.getAttribute( 'aria-expanded' ) === 'true';
			var panel = document.getElementById( b.getAttribute( 'aria-controls' ) );
			b.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			if ( panel ) {
				panel.hidden = open;
			}
		} );
	} );

	/* ---------- Filter chips: filter the next Query Loop in place ---------- */
	document.querySelectorAll( '[data-filter-group]' ).forEach( function ( group ) {
		var list = null;
		var el = group;
		while ( el && ! list ) {
			el = el.nextElementSibling;
			if ( el ) {
				list = el.matches( '.wp-block-query' ) ? el : el.querySelector( '.wp-block-query' );
			}
		}
		if ( ! list ) {
			return;
		}
		var status = group.querySelector( '[data-filter-status]' );
		var chips = group.querySelectorAll( '[data-filter]' );
		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var cls = chip.dataset.filter;
				var shown = 0;
				chips.forEach( function ( c ) {
					c.setAttribute( 'aria-pressed', c === chip ? 'true' : 'false' );
				} );
				list.querySelectorAll( '.wp-block-post' ).forEach( function ( post ) {
					var on = ! cls || post.classList.contains( cls );
					post.hidden = ! on;
					shown += on ? 1 : 0;
				} );
				if ( status ) {
					status.textContent = shown + ( shown === 1 ? ' result' : ' results' );
				}
			} );
		} );
	} );

	/* ---------- Forms: preset from ?study= / ?role=, inline email check ---------- */
	var params = new URLSearchParams( window.location.search );
	document.querySelectorAll( '[data-alpha-form]' ).forEach( function ( form ) {
		[ 'study_type', 'role' ].forEach( function ( name ) {
			var sel = form.querySelector( 'select[name="' + name + '"]' );
			var want = params.get( name === 'study_type' ? 'study' : 'role' );
			if ( sel && want ) {
				Array.prototype.forEach.call( sel.options, function ( o ) {
					if ( o.value === want || o.text === want ) {
						sel.value = o.value;
					}
				} );
			}
		} );
		form.addEventListener( 'submit', function ( e ) {
			var bad = null;
			form.querySelectorAll( '[required]' ).forEach( function ( f ) {
				var ok = f.type === 'email' ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( f.value.trim() ) : f.value.trim() !== '' || ( f.type === 'file' && f.files.length );
				f.setAttribute( 'aria-invalid', ok ? 'false' : 'true' );
				var err = f.getAttribute( 'aria-describedby' ) && document.getElementById( f.getAttribute( 'aria-describedby' ) );
				if ( err ) {
					err.hidden = ok;
				}
				if ( ! ok && ! bad ) {
					bad = f;
				}
			} );
			if ( bad ) {
				e.preventDefault();
				bad.focus();
			}
		} );
	} );
	var done = document.querySelector( '[data-form-done]' );
	if ( done ) {
		done.focus();
	}
} )();
