#!/usr/bin/env python3
"""
Turn every section of the approved static demo into a WordPress custom block.

For each top-level section (the page header, each child of <main>, the footer) this:
  * writes a template that is the demo's exact markup with editable values replaced
    by tokens ({{&text}}, {{@url}}, {{attr}}, {{%svg}}, {{#loop}}…{{/loop}});
  * extracts that instance's values as the block's attributes;
  * merges sections whose templates are identical into one block type.

Outputs, inside the theme:
  blocks/sections/<name>/block.json, template.html, render.php
  assets/css/demo/site.css          (shared demo CSS, verbatim)
  assets/css/demo/skins/<skin>.css  (each page's own inline CSS, verbatim)
  assets/js/demo-site.js            (demo behaviour and animations, verbatim)
  seed/pages/<slug>.html            (page content as section blocks)
  seed/sections.json                (report: page -> blocks)

Usage: python3 tools/build_sections.py
"""
import hashlib
import json
import os
import re
import shutil
import sys
from collections import OrderedDict

from bs4 import BeautifulSoup, NavigableString, Comment, Tag

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'wordpress', 'alpha-preclinical')
OUT_BLOCKS = os.path.join(THEME, 'blocks', 'sections')

# demo file -> (WP page slug, skin, page label)
PAGES = OrderedDict([
    ('index', ('home', 'home', 'Home')),
    ('expertise', ('expertise', 'expertise', 'Expertise')),
    ('services', ('services', 'services', 'Services')),
    ('tumor-models', ('tumor-models', 'tumor-models', 'Tumor models')),
    ('about', ('about', 'about', 'About')),
    ('publications', ('publications', 'publications', 'Publications')),
    ('team', ('team', 'team', 'Team')),
    ('careers', ('careers', 'careers', 'Careers')),
    ('contact', ('contact', 'contact', 'Contact')),
    ('blog', ('blog', 'blog', 'Blog')),
    ('privacy', ('privacy-policy', 'legal', 'Privacy policy')),
    ('terms', ('terms', 'legal', 'Terms of use')),
    ('accessibility', ('accessibility', 'legal', 'Accessibility')),
])
BLOG_POSTS = sorted(f[5:-5] for f in os.listdir(ROOT) if f.startswith('blog-') and f.endswith('.html'))

PAGE_URL = {'index': '/', 'privacy': '/privacy-policy/'}
for k, v in PAGES.items():
    PAGE_URL.setdefault(k, '/' + v[0] + '/')
# WordPress slugs where the content plan chose a different URL than the demo file
POST_SLUG = {'mrna-liver-depot-study-design': 'mrna-lnp-liver-depot-in-vivo-study-design'}
for b in BLOG_POSTS:
    PAGE_URL['blog-' + b] = '/blog/' + POST_SLUG.get(b, b) + '/'

INLINE = {'a', 'em', 'strong', 'b', 'i', 'br', 'sup', 'sub', 'span', 'abbr', 'code', 'small',
          'time', 'mark', 'u', 's', 'q', 'cite', 'bdi', 'wbr', 'kbd', 'dfn'}
VOID = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'}
URL_ATTRS = {'href', 'src', 'action', 'poster'}
SRCSET_ATTRS = {'srcset'}
REF_ATTRS = {'id', 'aria-controls', 'aria-labelledby', 'aria-describedby', 'for', 'data-apply-role', 'data-topic', 'data-filter', 'name'}
TEXT_ATTRS = {'width', 'height', 'sizes', 'alt', 'aria-label', 'title', 'datetime', 'data-count', 'placeholder', 'value', 'content',
              'data-label', 'data-text', 'data-subject', 'data-body'}


def map_link(href):
    """Demo link -> site-relative WordPress link."""
    if not href or href.startswith(('http:', 'https:', 'mailto:', 'tel:', '#', '/', 'data:', 'javascript:')):
        return href
    if href.startswith('assets/'):
        return href  # resolved to the theme folder at render time
    m = re.match(r'^([a-z0-9-]+)\.html(\?[^#]*)?(#.*)?$', href)
    if m:
        page, frag = m.group(1), (m.group(2) or '') + (m.group(3) or '')
        url = PAGE_URL.get(page)
        if url is None:
            print('  ! unmapped link', href, file=sys.stderr)
            url = '/' + page + '/'
        return url + frag
    return href


def map_html_links(html):
    return re.sub(r'(href|src)="([^"]*)"', lambda m: '%s="%s"' % (m.group(1), map_link(m.group(2)).replace('"', '&quot;')), html)


def esc_attr(v):
    return v.replace('&', '&amp;').replace('"', '&quot;').replace('<', '&lt;').replace('>', '&gt;')


