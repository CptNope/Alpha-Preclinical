#!/usr/bin/env python3
"""Convert the Alpha Preclinical design-canvas artboards (.dc.html) into
standalone static pages for GitHub Pages.

Usage: python3 tools/convert.py <design-dir> <site-dir>

- Moves each artboard's <helmet> (fonts + styles) into <head>
- Rewrites uploaded-asset URLs (/_blob/<id>) to local files in assets/img/
- Rewrites artboard links (Main.dc.html -> index.html, ...)
- Pre-renders the template loops (<sc-for>/<sc-if>) from each page's own data
  and wires them to assets/js/site.js (filters, accordions, demo forms)
"""
import html, json, re, subprocess, sys
from pathlib import Path

SRC, OUT = Path(sys.argv[1]), Path(sys.argv[2])

PAGES = {
    "Main.dc.html": "index.html",
    "About.dc.html": "about.html",
    "Team.dc.html": "team.html",
    "Publications.dc.html": "publications.html",
    "Blog.dc.html": "blog.html",
    "BlogPost.dc.html": "blog-mrna-liver-depot-study-design.html",
    "Model.dc.html": "tumor-models.html",
    "Services.dc.html": "services.html",
    "Expertise.dc.html": "expertise.html",
    "Privacy.dc.html": "privacy.html",
    "Terms.dc.html": "terms.html",
    "Accessibility.dc.html": "accessibility.html",
    "Contact.dc.html": "contact.html",
    "Careers.dc.html": "careers.html",
}

BLOBS = {
    "5bedb9f60c7733d86df9882fb9203c7e": "logo-white.png",
    "89e989d9efac5d46f675da40c6c0d1af": "logo-color.png",
    "4902e50d4f6db0edc079f4555cedece8": "team/ashley-kolofsky.jpg",
    "d2a6749285f43cca6b8e2205d889e9d9": "team/ashley-kolofsky-alt.jpg",
    "c9d31a9dcb31363d7c0a0ba388f59670": "team/barak-yahalom.jpg",
    "90d2010c3308d1f32d45b081d74dee0b": "team/barak-yahalom-alt.jpg",
    "22a259045205d87350a7c4a2d4743305": "team/cindy-hopper.jpg",
    "7ef4fed6780a103583e1f7c2db5e78b4": "team/cindy-hopper-alt.jpg",
    "96ff2bcfb76710fba07fe859fbc51e0d": "team/gil-chacon.jpg",
    "7fa90d189bc6591ba8d59455844bd6db": "team/gil-chacon-alt.jpg",
    "590caa59917513c5891b761a02b75807": "team/joan-flanagan.jpg",
    "1aa762b21513362591f4458523ce3c40": "team/joan-flanagan-alt.jpg",
    "4b5391a4eda08ec8242ae09059e6a7e3": "lab-biosafety-cabinet.jpg",
    "35e49d3be0586d13613489ae4711ffcd": "team-group.jpg",
    "6835d5a9a6ca2e549cf8f7447ed8fb53": "building-sign.jpg",
    "c3a8bbadbbc0be6038c94e6152bf7e06": "map-722-plantation-st.jpg",
}

TITLES = {
    "index.html": "Preclinical In Vivo CRO in Worcester, MA | Alpha Preclinical",
    "about.html": "About Us | Alpha Preclinical",
    "team.html": "Our Team | Alpha Preclinical",
    "publications.html": "Publications | Alpha Preclinical",
    "blog.html": "Blog | Alpha Preclinical",
    "blog-mrna-liver-depot-study-design.html": "Designing In Vivo mRNA-LNP Studies | Alpha Preclinical",
    "tumor-models.html": "Tumor Models for In Vivo Oncology Studies | Alpha Preclinical",
    "contact.html": "Contact and Study Quotes | Alpha Preclinical",
    "expertise.html": "Preclinical Disease Models and Expertise | Alpha Preclinical",
    "privacy.html": "Privacy Policy | Alpha Preclinical",
    "terms.html": "Terms of Use | Alpha Preclinical",
    "accessibility.html": "Accessibility Statement | Alpha Preclinical",
    "services.html": "Preclinical CRO Services: IVIS, Surgery, In Vitro Lab | Alpha Preclinical",
    "careers.html": "Careers | Alpha Preclinical",
}

