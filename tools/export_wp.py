#!/usr/bin/env python3
"""Export the approved static site into WordPress seed content for the block theme.

Reads the generated preview pages, the canvas data arrays and content/blog-posts.md, and
writes wordpress/alpha-preclinical/seed/:

  pages/<slug>.html   block markup for each page (core blocks + alpha/* blocks + bindings)
  publications.json   16 papers with all citation fields
  team.json           5 people: titles, bios (block markup), links, PMIDs, lists
  jobs.json           open roles
  posts.json          7 blog drafts as block markup with SEO fields
  pages.json          page list: slug, title, excerpt (header lead), template, order

The theme's importer (inc/seed.php, `wp alpha seed`) turns these into WordPress content.
Images are written as {{img:<file>}} tokens; the importer swaps in media-library URLs.

Usage: python3 tools/export_wp.py
"""
import html
import json
import re
import subprocess
import zlib
import sys
from pathlib import Path

import markdown
from bs4 import BeautifulSoup, NavigableString, Tag

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'wordpress/alpha-preclinical/seed'
E = html.escape

# ------------------------------------------------------------------ links
PAGE_SLUGS = {
    'index.html': '/', 'expertise.html': '/expertise/', 'services.html': '/services/',
    'tumor-models.html': '/tumor-models/', 'publications.html': '/publications/', 'about.html': '/about/',
    'team.html': '/team/', 'blog.html': '/blog/', 'careers.html': '/careers/', 'contact.html': '/contact/',
    'privacy.html': '/privacy-policy/', 'terms.html': '/terms/', 'accessibility.html': '/accessibility/',
}


def map_href(href):
    href = re.sub(r'^https?://(www\.)?alphapreclinical\.com(?=/)', '', href or '')
    if not href or href.startswith(('http', 'mailto:', 'tel:', '/')):
        return href
    if href.startswith('#'):
        return href
    path, _, frag = href.partition('#')
    path, _, query = path.partition('?')
    if path.startswith('blog-') and path.endswith('.html'):
        new = '/blog/' + path[5:-5] + '/'
    elif path == 'team.html' and frag:
        return '/team/' + frag + '/'
    else:
        new = PAGE_SLUGS.get(path, '/' + path.replace('.html', '/'))
    if query:
        new += '?' + query
    if frag:
        new += '#' + frag
    return new


def inline(node):
    """Inline HTML of a node's children, cleaned for a paragraph/heading/list item."""
    out = []
    for c in node.children:
        if isinstance(c, NavigableString):
            out.append(E(str(c), quote=False))
        elif isinstance(c, Tag):
            if c.name in ('svg', 'img', 'button'):
                continue
            inner_html = inline(c)
            if c.name == 'a':
                href = map_href(c.get('href', ''))
                out.append(f'<a href="{E(href)}">{inner_html}</a>')
            elif c.name in ('strong', 'b'):
                out.append(f'<strong>{inner_html}</strong>')
            elif c.name in ('em', 'i'):
                out.append(f'<em>{inner_html}</em>')
            elif c.name == 'br':
                out.append('<br>')
            elif c.name == 'span' and c.find_previous_sibling('a') is not None and c.parent.name == 'li':
                out.append('<br>' + inner_html)
            elif c.name in ('code', 'sub', 'sup'):
                out.append(f'<{c.name}>{inner_html}</{c.name}>')
            else:
                out.append(inner_html)
    return re.sub(r'\s+', ' ', ''.join(out)).strip()


# ------------------------------------------------------------------ block serializers
def comment(name, attrs=None):
    a = (' ' + json.dumps(attrs, ensure_ascii=False, separators=(',', ':'))) if attrs else ''
    return name, a


def blk(name, attrs=None, inner=None):
    n, a = comment(name, attrs)
    if inner is None:
        return f'<!-- wp:{n}{a} /-->'
    return f'<!-- wp:{n}{a} -->\n{inner}\n<!-- /wp:{n} -->'


def para(text, cls=None):
    if not text:
        return ''
    attrs = {'className': cls} if cls else None
    c = f' class="{cls}"' if cls else ''
    return blk('paragraph', attrs, f'<p{c}>{text}</p>')


def heading(text, level=2, anchor=None, cls=None):
    attrs = {}
    if level != 2:
        attrs['level'] = level
    if anchor:
        attrs['anchor'] = anchor
    if cls:
        attrs['className'] = cls
    idp = f' id="{anchor}"' if anchor else ''
    klass = 'wp-block-heading' + (f' {cls}' if cls else '')
    return blk('heading', attrs or None, f'<h{level} class="{klass}"{idp}>{text}</h{level}>')


