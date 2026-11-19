<?php
/**
 * Title: Featured publications
 * Slug: alpha/featured-publications
 * Categories: alpha-sections
 * Description: The live publication count and the three papers marked Feature on the homepage.
 *
 * @package AlphaPreclinical
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"alpha-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull alpha-section"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"42%"} -->
<div class="wp-block-column" style="flex-basis:42%"><!-- wp:alpha/stat {"value":"16","label":"peer-reviewed papers","size":"xl","metadata":{"bindings":{"value":{"source":"alpha/site","args":{"key":"publication_count"}}}}} /-->
<!-- wp:heading -->
<h2 class="wp-block-heading">Published, peer-reviewed science</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->
<!-- wp:column {"width":"58%"} -->
<div class="wp-block-column" style="flex-basis:58%"><!-- wp:query {"queryId":289,"query":{"perPage":3,"pages":0,"offset":0,"postType":"publication","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false,"alphaFeatured":true},"className":"alpha-papers alpha-papers--featured"} -->
<div class="wp-block-query alpha-papers alpha-papers--featured"><!-- wp:post-template -->
<!-- wp:group {"className":"alpha-paper","layout":{"type":"constrained"}} -->
<div class="wp-block-group alpha-paper"><!-- wp:heading {"level":3,"metadata":{"bindings":{"content":{"source":"alpha/publication","args":{"key":"title_link"}}}},"className":"alpha-paper__title"} -->
<h3 class="wp-block-heading alpha-paper__title">Paper title</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/publication","args":{"key":"authors"}}}},"className":"alpha-paper__by"} -->
<p class="alpha-paper__by">Authors</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/publication","args":{"key":"citation"}}}},"className":"alpha-paper__cite"} -->
<p class="alpha-paper__cite">Journal. Year;Vol(Issue):pages.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/publication","args":{"key":"ids"}}}},"className":"alpha-paper__ids"} -->
<p class="alpha-paper__ids">DOI · PMID · PMCID</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"metadata":{"bindings":{"url":{"source":"alpha/publication","args":{"key":"pdf_url"}}}},"className":"is-style-arrow"} -->
<div class="wp-block-button is-style-arrow"><a class="wp-block-button__link wp-element-button" href="#">Read PDF</a></div>
<!-- /wp:button -->
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"alpha/publication","args":{"key":"pubmed_url"}}}},"className":"is-style-arrow"} -->
<div class="wp-block-button is-style-arrow"><a class="wp-block-button__link wp-element-button" href="#">PubMed</a></div>
<!-- /wp:button -->
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"alpha/publication","args":{"key":"pmc_url"}}}},"className":"is-style-arrow"} -->
<div class="wp-block-button is-style-arrow"><a class="wp-block-button__link wp-element-button" href="#">Free full text</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
