#!/usr/bin/env python3
"""Build individual blog post pages from content/blog-posts.md.

Run after tools/convert.py:
    python3 tools/convert.py design/canvas .
    python3 tools/build_posts.py

Uses the designed post page (blog-mrna-liver-depot-study-design.html, generated
from the BlogPost artboard) as the layout, and fills it with each draft's content,
author, reviewer, contents list, sources and related research. Then points every
link whose text names a post at that post's page, site-wide.
"""
import glob
import html
import json
import re
from pathlib import Path

import markdown
from urllib.parse import quote

ROOT = Path(__file__).resolve().parent.parent
TEMPLATE = ROOT / 'blog-mrna-liver-depot-study-design.html'
E = html.escape

PEOPLE = {
    'Barak Yahalom, DVM': {
        'img': 'assets/img/team/barak-yahalom.jpg', 'anchor': 'barak-yahalom',
        'linkedin': 'https://www.linkedin.com/in/barak-yahalom-dvm-8631089/',
        'pubmed': ['22368175', '23114041', '15235770', '30879951', '27356951', '33274557', '22363247', '21555934'],
        'role': 'CEO and Chief Scientific Officer',
        'bio': "Barak is a veterinarian with more than 20 years in biotechnology and preclinical research, including director of in vivo pharmacology roles at several biotech companies and positions at Boston Children's Hospital and Biomere. His eight peer-reviewed papers span rat models of type 1 diabetes, mRNA liver depot therapy for hemophilia B and Fabry disease (Gene Therapy, Molecular Therapy) and pediatric anesthesia models (Anesthesiology).",
        'creds': ['DVM, Hebrew University of Jerusalem', '20+ years in preclinical research', '8 peer-reviewed papers, 2004–2021', 'Sideromics Scientific Advisory Board'],
        'first': 'Barak'},
    'Joan Flanagan, PhD': {
        'img': 'assets/img/team/joan-flanagan.jpg', 'anchor': 'joan-flanagan',
        'linkedin': 'https://www.linkedin.com/in/joan-flanagan-phd-62368911/',
        'pubmed': ['16123363', '15229376', '12524530', '10198436'],
        'role': 'President and Chief Operating Officer',
        'bio': 'Joan has more than 20 years in in vivo preclinical contract research, with positions at Biomedical Research Models and Charles River Laboratories, and a PhD in molecular biology and biochemistry from UMass Medical School. Her publications include the BBZDR/Wor rat model of type 2 diabetes complications (ILAR Journal) and the LEW.1WR1 rat model of autoimmune diabetes (Diabetes).',
        'creds': ['PhD, Molecular Biology and Biochemistry, UMass Medical School', '20+ years in preclinical CROs', '4 PubMed-indexed papers, 1999–2005', 'Massachusetts Society for Medical Research'],
        'first': 'Joan'},
    'Cindy Hopper': {
        'img': 'assets/img/team/cindy-hopper.jpg', 'anchor': 'cindy-hopper',
        'role': 'Founding Scientist, In Vitro Laboratory Services',
        'bio': "Cindy is one of Alpha's founding scientists and leads its in vitro laboratory: multicolor flow cytometry and immunophenotyping, ELISA and cytokine assays, serum chemistry, primary cell isolation and histology, processed fresh beside the vivarium.",
        'creds': ['AS, Quinsigamond Community College', 'Continuing studies, Worcester Polytechnic Institute', 'Leads flow cytometry and in vitro assays'],
        'first': 'Cindy'},
    'Gil Chacon': {
        'img': 'assets/img/team/gil-chacon.jpg', 'anchor': 'gil-chacon',
        'role': 'In Vivo Studies, Surgical Support and Training',
        'bio': 'Gil has nearly 20 years in preclinical research. He works on in vivo studies, provides technical and surgical support, trains staff and keeps facility operations running. Before Alpha, he was training and development coordinator at a preclinical CRO.',
        'creds': ['Nearly 20 years in preclinical research', 'Surgical and technical support', 'Staff training'],
        'first': 'Gil'},
}
REVIEWER = {'Barak Yahalom, DVM': 'Joan Flanagan, PhD', 'Joan Flanagan, PhD': 'Barak Yahalom, DVM',
            'Cindy Hopper': 'Barak Yahalom, DVM', 'Gil Chacon': 'Barak Yahalom, DVM'}