def lst(items, ordered=False, cls=None):
    if not items:
        return ''
    attrs = {}
    if ordered:
        attrs['ordered'] = True
    if cls:
        attrs['className'] = cls
    tag = 'ol' if ordered else 'ul'
    inner = '\n'.join(blk('list-item', None, f'<li>{i}</li>') for i in items)
    klass = 'wp-block-list' + (f' {cls}' if cls else '')
    return blk('list', attrs or None, f'<{tag} class="{klass}">{inner}</{tag}>')


def table(head, rows, cls=None):
    th = ''.join(f'<th>{h}</th>' for h in head)
    tb = ''.join('<tr>' + ''.join(f'<td>{c}</td>' for c in r) + '</tr>' for r in rows)
    attrs = {'className': cls} if cls else None
    klass = 'wp-block-table' + (f' {cls}' if cls else '')
    return blk('table', attrs, f'<figure class="{klass}"><table class="has-fixed-layout"><thead><tr>{th}</tr></thead><tbody>{tb}</tbody></table></figure>')


def group(inner, cls=None, align=None, layout='constrained', tag='div', anchor=None):
    attrs = {}
    if tag != 'div':
        attrs['tagName'] = tag
    if anchor:
        attrs['anchor'] = anchor
    if align:
        attrs['align'] = align
    if cls:
        attrs['className'] = cls
    attrs['layout'] = {'type': layout} if layout != 'flex' else {'type': 'flex', 'flexWrap': 'wrap'}
    klass = 'wp-block-group' + (f' align{align}' if align else '') + (f' {cls}' if cls else '')
    idp = f' id="{anchor}"' if anchor else ''
    return blk('group', attrs, f'<{tag} class="{klass}"{idp}>{inner}</{tag}>')


def columns(cols, cls=None):
    """cols: list of (width or None, inner)."""
    parts = []
    for width, inner in cols:
        if width:
            parts.append(blk('column', {'width': width}, f'<div class="wp-block-column" style="flex-basis:{width}">{inner}</div>'))
        else:
            parts.append(blk('column', None, f'<div class="wp-block-column">{inner}</div>'))
    attrs = {'className': cls} if cls else None
    klass = 'wp-block-columns' + (f' {cls}' if cls else '')
    return blk('columns', attrs, f'<div class="{klass}">' + '\n'.join(parts) + '</div>')


def button(text, href, style=None, study=None):
    attrs = {}
    if style:
        attrs['className'] = f'is-style-{style}'
    if study is not None:
        attrs['metadata'] = {'bindings': {'url': {'source': 'alpha/study', 'args': {'study': study}}}}
    klass = 'wp-block-button' + (f' is-style-{style}' if style else '')
    return blk('button', attrs or None, f'<div class="{klass}"><a class="wp-block-button__link wp-element-button" href="{E(href)}">{text}</a></div>')


def buttons(btns):
    return blk('buttons', None, '<div class="wp-block-buttons">' + '\n'.join(btns) + '</div>')


def image(file, alt, caption=None, cls=None):
    attrs = {'sizeSlug': 'large', 'linkDestination': 'none'}
    if cls:
        attrs['className'] = cls
    cap = f'<figcaption class="wp-element-caption">{caption}</figcaption>' if caption else ''
    klass = 'wp-block-image size-large' + (f' {cls}' if cls else '')
    return blk('image', attrs, f'<figure class="{klass}"><img src="{{{{img:{file}}}}}" alt="{E(alt)}"/>{cap}</figure>')


def alpha(name, attrs=None):
    return blk('alpha/' + name, attrs or None)


def bound_para(source, key, fallback='', cls=None, extra_args=None):
    args = {'key': key}
    if extra_args:
        args.update(extra_args)
    attrs = {'metadata': {'bindings': {'content': {'source': source, 'args': args}}}}
    if cls:
        attrs['className'] = cls
    c = f' class="{cls}"' if cls else ''
    return blk('paragraph', attrs, f'<p{c}>{fallback}</p>')


def bound_heading(source, key, level=3, fallback='', cls=None):
    attrs = {'level': level, 'metadata': {'bindings': {'content': {'source': source, 'args': {'key': key}}}}}
    if cls:
        attrs['className'] = cls
    klass = 'wp-block-heading' + (f' {cls}' if cls else '')
    return blk('heading', attrs, f'<h{level} class="{klass}">{fallback}</h{level}>')


def bound_button(text, source, key, style=None):
    attrs = {'metadata': {'bindings': {'url': {'source': source, 'args': {'key': key}}}}}
    if style:
        attrs['className'] = f'is-style-{style}'
    klass = 'wp-block-button' + (f' is-style-{style}' if style else '')
    return blk('button', attrs, f'<div class="{klass}"><a class="wp-block-button__link wp-element-button" href="#">{text}</a></div>')