META = {
    "index.html": "Alpha Preclinical is a Worcester, MA preclinical CRO for in vivo PK/PD, efficacy and proof-of-concept studies, with imaging, surgery and in vitro lab services.",
    "about.html": "Founded in 2020, Alpha Preclinical is a US-owned preclinical research company in Worcester, MA, built by scientists with decades of CRO and pharma experience.",
    "team.html": "Meet the Alpha Preclinical team: Barak Yahalom, DVM, Joan Flanagan, PhD, and the founding research team who run your preclinical studies.",
    "publications.html": "Sixteen peer-reviewed papers from Alpha Preclinical scientists and collaborators on rodent disease models, mRNA delivery, anesthesia and gene regulation.",
    "blog.html": "Practical guidance on preclinical study design, model selection and in vivo readouts from the scientists who run Alpha Preclinical's studies.",
    "blog-mrna-liver-depot-study-design.html": "How preclinical studies of mRNA-LNP protein replacement are designed, using published Fabry disease and hemophilia B studies as worked examples.",
    "tumor-models.html": "Syngeneic and xenograft tumor models for in vivo oncology efficacy studies, with caliper and bioluminescent IVIS imaging readouts. Worcester, MA.",
    "contact.html": "Request a preclinical study quote from Alpha Preclinical. Email info@alphapreclinical.com or visit us at 722 Plantation Street, Worcester, MA 01605.",
    "expertise.html": "Rodent disease models and study expertise in PK/PD, gene therapy, autoimmune and metabolic disease, inflammation and fibrosis, and oncology.",
    "privacy.html": "How Alpha Preclinical collects, uses and protects information submitted through its website contact, quote and job application forms.",
    "terms.html": "Terms governing use of the Alpha Preclinical website, including intellectual property, acceptable use and limitations of liability.",
    "accessibility.html": "Alpha Preclinical's commitment to WCAG 2.1 AA accessibility, how the site was tested, known limitations and how to request help.",
    "services.html": "In vivo PK/PD, proof-of-concept and efficacy studies, plus IVIS imaging, USDA-compliant surgery, colony management and in vitro lab services in Worcester, MA.",
    "careers.html": "Join Alpha Preclinical in Worcester, MA. Open roles for in vivo and in vitro research associates, technicians and study directors.",
}

ORG_LD = {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Alpha Preclinical LLC",
    "url": "https://www.alphapreclinical.com/",
    "logo": "https://www.alphapreclinical.com/assets/img/logo-color.png",
    "email": "info@alphapreclinical.com",
    "sameAs": ["https://www.linkedin.com/company/alpha-preclinical"],
    "foundingDate": "2020",
    "address": {"@type": "PostalAddress", "streetAddress": "722 Plantation Street",
                "addressLocality": "Worcester", "addressRegion": "MA",
                "postalCode": "01605", "addressCountry": "US"},
}
ARTICLE_LD = {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "What an mRNA liver depot study looks like in practice",
    "author": {"@type": "Person", "name": "Barak Yahalom", "honorificSuffix": "DVM",
               "jobTitle": "Chief Executive Officer and Chief Scientific Officer"},
    "reviewedBy": {"@type": "Person", "name": "Joan Flanagan", "honorificSuffix": "PhD"},
    "publisher": {"@type": "Organization", "name": "Alpha Preclinical LLC"},
    "citation": [
        "DeRosa F, et al. Therapeutic efficacy in a hemophilia B model using a biosynthetic mRNA liver depot system. Gene Therapy. 2016;23(10):699-707.",
        "DeRosa F, et al. Improved efficacy in a Fabry disease model using a systemic mRNA liver depot system as compared to enzyme replacement therapy. Molecular Therapy. 2019;27(4):878-889.",
    ],
}

E = html.escape


