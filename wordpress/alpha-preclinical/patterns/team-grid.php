<?php
/**
 * Title: Team grid
 * Slug: alpha/team-grid
 * Categories: alpha-sections
 * Description: Everyone on the team as portrait cards, in menu order.
 *
 * @package AlphaPreclinical
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"alpha-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull alpha-section"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:query {"queryId":808,"query":{"perPage":20,"pages":0,"offset":0,"postType":"team_member","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"alpha-team-grid"} -->
<div class="wp-block-query alpha-team-grid"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:alpha/person-card /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