# ------------------------------------------------------------------ generic HTML → blocks
STYLE_FOR = {'btn-light': 'light', 'btn-ghost-dark': 'ghost-dark', 'btn-ghost': None, 'area-cta': None, 'btn-primary': None}


def study_of(href):
    m = re.search(r'[?&]study=([^&#]+)', href or '')
    if not m:
        return None
    from urllib.parse import unquote
    return unquote(m.group(1))


def lead_members(node):
    slugs = [a['href'].split('#', 1)[1] for a in node.select('a[href*="team.html#"]')]
    return ','.join(dict.fromkeys(slugs))


def is_button(a):
    cls = a.get('class') or []
    return any(c.startswith('btn') or c in ('area-cta', 'apply') for c in cls)


def btn_block(a):
    cls = a.get('class') or []
    style = next((STYLE_FOR[c] for c in cls if c in STYLE_FOR and STYLE_FOR[c]), None)
    href = a.get('href', '')
    st = study_of(href)
    return button(inline(a), map_href(href), style, study=st if st is not None else None)


def convert(node, depth=0):
    """Convert children of a node into block markup."""
    out = []
    pending_btns = []

    def flush():
        if pending_btns:
            out.append(buttons(pending_btns.copy()))
            pending_btns.clear()

    for c in node.children:
        if isinstance(c, NavigableString):
            t = str(c).strip()
            if t:
                flush()
                out.append(para(E(t, quote=False)))
            continue
        if not isinstance(c, Tag) or c.name in ('svg', 'script', 'style', 'button', 'input'):
            continue
        cls = c.get('class') or []
        if c.name == 'a' and is_button(c):
            pending_btns.append(btn_block(c))
            continue
        flush()
        if c.name in ('h1', 'h2', 'h3', 'h4', 'h5'):
            level = max(2, int(c.name[1]))
            out.append(heading(inline(c), level, anchor=c.get('id') if c.get('id') and not c.get('id', '').endswith('-h') else None))
        elif c.name == 'p':
            t = inline(c)
            if t:
                out.append(para(t, 'is-style-lead' if set(cls) & {'intro', 'lead-in', 'desc', 'lede'} else None))
        elif c.name in ('ul', 'ol'):
            items = []
            for li in c.find_all('li', recursive=False):
                main_ = inline(li)
                items.append(main_)
            out.append(lst(items, ordered=c.name == 'ol'))
        elif c.name == 'table':
            head = [inline(th) for th in c.select('thead th')]
            rows = [[inline(td) for td in tr.find_all(['td', 'th'])] for tr in c.select('tbody tr')]
            out.append(table(head, rows, 'is-style-specs'))
        elif c.name == 'dl':
            items = []
            for d in c.find_all('div', recursive=False) or [c]:
                dt, dd = d.find('dt'), d.find('dd')
                if dt and dd:
                    items.append(group(heading(inline(dt), 3, cls='alpha-fact__value') + '\n' + para(inline(dd)), cls='alpha-fact'))
            out.append(group('\n'.join(items), cls='alpha-facts', layout='grid'))
        elif c.name == 'figure' or c.name == 'img':
            img = c if c.name == 'img' else c.find('img')
            if img and img.get('src', '').startswith('assets/img/'):
                cap = c.find('figcaption') if c.name == 'figure' else None
                out.append(image(img['src'].replace('assets/img/', ''), img.get('alt', ''), inline(cap) if cap else None))
        elif c.name == 'address':
            out.append(para(inline(c)))
        elif c.name == 'blockquote':
            out.append(blk('quote', None, '<blockquote class="wp-block-quote">' + convert(c, depth + 1) + '</blockquote>'))
        elif 'lead' in cls and c.name == 'div':
            m = lead_members(c)
            if m:
                out.append(alpha('lead-chip', {'members': m}))
        elif 'band' in cls:
            h = c.find(['h2', 'h3'])
            p = c.find('p')
            a = c.find('a')
            out.append(alpha('cta-band', {
                'heading': inline(h) if h else '', 'text': inline(p) if p else '',
                'buttonText': inline(a) if a else 'Request a study quote',
                'buttonUrl': map_href(a['href']) if a and a.get('href') else '',
                'secondaryText': '', 'align': 'full'}))
        elif c.name in ('form',) or 'done' in cls:
            continue
        elif c.name in ('section', 'div', 'article', 'aside', 'header', 'footer', 'nav', 'li'):
            inner = convert(c, depth + 1)
            if not inner.strip():
                continue
            sect = None
            if c.name == 'section':
                if set(cls) & {'sec-mist', 'area-mist'}:
                    sect = 'is-style-section-mist'
                elif set(cls) & {'area-deep', 'sec-deep', 'svc-split'}:
                    sect = 'is-style-section-abyss'
                out.append(group(group(inner, align='wide', layout='default'),
                                 cls=' '.join(filter(None, [sect, 'alpha-section'])), align='full', tag='section',
                                 anchor=c.get('id')))
            elif c.name == 'article' and 'svc' in cls:
                kids = [k for k in c.children if isinstance(k, Tag)]
                text_col = next((k for k in kids if k.name == 'div' and 'detail' not in (k.get('class') or [])), None)
                detail = next((k for k in kids if 'detail' in (k.get('class') or [])), None)
                media = next((k for k in kids if k.name == 'figure'), None)
                left = convert(text_col, depth + 1) if text_col else ''
                if media:
                    left = convert(BeautifulSoup(str(media), 'html.parser'), depth + 1) + '\n' + left
                right = convert(detail, depth + 1) if detail else ''
                cols = [('42%', left), ('58%', right)]
                if 'flip' in cls:
                    cols.reverse()
                out.append(group(columns(cols, 'alpha-area'), cls='alpha-area-wrap', anchor=c.get('id'), layout='default'))
            else:
                # flatten wrappers with a single child, keep the rest as plain groups
                kids = [k for k in c.children if isinstance(k, Tag) and k.name not in ('svg',)]
                if len(kids) <= 1 or 'wrap' in cls:
                    out.append(inner)
                else:
                    name = next((x for x in cls if x not in ('wrap',)), '')
                    out.append(group(inner, cls=('alpha-' + name) if name else None, layout='constrained'))
        else:
            t = inline(c)
            if t:
                out.append(para(t))
    flush()
    return '\n\n'.join(x for x in out if x)


