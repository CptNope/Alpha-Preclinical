<?php
/**
 * Title: Footer
 * Slug: alpha/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Logo, address and email (bound to the site details), three link columns and the legal row.
 *
 * @package AlphaPreclinical
 */

?>
<!-- wp:group {"tagName":"footer","align":"full","className":"is-style-section-footer alpha-footer","style":{"spacing":{"padding":{"top":"var:preset|spacing|5","bottom":"var:preset|spacing|4"}}},"layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull is-style-section-footer alpha-footer" style="padding-top:var(--wp--preset--spacing--5);padding-bottom:var(--wp--preset--spacing--4)"><!-- wp:columns {"align":"wide","className":"alpha-footer__cols"} -->
<div class="wp-block-columns alignwide alpha-footer__cols"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:image {"width":"auto","height":"64px","sizeSlug":"full","linkDestination":"custom"} -->
<figure class="wp-block-image size-full is-resized"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo-white.png' ) ); ?>" alt="<?php esc_attr_e( 'Alpha Preclinical home', 'alpha-preclinical' ); ?>" style="width:auto;height:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/site","args":{"key":"address"}}}}} -->
<p>722 Plantation Street<br>Worcester, MA 01605</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/site","args":{"key":"email_link"}}}}} -->
<p><a href="mailto:info@alphapreclinical.com">info@alphapreclinical.com</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/site","args":{"key":"phone_link"}}}}} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-ghost-dark","metadata":{"bindings":{"url":{"source":"alpha/site","args":{"key":"linkedin"}}}}} -->
<div class="wp-block-button is-style-ghost-dark"><a class="wp-block-button__link wp-element-button" href="https://www.linkedin.com/company/alpha-preclinical"><?php esc_html_e( 'LinkedIn', 'alpha-preclinical' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:heading {"level":2,"className":"alpha-footer__h"} -->
<h2 class="wp-block-heading alpha-footer__h"><?php esc_html_e( 'Expertise', 'alpha-preclinical' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"alpha-footer__list"} -->
<ul class="wp-block-list alpha-footer__list"><!-- wp:list-item -->
<li><a href="/expertise/#pk-pd">PK/PD</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/expertise/#gene-therapy">Gene therapy</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/expertise/#autoimmune">Autoimmune disease</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/expertise/#metabolic">Metabolic disease</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/expertise/#fibrosis">Inflammation and fibrosis</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/tumor-models/">Tumor models</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:heading {"level":2,"className":"alpha-footer__h"} -->
<h2 class="wp-block-heading alpha-footer__h"><?php esc_html_e( 'Services', 'alpha-preclinical' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"alpha-footer__list"} -->
<ul class="wp-block-list alpha-footer__list"><!-- wp:list-item -->
<li><a href="/services/#ivis">IVIS imaging</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/services/#surgery">Surgical services</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/services/#colony">Colony management</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/services/#in-vitro">In vitro laboratory</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/services/#support">Study support</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:heading {"level":2,"className":"alpha-footer__h"} -->
<h2 class="wp-block-heading alpha-footer__h"><?php esc_html_e( 'Company', 'alpha-preclinical' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"alpha-footer__list"} -->
<ul class="wp-block-list alpha-footer__list"><!-- wp:list-item -->
<li><a href="/about/">About</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/team/">Team</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/publications/">Publications</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/blog/">Blog</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/careers/">Careers</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="/contact/">Contact</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","className":"alpha-footer__base","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide alpha-footer__base"><!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"alpha/site","args":{"key":"copyright"}}}}} -->
<p>© Alpha Preclinical LLC</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"alpha-footer__legal"} -->
<p class="alpha-footer__legal"><a href="/privacy-policy/">Privacy</a> <a href="/terms/">Terms</a> <a href="/accessibility/">Accessibility</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