def attr_str(tag, k, v):
    if isinstance(v, list):
        v = ' '.join(v)
    if v == '' and k not in ('alt', 'value', 'content'):
        return ' ' + k
    return ' %s="%s"' % (k, esc_attr(v))


def is_leaf(el):
    """A leaf holds inline text only: its inner HTML becomes one rich-text field."""
    if el.name in ('svg', 'img', 'input', 'select', 'textarea', 'button') and el.name != 'button':
        return False
    has_text = any(isinstance(c, NavigableString) and not isinstance(c, Comment) and c.strip() for c in el.descendants)
    if not has_text:
        return False
    for d in el.descendants:
        if isinstance(d, Tag) and (d.name not in INLINE or d.name == 'svg' or d.find('img') or d.find('svg')):
            return False
        if isinstance(d, Tag) and d.name == 'span' and d.get('class') and d.find(True):
            return False
        if isinstance(d, Tag) and d.attrs.get('data-count') is not None:
            return False
    return True


def inner_html(el):
    return ''.join(str(c) for c in el.contents)


def signature(el):
    """Structure of an element ignoring editable values: used to detect repeated items."""
    if isinstance(el, NavigableString):
        return 't' if el.strip() else ''
    keys = tuple(sorted(k for k in el.attrs if k not in URL_ATTRS and k not in TEXT_ATTRS and k not in REF_ATTRS and k not in SRCSET_ATTRS))
    static = tuple((k, ' '.join(v) if isinstance(v, list) else v) for k, v in sorted(el.attrs.items())
                   if k not in URL_ATTRS and k not in TEXT_ATTRS and k not in SRCSET_ATTRS and (k not in REF_ATTRS or k == 'name'))
    if el.name == 'svg':
        return ('svg', static)
    if is_leaf(el):
        return (el.name, static, keys, 'leaf')
    return (el.name, static, keys, tuple(s for s in (signature(c) for c in el.children) if s))


LABELS = {
    'h1': 'Heading', 'h2': 'Heading', 'h3': 'Title', 'h4': 'Subheading', 'p': 'Text', 'text': 'Text', 'lead': 'Intro',
    'lead_p': 'Intro', 'intro': 'Intro', 'li': 'List item', 'span': 'Label', 'div': 'Content', 'a': 'Link text',
    'b': 'Bold text', 'small': 'Small text', 'dt': 'Term', 'dd': 'Value', 'td': 'Cell', 'th': 'Column heading',
    'figcaption': 'Caption', 'tag': 'Topic tag', 'dek': 'Summary line', 'btn': 'Button text', 'btn_primary': 'Button text',
    'btn_light': 'Button text', 'btn_ghost_dark': 'Second button text', 'role': 'Job title', 'meta': 'Byline',
    'count': 'Count', 'jr': 'Journal and year', 'by': 'Authors', 'area': 'Research area', 'cite': 'Citation',
    'dates': 'Dates and reading time', 'none': 'Note when nothing matches', 'empty': 'Note when nothing matches',
    'button': 'Button text', 'label': 'Label', 'q': 'Question', 'ans': 'Answer',
    'hero_ctas': 'Buttons', 'motion_toggle': 'Animation button text', 'more_links': 'Links', 'nav_aria_label': 'Breadcrumb label',
    'filters_aria_label': 'Filter group label', 'sub': 'Intro', 'svc_intro': 'Intro', 'grp_h': 'Heading',
}


def humanize(name):
    """Editor label for a field name such as btn_primary_url, h2_2 or img_alt."""
    m = re.match(r'^(.*?)(?:_(\d+))?$', name)
    base, num = m.group(1), m.group(2)
    suffix = ' ' + num if num else ''
    for end, label in (('_url', 'Link'), ('_img', 'Image'), ('_alt', 'Image description (alt text)'),
                       ('_aria_label', 'Screen reader label'), ('_srcset', 'Image sizes (srcset)'),
                       ('_items', 'Items'), ('_icon', 'Icon (SVG)'), ('_width', 'Image width'), ('_height', 'Image height'),
                       ('_data_count', 'Number to count up to'), ('_data_filter', 'Filter value'), ('_data_topic', 'Filter topic'),
                       ('_title', 'Tooltip'), ('_placeholder', 'Placeholder')):
        if base.endswith(end):
            stem = base[:-len(end)]
            pre = re.sub(r' text$', '', LABELS.get(stem, re.sub(r'[_-]+', ' ', stem).strip().capitalize()))
            if not stem:
                return label + suffix
            if end == '_items':
                return pre + ' (list)' + suffix
            return pre + ' ' + label.lower() + suffix
    if base in LABELS:
        return LABELS[base] + suffix
    return re.sub(r'[_-]+', ' ', base).strip().capitalize() + suffix


