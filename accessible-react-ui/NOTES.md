# Accessible React UI

Components from two shipped apps. The first three are from [digitallotusdev.com](https://digitallotusdev.com) (live — Next.js 16, React 19 with the React Compiler, Tailwind v4); the last is from [recipe-generator](https://github.com/gabidombrowski/recipe-generator).

- **`Header.tsx`** — site navigation: scroll state, Escape closes the menu *and restores focus to the trigger*, outside-`pointerdown` close, and a `matchMedia` listener that drops menu state when the viewport crosses the nav breakpoint. Every effect cleans up after itself.
- **`ArtistInquiryForm.tsx`** — a controlled form with a `FormState` union (`idle | submitting | success | error`) and a focus-management effect for the success state that replaces the form, so keyboard and screen-reader users aren't stranded on a DOM node that no longer exists.
- **`ProcessAccordion.tsx`** — disclosure widget with correct `aria-expanded` / `aria-controls` / `id` pairing.
- **`announcer.tsx`** — an app-wide `aria-live` announcement channel. The comments document two non-obvious choices: `window` events over React context (so any layer can announce without prop-drilling a provider), and `setTimeout` over `requestAnimationFrame` (hidden tabs stop firing frames, which would silently drop announcements).