def page_main(file):
    soup = BeautifulSoup((ROOT / file).read_text(), 'html.parser')
    main = soup.find('main')
    h1 = soup.find('h1')
    title = h1.get_text(' ', strip=True) if h1 else ''
    p = h1.find_next('p') if h1 else None
    lead = p.get_text(' ', strip=True) if p and not (main and main in p.parents) else ''
    return soup, main, title, lead


def page_header(lead=''):
    return alpha('page-header', {'lead': lead, 'align': 'full'}) if lead else alpha('page-header', {'align': 'full'})


def js_array(src, start_marker):
    i = src.index(start_marker)
    i = src.index('[', i)
    depth, j = 0, i
    while True:
        ch = src[j]
        if ch == '[':
            depth += 1
        elif ch == ']':
            depth -= 1
            if depth == 0:
                break
        j += 1
    out = subprocess.run(['node', '-e', f'process.stdout.write(JSON.stringify({src[i:j + 1]}))'],
                         capture_output=True, text=True, check=True)
    return json.loads(out.stdout)


# ------------------------------------------------------------------ data
def export_publications():
    P = js_array((ROOT / 'design/canvas/Publications.dc.html').read_text(), 'const P')
    keys = ['area', 'title', 'authors', 'alpha_authors', 'journal', 'pdf_url', 'pmid', 'pmcid', 'doi',
            'volume', 'issue', 'pages', 'issue_date', 'epub_date']
    featured = {'30879951', '22368175', '21555934'}
    out = []
    for i, row in enumerate(P):
        d = dict(zip(keys, row))
        # citation author lists on the site are full names; keep them, comma separated
        d['alpha_authors'] = '' if d['alpha_authors'].lower().startswith('none') else d['alpha_authors']
        d['legacy_pdf'] = d['pdf_url']
        d['featured'] = d['pmid'] in featured
        d['menu_order'] = i
        out.append(d)
    return out