class Tokenizer:
    """Builds the template string and the field schema/data for one section."""

    def __init__(self):
        self.loops = set()

    def name_for(self, el, used, suffix=''):
        cls = [c for c in el.get('class', []) if c not in ('wrap', 'sec', 'btn', 'is-open')]
        base = (cls[0] if cls else el.name) + suffix
        base = re.sub(r'[^a-z0-9_]', '_', base.lower()).strip('_') or 'field'
        n, i = base, 2
        while n in used:
            n = '%s_%d' % (base, i)
            i += 1
        used.add(n)
        return n

    def element(self, el, fields, data, used, in_loop, top=False):
        if isinstance(el, Comment):
            return ''
        if isinstance(el, NavigableString):
            txt = str(el)
            if txt.strip():
                n = self.name_for_text(used)
                fields[n] = {'type': 'html', 'label': 'Text'}
                data[n] = txt.strip()
                lead = txt[:len(txt) - len(txt.lstrip())]
                trail = txt[len(txt.rstrip()):]
                return esc_text(lead) + '{{&%s}}' % n + esc_text(trail)
            return txt
        # svg: static unless inside a repeated item
        if el.name == 'svg':
            if in_loop:
                n = self.name_for(el, used, '_icon')
                fields[n] = {'type': 'svg', 'label': 'Icon (SVG)'}
                data[n] = str(el)
                return '{{%%%s}}' % n
            return str(el)
        out = '<' + el.name
        for k, v in el.attrs.items():
            if k in SRCSET_ATTRS:
                n = self.name_for(el, used, '_srcset')
                fields[n] = {'type': 'srcset', 'label': humanize(n)}
                data[n] = ', '.join(map_link(x.strip().split(' ')[0]) + (' ' + x.strip().split(' ', 1)[1] if ' ' in x.strip() else '') for x in v.split(','))
                out += ' %s="{{$%s}}"' % (k, n)
            elif k in URL_ATTRS:
                n = self.name_for(el, used, '_' + ('url' if k != 'src' else 'img'))
                fields[n] = {'type': 'url' if k != 'src' else 'image', 'label': humanize(n)}
                data[n] = map_link(v)
                out += ' %s="{{@%s}}"' % (k, n)
            elif (k in TEXT_ATTRS and v != '') or (in_loop and k in REF_ATTRS and k != 'name'):
                n = self.name_for(el, used, '_' + k.replace('-', '_'))
                fields[n] = {'type': 'text', 'label': humanize(n)}
                data[n] = v if not isinstance(v, list) else ' '.join(v)
                out += ' %s="{{%s}}"' % (k, n)
            else:
                out += attr_str(el, k, v)
        out += '>'
        if el.name in VOID:
            return out
        if el.name in ('textarea', 'select', 'option', 'style', 'script', 'template'):
            out += inner_html(el)
        elif is_leaf(el):
            n = self.name_for(el, used)
            fields[n] = {'type': 'html', 'label': humanize(n)}
            data[n] = map_html_links(inner_html(el).strip())
            raw = inner_html(el)
            lead = raw[:len(raw) - len(raw.lstrip())]
            trail = raw[len(raw.rstrip()):]
            out += lead + '{{&%s}}' % n + trail
        else:
            out += self.children(el, fields, data, used, in_loop)
        return out + '</%s>' % el.name

    def name_for_text(self, used):
        n, i = 'text', 2
        while n in used:
            n = 'text_%d' % i
            i += 1
        used.add(n)
        return n

    def children(self, el, fields, data, used, in_loop):
        kids = list(el.children)
        tags = [c for c in kids if isinstance(c, Tag)]
        sigs = {id(c): signature(c) for c in tags}
        out = ''
        i = 0
        while i < len(kids):
            c = kids[i]
            if isinstance(c, Tag):
                # collect a run of same-signature siblings (whitespace between allowed)
                run = [c]
                j = i + 1
                seps = []
                while j < len(kids):
                    d = kids[j]
                    if isinstance(d, NavigableString) and not d.strip():
                        seps.append(str(d))
                        j += 1
                        continue
                    if isinstance(d, Tag) and sigs[id(d)] == sigs[id(c)]:
                        run.append(d)
                        j += 1
                        continue
                    break
                # trim trailing whitespace from the run end
                k = i
                count = 0
                last = i
                while k < j:
                    if isinstance(kids[k], Tag):
                        count += 1
                        last = k
                    k += 1
                if len(run) >= 2:
                    ln = self.name_for(c, self.loops, '_items')
                    used.add(ln)
                    item_fields = OrderedDict()
                    items = []
                    item_tpl = None
                    sep = ''
                    snapshot = set(self.loops)
                    grown = set(self.loops)
                    for idx, item in enumerate(run):
                        f, d = OrderedDict(), OrderedDict()
                        self.loops = set(snapshot)
                        tpl = self.element(item, f, d, set(self.loops), True)
                        grown |= self.loops
                        if item_tpl is None:
                            item_tpl, item_fields = tpl, f
                        items.append(d)
                    self.loops = grown
                    # separator: whitespace between first two items
                    between = []
                    p = kids.index(run[0]) + 1
                    while p < len(kids) and kids[p] is not run[1]:
                        between.append(str(kids[p]))
                        p += 1
                    sep = ''.join(between)
                    fields[ln] = {'type': 'items', 'label': humanize(ln), 'fields': item_fields}
                    data[ln] = items
                    out += '{{#%s}}%s{{/%s}}' % (ln, item_tpl + sep if sep else item_tpl, ln)
                    i = last + 1
                    continue
                out += self.element(c, fields, data, used, in_loop)
                i += 1
            else:
                out += self.element(c, fields, data, used, in_loop)
                i += 1
        return out


