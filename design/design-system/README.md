# Alpha Preclinical

Alpha Preclinical is a preclinical in vivo contract research organization in Worcester, Massachusetts, founded in 2020. Its clients are biotech and pharma scientists who need PK/PD, efficacy and proof-of-concept studies run carefully, quickly and at a fair cost. The website's job is to make a scientist trust Alpha enough to request a study quote.

This system keeps the brand's existing colours, which come straight from the logo and the building signage, and gives them a sharper, more confident web presence.

## The idea: strata

The alpha in the logo is filled with stacked bands of blue, from deep `abyss` to pale `shallows`. That layering is the brand's one signature shape. On the web it appears as **strata**: layered, flowing wave bands in the five brand blues.

- Strata appear at full size in exactly one place per page, the hero, where the bands drift very slowly. Everywhere else, the motif is reduced to a single contour line (`current`, 1.5px) used as a section divider.
- Photos may take one wave-cut edge (bottom or right). Never more than one per section.
- No gradients, glows or purple. The depth comes from flat bands overlapping, the way the logo does it.

## Colour

Five brand blues plus two neutrals. Use them by role, not by taste.

- `navy` is the ink. All headings and body text on light grounds.
- `abyss` is the dark surface: hero, CTA band, footer. On it, text is `white`, `ink-on-abyss` or `shallows`.
- `tide` is the action colour. Buttons, links and the focus ring. One primary action per view.
- `current` and `shallows` are band and accent colours. `current` is never text on `paper`.
- `mist` alternates with `paper` to separate long-page sections without borders.

All text pairs meet WCAG AA: `navy`/`paper` 14:1, `tide`/`paper` 7.1:1, `white`/`tide` 7.1:1, `shallows`/`abyss` 6.8:1, `ink-on-abyss`/`abyss` 7.7:1.

## Type

- **Bricolage Grotesque** (display): headings only, set tight (-0.025em) at semibold. Its slightly irregular, inky letterforms keep a science site from feeling corporate-sterile.
- **Figtree** (body): text, navigation, buttons. Its round geometry echoes the logo's wordmark.

Headings are sentence case. No all-caps labels or eyebrow text above headings. Keep line length under 72 characters.

## Voice

Plain, specific, scientist-to-scientist. Name the actual assay, model or instrument ("Spectrum IVIS imager", "USDA-compliant surgical suite") instead of adjectives. Calls to action say what happens: "Request a study quote", "Browse disease models".

## Layout

- 1240px content width, 12 columns, `space-4` gutters on desktop and `space-2` on mobile.
- Content is left-aligned. Only the CTA band centres its text.
- Sections breathe: `space-6` vertical padding on desktop.
- Lists of things (models, services) are typographic lists and split layouts, not grids of identical cards.

## Motion

One orchestrated moment: the hero strata settle into place on load and then drift slowly. Everything else is still, except direct feedback on hover and focus. Respect `prefers-reduced-motion` by freezing the strata.

## WordPress FSE mapping

Tokens map one-to-one to `theme.json`: colours to `settings.color.palette` (slug = token name), the two families to `settings.typography.fontFamilies` with self-hosted woff2 files, type styles to `fontSizes` and `styles.elements`, spacing to `settings.spacing.spacingSizes`. Buttons use the core Button block with the `radius-pill` style.

## Logos

Colour logo for light grounds, white logo for `abyss` and `tide`. Keep clear space of at least the height of the "P" around it. Never recolour or stretch the alpha.