# Per-post presentation details not in the drafts
POSTS = {
    1: {'topic': 'Study design', 'img': ('assets/img/team-group.jpg', 'The Alpha Preclinical team outside the Worcester facility', 'The Alpha study team, Worcester, Massachusetts.'),
        'infographic': {'src': 'assets/img/blog/scope-in-vivo-efficacy-study-hero', 'w': 1672, 'h': 941, 'sizes': (1200, 1672), 'full_link': False,
                        'alt': 'Illustration: a scientist in a lab coat and a colleague in a business suit high-five in front of a screen of study charts, beside laboratory equipment.',
                        'caption': 'A well-scoped study gives scientists and decision-makers results they can act on.'},
        'summary': ['Name the decision the study informs before choosing anything else.', 'Confirm exposure with a short PK study, then match the model to your mechanism.', 'Set the primary endpoint, group size and controls together, and design for ARRIVE 2.0.'],
        'papers': [], 'cta': 'Planning your first efficacy study?'},
    2: {'topic': 'Tumor models', 'img': ('assets/img/lab-biosafety-cabinet.jpg', 'Alpha scientist preparing samples in a biosafety cabinet', 'Sample preparation in the Alpha laboratory, Worcester, Massachusetts.'),
        'infographic': {'src': 'assets/img/blog/caliper-vs-ivis-hero', 'w': 1672, 'h': 941, 'sizes': (1200, 1672), 'full_link': False,
                        'alt': 'Illustration: one scientist measures tumors with digital calipers while a colleague presents a bioluminescence imaging system and its heat-map results on a monitor.',
                        'caption': 'Calipers and bioluminescence imaging measure tumor burden in different ways, and suit different models.'},
        'summary': ['Calipers are enough for most subcutaneous tumors if every group is measured the same way.', 'Orthotopic and metastatic models need bioluminescence imaging.', 'Run a luciferin kinetic curve per model and keep the imaging schedule constant.'],
        'papers': [], 'cta': 'Planning an oncology study?'},
    3: {'topic': 'Metabolic disease', 'img': ('assets/img/lab-biosafety-cabinet.jpg', 'Alpha scientist preparing samples in a biosafety cabinet', 'Sample preparation in the Alpha laboratory, Worcester, Massachusetts.'),
        'infographic': {'src': 'assets/img/blog/diet-vs-genetic-t2d-hero', 'w': 1672, 'h': 941, 'sizes': (1200, 1672), 'full_link': False,
                        'alt': 'Illustration: a split lab scene. On the left, a scientist weighs high-fat diet pellets beside a screen showing body weight, glucose and fatty liver; on the right, a colleague presents genetic data and blood samples on a second screen.',
                        'caption': 'Diet-induced and genetic models reach type 2 diabetes by different routes, and answer different questions.'},
        'summary': ['Diet-induced obesity models mirror early, diet-driven disease; genetic models give fast, severe disease.', 'Substrain, diet and starting age change DIO results, so fix them in the protocol.', 'Many programs use one of each to cover early and advanced disease.'],
        'papers': [('The BBZDR/Wor Rat Model for Investigating the Complications of Type 2 Diabetes Mellitus', 'Tirabassi RS, Flanagan JF, Wu T, Kislauskis EH, Birckbichler PJ, Guberski DL', 'Flanagan JF'),
                   ('The C57BL/6NCrl-lb Mouse: A New Model for Metabolic Syndrome', 'Owens DR, Peterson RG, Yeh W-K, Wang Y, Pritchett-Corning KR, Elder B, Clifford CB, Flanagan J', 'Flanagan J'),
                   ('Antidiabetic effect of novel modulating peptides of G-protein-coupled kinase in experimental models of diabetes', 'Anis Y, Leshem O, Reuveni H, Wexler I, Ben Sasson R, Yahalom B, et al.', 'Yahalom B')],
        'cta': 'Developing a metabolic therapy?'},
    5: {'topic': 'Lab services', 'img': ('assets/img/lab-biosafety-cabinet.jpg', 'Alpha scientist preparing samples in a biosafety cabinet', 'Fresh sample processing in the Alpha laboratory, steps from the vivarium.'),
        'summary': ['Flow cytometry shows how a drug worked, not just whether it did.', 'Process samples fresh, on site, to protect viability and consistency.', 'Design the panel around one question, with viability dye, Fc block and FMO controls.'],
        'papers': [], 'cta': 'Planning an immunology readout?'},
    6: {'topic': 'Study design', 'img': ('assets/img/team-group.jpg', 'The Alpha Preclinical team outside the Worcester facility', 'The Alpha in vivo and surgical team.'),
        'summary': ['Osmotic pumps give steady exposure for days to weeks without repeated handling.', 'Choose them for short half-life compounds, steady-state questions, long studies and brain delivery.', 'Check stability at 37 °C, solubility, priming and fill volume before you implant.'],
        'papers': [('Spinal Anesthesia in Infant Rats: Development of a Model and Assessment of Neurologic Outcomes', 'Yahalom B, Athiraman UK, Soriano S, Zurakowski D, Corfas G, Carpino E, Berde CB', 'Yahalom B'),
                   ('Tetrodotoxin-Bupivacaine-Epinephrine Combinations for Prolonged Local Anesthesia', 'Berde CB, Athiraman U, Yahalom B, Zurakowski D, Corfas G, Bognet C', 'Yahalom B')],
        'cta': 'Need continuous or brain-targeted dosing?'},
    7: {'topic': 'Autoimmune disease', 'img': ('assets/img/lab-biosafety-cabinet.jpg', 'Alpha scientist preparing samples in a biosafety cabinet', 'Sample preparation in the Alpha laboratory, Worcester, Massachusetts.'),
        'summary': ['Rat models reach type 1 diabetes by different genetic routes than the NOD mouse.', 'Inducible models such as LEW.1WR1 let prevention studies be timed precisely.', 'Confirming a NOD result in a rat model makes it more convincing.'],
        'papers': [('LEW.1WR1 Rats Develop Autoimmune Diabetes Spontaneously and in Response to Environmental Perturbation', 'Mordes JP, Guberski DL, Leif JH, Woda BA, Flanagan JF, Greiner DL, Kislauskis EH, Tirabassi RS', 'Flanagan JF'),
                   ('Prevention of Type 1 Diabetes in the Rat With an Allele-Specific Anti-T-Cell Receptor Antibody: Vβ13 as a Therapeutic Target and Biomarker', 'Liu Z, Cort L, Eberwine R, Herrmann T, Leif JH, Greiner DL, Yahalom B, Blankenhorn EP, Mordes JP', 'Yahalom B'),
                   ('Development of Standardized Insulin Treatment Protocols for Spontaneous Rodent Models of Type 1 Diabetes', 'Grant CW, Duclos SK, Moran-Paul CM, Yahalom B, Tirabassi RS, Arreaza-Rubin G, Spain LM, Guberski DL', 'Yahalom B')],
        'cta': 'Working on an autoimmune therapy?'},
}
EXISTING = {4: 'blog-mrna-liver-depot-study-design.html'}