def register(types, soup, el, region, demo, key, want=None, inner=False):
    """Tokenize one section and register (or reuse) its block type."""
    tk = Tokenizer()
    fields, data = OrderedDict(), OrderedDict()
    if region == 'header':
        hdr = el.select_one('.hdr')
        if hdr:
            hdr.replace_with(soup.new_tag('alpha-nav'))
    tpl = tk.element(el, fields, data, set(), False, top=True)
    tpl = tpl.replace('<alpha-nav></alpha-nav>', '{{>nav}}').replace('<alpha-inner></alpha-inner>', '{{>inner}}')
    h = hashlib.md5(tpl.encode()).hexdigest()
    if h not in types:
        types[h] = {'key': key, 'template': tpl, 'fields': fields, 'pages': [], 'region': region, 'el': el,
                    'default': data, 'demo': demo, 'want': want, 'inner': inner}
    types[h]['pages'].append(demo)
    return h, data


CORE_TEXT = ('p', 'h2', 'h3', 'ul', 'ol')


def build_posts(types, skins):
    """Blog articles: header bar (template), then in the post content the head, the
    body (article text as core blocks plus article-part blocks), research, related, CTA."""
    seed = OrderedDict()
    for b in BLOG_POSTS:
        demo = 'blog-' + b
        soup = BeautifulSoup(open(os.path.join(ROOT, demo + '.html'), encoding='utf-8'), 'html5lib')
        css = css_of(soup)
        if 'blog-article' in skins and skins['blog-article'] != css:
            print('  ! blog article CSS differs', demo, file=sys.stderr)
        skins['blog-article'] = css
        ap = soup.select_one('.ap')
        bar_h, _ = register(types, soup, ap.find('header'), 'header', demo, demo + ':bar', 'post-bar')
        items = []
        for el in ap.find('main').find_all(recursive=False):
            cls = el.get('class', [])
            if 'head' in cls:
                want = 'post-head-infographic' if el.select_one('.hero-img.infographic') else 'post-head'
                items.append(('block',) + register(types, soup, el, 'main', demo, demo + ':head', want))
            elif 'body' in cls:
                art = el.find('article')
                parts = []
                for c in art.find_all(recursive=False):
                    parts.append(article_part(types, soup, c, demo))
                art.clear()
                art.append(soup.new_tag('alpha-inner'))
                h, data = register(types, soup, el, 'main', demo, demo + ':body', 'post-body', inner=True)
                items.append(('body', h, data, parts))
            elif 'research' in cls:
                items.append(('block',) + register(types, soup, el, 'main', demo, demo + ':research', 'post-research'))
            elif 'related' in cls:
                items.append(('block',) + register(types, soup, el, 'main', demo, demo + ':related', 'post-related'))
            else:
                items.append(('block',) + register(types, soup, el, 'main', demo, demo + ':cta', 'post-cta'))
        seed[POST_SLUG.get(b, b)] = items
    return seed


def article_part(types, soup, el, demo):
    """Article text becomes core blocks; designed parts (summary, callout, review,
    author, references, inline CTA, tables) become article-* blocks."""
    cls = el.get('class', [])
    inner = ''.join(str(c) for c in el.contents)
    if el.name == 'p' and not el.find(['img', 'svg']):
        return ('core', {'type': 'p', 'className': ' '.join(cls), 'html': map_html_links(inner.strip())})
    if el.name in ('h2', 'h3') and not cls:
        return ('core', {'type': 'h', 'level': int(el.name[1]), 'id': el.get('id', ''), 'html': map_html_links(inner.strip())})
    if el.name in ('ul', 'ol') and all(c.name == 'li' for c in el.find_all(recursive=False)):
        return ('core', {'type': 'list', 'ordered': el.name == 'ol', 'className': ' '.join(cls),
                         'items': [map_html_links(''.join(str(x) for x in li.contents).strip()) for li in el.find_all('li', recursive=False)]})
    want = 'article-' + (cls[0] if cls else el.name)
    h, data = register(types, soup, el, 'main', demo, demo + ':' + want, want)
    return ('block', h, data)


