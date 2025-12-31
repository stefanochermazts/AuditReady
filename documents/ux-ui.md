You are a senior frontend engineer with deep Filament v4.4.0 + Tailwind knowledge.
I have a Laravel app using Filament Admin Panel. I want to customize the UI to an “Enterprise Audit” design system: neutral, dense, high-clarity, minimal decoration, extremely consistent.
Use context7 if you need the last specifications about filament 4.4.0 version.

Goal

Implement a cohesive Filament theme customization that:

looks enterprise + trustworthy (audit/compliance tool)

maximizes information density (tables, lists) without harming readability

uses semantic colors for statuses (Missing / In Review / Completed)

stays fully compatible with Filament upgrades (no hacking core vendor files)

meets accessibility basics (contrast, focus states, keyboard usability)

Constraints

Do NOT edit anything under vendor/.

Prefer Filament-supported APIs: Panel config, theme tokens, Blade overrides in resources/views/vendor/filament/ only when necessary.

Avoid heavy custom CSS; use Tailwind utilities and a small curated CSS layer only for repeated patterns.

No fancy gradients, no “marketing SaaS” feel, no animation-heavy UI.

Default to light mode. Dark mode is out of scope for now.

Step 1 — Define the “Enterprise Audit” visual tokens

In the Filament panel provider (likely app/Providers/Filament/AdminPanelProvider.php or similar), configure:

a neutral primary color (deep blue / petroleum) used sparingly

semantic colors:

success = Completed

warning = In Review

danger = Missing / Risk

Ensure colors are applied through Filament’s ->colors([...]) so badges, buttons, alerts, etc. inherit them consistently.

Acceptance criteria:

Buttons, badges, notifications, and status indicators reflect the token palette without component-by-component overrides.

Step 2 — Typography and spacing (density)

Update Tailwind / theme setup to use:

Inter (or system stack fallback) as the primary font

compact spacing defaults for tables and form layouts, while preserving minimum tap targets for buttons.

Targets:

Table row height should be compact (but not cramped).

Forms should be clear: labels always visible, help text subtle, errors explicit.

Acceptance criteria:

Tables show more rows per viewport vs default Filament, while remaining readable.

Forms have consistent vertical rhythm and no visual noise.

Step 3 — Table design (most important)

We rely heavily on Filament Tables. Apply an “audit-grade” table style:

Compact rows (py-2 or equivalent), consistent column spacing

Subtle hover state (very light), no strong zebra striping

Strong typographic hierarchy inside cells:

primary data: medium weight

secondary/meta: smaller + muted

Status column uses semantic badges (Missing/In Review/Completed) with consistent color mapping

Action buttons:

Primary actions visible but not loud

Destructive actions clearly danger-styled

Export actions are emphasized but not alarming

Implementation approach:

Prefer configuring table defaults via Filament’s APIs / table configuration.

If needed, create a reusable “status badge” view component or helper that maps status -> badge color + label.

Acceptance criteria:

All major tables in the app visually match (no one-off styling).

Status badges are consistent everywhere.

Action areas are clean and predictable.

Step 4 — Page layout and navigation (trustworthy enterprise look)

Adjust the overall layout:

Use neutral backgrounds (not pure white). Very light gray page background, white surfaces/cards.

Reduce excessive card rounding/shadows: keep it subtle and professional.

Ensure sidebar/nav looks “tooling-like” (clear active state, good spacing, no gimmicks).

Page headers: clear title, breadcrumb if present, right-aligned actions.

Implementation notes:

Use Filament layout overrides only if necessary (publish views into resources/views/vendor/filament/), otherwise prefer configuration.

Keep surfaces consistent across pages: same padding, same card style.

Acceptance criteria:

The app feels like enterprise internal tooling, not a consumer app.

Navigation is easy to scan; active location is obvious.

Step 5 — Forms and validation UX

Improve forms for audit workflows:

Always-visible labels (no floating placeholders as the only label)

Clear required markers

Error messages concise, consistent style

Inputs: reduce rounded corners, subtle borders, high focus visibility

Acceptance criteria:

Keyboard focus is clearly visible on inputs and buttons.

Errors are noticeable without being aggressive.

Step 6 — Accessibility and security-adjacent UI details

Ensure contrast for text and statuses is acceptable (aim for WCAG AA where feasible).

Focus states: visible outline/ring, consistent across components.

Avoid color-only status communication: include text labels (“Missing”, “In review”, “Completed”).

Acceptance criteria:

Status meaning is still clear in grayscale / color-blind scenarios.

Step 7 — Deliverables

Make the changes as a clean PR-level implementation:

Update the panel provider theme configuration (colors, possibly font).

Add or update Tailwind config (font family, any needed extensions).

Add a small, maintainable CSS file only if required (e.g., resources/css/filament-audit.css) and wire it properly.

Create a reusable helper/component for status badges.

Apply changes to at least:

one “Audit list” table

one “Audit detail” page

one “Control detail” page with evidence upload
so we can visually verify consistency.

Be explicit in code comments about why each override exists and keep it minimal.

Important: Do not over-engineer

If you find yourself rewriting Filament components, stop and use supported extension points. The goal is a cohesive theme via tokens + small overrides, not a custom UI framework.

Now implement these changes in the existing codebase, showing the specific files you modify and the rationale for each.