# Phrases that identify a post in link text anywhere on the site
LINK_PHRASES = {
    1: ['How to scope your first'], 2: ['Caliper or IVIS'], 3: ['Diet-induced versus genetic'],
    4: ['mRNA liver depot'], 5: ['in-house flow cytometry', 'Flow cytometry and immunophenotyping'],
    6: ['Continuous dosing with Alzet'], 7: ['rat models of type 1 diabetes'],
}


def slugify(t):
    return re.sub(r'[^a-z0-9]+', '-', t.lower()).strip('-')


def parse_posts(md):
    posts = {}
    for m in re.finditer(r'^## Post (\d+): (.+?)\n(.*?)(?=^## Post \d+:|^## Sources)', md, re.S | re.M):
        n, title, body = int(m.group(1)), m.group(2).strip(), m.group(3)
        seo = dict(re.findall(r'^\| (Author|Title tag|Meta description|Slug|Primary keyword) \| (.+?) \|$', body, re.M))
        seo = {k: v.replace('\\|', '|') for k, v in seo.items()}
        text = re.sub(r'\A\s*(\|.*\|\n)+', '', body, count=1)          # drop only the leading SEO table
        text = re.sub(r'\n\*[^*\n]+\*\s*\n+---\s*$', '\n', text.strip() + '\n')  # drop closing CTA line + rule
        text = re.sub(r'\n---\s*$', '\n', text)
        text = text.replace('- [ ] ', '- ')
        posts[n] = {'title': title, 'seo': seo, 'md': text.strip()}
    return posts