def write_posts(types, post_seed):
    """Post content for seed/posts.json and the single post template."""
    import subprocess
    path = os.path.join(THEME, 'seed', 'posts.json')
    posts = json.load(open(path, encoding='utf-8'))
    by_slug = {p['slug']: p for p in posts}
    helper = os.path.join(ROOT, 'tools', 'core_blocks.cjs')
    node_path = os.environ.get('ALPHA_NODE_MODULES', '/home/claude/blockval/node_modules')
    for slug, items in post_seed.items():
        out = []
        for it in items:
            if it[0] == 'block':
                out.append(block_comment('alpha/' + types[it[1]]['name'], it[2]))
            else:
                _, h, data, parts = it
                req = []
                for p in parts:
                    if p[0] == 'core':
                        req.append(p[1])
                    else:
                        req.append({'type': 'raw', 'markup': block_comment('alpha/' + types[p[1]]['name'], p[2])})
                res = subprocess.run(['node', helper], input=json.dumps(req), capture_output=True, text=True,
                                     env=dict(os.environ, NODE_PATH=node_path))
                if res.returncode:
                    raise SystemExit(res.stderr)
                inner = '\n\n'.join(json.loads(res.stdout))
                name = 'alpha/' + types[h]['name']
                js = block_comment(name, data)[len('<!-- wp:' + name):-len(' /-->')]
                out.append('<!-- wp:%s%s -->\n%s\n<!-- /wp:%s -->' % (name, js, inner, name))
        if slug in by_slug:
            by_slug[slug]['content'] = '\n\n'.join(out) + '\n'
        else:
            print('  ! post not in posts.json', slug, file=sys.stderr)
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(posts, f, indent=1, ensure_ascii=False)
    bar = [t['name'] for t in types.values() if t.get('want') == 'post-bar'][0]
    with open(os.path.join(THEME, 'templates', 'single.html'), 'w') as f:
        f.write('<!-- wp:alpha/shell -->\n<!-- wp:alpha/%s /-->\n<!-- wp:post-content /-->\n<!-- wp:alpha/site-footer /-->\n<!-- /wp:alpha/shell -->\n' % bar)


def esc_text(t):
    return t


def section_name(el, page, nav_slot=False):
    cls = [c for c in el.get('class', []) if c not in ('wrap',)]
    inner = None
    wraps = [c for c in el.find_all(recursive=False) if c.name == 'div' and 'wrap' in c.get('class', []) and len(c.get('class', [])) > 1]
    if wraps:
        inner = [c for c in wraps[0]['class'] if c != 'wrap'][0]
    parts = [c for c in cls if c not in ('sec',)] or [el.name]
    if inner and inner not in parts:
        parts.append(inner)
    return '-'.join(parts)


def css_of(soup):
    return '\n'.join(''.join(str(c) for c in st.contents) for st in soup.find_all('style'))