def export_team():
    soup = BeautifulSoup((ROOT / 'team.html').read_text(), 'html.parser')
    sys.path.insert(0, str(ROOT / 'tools'))
    src = (ROOT / 'tools/build_posts.py').read_text()
    people_src = src[src.index('PEOPLE = {'):src.index('REVIEWER = ')]
    ns = {}
    exec(people_src, ns)
    PEOPLE = {k.split(',')[0]: v for k, v in ns['PEOPLE'].items()}
    # card titles exactly as on the approved homepage cards
    short = {'barak-yahalom': 'CEO and Chief Scientific Officer', 'joan-flanagan': 'President and COO',
             'ashley-kolofsky': 'Study Team Supervisor', 'cindy-hopper': 'Founding Scientist, In Vitro',
             'gil-chacon': 'In Vivo and Surgical Support'}
    out = []
    for i, art in enumerate(soup.select('article.person')):
        slug = art['id']
        full = art.find('h3').get_text(strip=True)
        name, _, creds = full.partition(', ')
        role = art.find('p', class_='role').get_text(strip=True)
        bio_ps = [inline(p) for p in art.select('.bio p')]
        content = '\n\n'.join(para(p) for p in bio_ps)
        sel = art.select_one('.sel')
        if sel:
            items = []
            for li in sel.select('li'):
                a = li.find('a')
                span = li.find('span')
                items.append(f'<a href="{E(a["href"])}">{inline(a)}</a><br>{inline(span) if span else ""}')
            content += '\n\n' + heading('Selected publications', 2) + '\n\n' + lst(items, cls='is-style-rows')
        lists = {}
        for d in art.select('.side > div'):
            h4 = d.find('h4')
            if h4:
                lists[h4.get_text(strip=True)] = [li.get_text(' ', strip=True) for li in d.select('li')]
        leads = lists.get('Leads') or lists.get('Supports') or []
        pm = ''
        a_pm = art.find('a', href=re.compile('pubmed.ncbi.nlm.nih.gov/\\?term='))
        if a_pm:
            pm = ','.join(re.findall(r'(?:=|%20)(\d+)%5Bpmid', a_pm['href']))
        li = art.find('a', href=re.compile('linkedin.com'))
        out.append({
            'slug': slug, 'name': name, 'credentials': creds, 'job_title': role, 'short_title': short.get(slug, ''),
            'excerpt': re.sub(r'<.*?>', '', bio_ps[0]) if bio_ps else '', 'content': content,
            'linkedin': li['href'] if li else '', 'pubmed_ids': pm,
            'leads': ', '.join(leads), 'highlights': '\n'.join(lists.get('Experience', [])),
            'education': '\n'.join(lists.get('Education', [])),
            'photo': f'team/{slug}.jpg', 'alt_photo': f'team/{slug}-alt.jpg', 'menu_order': i,
            'needs_confirmation': slug in ('cindy-hopper', 'gil-chacon', 'ashley-kolofsky'),
        })
    return out


def export_jobs():
    roles = js_array((ROOT / 'design/canvas/Careers.dc.html').read_text(), 'const all')
    return [{'title': r['title'], 'team': r['team'], 'summary': r['summary'], 'requirements': '\n'.join(r['reqs']),
             'employment_type': 'FULL_TIME', 'location': 'Worcester, MA', 'menu_order': i} for i, r in enumerate(roles)]


def export_posts():
    src = (ROOT / 'tools/build_posts.py').read_text()
    ns = {'re': re}
    exec(src[src.index('def parse_posts'):src.index('def render_body')], ns)
    posts = ns['parse_posts']((ROOT / 'content/blog-posts.md').read_text())
    blog = js_array((ROOT / 'design/canvas/Blog.dc.html').read_text(), 'const all')
    topics = {b['title']: b for b in blog}
    heroes = {1: 'blog/scope-in-vivo-efficacy-study-hero-1672.jpg', 2: 'blog/caliper-vs-ivis-hero-1672.jpg',
              3: 'blog/diet-vs-genetic-t2d-hero-1672.jpg', 4: 'blog/mrna-liver-depot-hero-1698.jpg'}
    out = []
    for n, p in sorted(posts.items()):
        body = markdown.markdown(p['md'], extensions=['tables', 'sane_lists'])
        body = re.sub(r'<h3>', '<h2>', body).replace('</h3>', '</h2>')
        body = re.sub(r'<h4>', '<h3>', body).replace('</h4>', '</h3>')
        blocks = convert(BeautifulSoup(f'<div>{body}</div>', 'html.parser').div)
        # The first paragraph is the lede.
        blocks = blocks.replace('<!-- wp:paragraph -->\n<p>', '<!-- wp:paragraph {"className":"is-style-lead"} -->\n<p class="is-style-lead">', 1)
        meta = topics.get(p['title'], {})
        author = p['seo'].get('Author', '')
        out.append({
            'n': n, 'title': p['title'], 'slug': p['seo']['Slug'].rsplit('/', 1)[-1],
            'author_slug': re.sub(r'[^a-z]+', '-', author.split(',')[0].lower()).strip('-'),
            'author': author, 'category': meta.get('topic', 'Study design'), 'excerpt': meta.get('dek', ''),
            'seo_title': p['seo'].get('Title tag', ''), 'seo_description': p['seo'].get('Meta description', ''),
            'focus_keyword': p['seo'].get('Primary keyword', ''), 'hero': heroes.get(n, ''), 'content': blocks,
        })
    return out