def render_body(mdtext):
    body = markdown.markdown(mdtext, extensions=['tables', 'sane_lists'])
    toc, sources = [], []

    def h(m):
        t = re.sub(r'<.*?>', '', m.group(1))
        sid = slugify(re.sub(r'^\d+\.\s*', '', t))[:48]
        toc.append((sid, re.sub(r'^\d+\.\s*', '', t)))
        return f'<h2 id="{sid}">{m.group(1)}</h2>'
    body = re.sub(r'<h3>(.*?)</h3>', h, body)
    for href, label in re.findall(r'<a href="(https?://[^"]+)">(.*?)</a>', body):
        if href not in [s[0] for s in sources]:
            sources.append((href, re.sub(r'<.*?>', '', label)))
    body = re.sub(r'<a href="(https?://[^"]+)">', r'<a href="\1" rel="noopener">', body)
    body = re.sub(r'<ul>', '<ul class="list">', body)
    body = re.sub(r'<ol>', '<ol class="list">', body)
    # wide tables scroll inside a labeled, keyboard-focusable region on small screens (WCAG 1.4.10)
    def wrap(m):
        before = body[:m.start()]
        heads = re.findall(r'<h2 id="[^"]+">(.*?)</h2>', before)
        label = re.sub(r'<.*?>', '', heads[-1]) if heads else 'Data table'
        return f'<div class="table-scroll" role="region" aria-label="{E(label)}" tabindex="0">{m.group(0)}</div>'
    body = re.sub(r'<table>.*?</table>', wrap, body, flags=re.S)
    # first paragraph is the lede
    body = body.replace('<p>', '<p class="lede">', 1)
    return body, toc, sources


def words(s):
    return len(re.sub(r'<.*?>', ' ', s).split())