def main():
    if os.path.isdir(OUT_BLOCKS):
        shutil.rmtree(OUT_BLOCKS)
    os.makedirs(OUT_BLOCKS)
    skins_dir = os.path.join(THEME, 'assets', 'css', 'demo', 'skins')
    os.makedirs(skins_dir, exist_ok=True)
    shutil.copy(os.path.join(ROOT, 'assets', 'css', 'site.css'), os.path.join(THEME, 'assets', 'css', 'demo', 'site.css'))
    shutil.copy(os.path.join(ROOT, 'assets', 'js', 'site.js'), os.path.join(THEME, 'assets', 'js', 'demo-site.js'))

    types = OrderedDict()  # tpl hash -> {name, template, fields, pages, data(first)}
    page_blocks = OrderedDict()
    sources = [(k,) + v for k, v in PAGES.items()]
    skins = {}
    for demo, slug, skin, label in sources:
        soup = BeautifulSoup(open(os.path.join(ROOT, demo + '.html'), encoding='utf-8'), 'html5lib')
        css = css_of(soup)
        if skin in skins and skins[skin] != css:
            print('  ! skin differs within group', skin, demo, file=sys.stderr)
        skins[skin] = css
        ap = soup.select_one('.ap')
        parts = []
        for el in ap.find_all(recursive=False):
            if el.name == 'main':
                parts += [('main', c) for c in el.find_all(recursive=False)]
            elif el.name == 'header':
                parts.append(('header', el))
            elif el.name == 'footer':
                parts.append(('footer', el))
        blocks = []
        for region, el in parts:
            h, data = register(types, soup, el, region, demo, '%s:%d' % (demo, len(blocks)))
            blocks.append((h, data, region))
        page_blocks[demo] = {'slug': slug, 'skin': skin, 'label': label, 'blocks': blocks}

    post_seed = build_posts(types, skins)

    # name block types
    used_names = set()
    for h, t in types.items():
        el = t['el']
        if t['region'] == 'footer':
            base = 'site-footer'
        elif t.get('want'):
            base = t['want']
        elif NAMES.get(t['key']):
            base = NAMES[t['key']]
        elif t['region'] == 'header':
            base = {'hero': 'home-hero', 'top': 'page-top', 'bar': 'post-bar'}.get(el.get('class', [''])[0], 'page-top')
            if len(t['pages']) == 1 and base == 'page-top':
                base = short(t['pages'][0]) + '-top'
        elif NAMES.get(t['key']):
            base = NAMES[t['key']]
        else:
            sn = el.get('id') or section_name(el, t['demo'])
            if len(t['pages']) == 1 and not t['pages'][0].startswith('blog-'):
                base = short(t['pages'][0]) + '-' + sn
            elif all(p.startswith('blog-') and p != 'blog' for p in t['pages']):
                base = 'post-' + sn if len(t['pages']) > 1 else short(t['pages'][0]) + '-' + sn
            else:
                base = sn
        base = re.sub(r'[^a-z0-9-]', '-', base.lower()).strip('-')
        n, i = base, 2
        while n in used_names:
            n = '%s-%d' % (base, i)
            i += 1
        used_names.add(n)
        t['name'] = n

    # write blocks
    for h, t in types.items():
        d = os.path.join(OUT_BLOCKS, t['name'])
        os.makedirs(d)
        tpl = t['template']
        with open(os.path.join(d, 'template.html'), 'w', encoding='utf-8') as f:
            f.write(tpl)
        attrs = OrderedDict()
        for fname, spec in t['fields'].items():
            if spec['type'] == 'items':
                attrs[fname] = {'type': 'array', 'default': t['default'][fname]}
            else:
                attrs[fname] = {'type': 'string', 'default': t['default'][fname]}
        title = title_for(t)
        meta = OrderedDict([
            ('$schema', 'https://schemas.wp.org/trunk/block.json'),
            ('apiVersion', 3),
            ('name', 'alpha/' + t['name']),
            ('title', title),
            ('category', 'alpha-sections'),
            ('description', 'Section from the approved design, used on: ' + ', '.join(page_label(p) for p in t['pages'][:6]) + ('…' if len(t['pages']) > 6 else '') + '.'),
            ('icon', 'layout'),
            ('textdomain', 'alpha-preclinical'),
            ('attributes', attrs),
            ('supports', {'html': False, 'customClassName': False, 'className': False, 'reusable': False}),
            ('render', 'file:./render.php'),
            ('alphaRegion', t['region']),
            ('alphaFields', t['fields']),
        ])
        with open(os.path.join(d, 'block.json'), 'w', encoding='utf-8') as f:
            json.dump(meta, f, indent=1, ensure_ascii=False)
        with open(os.path.join(d, 'render.php'), 'w') as f:
            f.write("<?php\n/**\n * Renders the \"%s\" section exactly as in the approved design.\n *\n * @package Alpha_Preclinical\n */\n\necho alpha_section_render( __DIR__, $attributes, $block, $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per field in alpha_section_render().\n" % title)

    # shared header row (logo, menu, quote button) from a non-home page, links mapped
    soup = BeautifulSoup(open(os.path.join(ROOT, 'about.html'), encoding='utf-8'), 'html5lib')
    hdr = str(soup.select_one('.hdr'))
    hdr = hdr.replace(' aria-current="page"', '')
    hdr = map_html_links(hdr)
    hdr = hdr.replace('href="/" aria-label', 'href="{{logo_href}}" aria-label')
    hdr = re.sub(r'(<a aria-label="Alpha Preclinical home" href=")/(")', r'\1{{logo_href}}\2', hdr)
    hdr = hdr.replace('style="width:72px;', 'style="width:{{logo_width}};')
    hdr = hdr.replace('src="assets/', 'src="{{theme}}/assets/')
    os.makedirs(os.path.join(THEME, 'inc', 'templates'), exist_ok=True)
    with open(os.path.join(THEME, 'inc', 'templates', 'nav.html'), 'w', encoding='utf-8') as f:
        f.write(hdr)

    # demo images used by the sections
    src_img = os.path.join(ROOT, 'assets', 'img')
    for dp, dn, fn in os.walk(src_img):
        for n in fn:
            rel = os.path.relpath(os.path.join(dp, n), src_img)
            dst = os.path.join(THEME, 'assets', 'img', rel)
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            if not os.path.exists(dst) or os.path.getsize(dst) != os.path.getsize(os.path.join(dp, n)):
                shutil.copy(os.path.join(dp, n), dst)

    # skins
    for skin, css in skins.items():
        with open(os.path.join(skins_dir, skin + '.css'), 'w', encoding='utf-8') as f:
            f.write('/* Styles for the "%s" pages, copied verbatim from the approved design. */\n' % skin + css)

    # seed pages
    report = OrderedDict()
    seed_dir = os.path.join(THEME, 'seed', 'sections')
    if os.path.isdir(seed_dir):
        shutil.rmtree(seed_dir)
    os.makedirs(seed_dir)
    for demo, pb in page_blocks.items():
        out = []
        for h, data, region in pb['blocks']:
            t = types[h]
            if region == 'footer':
                continue  # the footer lives in the page template
            out.append(block_comment('alpha/' + t['name'], with_bindings(t, data)))
        fname = demo.replace('blog-', 'post-') if demo.startswith('blog-') else pb['slug']
        with open(os.path.join(seed_dir, fname + '.html'), 'w', encoding='utf-8') as f:
            f.write('\n\n'.join(out) + '\n')
        report[demo] = {'skin': pb['skin'], 'blocks': [types[h]['name'] for h, _, _ in pb['blocks']]}
    with open(os.path.join(THEME, 'seed', 'sections.json'), 'w') as f:
        json.dump(report, f, indent=1)
    with open(os.path.join(THEME, 'seed', 'skins.json'), 'w') as f:
        json.dump({pb['slug']: pb['skin'] for pb in page_blocks.values()}, f, indent=1)

    write_posts(types, post_seed)

    # page templates: the design's frame around the page content
    frame = '<!-- wp:alpha/shell -->\n%s\n<!-- wp:alpha/site-footer /-->\n<!-- /wp:alpha/shell -->\n'
    for tname in ('page', 'page-wide', 'page-legal', 'front-page'):
        with open(os.path.join(THEME, 'templates', tname + '.html'), 'w') as f:
            f.write(frame % '<!-- wp:post-content /-->')
    # posts index (the Blog page): its sections live in the template
    blog = page_blocks['blog']
    with open(os.path.join(THEME, 'templates', 'home.html'), 'w') as f:
        f.write(frame % '\n'.join(block_comment('alpha/' + types[h]['name'], d) for h, d, r in blog['blocks'] if r != 'footer'))
    print('%d block types from %d pages' % (len(types), len(page_blocks)))
    for h, t in types.items():
        nf = len(t['fields'])
        print('  %-38s %-7s fields=%-3d pages=%s' % (t['name'], t['region'], nf, ','.join(short(p) for p in t['pages'])[:70]))


