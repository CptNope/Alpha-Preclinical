<?php
/**
 * Title: Open roles
 * Slug: alpha/open-roles
 * Categories: alpha-sections
 * Description: Open roles as expandable rows, with team filter chips and Apply links.
 *
 * @package AlphaPreclinical
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-section-mist alpha-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull is-style-section-mist alpha-section"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading -->
<h2 class="wp-block-heading">Open roles</h2>
<!-- /wp:heading -->
<!-- wp:alpha/filter-chips {"taxonomy":"job_team","label":"Filter roles","showCounts":false} /-->
<!-- wp:query {"queryId":206,"query":{"perPage":50,"pages":0,"offset":0,"postType":"job","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"alpha-roles"} -->
<div class="wp-block-query alpha-roles"><!-- wp:post-template -->
<!-- wp:details {"className":"alpha-role","metadata":{"bindings":{"summary":{"source":"alpha/job","args":{"key":"title"}}}}} -->
<details class="wp-block-details alpha-role"><summary>Role</summary><!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/job","args":{"key":"team"}}}},"className":"alpha-role__meta"} -->
<p class="alpha-role__meta"></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/job","args":{"key":"summary"}}}}} -->
<p></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">What we're looking for</h4>
<!-- /wp:heading -->
<!-- wp:alpha/meta-list {"source":"job","key":"requirements"} /-->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"metadata":{"bindings":{"url":{"source":"alpha/job","args":{"key":"apply_url"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Apply for this role</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></details>
<!-- /wp:details -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
