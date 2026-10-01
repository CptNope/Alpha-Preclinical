# Alpha Preclinical — Website Redesign

A full redesign of [alphapreclinical.com](https://www.alphapreclinical.com/) for Alpha Preclinical LLC, a preclinical in vivo contract research organization (CRO) at 722 Plantation Street, Worcester, MA.

This repo holds three things:

1. **A static preview site** (the HTML pages in the root) that runs on GitHub Pages, for client review.
2. **The design source**: the design-canvas artboards, the design system, and the blog drafts written during the design session.
3. **A starter `theme.json`** for the production build as a WordPress full-site-editing (FSE) block theme.

**Live preview:** https://cptnope.github.io/Alpha-Preclinical/ (once GitHub Pages is serving `main`)

> [!IMPORTANT]
> This is a **review build**, not the production site. Forms validate and confirm on screen but send nothing, blog share links are placeholders, publication PDFs are still served from the old site, and several items still need client confirmation (see [Open items](#open-items-before-launch)).

---

## Contents

- [Design direction](#design-direction)
- [Navigation order](#navigation-order)
- [Calls to action](#calls-to-action)
- [Motion](#motion)
- [Site map](#site-map)
- [Pages](#pages)
- [Repository structure](#repository-structure)
- [Design system](#design-system)
- [SEO and E-E-A-T](#seo-and-e-e-a-t)
- [Who leads what](#who-leads-what)
- [How the preview site is built](#how-the-preview-site-is-built)
- [Deploying to GitHub Pages](#deploying-to-github-pages)
- [Moving to WordPress FSE](#moving-to-wordpress-fse)
- [Accessibility](#accessibility)
- [Open items before launch](#open-items-before-launch)
- [Asset ownership](#asset-ownership)

---

## Design direction

The brand's colors are kept exactly. They were sampled from the existing logo and the layered-wave hero art, which also appears on the building signage.

**The signature idea is "strata".** The alpha in the logo is filled with stacked bands of blue. On the site that becomes five flowing wave bands in the brand blues: drifting slowly in the homepage hero and dissolving into the page. Every inner page carries a shorter band of the same waves that also drifts (and blog posts a thin one under the header), each with its own pause button. The site visually matches the sign visitors see at the entrance.

The client's two reference sites shaped different parts of the work:

| Reference | What it contributed |
| --- | --- |
| [Wave Life Sciences](https://wavelifesciences.com/) | Visual polish: generous white space, pill buttons, wave-line motifs, confident type |
| [Melior Discovery](https://www.meliordiscovery.com/) | Information architecture that ranks: therapeutic areas as crawlable links, specific capabilities, proof points, publications, FAQ, repeated quote CTAs |

```mermaid
flowchart LR
    A["Existing logo<br/>and signage"] --> D["Strata motif<br/>+ kept brand blues"]
    B["Wave Life Sciences<br/>visual polish"] --> E["Redesign"]
    C["Melior Discovery<br/>SEO page structure"] --> E
    D --> E
    F["Old site content<br/>bios, services, 16 papers"] --> E
    E --> G["Design canvas<br/>14 artboards"]
    E --> H["Design system<br/>tokens + rules"]
    E --> I["7 blog drafts<br/>SEO-optimized"]
```

---

## Navigation order

**Expertise · Services · Publications · About · Team · Blog · Careers · Contact**, the same in the header, the mobile menu and the footer's Company column. It follows a buyer's questions in order: *can you run my model* (Expertise, Services), *can I trust the science* (Publications), *who are you* (About, Team), then supporting content, hiring and contact. "Request a study quote" stays as the header button on every page.

## Calls to action

Every study CTA lands on the contact form. Links from an area or service pass the study type, so the form opens with it already chosen (`contact.html?study=Tumor%20models#form`, read by `assets/js/site.js`).

```mermaid
flowchart LR
    HM["Home<br/>after 'How a study runs'"] -- "Talk to a scientist" --> F["contact.html#form"]
    EX["Expertise<br/>each of the 6 areas"] -- "Plan your … study<br/>(study type preset)" --> F
    SV["Services<br/>each service"] -- "Add … to your study<br/>(study type preset)" --> F
    BP["Every blog post<br/>mid-article box + closing band"] -- "Talk to a scientist<br/>(topic preset)" --> F
    PB["Publications"] -- "Want the authors on your study?" --> F
    BL["Blog index"] -- "Ask a scientist" --> F
    TM["Team"] -- "Put this team on your program" --> F
    AB["About, Model, Services, Expertise"] -- "closing band" --> F
```

## Motion

All motion is decorative, built from the strata wave idea, and switched off entirely when a visitor's device asks for reduced motion (or JavaScript is off). It lives in `assets/css/site.css` and `assets/js/site.js`, so the design canvas shows the resting state.

| Motion | Where | Notes |
| --- | --- | --- |
| Drifting wave bands | Every page header | Continuous, so each has a Pause button (WCAG 2.2.2) |
| Header content rises in | Every page, on load | Under 1 second, staggered |
| Sections fade up as they scroll into view | Cards, lists, steps, papers, team, bands | Staggered across rows; anything that receives keyboard focus shows at once |
| Contour lines draw left to right | CTA bands and the closing CTA | One-time, about 2.5 seconds |
| Process connector lines draw in | Home, "How a study runs" | Follows each step in |
| Numbers count up | Home hero stats, "16 papers" | Real number stays in the markup and in an `aria-label` |
| Photos settle from a slight zoom | Revealed sections with photos | |
| Hover: cards lift, nav underline grows, arrows nudge | Site-wide | |

---

## Site map

```mermaid
flowchart TD
    H["Home<br/>index.html"]
    H --> EX["Expertise<br/>expertise.html<br/>(6 areas as sections)"]
    H --> SV["Services<br/>services.html"]
    H --> PB["Publications<br/>publications.html"]
    H --> AB["About<br/>about.html"]
    H --> TM["Team<br/>team.html"]
    H --> BL["Blog<br/>blog.html"]
    H --> CA["Careers<br/>careers.html"]
    H --> CO["Contact / quote<br/>contact.html"]
    H --> LG["Footer: Privacy, Terms,<br/>Accessibility"]

    EX --> TU["Tumor models<br/>tumor-models.html<br/>(deep-dive template)"]
    EX --> BL

    BL --> BP["7 blog posts<br/>blog-*.html<br/>(one shared template)"]
    BP --> PB
    BP --> TM
    BP --> EX
    TM --> PB
    TM --> EX
    TU --> TM
    SV --> TM
    SV --> CO

    classDef built fill:#1E58A0,color:#fff,stroke:#0F3360
    class H,EX,SV,AB,TM,PB,BL,CA,CO,TU,BP,LG built
```

Every page is designed and built. The six expertise areas are sections of `expertise.html` (anchors `#pk-pd`, `#gene-therapy`, `#autoimmune`, `#metabolic`, `#fibrosis`, `#tumor`); `tumor-models.html` shows how any area can grow into its own deep-dive page later.

---

## Pages

| Page | File | Interactive in preview | What it does |
| --- | --- | --- | --- |
| Home | `index.html` | FAQ accordion, mobile menu, count-up stats | A short front door to the full pages: hero with animated strata, 6 expertise areas with leads, 4 service tiles, study process with a "talk to a scientist" CTA, 2 featured papers with headline stats, the team in one row, FAQ, quote CTA |
| Services | `services.html` | Jump links to each service | Study types, then IVIS imaging, surgical services, colony management, in vitro laboratory and study support, each with its lead and full detail from the old site |
| Expertise | `expertise.html` | Jump links to each area | All six therapeutic areas: overview, the models from the old site in a table, who leads it, related papers and blog posts |
| Tumor models | `tumor-models.html` | — | Template for each disease-model page: models table, in-house endpoints, study team, related expertise |
| Publications | `publications.html` | Filter by research area | All 16 papers grouped by area, Alpha authors called out |
| About | `about.html` | — | Company story, key facts, values, facility, leadership |
| Team | `team.html` | Photo swap on hover | Group photo, leadership and founding team profiles with education, prior roles, affiliations and the areas each person leads |
| Blog | `blog.html` | Filter by topic | Index of the seven launch posts, all linked |
| Blog post (designed) | `blog-mrna-liver-depot-study-design.html` | — | Single-post template built for E-E-A-T (see below) |
| Blog posts (generated) | `blog-how-to-scope-in-vivo-efficacy-study.html`, `blog-caliper-vs-ivis-tumor-burden.html`, `blog-diet-induced-vs-genetic-type-2-diabetes-models.html`, `blog-flow-cytometry-immunophenotyping-in-vivo-studies.html`, `blog-alzet-osmotic-pump-continuous-dosing.html`, `blog-rat-models-type-1-diabetes.html` | — | The other six drafts on the same template: author and reviewer, contents, key takeaways, sources, related Alpha papers, quote CTA, `Article` schema |
| Contact | `contact.html` | Demo form with validation | Quote form with study-type picker, contact details, building photo, branded map |
| Privacy policy | `privacy.html` | — | **Draft for legal review.** What the forms collect, how it is used and shared, retention, rights |
| Terms of use | `terms.html` | — | **Draft for legal review.** Site use, studies governed by separate agreements, IP, disclaimers, Massachusetts law |
| Accessibility statement | `accessibility.html` | — | WCAG 2.1 AA commitment, what was done, how it was tested, known limitations, how to get help |
| Careers | `careers.html` | Role filter, accordion, demo application form | Culture, four open roles, application form with resume upload |

---

## Repository structure

```mermaid
flowchart LR
    R(("repo root")) --> P["*.html<br/>20 static pages"]
    R --> AS["assets/"]
    AS --> CSS["css/site.css<br/>shared: menu, skip link, filters"]
    AS --> JS["js/site.js<br/>filters, accordions, demo forms"]
    AS --> IMG["img/<br/>logos, photos, map"]
    IMG --> TEAM["team/<br/>10 headshots"]
    R --> DS["design/"]
    DS --> CV["canvas/<br/>14 .dc.html artboards + canvas.json"]
    DS --> SYS["design-system/<br/>tokens.json, README.md, cover"]
    R --> CT["content/blog-posts.md<br/>7 SEO blog drafts"]
    R --> WP["wordpress/theme.json<br/>FSE starter"]
    R --> TL["tools/convert.py<br/>canvas → static HTML"]
    R --> TB["tools/build_posts.py<br/>drafts → post pages"]
    R --> DC["docs/<br/>accessibility audit + script"]
```

```text
.
├── index.html, about.html, team.html, publications.html, blog.html,
│   services.html, expertise.html, tumor-models.html,
│   blog-*.html (7 posts),
│   contact.html, careers.html,
│   privacy.html, terms.html, accessibility.html   # generated preview pages
├── assets/
│   ├── css/site.css                     # shared styles (each page also keeps its own inline styles)
│   ├── js/site.js                       # no-dependency behavior
│   └── img/                             # logos, lab + building photos, map, team/
├── design/
│   ├── canvas/                          # design-canvas source artboards (*.dc.html) + canvas.json
│   └── design-system/                   # tokens.json, README.md (brand book), Cover preview
├── content/blog-posts.md                # all 7 blog drafts with SEO fields and sources
├── wordpress/theme.json                 # token mapping for the FSE block theme
├── tools/convert.py                     # regenerates the HTML pages from design/canvas
├── tools/build_posts.py                 # builds the six other posts from content/blog-posts.md
├── tools/pdf_links.py                   # links paper titles to their PDFs
├── docs/accessibility-audit.md          # WCAG 2.1 AA audit report
├── docs/a11y-audit.py                   # re-runnable audit (axe-core + custom checks)
├── .nojekyll                            # serve files as-is on GitHub Pages
└── LICENSE
```

---

## Design system

The full brand book is in [`design/design-system/README.md`](design/design-system/README.md) and the tokens in [`design/design-system/tokens.json`](design/design-system/tokens.json).

### Color

| Token | Hex | Role | Contrast |
| --- | --- | --- | --- |
| `navy` | `#1F2858` | Ink: headings and body text (the wordmark color) | 14:1 on white |
| `abyss` | `#0F3360` | Dark surface: hero, CTA bands, footer | white text 12.6:1 |
| `tide` | `#1E58A0` | Action: buttons, links, focus | 7.1:1 on white |
| `current` | `#4B98D2` | Wave band, rules, icons (never body text) | 3.1:1, decorative only |
| `shallows` | `#88C6EE` | Lightest band; accent and buttons on `abyss` | 6.8:1 on abyss |
| `mist` | `#EEF5FB` | Alternate section background | — |
| `ink-muted` | `#4A5878` | Secondary text on white | 7.1:1 |
| `ink-on-abyss` | `#B9CCE3` | Secondary text on `abyss` | 7.7:1 |

```mermaid
flowchart LR
    S1["abyss #0F3360"] --- S2["tide #1E58A0"] --- S3["current #4B98D2"] --- S4["shallows #88C6EE"] --- S5["mist #EEF5FB"]
    style S1 fill:#0F3360,color:#fff,stroke:#0F3360
    style S2 fill:#1E58A0,color:#fff,stroke:#1E58A0
    style S3 fill:#4B98D2,color:#0F3360,stroke:#4B98D2
    style S4 fill:#88C6EE,color:#0F3360,stroke:#88C6EE
    style S5 fill:#EEF5FB,color:#1F2858,stroke:#D3E0EE
```

The five strata bands, deepest to lightest, as they stack in the hero.

### Type

| Role | Family | Use |
| --- | --- | --- |
| Display | **Bricolage Grotesque** 600, tracked −0.025em | Headings only |
| Body | **Figtree** 400–600 | Text, navigation, buttons (echoes the wordmark's round geometry) |

Rules: sentence-case headings, no all-caps labels, line length under ~72 characters, one primary action per view, motion only in the hero (frozen under `prefers-reduced-motion`).

---

## SEO and E-E-A-T

Alpha's strongest ranking asset is that real scientists with published research run the studies. The design surfaces that wherever it can.

```mermaid
flowchart TD
    E1["Experience<br/>first-person posts, 'From the lab' callouts,<br/>real lab + team photos"]
    E2["Expertise<br/>DVM / PhD credentials, 'Led by' on every<br/>service and expertise area"]
    E3["Authoritativeness<br/>16 publications, Alpha authors highlighted,<br/>numbered citations in posts"]
    E4["Trust<br/>scientific reviewer byline, editorial policy,<br/>corrections email, real address + map"]
    E1 --> T["Topic pages and blog posts<br/>rank and convert"]
    E2 --> T
    E3 --> T
    E4 --> T
```

| Element | Where |
| --- | --- |
| Unique `<title>` and meta description per page | All pages (`tools/convert.py`) |
| One H1 per page, keyword first | All pages |
| `Organization` and `FAQPage` JSON-LD | `index.html` |
| `Article` JSON-LD with author, reviewer and citations | Blog post |
| Breadcrumbs | All inner pages |
| Author byline, reviewer, published/updated dates, author bio box | Blog post |
| Related team publications and related posts | Blog post |
| "Led by" lines and study-team block linking to Team profiles | Home, tumor models template, Team |
| Internal links between expertise, team, publications and blog | Throughout |

The seven launch posts in [`content/blog-posts.md`](content/blog-posts.md) each include a title tag, meta description, slug, primary and secondary keywords, and the internal links to add.

| # | Post | Author | Primary keyword |
| --- | --- | --- | --- |
| 1 | How to scope your first in vivo efficacy study | Barak Yahalom, DVM | in vivo efficacy study |
| 2 | Caliper or IVIS? Choosing how to measure tumor burden | Barak Yahalom, DVM | tumor volume caliper vs bioluminescence imaging |
| 3 | Diet-induced versus genetic models of type 2 diabetes | Joan Flanagan, PhD | type 2 diabetes animal models |
| 4 | What an mRNA liver depot study looks like in practice | Barak Yahalom, DVM | mRNA LNP in vivo study |
| 5 | Getting more from every animal with in-house flow cytometry | Cindy Hopper | mouse immunophenotyping flow cytometry |
| 6 | Continuous dosing with Alzet pumps: when and why | Gil Chacon | Alzet osmotic pump |
| 7 | Spontaneous rat models of type 1 diabetes, explained | Joan Flanagan, PhD | type 1 diabetes rat model |

---

## Who leads what

Each service and expertise area names the person who leads it, and each Team profile links back. Only connections backed by a bio or publication were made; three default to the CSO and need confirming.

```mermaid
flowchart LR
    B["Barak Yahalom, DVM<br/>CEO & CSO"]
    J["Joan Flanagan, PhD<br/>President & COO"]
    C["Cindy Hopper<br/>Founding Scientist"]
    G["Gil Chacon<br/>In vivo & surgical"]
    A["Ashley Kolofsky<br/>Study team supervisor"]

    B --> PK["PK/PD"]
    B --> GT["Gene therapy"]
    B -.->|confirm| IF["Inflammation & fibrosis"]
    B -.->|confirm| TU["Tumor models"]
    B -.->|confirm| IV["IVIS imaging"]
    B --> SU["Surgical services"]
    G --> SU
    J --> AU["Autoimmune disease"]
    J --> ME["Metabolic disease"]
    C --> VT["In vitro laboratory"]
    A --> CM["Colony management"]
```

| Area | Lead | Evidence |
| --- | --- | --- |
| PK/PD | Barak | Directed in vivo pharmacology at several biotechs |
| Gene therapy | Barak | Fabry and hemophilia B mRNA papers |
| Autoimmune disease | Joan | LEW.1WR1 type 1 diabetes paper |
| Metabolic disease | Joan | BBZDR/Wor and metabolic syndrome papers |
| Surgical services | Barak and Gil | Staff DVM; Gil provides surgical support |
| Colony management | Ashley | Vivarium operations |
| In vitro laboratory | Cindy | Leads in vitro services |
| Inflammation & fibrosis, tumor models, IVIS | Barak | **Assumed (CSO). Confirm with client.** |

---

## How the preview site is built

The designs were made on a design canvas whose artboards (`design/canvas/*.dc.html`) need that tool's runtime. `tools/convert.py` turns them into plain HTML that runs anywhere.

```mermaid
flowchart LR
    A["design/canvas/*.dc.html"] --> B["tools/convert.py"]
    B --> C["Move helmet fonts + styles into head"]
    B --> D["Rewrite uploaded-image URLs<br/>to assets/img/"]
    B --> E["Rewrite artboard links<br/>Main.dc.html → index.html"]
    B --> F["Pre-render loops from page data<br/>FAQ, papers, posts, roles"]
    B --> G["Add meta, JSON-LD, skip link,<br/>mobile menu, site.js"]
    C & D & E & F & G --> H["*.html in repo root"]
    MD["content/blog-posts.md"] --> BP["tools/build_posts.py"]
    H -- "designed post as template" --> BP
    BP --> H2["6 more blog-*.html<br/>+ read times + internal links"]
    H & H2 --> I["GitHub Pages"]
```

To regenerate after editing an artboard or a blog draft (needs Python 3 with the `markdown` package, and Node.js, which evaluates each page's data arrays). Run both, in this order:

```bash
pip install markdown
python3 tools/convert.py design/canvas .
python3 tools/build_posts.py
```

`build_posts.py` also runs `tools/pdf_links.py`, which points every paper title on the site at that paper's PDF (URLs live in the `P` list in `design/canvas/Publications.dc.html`). It fills the designed post with each draft (the SEO table at the top of each draft becomes the title, meta and slug), sets read times from real word counts on every page, and points matching links across the site at the new posts.

The script stops with an error if any template syntax (`{{ }}`, `<sc-for>`, `<sc-if>`) is left unconverted.

To preview locally:

```bash
python3 -m http.server 8000
# open http://localhost:8000
```

---

## Deploying to GitHub Pages

1. Repo **Settings → Pages**.
2. **Source:** Deploy from a branch. **Branch:** `main`, folder `/ (root)`.
3. Save. The site publishes at `https://cptnope.github.io/Alpha-Preclinical/` within a minute or two.

All paths are relative, so the site works under the `/Alpha-Preclinical/` subpath without changes. `.nojekyll` makes Pages serve files as-is.

---

## Moving to WordPress FSE

```mermaid
flowchart TD
    T["design-system/tokens.json"] --> TJ["wordpress/theme.json<br/>palette, fonts, sizes, spacing"]
    TJ --> TH["Block theme"]
    P["Static pages"] --> PT["Block patterns<br/>one per section"]
    PT --> TH
    P --> TP["Templates & parts<br/>header, footer, single, archive, page"]
    TP --> TH
    BC["content/blog-posts.md"] --> PO["Posts with Rank Math / Yoast fields"]
    PO --> TH
    TH --> L["Launch on Cloudways"]
```

| Static piece | FSE equivalent |
| --- | --- |
| Header / footer (same on every page) | `parts/header.html`, `parts/footer.html` (Navigation block) |
| Hero with strata | Block pattern: Cover or Group with inline SVG bands |
| Expertise list with leads | Pattern + a relationship field from each service page to a Team member |
| Publications | Custom post type `publication` (area taxonomy, authors, journal, year, DOI, PDF) with a Query Loop |
| Blog post | `single.html` template; author box from user profile; `Article` schema via SEO plugin |
| Team profiles | Custom post type `team_member`; `Person` schema with `sameAs` (ORCID, Google Scholar, LinkedIn) |
| Contact and careers forms | Gravity Forms or Fluent Forms; route study type to the right inbox |
| Open roles | Custom post type `job` with `JobPosting` schema |

`wordpress/theme.json` already maps the palette, both font families, the type scale, spacing scale and button style. Add the woff2 files to `assets/fonts/` in the theme (download Bricolage Grotesque and Figtree from Google Fonts and self-host them).

---

## Accessibility

The site was audited against **WCAG 2.1 AA** (plus the WCAG 2.2 target-size rule). Full report: [`docs/accessibility-audit.md`](docs/accessibility-audit.md).

| Check | Result |
| --- | --- |
| axe-core (wcag2a, wcag2aa, wcag21a, wcag21aa) | 0 violations on all 20 pages |
| Reflow at 320 px and text spacing (1.4.10, 1.4.12) | Pass on all pages |
| Visible focus on every focusable element (2.4.7) | Pass |
| Wave animation can be paused (2.2.2) | Pause button on every page; starts paused under reduced motion. Scroll reveals and line drawing run once and finish in under 5 seconds |
| Filter results announced (4.1.3) | `role="status"` live region |
| Targets at least 24 px (2.5.8) | Pass, except links inside sentences (exempt) |

Re-run after changes:

```bash
python3 -m http.server 8766 &
AXE_PATH=path/to/axe.min.js python3 docs/a11y-audit.py http://localhost:8766 audit.json
```

---

## Open items before launch

### Confirm with the client

- [ ] Job titles for Ashley, Cindy and Gil (written from their old role descriptions)
- [ ] Cindy's bio edit (homeopathy reference changed to "preventive approaches to medicine")
- [ ] Joan's Becker College advisory board line (Becker College closed in 2021)
- [ ] Leads for inflammation & fibrosis, tumor models and IVIS (defaulted to Barak)
- [ ] Alpha's role on three LNP papers with no confirmed Alpha author
- [ ] Location for job postings: old posts say **North Grafton, MA**; site says Worcester
- [ ] Whether the four open roles are still open
- [ ] Editorial policy on the blog post (claims a second PhD/DVM review)
- [ ] Phone number, if they want one listed

### Content to collect

- [ ] Original full-resolution photos (current images were pulled from Wix at 1,000 px)
- [ ] Journal, year, volume and DOI for each publication
- [ ] Copy the 16 PDFs into the theme or media library before the old site goes offline (links currently point at `alphapreclinical.com/_files/ugd/…`), and confirm the client may host publisher versions; link the DOI where it can't
- [ ] ORCID / Google Scholar links for Barak and Joan
- [ ] Real lab photos for each therapeutic area
- [ ] Confirm the model lists on the Expertise page are current (copied from the old site)
- [ ] Author review of each blog post, plus one first-hand detail per post

### Build tasks

- [ ] Attorney review of the draft Privacy Policy and Terms of Use (bracketed items depend on the final analytics, form and hosting tools)
- [ ] Response time for accessibility requests (Accessibility Statement)
- [ ] Screen-reader pass with VoiceOver and NVDA on staging
- [ ] Share links on blog posts (placeholders)
- [ ] Real form handling and spam protection
- [ ] `JobPosting`, `Person` and `ScholarlyArticle` schema
- [ ] XML sitemap and redirects from old Wix URLs (`/experetise`, `/services`, `/about-us`, `/pk-pd`, …)

---

## Asset ownership

The MIT license covers the code in this repo (HTML structure, CSS, JavaScript, `tools/`). **It does not cover the client's assets**: the Alpha Preclinical name and logo, team and facility photos, and the client's written content belong to Alpha Preclinical LLC and are included only for this redesign. The map image is derived from © OpenStreetMap contributors data (ODbL).