NAMES = {
    'index:0': 'home-hero', 'index:1': 'home-expertise', 'index:2': 'home-services', 'index:3': 'home-steps',
    'index:4': 'home-publications', 'index:5': 'home-team', 'index:6': 'home-faq', 'index:7': 'home-cta',
    'expertise:0': 'expertise-top', 'expertise:1': 'expertise-pk-pd', 'expertise:2': 'expertise-gene-therapy',
    'expertise:3': 'expertise-autoimmune', 'expertise:4': 'expertise-metabolic', 'expertise:5': 'expertise-fibrosis',
    'expertise:6': 'expertise-tumor', 'expertise:7': 'scientist-cta',
    'services:0': 'services-top', 'services:1': 'services-intro', 'services:2': 'services-ivis',
    'services:3': 'services-surgery', 'services:4': 'services-colony', 'services:5': 'services-in-vitro',
    'services:6': 'services-support',
    'tumor-models:0': 'page-top-sub', 'tumor-models:1': 'tumor-models-models', 'tumor-models:2': 'tumor-models-endpoints',
    'tumor-models:3': 'tumor-models-team', 'tumor-models:4': 'tumor-models-related',
    'about:0': 'page-top', 'about:1': 'about-story', 'about:2': 'about-values', 'about:3': 'about-facility',
    'about:4': 'about-leadership',
    'publications:1': 'publications-list',
    'team:1': 'team-photo', 'team:2': 'team-leadership', 'team:3': 'team-research', 'team:4': 'team-cta',
    'careers:0': 'careers-top', 'careers:1': 'careers-why', 'careers:2': 'careers-roles', 'careers:3': 'careers-apply',
    'contact:1': 'contact-form', 'contact:2': 'contact-map', 'blog:1': 'blog-list',
    'privacy:0': 'page-top-legal', 'privacy:1': 'privacy-content', 'terms:1': 'terms-content',
    'accessibility:1': 'accessibility-content',
}
TITLES = {
    'site-footer': 'Site footer', 'scientist-cta': 'Talk to a scientist band', 'page-top': 'Page header',
    'page-top-sub': 'Page header (three-level breadcrumb)', 'page-top-legal': 'Page header (legal page)',
}


def short(p):
    return {'index': 'home'}.get(p, p.replace('blog-', '')[:24].rstrip('-'))


def page_label(p):
    if p in PAGES:
        return PAGES[p][2]
    return 'blog post'


