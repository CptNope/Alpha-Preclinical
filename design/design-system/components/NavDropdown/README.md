# NavDropdown

The header: white logo, six links in buyer order, and the quote button. Expertise, Services and About open dropdown panels.

- Order: **Expertise ▾ · Services ▾ · Publications · About ▾ · Blog · Contact**, then "Request a study quote" (`ap-btn-light`) on every page.
- Links: Figtree 500 16px white on `abyss`; an underline grows on hover and marks the current page; the current section turns `shallows`.
- Panels: white, `radius-m`, `shadow-menu` (the one heavier shadow). Each item is a Bricolage 18px name plus a one-line note. Expertise and Services use a wide three-column panel with an intro, "All..." button and a photo link.
- A mouse opens a panel on hover. The chevron button opens it for keyboard and touch (`aria-expanded`); Escape closes it and returns focus to the button.
- Below 1080px the header becomes a full-screen `abyss` menu where each section expands in place.