def main():
    md = (ROOT / 'content/blog-posts.md').read_text()
    posts = parse_posts(md)
    tpl = TEMPLATE.read_text()
    files = dict(EXISTING)
    for n, p in posts.items():
        if n not in EXISTING:
            files[n] = 'blog-' + p['seo']['Slug'].rsplit('/', 1)[-1] + '.html'

    meta_all = {n: {'title': p['title'], 'author': p['seo']['Author'],
                    'topic': POSTS.get(n, {'topic': 'Gene therapy'})['topic'],
                    'read': f"{max(3, round(words(markdown.markdown(p['md'], extensions=['tables'])) / 200))} min read"} for n, p in posts.items()}

    for n, p in posts.items():
        if n in EXISTING:
            continue
        cfg, author = POSTS[n], p['seo']['Author']
        a, r = PEOPLE[author], PEOPLE[REVIEWER[author]]
        body, toc, sources = render_body(p['md'])
        s = tpl
        # head
        s = re.sub(r'<title>.*?</title>', f'<title>{E(p["seo"]["Title tag"])}</title>', s)
        s = re.sub(r'(<meta name="description" content=")[^"]*', lambda m: m.group(1) + E(p['seo']['Meta description']), s)
        s = re.sub(r'(<meta property="og:title" content=")[^"]*', lambda m: m.group(1) + E(p['seo']['Title tag']), s)
        s = re.sub(r'(<meta property="og:description" content=")[^"]*', lambda m: m.group(1) + E(p['seo']['Meta description']), s)
        ld = {'@context': 'https://schema.org', '@type': 'Article', 'headline': p['title'],
              'author': {'@type': 'Person', 'name': author.split(',')[0], 'jobTitle': a['role']},
              'reviewedBy': {'@type': 'Person', 'name': REVIEWER[author].split(',')[0]},
              'publisher': {'@type': 'Organization', 'name': 'Alpha Preclinical LLC'},
              'keywords': p['seo'].get('Primary keyword', ''), 'citation': [u for u, _ in sources]}
        s = re.sub(r'<script type="application/ld\+json">.*?</script>', '<script type="application/ld+json">' + json.dumps(ld, ensure_ascii=False) + '</script>', s, flags=re.S)
        # header block
        s = re.sub(r'<li aria-current="page">[^<]*</li></ol>', f'<li aria-current="page">{E(cfg["topic"])}</li></ol>', s, count=1)
        s = re.sub(r'(<div class="head-main">.*?)<span class="tag">.*?</span>', lambda m: m.group(1) + f'<span class="tag">{E(cfg["topic"])}</span>', s, count=1, flags=re.S)
        s = re.sub(r'<h1>.*?</h1>', f'<h1>{E(p["title"])}</h1>', s, count=1, flags=re.S)
        s = re.sub(r'<p class="dek">.*?</p>', f'<p class="dek">{E(p["seo"]["Meta description"])}</p>', s, count=1, flags=re.S)
        byline = (f'<div class="byline">\n      <div class="who"><img src="{a["img"]}" alt=""><div><b><a href="team.html#{a["anchor"]}">{E(author)}</a></b><small>{E(a["role"])}</small></div></div>\n'
                  f'      <div class="who"><img src="{r["img"]}" alt=""><div><small>Scientific review by</small><b><a href="team.html#{r["anchor"]}">{E(REVIEWER[author])}</a></b></div></div>\n'
                  f'      <p class="dates"><b>Published</b> [Date]<br><b>Updated</b> [Date], {meta_all[n]["read"]}</p>\n    </div>')
        s = re.sub(r'<div class="byline">.*?</p>\s*</div>', byline, s, count=1, flags=re.S)
        img, alt, cap = cfg['img']
        info = cfg.get('infographic')
        if info:
            src = info['src']
            lo, hi = info.get('sizes', (1200, 2000))
            fig = (f'<figure class="hero-img infographic">\n    <img src="{src}-{lo}.jpg" srcset="{src}-{lo}.jpg {lo}w, {src}-{hi}.jpg {hi}w" '
                   f'sizes="(max-width: 1080px) 100vw, 820px" width="{info["w"]}" height="{info["h"]}" fetchpriority="high" alt="{E(info["alt"])}">\n'
                   f'    <figcaption>{E(info["caption"])}'
                   + (f' <a href="{src}-{hi}.jpg" target="_blank" rel="noopener" aria-label="Open the image full size (opens in new tab)">Open full size</a>' if info.get('full_link', True) else '')
                   + '</figcaption>\n  </figure>')
        else:
            fig = f'<figure class="hero-img">\n    <img src="{img}" alt="{E(alt)}">\n    <figcaption>{E(cap)}</figcaption>\n  </figure>'
        s = re.sub(r'<figure class="hero-img[^"]*">.*?</figure>', lambda m: fig, s, count=1, flags=re.S)
        share_img = f"{info['src']}-{info.get('sizes', (1200, 2000))[1]}.jpg" if info else 'assets/img/building-sign.jpg'
        if share_img:
            s = re.sub(r'(<meta property="og:image" content=")[^"]*', lambda m: m.group(1) + share_img, s)
            if info:
                s = re.sub(r'("@type": "Article", )', lambda m: m.group(1) + f'"image": "{share_img}", ', s, count=1)
        # contents list
        toc_html = ''.join(f'<li><a href="#{sid}">{E(t)}</a></li>' for sid, t in toc)
        if sources:
            toc_html += '<li><a href="#refs">Sources</a></li>'
        s = re.sub(r'(<aside class="toc"[^>]*>\s*<h2>On this page</h2>\s*<ol>).*?(</ol>)', lambda m: m.group(1) + toc_html + m.group(2), s, count=1, flags=re.S)
        # article
        summary = '<div class="summary">\n      <h2>In short</h2>\n      <ul>' + ''.join(f'<li>{E(x)}</li>' for x in cfg['summary']) + '</ul>\n    </div>'
        refs = ''
        if sources:
            refs = ('<section class="refs" id="refs" aria-labelledby="refs-h">\n      <h2 id="refs-h">Sources</h2>\n      <ol>'
                    + ''.join(f'<li><a href="{u}" rel="noopener">{E(l)}</a></li>' for u, l in sources) + '</ol>\n    </section>')
        review = re.search(r'<div class="review">.*?</div>', s, re.S).group(0)
        author_box = (f'<section class="author" aria-labelledby="author-h">\n      <img src="{a["img"]}" alt="{E(author.split(",")[0])}">\n      <div>\n'
                      f'        <h2 id="author-h">{E(author)}</h2>\n        <p class="role">{E(a["role"])}, Alpha Preclinical</p>\n'
                      f'        <p>{E(a["bio"])}</p>\n        <div class="creds">' + ''.join(f'<span>{E(c)}</span>' for c in a['creds']) + '</div>\n'
                      f'        <p class="author-links"><a class="more" href="team.html#{a["anchor"]}">Full profile</a>'
                      + (f'<a class="li" href="{a["linkedin"]}" target="_blank" rel="noopener" aria-label="{E(author.split(",")[0])} on LinkedIn (opens in new tab)">LinkedIn</a>' if a.get('linkedin') else '')
                      + (f'<a class="li" href="https://pubmed.ncbi.nlm.nih.gov/?term={quote(" OR ".join(i + "[pmid]" for i in a["pubmed"]), safe="")}&amp;sort=date" target="_blank" rel="noopener" aria-label="{E(author.split(",")[0])}\'s {len(a["pubmed"])} papers on PubMed (opens in new tab)">PubMed</a>' if a.get('pubmed') else '')
                      + '</p>\n      </div>\n    </section>')
        # mid-article CTA before the third section heading (or the last one in short posts)
        STUDY = {'Tumor models': 'Tumor models', 'Metabolic disease': 'Metabolic disease',
                 'Autoimmune disease': 'Autoimmune disease', 'Gene therapy': 'Gene therapy',
                 'Lab services': 'IVIS imaging, surgery or lab services'}
        study = STUDY.get(cfg['topic'])
        href = 'contact.html' + (f'?study={quote(study, safe="")}' if study else '') + '#form'
        inline = (f'<aside class="inline-cta" aria-label="Talk to a scientist">\n      <p><b>{E(cfg["cta"])}</b> '
                  'A senior scientist can review your model, dose and endpoints before you commit animals.</p>\n'
                  f'      <a class="area-cta" href="{href}">Talk to a scientist</a>\n    </aside>\n    ')
        heads = [m.start() for m in re.finditer(r'<h2 id=', body)]
        if heads:
            at = heads[2] if len(heads) > 3 else heads[-1]
            body = body[:at] + inline + body[at:]
        article = f'<article>\n    {summary}\n\n    {body}\n\n    {refs}\n\n    {review}\n\n    {author_box}\n  </article>'
        s = re.sub(r'<article>.*?</article>', lambda m: article, s, count=1, flags=re.S)
        # related research
        if cfg['papers']:
            lis = ''.join(f'<li class="paper"><p class="jr">Peer-reviewed publication</p><h3><a href="publications.html">{E(t)}</a></h3><p class="by">'
                          + E(by).replace(E(hl), f'<b>{E(hl)}</b>') + '</p></li>' for t, by, hl in cfg['papers'])
            s = re.sub(r'(<section class="research".*?<ul class="papers">).*?(</ul>)', lambda m: m.group(1) + lis + m.group(2), s, count=1, flags=re.S)
            s = re.sub(r'<p class="sub">.*?</p>', '<p class="sub">Peer-reviewed papers on this topic co-authored by Alpha scientists.</p>', s, count=1, flags=re.S)
        else:
            s = re.sub(r'<section class="research".*?</section>\s*', '', s, count=1, flags=re.S)
        # keep reading: three other posts
        others = [k for k in sorted(meta_all) if k != n][:3] if n > 3 else [k for k in sorted(meta_all) if k != n][-3:]
        cards = ''.join(f'<a class="post" href="{files[k]}"><span class="tag">{E(meta_all[k]["topic"])}</span><h3>{E(meta_all[k]["title"])}</h3><p>{E(meta_all[k]["author"])}, {meta_all[k]["read"]}</p></a>' for k in others)
        s = re.sub(r'(<div class="posts">).*?(</div>\s*</div>\s*</section>)', lambda m: m.group(1) + cards + m.group(2), s, count=1, flags=re.S)
        # closing CTA
        s = re.sub(r'<h2 id="cta-h">.*?</h2>', f'<h2 id="cta-h">{E(cfg["cta"])}</h2>', s, count=1, flags=re.S)
        s = re.sub(r'(<section class="cta".*?<p>).*?(</p>)', lambda m: m.group(1) + f'Talk with {a["first"]} and the Alpha team about models, endpoints and timelines for your program.' + m.group(2), s, count=1, flags=re.S)
        (ROOT / files[n]).write_text(s)
        print(f'post {n} -> {files[n]} ({meta_all[n]["read"]}, {len(toc)} sections, {len(sources)} sources)')

    # Make read times everywhere match the real word counts
    for f in glob.glob(str(ROOT / '*.html')):
        s = Path(f).read_text()
        s2 = s
        for k, m in meta_all.items():
            s2 = re.sub(r'(' + re.escape(E(m['title'])) + r'</h[23]>.{0,400}?)\d+ min read', lambda x: x.group(1) + m['read'], s2, flags=re.S)
        if Path(f).name == EXISTING[4]:
            s2 = re.sub(r'(<b>Updated</b> \[Date\], )\d+ min read', lambda x: x.group(1) + meta_all[4]['read'], s2)
        if s2 != s:
            Path(f).write_text(s2)

    # Point every link that names a post at that post's page, across the whole site
    for f in glob.glob(str(ROOT / '*.html')):
        s = Path(f).read_text()
        def retarget(m):
            inner = m.group(3)
            text = re.sub(r'<.*?>', '', inner)
            for k, phrases in LINK_PHRASES.items():
                if any(ph.lower() in text.lower() for ph in phrases):
                    return f'<a {m.group(1)}href="{files[k]}"{m.group(2)}>{inner}</a>'
            return m.group(0)
        s2 = re.sub(r'<a ([^>]*?)href="(?:blog-[a-z0-9-]+\.html|blog\.html)"([^>]*)>(.*?)</a>', retarget, s, flags=re.S)
        if s2 != s:
            Path(f).write_text(s2)

    # Paper titles link to the paper's PDF (see tools/pdf_links.py)
    import sys
    sys.path.insert(0, str(ROOT / 'tools'))
    from pdf_links import rewrite as pdf_rewrite
    for f in glob.glob(str(ROOT / '*.html')):
        s = Path(f).read_text()
        s2 = pdf_rewrite(s)
        if s2 != s:
            Path(f).write_text(s2)


if __name__ == '__main__':
    main()