def title_for(t):
    n = t['name']
    if n in TITLES:
        return TITLES[n]
    for k, v in PAGES.items():
        pre = {'index': 'home'}.get(k, k)
        if n.startswith(pre + '-'):
            rest = n[len(pre) + 1:].replace('-', ' ')
            return v[2] + ': ' + rest
    pretty = n.replace('-', ' ')
    return pretty[:1].upper() + pretty[1:]


def with_bindings(t, data):
    """Team cards on the Team page: name and job title come from the team member's
    record through the Block Bindings API (alpha/person source)."""
    tpl = t['template']
    bindings = OrderedDict()
    for m in re.finditer(r'<article class="person" id="([a-z0-9-]+)">(.*?)</article>', tpl, re.S):
        slug, body = m.group(1), m.group(2)
        h3 = re.search(r'<h3>\{\{&([a-z0-9_]+)\}\}</h3>', body)
        role = re.search(r'<p class="role">\{\{&([a-z0-9_]+)\}\}</p>', body)
        if h3:
            bindings[h3.group(1)] = {'source': 'alpha/person', 'args': {'slug': slug, 'key': 'name'}}
        if role:
            bindings[role.group(1)] = {'source': 'alpha/person', 'args': {'slug': slug, 'key': 'job_title'}}
    if not bindings:
        return data
    out = OrderedDict(data)
    out['metadata'] = {'bindings': bindings}
    return out


def block_comment(name, data):
    js = json.dumps(data, ensure_ascii=False, separators=(',', ':'))
    # same escaping WordPress uses in serialize_block_attributes()
    js = js.replace('--', '\\u002d\\u002d').replace('<', '\\u003c').replace('>', '\\u003e').replace('&', '\\u0026').replace('\\"', '\\u0022')
    if js == '{}':
        return '<!-- wp:%s /-->' % name
    return '<!-- wp:%s %s /-->' % (name, js)


CITE = re.compile(r'^<i>(?P<journal>.+?)</i>\. (?P<date>[^;]+);(?P<volume>[^(:]*)(?:\((?P<issue>[^)]*)\))?:(?P<pages>.+?)\.(?: Epub (?P<epub>.+?)\.)?$')


def export_publications():
    """seed/publications.json from the approved Publications page (order, areas,
    citations, links) with the homepage's three papers marked featured."""
    soup = BeautifulSoup(open(os.path.join(ROOT, 'publications.html'), encoding='utf-8'), 'html5lib')
    home = BeautifulSoup(open(os.path.join(ROOT, 'index.html'), encoding='utf-8'), 'html5lib')
    featured = [c.select_one('h3').get_text(strip=True) for c in home.select('.paper-col')]
    short_by = {c.select_one('h3').get_text(strip=True): c.select_one('.by').get_text(strip=True) for c in home.select('.paper-col')}
    out = []
    for g in soup.select('section.group'):
        for paper in g.select('.paper'):
            title = paper.select_one('h3').get_text(strip=True)
            cite = ''.join(str(c) for c in paper.select_one('.cite').contents).strip()
            m = CITE.match(cite)
            if not m:
                raise SystemExit('cannot parse citation: ' + cite)
            ids = {dt.get_text(strip=True): dd for dt, dd in zip(paper.select('dt'), paper.select('dd'))}
            links = {a.get_text(strip=True): a['href'] for a in paper.select('.links a')}
            alpha = paper.select_one('.alpha').get_text(strip=True).replace('Alpha authors:', '').strip()
            if alpha.lower().startswith('none listed'):
                alpha = ''
            pdf = links.get('Read PDF', '')
            out.append(OrderedDict([
                ('area', g['data-topic']), ('title', title),
                ('authors', paper.select_one('.by').get_text(strip=True)),
                ('alpha_authors', alpha), ('authors_short', short_by.get(title, '')),
                ('journal', m.group('journal')),
                ('pdf_url', pdf), ('legacy_pdf', pdf),
                ('pmid', ids['PMID'].get_text(strip=True) if 'PMID' in ids else ''),
                ('pmcid', ids['PMCID'].get_text(strip=True) if 'PMCID' in ids else ''),
                ('doi', ids['DOI'].get_text(strip=True) if 'DOI' in ids else ''),
                ('volume', m.group('volume') or ''), ('issue', m.group('issue') or ''),
                ('pages', m.group('pages')), ('issue_date', m.group('date')),
                ('epub_date', m.group('epub') or ''),
                ('featured', title in featured), ('menu_order', len(out)),
            ]))
    with open(os.path.join(THEME, 'seed', 'publications.json'), 'w', encoding='utf-8') as f:
        json.dump(out, f, indent=1, ensure_ascii=False)
    print('%d publications, %d featured' % (len(out), sum(p['featured'] for p in out)))


if __name__ == '__main__':
    main()
    export_publications()