# ------------------------------------------------------------------ pages
def faq_block():
    items = js_array((ROOT / 'design/canvas/Main.dc.html').read_text(), 'const items')
    inner = '\n'.join(alpha('faq-item', {'question': it['q'], 'answer': it['a'], 'open': i == 0}) for i, it in enumerate(items))
    return blk('alpha/faq', None, inner)


def cta(heading_='Have a study in mind?', text='Tell us the model, endpoints and timeline. A scientist will reply with a plan and a quote.', button_='Request a study quote'):
    return alpha('cta-band', {'heading': heading_, 'text': text, 'buttonText': button_, 'align': 'full'})


def section(inner, style=None, anchor=None, extra=None):
    cls = ' '.join(filter(None, [f'is-style-section-{style}' if style else None, 'alpha-section', extra]))
    return group(group(inner, align='wide', layout='default'), cls=cls, align='full', tag='section', anchor=anchor)


def home_page():
    soup, main, _, _ = page_main('index.html')
    hero = soup.find('header', class_='hero')
    h1 = inline(hero.find('h1'))
    lead = inline(hero.find('p', class_='lead'))
    stats = []
    for d in hero.select('.hero-aside > div'):
        dt, dd = d.find('dt'), d.find('dd')
        if dt and dd:
            num = re.match(r'([\d.]+)(\D*)', dt.get_text(strip=True))
            stats.append(alpha('stat', {'value': num.group(1) if num else dt.get_text(strip=True), 'suffix': num.group(2) if num else '',
                                        'label': dd.get_text(' ', strip=True), 'size': 'l'}))
    hero_body = hero.select_one('.hero-body') or hero
    hero_btns = [btn_block(a) for a in hero_body.select('a') if is_button(a)]
    hero_inner = columns([
        ('64%', heading(h1, 1, cls='alpha-hero__title') + '\n' + para(lead, 'is-style-lead') + '\n' + (buttons(hero_btns) if hero_btns else '')),
        ('36%', alpha('hero-alpha', {'maxWidth': 400})),
    ], 'alpha-hero__cols') + '\n' + group('\n'.join(stats), cls='alpha-hero__stats', layout='grid')
    hero_block = group(group(hero_inner, align='wide', layout='default') + '\n' + alpha('strata-band', {'variant': 'hero', 'align': 'full'}),
                       cls='is-style-section-abyss alpha-hero', align='full', tag='header')

    # Expertise: heading column + list of the six areas (each a row linking to its section)
    exp_sec = main.select_one('.exp-grid')
    exp_aside = exp_sec.select_one('.exp-aside') if exp_sec else None
    rows = []
    for a in (exp_sec.select('a.model') if exp_sec else []):
        h3 = a.find('h3')
        p = a.find('p')
        lead_div = a.find(class_='lead')
        row = heading(f'<a href="{E(map_href(a["href"]))}">{inline(h3)}</a>', 3) + '\n' + para(inline(p) if p else '')
        if lead_div:
            imgs = [i['src'].split('/')[-1].replace('.jpg', '') for i in lead_div.find_all('img')]
            row += '\n' + alpha('lead-chip', {'members': ','.join(imgs)})
        rows.append(group(row, cls='alpha-model-row'))
    exp = section(columns([('42%', convert(exp_aside) if exp_aside else ''), ('58%', '\n'.join(rows))], 'alpha-sticky-cols'), anchor='expertise')

    # Services split on the deep ground with the lab photo
    svc = main.select_one('.svc-split')
    svc_body = svc.select_one('.svc-body') if svc else None
    services = group(columns([('50%', image('lab-biosafety-cabinet.jpg', 'Alpha scientist preparing samples in a biosafety cabinet', cls='alpha-bleed')),
                              ('50%', convert(svc_body) if svc_body else '')], 'alpha-split'),
                     cls='is-style-section-abyss alpha-section alpha-svc-split', align='full', tag='section')

    # How a study runs
    steps_sec = main.select_one('.strata-steps')
    steps = []
    if steps_sec:
        head = steps_sec.select_one('.steps-head') or steps_sec
        for li in steps_sec.select('.strata-list > li'):
            body = li.select_one('.stratum-body') or li
            steps.append(convert(body))
        steps_html = convert(head) if head is not steps_sec else ''
        steps_block = section(steps_html + '\n' + group('\n'.join(group(s, cls='alpha-stratum') for s in steps), cls='alpha-strata-steps'), anchor='how-a-study-runs')
    else:
        steps_block = ''

    # Publications: the live count + three featured papers (query loop)
    pubs = section(columns([
        ('42%', blk('alpha/stat', {'value': '16', 'label': 'peer-reviewed papers from our scientists and collaborators', 'size': 'xl',
                                   'metadata': {'bindings': {'value': {'source': 'alpha/site', 'args': {'key': 'publication_count'}}}}}) + '\n'
         + heading('Published, peer-reviewed science', 2) + '\n'
         + para('Our scientists have published in Diabetes, Gene Therapy, Molecular Therapy and Anesthesiology. Read the work behind the models we run.') + '\n'
         + buttons([button('All publications', '/publications/', 'arrow')])),
        ('58%', publication_query(featured=True, per_page=3)),
    ], 'alpha-pubs-top'), anchor='publications')

    team = section(heading('The team behind your study', 2) + '\n' + para('Scientists with 20+ years in preclinical research run every study themselves.', 'is-style-lead')
                   + '\n' + team_query(columns_=5) + '\n' + buttons([button('Meet the team', '/team/', 'arrow')]), style='mist', anchor='team')
    faq = section(columns([('42%', heading('Questions sponsors ask us', 2)), ('58%', faq_block())]), anchor='faq')
    return '\n\n'.join([hero_block, exp, services, steps_block, pubs, team, faq, cta()])