def js_array(src, start_marker):
    """Evaluate a JS array literal that begins at start_marker using node."""
    i = src.index(start_marker)
    i = src.index("[", i)
    depth, j = 0, i
    while True:
        c = src[j]
        if c == "[":
            depth += 1
        elif c == "]":
            depth -= 1
            if depth == 0:
                break
        j += 1
    text = src[i:j + 1]
    out = subprocess.run(["node", "-e", f"process.stdout.write(JSON.stringify({text}))"],
                         capture_output=True, text=True, check=True)
    return json.loads(out.stdout)


def cut(s, start, end_after_start, include_end=True, last=False):
    """Return (before, middle, after) splitting s on start..end."""
    a = s.index(start)
    b = s.rindex(end_after_start) if last else s.index(end_after_start, a)
    if include_end:
        b += len(end_after_start)
    return s[:a], s[a:b], s[b:]


def replace_region(s, start, end, new, last=False):
    before, _, after = cut(s, start, end, last=last)
    return before + new + after


def filter_buttons(labels, counts=None):
    """Toggle buttons for a filter group; a visually hidden status line announces results."""
    out = []
    for i, label in enumerate(labels):
        small = f" <small>{counts[label]}</small>" if counts else ""
        out.append(f'<button type="button" data-filter="{E(label)}" aria-pressed="{"true" if i == 0 else "false"}">{E(label)}{small}</button>')
    return "\n        ".join(out)


# ---------------------------------------------------------------- per-page
def page_main(s, src):
    items = js_array(src, "const items")
    rows = []
    for i, it in enumerate(items):
        open_ = i == 0
        rows.append(
            f'<div class="q"><button type="button" data-accordion aria-expanded="{"true" if open_ else "false"}" aria-controls="faq-{i}">'
            f'<span>{E(it["q"])}</span><span class="ico" aria-hidden="true">{"−" if open_ else "+"}</span></button>'
            f'<p class="ans" id="faq-{i}"{"" if open_ else " hidden"}>{E(it["a"])}</p></div>')
    s = replace_region(s, '<sc-for list="{{faqs}}"', "</sc-for>", "\n      ".join(rows))
    faq_ld = {"@context": "https://schema.org", "@type": "FAQPage",
              "mainEntity": [{"@type": "Question", "name": it["q"],
                              "acceptedAnswer": {"@type": "Answer", "text": it["a"]}} for it in items]}
    s = s.replace('href="#tumor"', 'href="tumor-models.html"')
    return s, [ORG_LD, faq_ld]


def citation(date, volume, issue, pages, epub):
    """NLM-style source line: 2012 May;61(5):1160–1168. Epub 2012 Apr 13."""
    c = date + (f";{volume}" if volume else "") + (f"({issue})" if issue else "") + (f":{pages}" if pages else "") + "."
    return c + (f" Epub {epub}." if epub else "")


MONTHS = {m: i for i, m in enumerate("Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec".split(), 1)}


def iso(d):
    """'2012 Apr 13' -> '2012-04-13', '2012 May' -> '2012-05', '2004' -> '2004'."""
    parts = d.split()
    out = parts[0]
    if len(parts) > 1 and parts[1][:3] in MONTHS:
        out += f"-{MONTHS[parts[1][:3]]:02d}"
        if len(parts) > 2:
            out += f"-{int(parts[2]):02d}"
    return out


