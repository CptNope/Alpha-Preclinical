<?php
/**
 * Title: Header
 * Slug: alpha/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: White logo, the six menu items in buyer order (three with dropdowns) and the quote button, on abyss.
 *
 * @package AlphaPreclinical
 */

?>
<!-- wp:group {"tagName":"header","align":"full","className":"is-style-section-abyss alpha-header","style":{"spacing":{"padding":{"top":"20px","bottom":"20px"}}},"layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull is-style-section-abyss alpha-header" style="padding-top:20px;padding-bottom:20px"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:image {"width":"auto","height":"52px","sizeSlug":"full","linkDestination":"custom","className":"alpha-logo"} -->
<figure class="wp-block-image size-full is-resized alpha-logo"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo-white.png' ) ); ?>" alt="<?php esc_attr_e( 'Alpha Preclinical home', 'alpha-preclinical' ); ?>" style="width:auto;height:52px"/></a></figure>
<!-- /wp:image -->

<!-- wp:navigation {"overlayMenu":"mobile","className":"alpha-nav","layout":{"type":"flex","justifyContent":"right"}} -->
<!-- wp:navigation-submenu {"label":"Expertise","url":"/expertise/","kind":"custom","isTopLevelItem":true} -->
<!-- wp:navigation-link {"label":"All expertise","url":"/expertise/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"PK/PD","description":"Exposure, half-life, target engagement","url":"/expertise/#pk-pd","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Gene therapy","description":"mRNA-LNP and AAV in vivo","url":"/expertise/#gene-therapy","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Autoimmune disease","description":"Spontaneous and induced models","url":"/expertise/#autoimmune","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Metabolic disease","description":"Diet-induced and genetic models","url":"/expertise/#metabolic","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Inflammation and fibrosis","description":"OA, fibrosis, LPS","url":"/expertise/#fibrosis","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Tumor models","description":"Subcutaneous, orthotopic","url":"/tumor-models/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-submenu {"label":"Services","url":"/services/","kind":"custom","isTopLevelItem":true} -->
<!-- wp:navigation-link {"label":"All services","url":"/services/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"IVIS imaging","url":"/services/#ivis","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Surgical services","url":"/services/#surgery","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Colony management","url":"/services/#colony","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"In vitro laboratory","url":"/services/#in-vitro","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Study support","url":"/services/#support","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-link {"label":"Publications","url":"/publications/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-submenu {"label":"About","url":"/about/","kind":"custom","isTopLevelItem":true} -->
<!-- wp:navigation-link {"label":"About Alpha","url":"/about/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Team","url":"/team/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Careers","url":"/careers/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-link {"label":"Blog","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Contact","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:buttons {"className":"alpha-header__cta"} -->
<div class="wp-block-buttons alpha-header__cta"><!-- wp:button {"className":"is-style-light","metadata":{"bindings":{"url":{"source":"alpha/site","args":{"key":"quote_url"}}}}} -->
<div class="wp-block-button is-style-light"><a class="wp-block-button__link wp-element-button" href="/contact/#form"><?php esc_html_e( 'Request a study quote', 'alpha-preclinical' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></header>
<!-- /wp:group -->
