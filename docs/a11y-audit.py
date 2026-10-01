"""WCAG 2.1 AA audit for the Alpha Preclinical static site.
axe-core (wcag2a, wcag2aa, wcag21a, wcag21aa) + custom checks:
touch targets (2.5.5 / 2.5.8), focus visibility (2.4.7), keyboard reach (2.1.1),
reflow at 320px (1.4.10), text spacing (1.4.12), heading order (1.3.1).
Usage: python3 audit.py <base-url> <out.json>"""
import json, sys
from playwright.sync_api import sync_playwright

BASE, OUT = sys.argv[1], sys.argv[2]
import os
AXE = open(os.environ.get('AXE_PATH', 'node_modules/axe-core/axe.min.js')).read()
PAGES = ['index', 'services', 'tumor-models', 'publications', 'about', 'team', 'blog',
         'blog-mrna-liver-depot-study-design', 'contact', 'careers']
import os
for extra in ['terms', 'privacy', 'accessibility']:
    if os.path.exists(f'{extra}.html'):
        PAGES.append(extra)

CUSTOM = r"""
() => {
  const vis = el => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && !el.closest('[hidden]'); };
  const out = {targets: [], headings: [], images: [], landmarks: {}};
  // touch targets: interactive elements smaller than 24px (2.5.8 AA in 2.2) and 44px (2.5.5 AAA guidance)
  document.querySelectorAll('a[href], button, input, select, textarea, summary').forEach(el => {
    if (!vis(el)) return;
    const r = el.getBoundingClientRect();
    const inline = getComputedStyle(el).display === 'inline' && el.closest('p, li, address, small, figcaption, td, dd');
    if (r.height < 24 || r.width < 24) out.targets.push({tag: el.tagName, text: (el.textContent || el.getAttribute('aria-label') || '').trim().slice(0, 40), w: Math.round(r.width), h: Math.round(r.height), inline: !!inline});
  });
  document.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(h => { if (vis(h)) out.headings.push(h.tagName + ': ' + h.textContent.trim().slice(0, 50)); });
  document.querySelectorAll('img').forEach(i => out.images.push({src: i.getAttribute('src'), alt: i.getAttribute('alt')}));
  ['header','nav','main','footer'].forEach(t => out.landmarks[t] = document.querySelectorAll(t).length);
  out.h1 = document.querySelectorAll('h1').length;
  out.lang = document.documentElement.lang;
  out.title = document.title;
  return out;
}
"""

FOCUS = r"""
() => {
  const el = document.activeElement; if (!el || el === document.body) return null;
  const s = getComputedStyle(el);
  const hasRing = (s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0) || (s.boxShadow && s.boxShadow !== 'none');
  const r = el.getBoundingClientRect();
  return {tag: el.tagName, text: (el.textContent || el.getAttribute('aria-label') || '').trim().slice(0, 40), ring: hasRing, visible: r.width > 0 && r.height > 0};
}
"""

report = {}
with sync_playwright() as p:
    b = p.chromium.launch()
    for name in PAGES:
        url = f'{BASE}/{name}.html'
        pg = b.new_page(viewport={'width': 1440, 'height': 900})
        pg.goto(url); pg.wait_for_timeout(300)
        pg.add_script_tag(content=AXE)
        axe = pg.evaluate("""async () => { const r = await axe.run(document, {runOnly: {type: 'tag', values: ['wcag2a','wcag2aa','wcag21a','wcag21aa']}, resultTypes: ['violations','incomplete']});
            const pick = v => ({id: v.id, impact: v.impact, help: v.help, tags: v.tags.filter(t => /^wcag\\d/.test(t)), nodes: v.nodes.slice(0, 6).map(n => ({target: n.target.join(' '), summary: (n.failureSummary || '').slice(0, 200), html: n.html.slice(0, 140)})), count: v.nodes.length});
            return {violations: r.violations.map(pick), incomplete: r.incomplete.map(pick)}; }""")
        custom = pg.evaluate(CUSTOM)
        # keyboard: tab through, record focus ring presence
        focus = []
        pg.keyboard.press('Tab')
        for _ in range(80):
            f = pg.evaluate(FOCUS)
            if f: focus.append(f)
            pg.keyboard.press('Tab')
        no_ring = [f for f in focus if not f['ring']]
        first = focus[0]['text'] if focus else None
        # reflow at 320px
        m = b.new_page(viewport={'width': 320, 'height': 700})
        m.goto(url); m.wait_for_timeout(300)
        sw = m.evaluate('document.documentElement.scrollWidth')
        wide = m.evaluate("""() => [...document.querySelectorAll('body *')].filter(e => e.getBoundingClientRect().right > 322 && getComputedStyle(e).position !== 'fixed' && !e.closest('svg') && e.getBoundingClientRect().width>0).slice(0,5).map(e => e.tagName + '.' + (e.className && e.className.baseVal === undefined ? e.className : '') )""")
        # text spacing 1.4.12
        m.add_style_tag(content='* { line-height: 1.5 !important; letter-spacing: .12em !important; word-spacing: .16em !important; } p { margin-bottom: 2em !important; }')
        sw2 = m.evaluate('document.documentElement.scrollWidth')
        m.close(); pg.close()
        report[name] = {'axe': axe, 'custom': custom, 'focus_count': len(focus), 'first_focus': first,
                        'no_focus_ring': no_ring[:10], 'reflow_320_scrollWidth': sw, 'reflow_overflowing': wide,
                        'text_spacing_scrollWidth': sw2}
        v = axe['violations']
        print(f"{name:40s} axe violations: {sum(x['count'] for x in v):3d} ({', '.join(x['id'] for x in v)}) | small targets: {len(custom['targets'])} | no-ring: {len(no_ring)} | 320px width: {sw} / spacing: {sw2} | h1: {custom['h1']}")
    b.close()
json.dump(report, open(OUT, 'w'), indent=1)
