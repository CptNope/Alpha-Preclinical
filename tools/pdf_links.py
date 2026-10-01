"""Point paper-title links at each paper's PDF.

Reads the paper list (title -> PDF URL) from design/canvas/Publications.dc.html and rewrites
any <a href="publications.html|Publications.dc.html">Paper title</a> in the given files.
Usage: python3 tools/pdf_links.py FILE [FILE ...]
"""
import html, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = (ROOT / "design/canvas/Publications.dc.html").read_text(encoding="utf-8")


def norm(t):
    return re.sub(r"[^a-z0-9]+", " ", html.unescape(t).lower().replace("–", "-")).strip()


PAPERS = {}
for m in re.finditer(r"^\s*\['[^']*', '((?:[^'\\]|\\.)*)'.*?'(https://[^']+\.pdf)'\]", SRC, re.M):
    PAPERS[norm(m.group(1))] = m.group(2)


def pdf_for(text):
    n = norm(text)
    if len(n) < 20:
        return None
    for t, url in PAPERS.items():
        if t == n or t.startswith(n) or n.startswith(t):
            return url
    return None


LINK = re.compile(r'<a href="(?:publications\.html|Publications\.dc\.html)">([^<]{20,})</a>')


def rewrite(s):
    def sub(m):
        url = pdf_for(m.group(1))
        if not url:
            return m.group(0)
        return (f'<a href="{url}" target="_blank" rel="noopener" '
                f'aria-label="{m.group(1)} (PDF, opens in new tab)">{m.group(1)}</a>')
    return LINK.sub(sub, s)


if __name__ == "__main__":
    assert len(PAPERS) == 16, len(PAPERS)
    for f in sys.argv[1:]:
        p = Path(f); s = p.read_text(encoding="utf-8"); n = rewrite(s)
        if n != s:
            p.write_text(n, encoding="utf-8")
            print(f"{f}: {len(LINK.findall(s)) - len(LINK.findall(n))} links -> PDF")
