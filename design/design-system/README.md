# Alpha Preclinical

Alpha Preclinical LLC is a preclinical in vivo contract research organization (CRO) at 722 Plantation Street, Worcester, Massachusetts, founded in 2020. Its clients are biotech and pharma scientists who need PK/PD, efficacy and proof-of-concept studies run carefully, quickly and at a fair cost. The website's one job is to make a scientist trust Alpha enough to **request a study quote**.

This is the client-approved system (November 2026 revision, `main` of [CptNope/Alpha-Preclinical](https://github.com/CptNope/Alpha-Preclinical)). It keeps the brand's existing colours and logo exactly, because they are on the building signage, and gives them a sharper, more confident web presence. It is the reference for the production WordPress FSE block theme.

> **Hard constraint:** never change the brand colours or the logo. Every blue in this system was sampled from the logo and the signage.

## The idea: strata

The alpha in the logo is filled with stacked bands of blue, from deep `abyss` to pale `shallows`. That layering is the brand's one signature shape. On the web it becomes **strata**: flowing wave bands in the brand blues.

- **Homepage hero:** the logo's own alpha, traced and enlarged (HeroAlpha). Five strata bands rise into it on arrival, drift, and fill it further as the visitor scrolls. Full-height drifting waves run along the foot of the hero.
- **Every inner page:** a shorter drifting band at the foot of the header (StrataBand), and a thin one under blog post headers. Each carries a **Pause animation** button.
- **Between sections:** the motif shrinks to a single 1.5px `current` contour line (ContourDivider), or contour lines framing a CTA band.
- Photos may take one wave-cut edge (bottom or right), never more than one per section.
- No gradients, glows or purple. Depth comes from flat bands overlapping, the way the logo does it.

The site matches the sign visitors see at the entrance.

## Colour

Five brand blues, two in-between strata blues, and neutrals. Use them by role, not by taste.

| Token | Hex | Role |
| --- | --- | --- |
| `navy` | #1F2858 | The ink. Headings and body text on light grounds (the wordmark colour) |
| `abyss` | #0F3360 | Dark surface: hero, page headers, CTA bands, deep sections |
| `abyss-deep` | #0B2747 | Footer ground only |
| `tide` | #1E58A0 | Action: buttons, links. One primary action per view |
| `current` | #4B98D2 | Wave band, contour lines, focus ring, icons. Never body text |
| `shallows` | #88C6EE | Lightest band; accent text and light button on `abyss` |
| `mist` | #EEF5FB | Alternate section ground, hover fills, table stripes |
| `ink-muted` | #4A5878 | Secondary text on `paper` and `mist` |
| `ink-on-abyss` | #B9CCE3 | Secondary text on `abyss` |
| `line` | #D3E0EE | Hairlines and dividers (decorative) |
| `field-border` | #7D8FAE | Form control borders |
| `error` | #B3261E | Validation only |

`strata-2` (#69AEE1) and `strata-4` (#3279BD) fill the gaps between bands inside the homepage alpha only.

Text pairs all meet WCAG AA: `navy`/`paper` 14:1, `tide`/`paper` 7.1:1, `paper`/`tide` 7.1:1, `ink-muted`/`paper` 7.1:1, `shallows`/`abyss` 6.8:1, `ink-on-abyss`/`abyss` 7.7:1. `current` is 3.1:1 on white, so it is decoration and focus only. The **Dark** theme is a token-level mapping kept from the original tokens; the approved site ships in Light.

## Type

- **Bricolage Grotesque 600** (display): headings only, tracked -0.025em (-0.03em at hero size). Its slightly irregular, inky letterforms keep a science site from feeling corporate-sterile.
- **Figtree 400-600** (body): text, navigation, buttons, forms. Its round geometry echoes the wordmark.

Rules: sentence-case headings; no all-caps labels or eyebrow text above headings; one H1 per page with the keyword first; lines under ~72 characters. Self-host both families as woff2 (the files are in `fonts/`).

## Voice

Plain, specific, scientist-to-scientist. Name the actual assay, model or instrument ("Spectrum IVIS imager", "USDA-compliant surgical suite", "LEW.1WR1 rat") instead of adjectives. Calls to action say what happens: "Request a study quote", "Plan your PK/PD study", "Talk to a scientist", "Browse disease models".

Avoid: "cutting-edge", "world-class", "solutions", exclamation marks, and claims Alpha hasn't confirmed.

## Calls to action

Every study CTA lands on the contact form. Links from an area or service pass the study type, so the form opens with it chosen: `contact.html?study=Tumor%20models#form`. "Request a study quote" is the header button on every page, and every page closes with a CTABand.

## Navigation

**Expertise ▾ · Services ▾ · Publications · About ▾ · Blog · Contact**, the same in the header and the mobile menu. It follows a buyer's questions in order: can you run my model, can I trust the science, who are you, then supporting content. Expertise and Services open wide dropdown panels; About opens About, Team, Careers. See NavDropdown.

## Layout

- 1240px content width, 12 columns, `space-4` gutters on desktop and `space-2` on mobile.
- Content is left-aligned. Only the CTA band centres its text.
- Sections breathe: `space-6` vertical padding on desktop (120px on area sections, 72px on phones).
- **Vary the composition.** No two adjacent sections share a layout: a heading column that stays in view beside a list, a full-height photo bleeding off one edge on the deep ground, stacked strata steps that move deeper and to the right, a giant count ("16") leading the publications. On Expertise and Services each area alternates `mist`, `paper` and `abyss` grounds and swaps the text column's side; some open with a wide photo.
- Lists of things (models, services, papers) are typographic rows and split layouts, not grids of identical cards.

## Motion

All motion is decorative, built from the strata idea, uses one easing curve (`cubic-bezier(.2,.7,.2,1)`), and switches off entirely under `prefers-reduced-motion` or with JavaScript off.

| Motion | Where |
| --- | --- |
| Drifting wave bands, with a Pause button (WCAG 2.2.2) | Every page header |
| Strata rise into the hero alpha and fill it on scroll | Homepage |
| Header content rises in, then drifts up and softens on scroll (90px parallax desktop, 40px phone) | Every page |
| Sections play in as they arrive (26px, 0.75s, up to 4-step stagger) and back out toward the edge they leave by | Cards, lists, steps, papers, team |
| Contour lines draw in (2.4s), rewind on exit | CTA bands |
| Numbers count up; the real number stays in an `aria-label` | Hero stats, "16 papers" |
| Hover: cards lift, nav underline grows, arrows nudge, faces zoom 3% | Pointer devices only |

## Photography

Real photos of the real lab, building and team only: no stock scientists, no illustrations of molecules. Faces crop high (object-position 50% 18%). Photos sit in `radius-m` frames. The current images are 1,000px pulls from the old Wix site; replace with originals before launch.

## Logos

Colour logo on light grounds, white logo on `abyss` and `tide`. Keep clear space of at least the height of the "P" around it. Never recolour, redraw or stretch the alpha. The traced alpha (`assets/Logos/alpha-strata.svg`) is for the homepage hero only, never a substitute for the logo.

## Accessibility

Target WCAG 2.1 AA (the preview build had 0 axe violations on all 20 pages). Visible 3px `current` focus ring on everything focusable; skip link; targets at least 24px (buttons 44-52px); filter results announced through a `role="status"` region; reflow to 320px; the wave animation pausable on every page.

## SEO and E-E-A-T

Alpha's strongest ranking asset is that published scientists run the studies, so the design surfaces them: a "Led by" chip (LeadChip) on every expertise area and service, author and reviewer bylines on posts, 16 publications with full citations and DOI/PMID/PMCID (PublicationItem), and the real address and map. Schema: `Organization`, `FAQPage`, `Article`, `Person` with `sameAs`, `ScholarlyArticle`, and `JobPosting`.

## WordPress FSE mapping

Tokens map one-to-one to `theme.json`: colours to `settings.color.palette` (slug = token name), the two families to `settings.typography.fontFamilies` with self-hosted woff2 files, type styles to `fontSizes` and `styles.elements`, spacing to `settings.spacing.spacingSizes`. Buttons use the core Button block with the `radius-pill` style.

| Component | FSE equivalent |
| --- | --- |
| NavDropdown, Footer | `parts/header.html`, `parts/footer.html` (Navigation block) |
| StrataBand, HeroAlpha | Block patterns: Cover/Group with inline SVG bands |
| ModelRow + LeadChip | Pattern with a relationship field to a `team_member` |
| PublicationItem | CPT `publication` + Query Loop |
| TeamMember | CPT `team_member` with `Person` schema |
| FormField | Gravity Forms or Fluent Forms, styled to match |
| CTABand, FAQItem | Block patterns |
