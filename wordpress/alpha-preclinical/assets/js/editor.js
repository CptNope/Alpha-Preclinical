/**
 * Alpha Preclinical editor script (no build step; uses the wp.* globals).
 *
 * 1. Editing UI for every alpha/* block: a live server render plus sidebar controls
 *    generated from the block's attributes. FAQ blocks get inline editing.
 * 2. Editor-side Block Bindings sources, so bound values show in the canvas.
 * 3. Sidebar panels for the meta fields of publications, team members, roles,
 *    pages and posts.
 */
( function ( wp, data ) {
	'use strict';
	if ( ! wp || ! data ) {
		return;
	}
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var C = wp.components;
	var SSR = wp.serverSideRender;

	var LABELS = {
		variant: 'Variant', meltInto: 'Last band melts into', showPause: 'Show Pause button', maxWidth: 'Max width (px)',
		heading: 'Heading', lead: 'Lead', showBreadcrumbs: 'Show breadcrumbs', tone: 'Colour', draw: 'Draw in on arrival',
		members: 'Team member slugs (comma separated)', label: 'Label', value: 'Number', suffix: 'Suffix', size: 'Size',
		schema: 'Print FAQPage schema', text: 'Text', buttonText: 'Button text', buttonUrl: 'Button link (empty = quote form)',
		secondaryText: 'Second button text', secondaryUrl: 'Second button link', taxonomy: 'Filter by', allLabel: '"All" label',
		showCounts: 'Show counts', formType: 'Form', submitText: 'Submit button', successHeading: 'Success heading',
		email: 'Email (empty = site setting)', address: 'Address (empty = site setting)', phone: 'Phone (empty = site setting)',
		showCareers: 'Show careers link', slug: 'Team member slug (empty = from Query Loop)', titleField: 'Title shown',
		showLeads: 'Show areas led', linkedin: 'LinkedIn URL', orcid: 'ORCID URL', scholar: 'Google Scholar URL',
		pubmed: 'PubMed URL', field: 'Person from post field', source: 'Source', key: 'Field', current: 'Current page label',
	};

	function control( name, def, value, set, bound ) {
		var label = LABELS[ name ] || name;
		var help = bound ? __( 'Bound to a data source. Unbind it in the block’s Attributes panel to type a value.', 'alpha-preclinical' ) : undefined;
		if ( def.enum ) {
			return el( C.SelectControl, {
				key: name, label: label, value: value, help: help, disabled: bound,
				options: def.enum.map( function ( v ) {
					return { label: v, value: v };
				} ),
				onChange: set,
			} );
		}
		if ( def.type === 'boolean' ) {
			return el( C.ToggleControl, { key: name, label: label, checked: !! value, onChange: set, __nextHasNoMarginBottom: true } );
		}
		if ( def.type === 'number' ) {
			return el( C.TextControl, { key: name, type: 'number', label: label, value: value, help: help, disabled: bound, onChange: function ( v ) {
				set( parseInt( v, 10 ) || 0 );
			} } );
		}
		var long = [ 'text', 'lead', 'answer', 'address' ].indexOf( name ) > -1;
		return el( long ? C.TextareaControl : C.TextControl, { key: name, label: label, value: value || '', help: help, disabled: bound, onChange: set } );
	}

	function genericEdit( name ) {
		return function ( props ) {
			var type = wp.blocks.getBlockType( name );
			var bindings = ( props.attributes.metadata && props.attributes.metadata.bindings ) || {};
			var controls = Object.keys( type.attributes )
				.filter( function ( a ) {
					return [ 'metadata', 'lock', 'className', 'align', 'style', 'anchor' ].indexOf( a ) === -1;
				} )
				.map( function ( a ) {
					return control( a, type.attributes[ a ], props.attributes[ a ], function ( v ) {
						var o = {};
						o[ a ] = v;
						props.setAttributes( o );
					}, !! bindings[ a ] );
				} );
			var blockProps = be.useBlockProps( { className: 'alpha-editor-ssr' } );
			return el(
				Fragment,
				null,
				el( be.InspectorControls, null, el( C.PanelBody, { title: __( 'Settings', 'alpha-preclinical' ), initialOpen: true }, controls ) ),
				el( 'div', blockProps, el( SSR, {
					block: name,
					attributes: props.attributes,
					urlQueryArgs: props.context && props.context.postId ? { post_id: props.context.postId } : undefined,
					EmptyResponsePlaceholder: function () {
						return el( C.Placeholder, { icon: type.icon && type.icon.src, label: type.title, instructions: type.description } );
					},
				} ) )
			);
		};
	}

	// Register edit UIs for the server-registered blocks.
	var names = [ 'strata-band', 'hero-alpha', 'page-header', 'contour', 'lead-chip', 'stat', 'cta-band', 'filter-chips', 'quote-form', 'contact-card', 'person-card', 'person-links', 'meta-list', 'breadcrumbs', 'content-review' ];
	names.forEach( function ( n ) {
		var name = 'alpha/' + n;
		wp.blocks.registerBlockType( name, {
			edit: genericEdit( name ),
			save: function () {
				return null;
			},
		} );
	} );

	// FAQ: inner blocks, edited inline.
	wp.blocks.registerBlockType( 'alpha/faq', {
		edit: function () {
			var blockProps = be.useBlockProps( { className: 'alpha-faq' } );
			var inner = be.useInnerBlocksProps( blockProps, {
				allowedBlocks: [ 'alpha/faq-item' ],
				template: [ [ 'alpha/faq-item', { open: true } ], [ 'alpha/faq-item' ] ],
			} );
			return el( 'div', inner );
		},
		save: function () {
			return el( be.InnerBlocks.Content );
		},
	} );
	wp.blocks.registerBlockType( 'alpha/faq-item', {
		edit: function ( props ) {
			var a = props.attributes;
			var blockProps = be.useBlockProps( { className: 'alpha-faq__item' } );
			return el(
				'div',
				blockProps,
				el( be.InspectorControls, null, el( C.PanelBody, { title: __( 'Settings', 'alpha-preclinical' ) }, el( C.ToggleControl, {
					label: __( 'Open on load', 'alpha-preclinical' ), checked: !! a.open, __nextHasNoMarginBottom: true,
					onChange: function ( v ) {
						props.setAttributes( { open: v } );
					},
				} ) ) ),
				el( be.RichText, { tagName: 'h3', className: 'alpha-faq__q', value: a.question, allowedFormats: [ 'core/italic' ], placeholder: __( 'Question', 'alpha-preclinical' ), onChange: function ( v ) {
					props.setAttributes( { question: v } );
				} } ),
				el( be.RichText, { tagName: 'p', className: 'alpha-faq__a', value: a.answer, placeholder: __( 'Answer', 'alpha-preclinical' ), onChange: function ( v ) {
					props.setAttributes( { answer: v } );
				} } )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------- Editor-side binding sources ---------- */
	if ( wp.blocks.registerBlockBindingsSource ) {
		var meta = function ( select, ctx, type ) {
			if ( ! ctx || ! ctx.postId ) {
				return null;
			}
			var rec = select( 'core' ).getEditedEntityRecord( 'postType', ctx.postType || type, ctx.postId );
			return rec && rec.type === type ? rec : null;
		};
		var esc = function ( s ) {
			return String( s || '' ).replace( /[&<>"]/g, function ( c ) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ];
			} );
		};
		var each = function ( bindings, fn ) {
			var out = {};
			Object.keys( bindings ).forEach( function ( attr ) {
				var v = fn( bindings[ attr ].args || {} );
				out[ attr ] = v === null || v === undefined || v === '' ? '' : v;
			} );
			return out;
		};
		var source = function ( name, label, fn, context ) {
			try {
				wp.blocks.registerBlockBindingsSource( {
					name: name, label: label, usesContext: context || [],
					getValues: function ( o ) {
						return each( o.bindings, function ( args ) {
							return fn( args, o.select, o.context );
						} );
					},
					canUserEditValue: function () {
						return false;
					},
				} );
			} catch ( e ) {}
		};
		source( 'alpha/site', __( 'Alpha: site details', 'alpha-preclinical' ), function ( args ) {
			return ( data.site || {} )[ args.key ] || '';
		} );
		source( 'alpha/study', __( 'Alpha: study quote link', 'alpha-preclinical' ), function ( args ) {
			if ( args.key === 'label' ) {
				return args.study ? 'Plan your ' + args.study + ' study' : 'Request a study quote';
			}
			return data.contactUrl.replace( '#form', '' ) + ( args.study ? ( data.contactUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + 'study=' + encodeURIComponent( args.study ) : '' ) + '#form';
		} );
		source( 'alpha/publication', __( 'Alpha: publication', 'alpha-preclinical' ), function ( args, select, ctx ) {
			var r = meta( select, ctx, 'publication' );
			if ( ! r ) {
				return '';
			}
			var m = r.meta || {};
			var title = r.title && ( r.title.raw !== undefined ? r.title.raw : r.title );
			var src = ( m.issue_date || '' ) + ( m.volume ? ';' + m.volume : '' ) + ( m.issue ? '(' + m.issue + ')' : '' ) + ( m.pages ? ':' + m.pages : '' ) + '.' + ( m.epub_date ? ' Epub ' + m.epub_date + '.' : '' );
			var alpha = ( m.alpha_authors || '' ).split( ',' ).map( function ( s ) {
				return s.trim();
			} );
			switch ( args.key ) {
				case 'title':
				case 'title_link':
					return esc( title );
				case 'authors':
					return ( m.authors || '' ).split( ',' ).map( function ( s ) {
						s = s.trim();
						return alpha.indexOf( s ) > -1 ? '<strong>' + esc( s ) + '</strong>' : esc( s );
					} ).join( ', ' );
				case 'journal':
					return esc( m.journal );
				case 'source':
					return esc( src );
				case 'citation':
					return '<em>' + esc( m.journal ) + '</em>. ' + esc( src );
				case 'ids':
					return [ m.doi ? 'DOI ' + esc( m.doi ) : '', m.pmid ? 'PMID ' + esc( m.pmid ) : '', m.pmcid ? 'PMCID ' + esc( m.pmcid ) : '' ].filter( Boolean ).join( ' · ' );
				case 'pdf_url':
					return m.pdf_url || '';
				case 'pubmed_url':
					return m.pmid ? 'https://pubmed.ncbi.nlm.nih.gov/' + m.pmid + '/' : '';
				case 'pmc_url':
					return m.pmcid ? 'https://pmc.ncbi.nlm.nih.gov/articles/' + m.pmcid + '/' : '';
				case 'doi_url':
					return m.doi ? 'https://doi.org/' + m.doi : '';
			}
			return '';
		}, [ 'postId', 'postType' ] );
		source( 'alpha/person', __( 'Alpha: team member', 'alpha-preclinical' ), function ( args, select, ctx ) {
			var r = null;
			if ( args.slug || args.field ) {
				var slug = args.slug;
				if ( ! slug && ctx && ctx.postId ) {
					var host = select( 'core' ).getEditedEntityRecord( 'postType', ctx.postType || 'post', ctx.postId );
					slug = host && host.meta ? host.meta[ args.field ] : '';
				}
				var found = slug ? select( 'core' ).getEntityRecords( 'postType', 'team_member', { slug: slug } ) : null;
				r = found && found[ 0 ];
			} else {
				r = meta( select, ctx, 'team_member' );
			}
			if ( ! r ) {
				return '';
			}
			var m = r.meta || {};
			var name = ( r.title && ( r.title.raw !== undefined ? r.title.raw : r.title.rendered ) ) + ( m.credentials ? ', ' + m.credentials : '' );
			switch ( args.key ) {
				case 'name':
				case 'name_link':
					return esc( name );
				case 'job_title':
					return esc( m.job_title );
				case 'short_title':
					return esc( m.short_title || m.job_title );
				case 'bio':
					return esc( r.excerpt && ( r.excerpt.raw !== undefined ? r.excerpt.raw : '' ) );
				case 'linkedin':
				case 'orcid':
				case 'scholar':
					return m[ args.key ] || '';
			}
			return '';
		}, [ 'postId', 'postType' ] );
		source( 'alpha/job', __( 'Alpha: open role', 'alpha-preclinical' ), function ( args, select, ctx ) {
			var r = meta( select, ctx, 'job' );
			if ( ! r ) {
				return '';
			}
			var m = r.meta || {};
			if ( args.key === 'title' ) {
				return esc( r.title && ( r.title.raw !== undefined ? r.title.raw : r.title.rendered ) );
			}
			if ( args.key === 'summary' ) {
				return esc( m.summary );
			}
			if ( args.key === 'location' ) {
				return esc( m.location );
			}
			return '';
		}, [ 'postId', 'postType' ] );
	}

	/* ---------- Meta panels ---------- */
	if ( wp.plugins && ( wp.editor && wp.editor.PluginDocumentSettingPanel ) ) {
		var Panel = wp.editor.PluginDocumentSettingPanel;
		wp.plugins.registerPlugin( 'alpha-meta', {
			render: function () {
				var postType = wp.data.useSelect( function ( select ) {
					return select( 'core/editor' ).getCurrentPostType();
				}, [] );
				var fields = ( data.meta || {} )[ postType ];
				var entity = wp.coreData.useEntityProp( 'postType', postType || 'post', 'meta' );
				if ( ! fields || ! entity ) {
					return null;
				}
				var metaVals = entity[ 0 ] || {};
				var setMeta = entity[ 1 ];
				return el( Panel, { name: 'alpha-meta', title: __( 'Alpha Preclinical fields', 'alpha-preclinical' ), initialOpen: true }, fields.map( function ( f ) {
					var set = function ( v ) {
						var o = {};
						o[ f.key ] = v;
						setMeta( Object.assign( {}, metaVals, o ) );
					};
					if ( f.type === 'boolean' ) {
						return el( C.ToggleControl, { key: f.key, label: f.label, checked: !! metaVals[ f.key ], onChange: set, __nextHasNoMarginBottom: true } );
					}
					if ( f.type === 'integer' ) {
						return el( C.TextControl, { key: f.key, type: 'number', label: f.label, value: metaVals[ f.key ] || 0, onChange: function ( v ) {
							set( parseInt( v, 10 ) || 0 );
						} } );
					}
					return el( f.multi ? C.TextareaControl : C.TextControl, { key: f.key, label: f.label, value: metaVals[ f.key ] || '', onChange: set } );
				} ) );
			},
		} );
	}
} )( window.wp, window.alphaEditor );
