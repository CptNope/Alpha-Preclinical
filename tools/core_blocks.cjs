// Serialize article text as core blocks exactly as the block editor would save them.
// Input (stdin): JSON array of {type:'p'|'h'|'list', ...}. Output: block markup.
// Run with the @wordpress/blocks + block-library packages installed (see tools/README).
require('global-jsdom/register');
window.matchMedia = window.matchMedia || (() => ({ matches: false, addListener() {}, removeListener() {} }));
const blocks = require('@wordpress/blocks');
require('@wordpress/block-library').registerCoreBlocks();
const items = JSON.parse(require('fs').readFileSync(0, 'utf8'));
const out = items.map((it) => {
  if (it.type === 'raw') return it.markup;
  let b;
  if (it.type === 'p') b = blocks.createBlock('core/paragraph', { content: it.html, className: it.className || undefined });
  else if (it.type === 'h') b = blocks.createBlock('core/heading', { content: it.html, level: it.level, anchor: it.id || undefined, className: it.className || undefined });
  else if (it.type === 'list') b = blocks.createBlock('core/list', { ordered: !!it.ordered, className: it.className || undefined },
    it.items.map((h) => blocks.createBlock('core/list-item', { content: h })));
  return blocks.serialize(b);
});
process.stdout.write(JSON.stringify(out));