def page_publications(s, src):
    P = js_array(src, "const P")
    order = ["Gene therapy", "Type 1 diabetes", "Type 2 diabetes and metabolic syndrome",
             "Anesthesia and analgesia", "Gene regulation"]
    counts = {"All": len(P), **{a: sum(1 for p in P if p[0] == a) for a in order}}
    s = replace_region(s, '<sc-for list="{{filters}}"', "</sc-for>", filter_buttons(["All"] + order, counts))
    s = s.replace('<div class="filters" role="group"', '<div class="filters" data-filter-group="pubs" data-noun="paper" role="group"', 1)
    groups = []
    for name in order:
        papers = [p for p in P if p[0] == name]
        lis = []
        for (area, title, others, alpha, journal, pdf, pmid, pmcid, doi,
             volume, issue, pages, date, epub) in papers:
            links = f'<a class="pdf" href="{E(pdf)}" target="_blank" rel="noopener" aria-label="Read PDF: {E(title)} (opens in new tab)">Read PDF</a>'
            if pmid:
                links += (f'<a class="pdf nih" href="https://pubmed.ncbi.nlm.nih.gov/{pmid}/" target="_blank" rel="noopener" '
                          f'aria-label="PubMed record: {E(title)} (opens in new tab)">PubMed</a>')
            if pmcid:
                links += (f'<a class="pdf nih" href="https://pmc.ncbi.nlm.nih.gov/articles/{pmcid}/" target="_blank" rel="noopener" '
                          f'aria-label="Free full text on PubMed Central: {E(title)} (opens in new tab)">Free full text</a>')
            ids = ""
            if doi:
                ids += f'<div><dt>DOI</dt><dd><a href="https://doi.org/{E(doi)}" target="_blank" rel="noopener">{E(doi)}</a></dd></div>'
            if pmid:
                ids += f'<div><dt>PMID</dt><dd>{pmid}</dd></div>'
            if pmcid:
                ids += f'<div><dt>PMCID</dt><dd>{pmcid}</dd></div>'
            lis.append(f'<li class="paper"><h3>{E(title)}</h3><p class="by">{E(others)}</p>'
                       f'<p class="cite"><i>{E(journal)}</i>. {E(citation(date, volume, issue, pages, epub))}</p>'
                       f'<dl class="ids">{ids}</dl>'
                       f'<p class="meta"><span class="alpha">Alpha authors: {E(alpha)}</span></p>'
                       f'<div class="links">{links}</div></li>')
        n = len(papers)
        groups.append(f'<section class="group" data-item="pubs" data-topic="{E(name)}"><div><h2>{E(name)}</h2>'
                      f'<p class="count">{n} paper{"s" if n != 1 else ""}</p></div>'
                      f'<ol class="list">{"".join(lis)}</ol></section>')
    s = replace_region(s, '<sc-for list="{{groups}}"', "</sc-for>", "\n    ".join(groups), last=True)

    def article(p):
        (area, title, others, alpha, journal, pdf, pmid, pmcid, doi,
         volume, issue, pages, date, epub) = p
        periodical = {"@type": "Periodical", "name": journal}
        part = periodical
        if volume:
            part = {"@type": "PublicationVolume", "volumeNumber": volume, "isPartOf": periodical}
        if issue:
            part = {"@type": "PublicationIssue", "issueNumber": issue, "isPartOf": part}
        a = {"@type": "ScholarlyArticle", "headline": title, "name": title,
             "author": [{"@type": "Person", "name": x.strip()} for x in others.split(",") if x.strip() and x.strip() != "et al."],
             "isPartOf": part, "datePublished": iso(epub or date), "url": pdf, "about": area}
        if pages:
            a["pagination"] = pages
        same = []
        if pmid: same.append(f"https://pubmed.ncbi.nlm.nih.gov/{pmid}/")
        if pmcid: same.append(f"https://pmc.ncbi.nlm.nih.gov/articles/{pmcid}/")
        if doi: same.append(f"https://doi.org/{doi}")
        if same: a["sameAs"] = same
        ident = [{"@type": "PropertyValue", "propertyID": k, "value": v}
                 for k, v in (("DOI", doi), ("PMID", pmid), ("PMCID", pmcid)) if v]
        if ident: a["identifier"] = ident
        return a
    ld = {"@context": "https://schema.org", "@type": "ItemList", "name": "Alpha Preclinical publications",
          "itemListElement": [{"@type": "ListItem", "position": i + 1, "item": article(p)} for i, p in enumerate(P)]}
    return s, [ld]


