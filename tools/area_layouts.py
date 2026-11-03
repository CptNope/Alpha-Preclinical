#!/usr/bin/env python3
"""One-off: split the Expertise and Services artboards' single list of <article class="svc">
into one section per area, each with its own background and composition, so the pages
stop repeating the same block six times (client review, Nov 2026). Idempotent: an artboard
that already has <section class="area ..."> is left alone. Styles live in assets/css/site.css."""
import re
from pathlib import Path

CANVAS = Path(__file__).resolve().parent.parent / "design" / "canvas"

# id -> (background, layout, media) ; background: mist | paper | deep ; layout: std | flip | wide | wide-right | full
PLANS = {
    "Expertise.dc.html": {
        "pk-pd": ("mist", "std", None),
        "gene-therapy": ("paper", "flip", None),
        "autoimmune": ("deep", "std", None),
        "metabolic": ("mist", "wide", "img/blog/diet-vs-genetic-t2d-hero-1200.jpg"),
        "fibrosis": ("paper", "flip", None),
        "tumor": ("deep", "wide-right", "img/blog/caliper-vs-ivis-hero-1200.jpg"),
    },
    "Services.dc.html": {
        "ivis": ("mist", "std", None),
        "surgery": ("paper", "flip", None),
        "colony": ("deep", "std", None),
        "in-vitro": ("mist", "full", None),
        "support": ("paper", "flip", None),
    },
}
ALT = {
    "/_blob/4b5391a4eda08ec8242ae09059e6a7e3": "An Alpha Preclinical scientist working in a biosafety cabinet",
}


def run():
    for name, plan in PLANS.items():
        p = CANVAS / name
        s = p.read_text()
        if '<section class="area ' in s:
            print(f"{name}: already split")
            continue
        m = re.search(r'<section class="sec sec-mist" aria-label="[^"]+">\s*<div class="wrap">(.*?)</div>\s*</section>', s, re.S)
        arts = re.findall(r'<article class="svc" id="([^"]+)">(.*?)</article>', m.group(1), re.S)
        out = []
        for aid, body in arts:
            bg, layout, media = plan[aid]
            cls = "svc" + {"std": "", "flip": " flip", "wide": " wide", "wide-right": " wide media-right", "full": " full"}[layout]
            if media:
                w, h = (1200, 675) if "blog" in media else (1000, 666)
                fig = (f'\n      <figure class="area-media"><img src="{media}" alt="{ALT.get(media, "")}" '
                       f'width="{w}" height="{h}" loading="lazy"></figure>')
                body = body.replace("\n      <div class=\"detail\">", fig + "\n      <div class=\"detail\">", 1)
            out.append(f'<section class="area area-{bg}" aria-labelledby="{aid}-h">\n  <div class="wrap">\n'
                       f'    <article class="{cls}" id="{aid}">{body}</article>\n  </div>\n</section>')
            # give each area heading an id for aria-labelledby
        html = "\n".join(out)
        html = re.sub(r'(<article class="[^"]*" id="([^"]+)">\s*<div>\s*)<h2>', lambda mm: mm.group(1) + f'<h2 id="{mm.group(2)}-h">', html)
        s = s[:m.start()] + html + s[m.end():]
        p.write_text(s)
        print(f"{name}: {len(arts)} areas")


if __name__ == "__main__":
    run()