def publication_card():
    return group('\n'.join([
        bound_heading('alpha/publication', 'title_link', 3, 'Paper title', 'alpha-paper__title'),
        bound_para('alpha/publication', 'authors', 'Authors', 'alpha-paper__by'),
        bound_para('alpha/publication', 'citation', 'Journal. Year;Vol(Issue):pages.', 'alpha-paper__cite'),
        bound_para('alpha/publication', 'ids', 'DOI · PMID · PMCID', 'alpha-paper__ids'),
        buttons([bound_button('Read PDF', 'alpha/publication', 'pdf_url', 'arrow'),
                 bound_button('PubMed', 'alpha/publication', 'pubmed_url', 'arrow'),
                 bound_button('Free full text', 'alpha/publication', 'pmc_url', 'arrow')]),
    ]), cls='alpha-paper')


def query(post_type, inner, per_page=100, extra=None, cls=None, layout=None, order_by='menu_order', order='asc'):
    q = {'perPage': per_page, 'pages': 0, 'offset': 0, 'postType': post_type, 'order': order, 'orderBy': order_by,
         'author': '', 'search': '', 'exclude': [], 'sticky': '', 'inherit': False}
    if extra:
        q.update(extra)
    attrs = {'queryId': zlib.crc32((post_type + str(extra) + str(per_page)).encode()) % 1000, 'query': q}
    if cls:
        attrs['className'] = cls
    tpl_attrs = {'layout': layout} if layout else None
    tpl = blk('post-template', tpl_attrs, inner)
    klass = 'wp-block-query' + (f' {cls}' if cls else '')
    return blk('query', attrs, f'<div class="{klass}">{tpl}</div>')


def publication_query(featured=False, per_page=100):
    extra = {'alphaFeatured': True} if featured else None
    return query('publication', publication_card(), per_page=per_page, extra=extra, cls='alpha-papers' + (' alpha-papers--featured' if featured else ''))


def team_query(columns_=3):
    return query('team_member', alpha('person-card'), per_page=20, cls='alpha-team-grid',
                 layout={'type': 'grid', 'columnCount': columns_})


def jobs_query():
    inner = blk('details', {'className': 'alpha-role', 'metadata': {'bindings': {'summary': {'source': 'alpha/job', 'args': {'key': 'title'}}}}},
                '<details class="wp-block-details alpha-role"><summary>Role</summary>'
                + bound_para('alpha/job', 'team', '', 'alpha-role__meta') + '\n'
                + bound_para('alpha/job', 'summary', '') + '\n'
                + heading('What we\'re looking for', 4) + '\n'
                + alpha('meta-list', {'source': 'job', 'key': 'requirements'}) + '\n'
                + buttons([bound_button('Apply for this role', 'alpha/job', 'apply_url')])
                + '</details>')
    return query('job', inner, per_page=50, cls='alpha-roles')


def generic_page(file):
    soup, main, title, lead = page_main(file)
    return title, lead, convert(main) if main else ''