def page_blog(s, src):
    posts = js_array(src, "const all")
    topics = ["All", "Study design", "Tumor models", "Metabolic disease", "Gene therapy",
              "Autoimmune disease", "Lab services"]
    s = replace_region(s, '<sc-for list="{{filters}}"', "</sc-for>", filter_buttons(topics))
    s = s.replace('<div class="filters" role="group"', '<div class="filters" data-filter-group="posts" role="group"').replace('data-filter-group="posts" role="group"', 'data-filter-group="posts" data-noun="post" role="group"', 1)
    f = posts[0]
    before, feat, after = cut(s, '<sc-if value="{{showFeature}}"', "</sc-if>")
    feat = feat.split(">", 1)[1].rsplit("</sc-if>", 1)[0]
    feat = (feat.replace("{{feature.topic}}", E(f["topic"])).replace("{{feature.title}}", E(f["title"]))
                .replace("{{feature.dek}}", E(f["dek"])).replace("{{feature.author}}", E(f["author"]))
                .replace("{{feature.read}}", E(f["read"])))
    feat = feat.replace('<a class="feature"', f'<a class="feature" data-item="posts" data-topic="{E(f["topic"])}"', 1)
    s = before + feat + after
    before, loop, after = cut(s, '<sc-for list="{{posts}}"', "</sc-for>")
    tpl = loop.split(">", 1)[1].rsplit("</sc-for>", 1)[0]
    cards = []
    for p in posts[1:]:
        c = tpl
        # card art: the post's image if it has one, otherwise the placeholder waves
        keep, drop = ("p.img", "p.noImg") if p.get("img") else ("p.noImg", "p.img")
        c = re.sub(r'<sc-if value="\{\{' + re.escape(drop) + r'\}\}"[^>]*>.*?</sc-if>', "", c, count=1, flags=re.S)
        c = re.sub(r'<sc-if value="\{\{' + re.escape(keep) + r'\}\}"[^>]*>(.*?)</sc-if>', lambda m: m.group(1), c, count=1, flags=re.S)
        c = c.replace("{{p.img}}", E(p.get("img", "")))
        for k in ("topic", "title", "dek", "author", "read"):
            c = c.replace("{{p.%s}}" % k, E(p[k]))
        c = c.replace('<a class="post"', f'<a class="post" data-item="posts" data-topic="{E(p["topic"])}"', 1)
        cards.append(c)
    s = before + "".join(cards) + after
    before, empty, after = cut(s, '<sc-if value="{{isEmpty}}"', "</sc-if>")
    empty = empty.split(">", 1)[1].rsplit("</sc-if>", 1)[0].replace('<p class="empty">', '<p class="empty" data-empty="posts" hidden>')
    return before + empty + after, []


def form_block(s, extra=None):
    """Turn the notSent/sent template pair into a static demo form + hidden confirmation."""
    a = s.index('<sc-if value="{{notSent}}"')
    c = s.index("</sc-if>", s.index("</form>", a)) + len("</sc-if>")
    before, form, after = s[:a], s[a:c], s[c:]
    form = form.split(">", 1)[1].rsplit("</sc-if>", 1)[0]
    form = form.replace(' onSubmit="{{submit}}"', ' data-demo-form')
    form = re.sub(r' value="\{\{email\}\}" onChange="\{\{setEmail\}\}" aria-invalid="\{\{emailInvalid\}\}"', ' required aria-invalid="false"', form)
    form = re.sub(r'<sc-if value="\{\{showEmailErr\}\}"[^>]*>(.*?)</sc-if>',
                  lambda m: m.group(1).replace('<span class="err"', '<span class="err" hidden'), form)
    if extra:
        form = extra(form)
    s = before + form + after
    before, done, after = cut(s, '<sc-if value="{{sent}}"', "</sc-if>")
    done = done.split(">", 1)[1].rsplit("</sc-if>", 1)[0]
    done = (done.replace('<div class="done" role="status">', '<div class="done" role="status" data-done hidden>')
                .replace("{{email}}", '<span data-email-out></span>')
                .replace("{{applyRole}}", '<span data-role-out></span>')
                .replace(' onClick="{{reset}}"', ' data-reset'))
    return before + done + after


def page_contact(s, src):
    return form_block(s), []


