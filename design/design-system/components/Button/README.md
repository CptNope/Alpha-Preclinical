# Button

Pill buttons for the one action that matters in a view; the label says what happens next.

- **Primary** (`ap-btn ap-btn-primary`): `tide` with `paper` text, hover `navy`. Use on `paper` and `mist`. One per view.
- **Light** (`ap-btn-light`): `shallows` with `abyss` text, hover `white`. The primary action on `abyss` (hero, CTA band, deep sections).
- **Ghost dark** (`ap-btn-ghost-dark`): the secondary action on `abyss`, a 1.5px inset `ink-on-abyss` ring.
- **Small** (`ap-btn-sm`, 44px): only inside dropdown panels ("All expertise") and dense rows.

Height 52px (44px small), `radius-pill`, Figtree 600 16px. Labels are specific: "Request a study quote", "Plan your PK/PD study", "Talk to a scientist". Every study CTA links to `contact.html?study=<type>#form` so the form opens with the study type chosen. The consumer provides the `href` and label.
