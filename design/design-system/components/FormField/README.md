# FormField

Label above a 52px input, select or textarea, as used on the quote and careers forms.

- Label: Figtree 600 15px; "(optional)" in `ink-muted` 500.
- Input: `radius-s`, 1.5px `field-border` (3.3:1, a real control boundary), hover `current`, focus `tide` border plus a 3px `current` glow at 35%.
- Error: `aria-invalid="true"` turns the border `error`, and an `ap-err` message linked by `aria-describedby` says how to fix it.
- Two columns on desktop, one on phones. The study type `<select>` is preset from `?study=` in the URL.