def page_careers(s, src):
    roles = js_array(src, "const all")
    s = replace_region(s, '<sc-for list="{{filters}}"', "</sc-for>", filter_buttons(["All", "In vivo", "In vitro"]))
    s = s.replace('<div class="filters" role="group"', '<div class="filters" data-filter-group="roles" role="group"').replace('data-filter-group="roles" role="group"', 'data-filter-group="roles" data-noun="role" role="group"', 1)
    lis = []
    for i, r in enumerate(roles):
        open_ = i == 0
        reqs = "".join(f"<li>{E(q)}</li>" for q in r["reqs"])
        lis.append(
            f'<li class="role" data-item="roles" data-topic="{E(r["team"])}">'
            f'<h3 class="role-h"><button class="role-btn" type="button" data-accordion aria-expanded="{"true" if open_ else "false"}" aria-controls="role-{i}">'
            f'<span class="role-title">{E(r["title"])}</span><span class="role-meta"><span class="chip">{E(r["team"])}</span>'
            f'<span class="chip">Full-time, on-site</span><span class="chip">Worcester, MA</span></span>'
            f'<span class="ico" aria-hidden="true">{"−" if open_ else "+"}</span></button></h3>'
            f'<div class="role-body" id="role-{i}"{"" if open_ else " hidden"}><div><p>{E(r["summary"])}</p>'
            f'<a class="btn btn-primary apply" href="#apply" data-apply-role="{E(r["title"])}">Apply for this role</a></div>'
            f'<div><h4>What we\'re looking for</h4><ul>{reqs}</ul></div></div></li>')
    s = replace_region(s, '<sc-for list="{{roles}}"', "</sc-for>\n    </ul>", "\n        ".join(lis) + "\n    </ul>")
    options = [r["title"] for r in roles] + ["General application"]

    def fix_select(form):
        form = form.replace(' value="{{applyRole}}" onChange="{{setRole}}"', ' name="role"')
        return re.sub(r'<sc-for list="\{\{roleOptions\}\}".*?</sc-for>',
                      "".join(f'<option value="{E(o)}">{E(o)}</option>' for o in options), form, flags=re.S)
    return form_block(s, fix_select), []


def page_post(s, src):
    ld = dict(ARTICLE_LD)
    hero = re.search(r'<figure class="hero-img infographic">\s*<img[^>]*?srcset="([^"]+)"', s)
    if hero:
        ld["image"] = "assets/" + hero.group(1).split(",")[-1].strip().split(" ")[0]
    return s, [ld]


def page_team(s, src):
    people = []
    for m in re.finditer(r'<article class="person" id="([a-z-]+)">(.*?)</article>', s, re.S):
        pid, body = m.groups()
        name = re.sub(r"<.*?>", "", re.search(r"<h3>(.*?)</h3>", body).group(1))
        role = re.search(r'<p class="role">(.*?)</p>', body).group(1)
        base, _, suffix = name.partition(", ")
        p = {"@type": "Person", "@id": f"team.html#{pid}", "name": base, "jobTitle": html.unescape(role),
             "worksFor": {"@type": "Organization", "name": "Alpha Preclinical"}, "url": f"team.html#{pid}"}
        if suffix:
            p["honorificSuffix"] = suffix
        li = re.search(r'<a class="li" href="([^"]+)"', body)
        if li:
            p["sameAs"] = [li.group(1)]
        txt = lambda h: html.unescape(re.sub(r"<.*?>", "", h)).strip()
        bio = re.search(r'<div class="bio">\s*<p>(.*?)</p>', body, re.S)
        if bio:
            p["description"] = txt(bio.group(1))
        p["image"] = f"assets/img/team/{pid}.jpg"
        def side(head):
            m = re.search(r"<h4>" + head + r"</h4><ul>(.*?)</ul>", body, re.S)
            return [txt(x) for x in re.findall(r"<li>(.*?)</li>", m.group(1), re.S)] if m else []
        knows = side("Leads") or side("Supports")
        if knows:
            p["knowsAbout"] = knows
        schools = []
        for e in side("Education"):
            parts = [x.strip() for x in e.split(",")]
            named = [x for x in parts if re.search(r"University|College|School|Institute", x)]
            org = named[-1] if named else parts[-1]
            if org and org not in schools:
                schools.append(org)
        if schools:
            p["alumniOf"] = [{"@type": "EducationalOrganization", "name": o} for o in schools]
        member = [m for m in side("Affiliations") if not m.startswith("[")]
        if member:
            p["memberOf"] = [{"@type": "Organization", "name": m} for m in member]
        people.append(p)
    return s, [{"@context": "https://schema.org", "@type": "ItemList", "name": "Alpha Preclinical team",
                "itemListElement": [{"@type": "ListItem", "position": i + 1, "item": p} for i, p in enumerate(people)]}]