def main():
    (OUT / 'pages').mkdir(parents=True, exist_ok=True)
    pages = []

    short = {'home': 'Home', 'expertise': 'Expertise', 'services': 'Services', 'tumor-models': 'Tumor models',
             'about': 'About', 'publications': 'Publications', 'team': 'Team', 'careers': 'Careers', 'contact': 'Contact',
             'blog': 'Blog', 'privacy-policy': 'Privacy policy', 'terms': 'Terms of use', 'accessibility': 'Accessibility'}

    def add(slug, title, content, excerpt='', template='page-wide', order=0, parent=''):
        (OUT / 'pages' / f'{slug}.html').write_text(content.strip() + '\n')
        pages.append({'slug': slug, 'title': title, 'short_title': short.get(slug, title), 'excerpt': excerpt,
                      'template': template, 'menu_order': order, 'parent': parent})

    add('home', 'Home', home_page(), template='', order=0)

    for slug, file, order in [('expertise', 'expertise.html', 1), ('services', 'services.html', 2),
                              ('tumor-models', 'tumor-models.html', 3), ('about', 'about.html', 5)]:
        title, lead, body = generic_page(file)
        if slug == 'tumor-models':
            add(slug, title, body, lead, order=order)
        else:
            add(slug, title, body, lead, order=order)

    # Publications: filter chips + every paper from the CPT
    _, _, title, lead = page_main('publications.html')
    add('publications', title or 'Publications', section(
        alpha('filter-chips', {'taxonomy': 'research_area', 'label': 'Filter by research area'}) + '\n' + publication_query()) + '\n'
        + cta('Want the authors on your study?', 'The scientists who wrote these papers design and run Alpha studies.', 'Request a study quote'),
        lead, order=4)

    # Team: group photo + everyone, then careers link
    _, _, title, lead = page_main('team.html')
    add('team', title or 'Team', section(image('team-group.jpg', 'The Alpha Preclinical team outside the Worcester facility', 'The Alpha study team, Worcester, Massachusetts.') + '\n' + team_query(3)) + '\n'
        + cta('Put this team on your program', 'Tell us what you need to show. The people above will design and run it.'), lead, order=6)

    # Careers: perks (converted), roles (query), application form
    soup, mainc, title, lead = page_main('careers.html')
    why = mainc.select_one('section') if mainc else None
    careers = (convert(BeautifulSoup(str(why), 'html.parser')) if why else '') + '\n' + section(
        heading('Open roles', 2, anchor='roles') + '\n' + alpha('filter-chips', {'taxonomy': 'job_team', 'label': 'Filter roles', 'showCounts': False}) + '\n' + jobs_query(), style='mist') + '\n' + section(
        heading('Apply', 2) + '\n' + para('Send your resume and tell us which role interests you. We read every application.') + '\n' + alpha('quote-form', {'formType': 'careers', 'submitText': 'Send application', 'successHeading': 'Application sent'}), anchor='apply')
    add('careers', title or 'Careers', careers, lead, order=7)

    # Contact: form + details + building photo
    _, _, title, lead = page_main('contact.html')
    contact = section(columns([
        ('58%', heading('Request a study quote', 2) + '\n' + alpha('quote-form', {'formType': 'quote'})),
        ('42%', alpha('contact-card') + '\n' + image('building-sign.jpg', 'Alpha Preclinical sign at the entrance of 722 Plantation Street', 'Look for our sign at the entrance off Plantation Street.')),
    ], 'alpha-contact-cols'))
    add('contact', title or 'Contact', contact, lead, order=8)

    # Blog index is the posts page (template home.html); give it an empty page to own the URL.
    _, _, title, lead = page_main('blog.html')
    add('blog', title or 'Blog', '', lead, template='', order=9)

    for slug, file, order in [('privacy-policy', 'privacy.html', 20), ('terms', 'terms.html', 21), ('accessibility', 'accessibility.html', 22)]:
        title, lead, body = generic_page(file)
        # The response time comes from Settings > Alpha Preclinical through a binding.
        body = re.sub(r'(<!-- wp:paragraph -->\n<p>Please tell us the page address and the problem you found\.) We aim to respond within \[number\] business days\.(</p>\n<!-- /wp:paragraph -->)',
                      lambda m: m.group(1) + m.group(2) + '\n\n' + bound_para('alpha/site', 'a11y_promise', 'We aim to respond within two business days.'), body)
        add(slug, title, body, lead, template='page-legal', order=order)

    (OUT / 'pages.json').write_text(json.dumps(pages, indent=1, ensure_ascii=False) + '\n')
    (OUT / 'publications.json').write_text(json.dumps(export_publications(), indent=1, ensure_ascii=False) + '\n')
    (OUT / 'team.json').write_text(json.dumps(export_team(), indent=1, ensure_ascii=False) + '\n')
    (OUT / 'jobs.json').write_text(json.dumps(export_jobs(), indent=1, ensure_ascii=False) + '\n')
    (OUT / 'posts.json').write_text(json.dumps(export_posts(), indent=1, ensure_ascii=False) + '\n')
    print('pages:', ', '.join(p['slug'] for p in pages))


if __name__ == '__main__':
    main()
