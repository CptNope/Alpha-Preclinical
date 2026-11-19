# StrataBand

The signature: wave bands in the brand blues, drifting slowly at the foot of every page header.

- Inner pages: a 110px band of four waves (`tide`, `current`, `shallows`, then `paper` so it melts into the page). Blog posts carry a thinner one under the header. The homepage hero uses the full-height version with five bands and the HeroAlpha.
- Each wave is a repeating path twice the viewport wide that slides left at its own speed (30-46s, alternate directions), so the layers drift against each other.
- Always ship the **Pause animation** button (WCAG 2.2.2). Under `prefers-reduced-motion` the waves start paused; with JavaScript off they rest still.
- Flat fills only: no gradients, glows or purple. Depth comes from bands overlapping, as in the logo.
