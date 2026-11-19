/**
 * Editing UI for the design's section blocks (no build step; uses the wp.* globals).
 *
 * Every section block renders on the server exactly as the approved design. In the
 * editor the canvas shows that live preview, and the sidebar lists every text, link
 * and image as a field. Repeated items (cards, rows, questions) can be added, removed
 * and reordered. Fields bound to a record (Block Bindings API) show where their value
 * comes from instead of an input.
 *
 * The blog article body holds the article text as normal paragraphs, headings and
 * lists, edited directly on the canvas.
 */
( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;
	var cfg = window.alphaSections || { blocks: {}, article: [] };

	function plain( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = String( html || '' );
		return ( d.textContent || '' ).replace( /\s+/g, ' ' ).trim();
	}

	function currentPostId() {
		var s = wp.data.select( 'core/editor' );
		return s && s.getCurrentPostId ? s.getCurrentPostId() : undefined;
	}

	/* One field. */
	function Field( props ) {
		var spec = props.spec;
		var value = props.value === undefined || props.value === null ? '' : props.value;
		var set = props.onChange;
		if ( props.bound ) {
			return el( c.Notice, { status: 'info', isDismissible: false, className: 'alpha-field-bound' },
				spec.label + ': ' + __( 'comes from', 'alpha-preclinical' ) + ' ' + props.bound );
		}
		switch ( spec.type ) {
			case 'items':
				return el( Items, { spec: spec, value: Array.isArray( value ) ? value : [], onChange: set } );
			case 'html':
				return el( c.TextareaControl, {
					label: spec.label,
					value: value,
					rows: value.length > 140 ? 5 : 2,
					help: /<[a-z]/i.test( value ) ? __( 'Inline formatting is kept: <b>, <i>, <a href="…">.', 'alpha-preclinical' ) : undefined,
					onChange: set,
					__nextHasNoMarginBottom: true
				} );
			case 'svg':
				return el( c.TextareaControl, { label: spec.label, value: value, rows: 3, onChange: set, className: 'alpha-code', __nextHasNoMarginBottom: true } );
			case 'image':
				return el( 'div', { className: 'alpha-field-image' },
					value ? el( 'img', { src: /^assets\//.test( value ) ? cfg.themeUrl + '/' + value : value, alt: '', style: { maxWidth: '100%', borderRadius: 6, marginBottom: 6 } } ) : null,
					el( c.TextControl, { label: spec.label, value: value, onChange: set, __nextHasNoMarginBottom: true } ),
					el( be.MediaUploadCheck, {},
						el( be.MediaUpload, {
							allowedTypes: [ 'image' ],
							onSelect: function ( media ) { set( media.url ); },
							render: function ( o ) {
								return el( c.Button, { variant: 'secondary', onClick: o.open, style: { marginTop: 6 } }, __( 'Choose image', 'alpha-preclinical' ) );
							}
						} )
					)
				);
			case 'url':
				return el( c.TextControl, {
					label: spec.label,
					value: value,
					type: 'text',
					help: __( 'A page on this site like /contact/#form, or a full address.', 'alpha-preclinical' ),
					onChange: set,
					__nextHasNoMarginBottom: true
				} );
			default:
				return el( c.TextControl, { label: spec.label, value: value, onChange: set, __nextHasNoMarginBottom: true } );
		}
	}

	/* A list of fields. */
	function Fields( props ) {
		var names = Object.keys( props.fields || {} );
		return el( c.__experimentalVStack || 'div', { spacing: 4 },
			names.map( function ( name ) {
				return el( Field, {
					key: name,
					spec: props.fields[ name ],
					value: props.values[ name ],
					bound: props.bindings && props.bindings[ name ] ? describeBinding( props.bindings[ name ] ) : ( props.live && props.live[ name ] ? props.live[ name ] : '' ),
					onChange: function ( v ) {
						var next = {};
						next[ name ] = v;
						props.onChange( next );
					}
				} );
			} )
		);
	}

	function describeBinding( b ) {
		var a = b.args || {};
		if ( b.source === 'alpha/person' ) {
			return __( 'the team member', 'alpha-preclinical' ) + ' "' + ( a.slug || '' ) + '" (' + ( a.key || '' ) + '). ' + __( 'Edit it under Team.', 'alpha-preclinical' );
		}
		return b.source;
	}

	/* Repeated items: add, remove, reorder. */
	function Items( props ) {
		var list = props.value;
		var spec = props.spec;
		function update( next ) { props.onChange( next ); }
		function move( i, d ) {
			var next = list.slice();
			var t = next[ i ];
			next[ i ] = next[ i + d ];
			next[ i + d ] = t;
			update( next );
		}
		return el( 'div', { className: 'alpha-items' },
			el( 'p', { className: 'alpha-items__label' }, spec.label + ' (' + list.length + ')' ),
			list.map( function ( item, i ) {
				var first = Object.keys( spec.fields ).filter( function ( k ) { return spec.fields[ k ].type === 'html'; } )[ 0 ];
				var title = ( i + 1 ) + '. ' + ( plain( first ? item[ first ] : '' ).slice( 0, 48 ) || __( 'Item', 'alpha-preclinical' ) );
				return el( c.PanelBody, { key: i, title: title, initialOpen: false, className: 'alpha-item' },
					el( Fields, {
						fields: spec.fields,
						values: item || {},
						onChange: function ( patch ) {
							var next = list.slice();
							next[ i ] = Object.assign( {}, item, patch );
							update( next );
						}
					} ),
					el( c.Flex, { justify: 'flex-start', gap: 2, style: { marginTop: 12 } },
						el( c.Button, { size: 'small', variant: 'secondary', disabled: i === 0, onClick: function () { move( i, -1 ); } }, __( 'Move up', 'alpha-preclinical' ) ),
						el( c.Button, { size: 'small', variant: 'secondary', disabled: i === list.length - 1, onClick: function () { move( i, 1 ); } }, __( 'Move down', 'alpha-preclinical' ) ),
						el( c.Button, { size: 'small', variant: 'secondary', onClick: function () { var n = list.slice(); n.splice( i + 1, 0, JSON.parse( JSON.stringify( item ) ) ); update( n ); } }, __( 'Duplicate', 'alpha-preclinical' ) ),
						el( c.Button, { size: 'small', isDestructive: true, variant: 'tertiary', onClick: function () { var n = list.slice(); n.splice( i, 1 ); update( n ); } }, __( 'Remove', 'alpha-preclinical' ) )
					)
				);
			} ),
			el( c.Button, {
				variant: 'secondary',
				onClick: function () {
					var base = list.length ? JSON.parse( JSON.stringify( list[ list.length - 1 ] ) ) : {};
					update( list.concat( [ base ] ) );
				}
			}, __( 'Add item', 'alpha-preclinical' ) )
		);
	}

	function sectionEdit( name, meta ) {
		return function ( props ) {
			var blockProps = be.useBlockProps( { className: 'alpha-section-preview' } );
			var bindings = ( props.attributes.metadata && props.attributes.metadata.bindings ) || {};
			var sidebar = el( be.InspectorControls, {},
				el( c.PanelBody, { title: __( 'Section content', 'alpha-preclinical' ), initialOpen: true },
					el( 'p', { className: 'alpha-help' }, __( 'This section matches the approved design. Change its text, links and images here; the preview updates.', 'alpha-preclinical' ) ),
					el( Fields, { fields: meta.fields, values: props.attributes, bindings: bindings, live: ( cfg.live || {} )[ name ] || {}, onChange: props.setAttributes } )
				)
			);
			if ( meta.inner ) {
				return el( Fragment, {}, sidebar,
					el( 'div', blockProps,
						el( 'div', { className: 'alpha-article-editor' },
							el( 'p', { className: 'alpha-article-editor__label' }, __( 'Article text. The "On this page" list and share links are set in the sidebar.', 'alpha-preclinical' ) ),
							el( 'div', { className: 'ap' }, el( 'article', { className: 'alpha-article' }, el( be.InnerBlocks, { allowedBlocks: cfg.article } ) ) )
						)
					)
				);
			}
			return el( Fragment, {}, sidebar,
				el( 'div', blockProps,
					el( 'div', { className: 'ap' },
						el( SSR, {
							block: name,
							attributes: props.attributes,
							urlQueryArgs: { post_id: currentPostId() },
							httpMethod: 'POST',
							LoadingResponsePlaceholder: function () { return el( c.Spinner ); }
						} )
					)
				)
			);
		};
	}

	Object.keys( cfg.blocks ).forEach( function ( name ) {
		var meta = cfg.blocks[ name ];
		wp.blocks.registerBlockType( name, {
			edit: sectionEdit( name, meta ),
			save: meta.inner ? function () { return el( be.InnerBlocks.Content ); } : function () { return null; }
		} );
	} );

	// The page frame used by the theme's templates (Site Editor).
	wp.blocks.registerBlockType( 'alpha/shell', {
		apiVersion: 3,
		title: __( 'Page frame', 'alpha-preclinical' ),
		category: 'alpha-sections',
		supports: { html: false, inserter: false },
		edit: function () {
			return el( 'div', be.useBlockProps( { className: 'ap' } ), el( be.InnerBlocks, {} ) );
		},
		save: function () { return el( be.InnerBlocks.Content ); }
	} );
} )( window.wp );