HANDLERS = {"Main.dc.html": page_main, "Team.dc.html": page_team, "Publications.dc.html": page_publications,
            "Blog.dc.html": page_blog, "Contact.dc.html": page_contact,
            "Careers.dc.html": page_careers, "BlogPost.dc.html": page_post}

MENU_BTN = ('<button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-menu>'
            '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" '
            'stroke-linecap="round" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14"></path></svg></button>')


def convert(name, outname):
    src = (SRC / name).read_text()
    title = re.search(r"<title>(.*?)</title>", src, re.S).group(1).strip()
    helmet = re.search(r"<helmet>(.*?)</helmet>", src, re.S).group(1)
    body = re.search(r"<x-dc>(.*?)</x-dc>", src, re.S).group(1)
    body = re.sub(r"<helmet>.*?</helmet>", "", body, flags=re.S).strip()
    lds = []
    if name in HANDLERS:
        body, lds = HANDLERS[name](body, src)
    page = helmet + "\n" + body
    for blob, f in BLOBS.items():
        page = page.replace(f"/_blob/{blob}", f"assets/img/{f}")
    # images published beside the artboards (project/img/...) live in assets/img/ on the site
    page = page.replace('src="img/', 'src="assets/img/')
    page = re.sub(r'srcset="([^"]*)"', lambda m: 'srcset="' + re.sub(r'(^|, )img/', r'\1assets/img/', m.group(1)) + '"', page)
    # a post's illustrated hero is also its share image
    hero = re.search(r'<figure class="hero-img infographic">\s*<img src="([^"]+)"[^>]*?srcset="([^"]+)"', page)
    share = hero.group(2).split(",")[-1].strip().split(" ")[0] if hero else None
    for a, b in PAGES.items():
        page = page.replace(f'href="{a}', f'href="{b}')
    # Main's own menu button becomes the shared one; other pages get one injected
    page = re.sub(r'<button class="menu-btn".*?</button>', "", page, flags=re.S)
    page = re.sub(r'(<a class="btn btn-light" href="[^"]*"[^>]*>Request a study quote</a>)(\s*</div>)',
                  lambda m: m.group(1) + MENU_BTN + m.group(2), page, count=1)
    leftover = re.findall(r"\{\{.*?\}\}|<sc-(?:for|if)|onClick=|onChange=|onSubmit=", page)
    if leftover:
        raise SystemExit(f"{name}: unconverted template syntax {leftover[:5]}")
    ld = "".join(f'\n<script type="application/ld+json">{json.dumps(x, ensure_ascii=False)}</script>' for x in lds)
    # The helmet holds the page's <link>/<style>; the page body starts at <div class="ap">
    head_part, body_part = page.split('<div class="ap">', 1)
    doc = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{E(TITLES[outname])}</title>
<meta name="description" content="{E(META[outname])}">
<meta property="og:title" content="{E(TITLES[outname])}">
<meta property="og:description" content="{E(META[outname])}">
<meta property="og:type" content="{'article' if outname.startswith('blog-') else 'website'}">
<meta property="og:image" content="{share or 'assets/img/building-sign.jpg'}">
<link rel="icon" href="assets/img/logo-color.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{head_part.strip()}
<link rel="stylesheet" href="assets/css/site.css">{ld}
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="ap">{body_part}
<script src="assets/js/site.js" defer></script>
</body>
</html>
"""
    # give the first <main> an id for the skip link
    doc = re.sub(r"<main(?![^>]*\bid=)", '<main id="main"', doc, count=1)
    (OUT / outname).write_text(doc)
    print(f"{name} -> {outname} ({len(doc)//1024} KB)")


for n, o in PAGES.items():
    convert(n, o)
