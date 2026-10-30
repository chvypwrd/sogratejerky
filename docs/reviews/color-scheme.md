# Safe and Sound Kickboxing — Color Scheme

---

## Brand Palette

| Token | Hex | Name | Usage |
|-------|-----|------|-------|
| `$brand-primary` | `#4A2040` | Deep Plum | Primary CTA buttons, links, accents, header brand color |
| `$brand-secondary` | `#C75B3A` | Warm Terracotta/Coral | Secondary CTA buttons, hover states, danger |
| `$brand-accent` | `#D4956A` | Warm Coral Accent | Hero eyebrow, subtle highlights |

---

## Neutral Palette

| Token | Hex | Name | Usage |
|-------|-----|------|-------|
| `$dark` | `#2B2B2B` | Deep Charcoal | Body text, headings, dark sections (hero, footer, CTA) |
| `$light` | `#FAF8F5` | Warm Cream | Body background |
| `$white` | `#FFFFFF` | White | Card backgrounds, light sections |
| `$soft-stone` | `#E8E4DF` | Soft Stone | Footer text, subtle backgrounds |
| `$border-color` | `#D6D1CB` | Border | Borders, dividers |

---

## Semantic / Text

| Token | Hex | Usage |
|-------|-----|-------|
| `$body-bg` | `#FAF8F5` | Page background |
| `$body-text` | `#2B2B2B` | Default text |
| `$text-muted` | `#6B6560` | Secondary/muted text |
| `$link-color` | `#4A2040` | Link text |
| `$link-hover-color` | `#3A1A33` | Link hover (10% darken) |

---

## Bootstrap Theme Overrides

| Bootstrap Key | Value | Notes |
|---------------|-------|-------|
| `$primary` | `#4A2040` | Plum — used for `bg-primary`, `text-primary` |
| `$secondary` | `#C75B3A` | Coral — used for `bg-secondary`, `text-secondary` |
| `$success` | `#2D7D46` | Green — status badges |
| `$info` | `#3A7CA5` | Blue — informational badges |
| `$warning` | `#D4956A` | Accent — warning badges |
| `$danger` | `#C75B3A` | Coral — destructive actions, error badges |
| `$light` | `#E8E4DF` | Stone — Bootstrap `bg-light` |
| `$dark` | `#2B2B2B` | Charcoal — Bootstrap `bg-dark`, `text-dark` |

---

## Button Colors

| Button Class | Background | Text | Use Case |
|--------------|-----------|------|----------|
| `.btn-brand` | `#4A2040` (plum) | White | Primary CTA on light backgrounds |
| `.btn-brand-outline` | Transparent | `#4A2040` (plum) | Secondary CTA on light backgrounds |
| `.btn-coral` | `#C75B3A` (coral) | White | Primary CTA on dark backgrounds (hero) |
| `.btn-coral-outline` | Transparent | `#C75B3A` (coral) | Secondary CTA on dark backgrounds |

---

## Dark Background Sections

| Section | Background | Text Color | CTA Button |
|---------|-----------|------------|------------|
| Hero | `$dark` (#2B2B2B) | `$white` | `btn-coral` |
| CTA Section | `$brand-primary` (#4A2040) | `$white` | `btn-light` / `btn-outline-light` |
| Footer | `$dark` (#2B2B2B) | `$soft-stone` (#E8E4DF) | N/A |

---

## Key Design Rules

1. **Headings on dark backgrounds** — Force `color: $white !important` via `.hero-section` and `.cta-section` CSS rules to override global `$headings-color` (charcoal)
2. **Hero CTAs** — Use coral (`btn-coral` / `btn-coral-outline`) for contrast on dark charcoal
3. **Light-background CTAs** — Use plum (`btn-brand` / `btn-brand-outline`) for contrast on cream/white
4. **Footer headings** — Explicitly styled white within `.site-footer`
5. **Links** — Plum on cream/white backgrounds; coral or white on dark backgrounds

---

## WCAG Contrast Notes

| Pair | Ratio | Status |
|------|-------|--------|
| Charcoal text on cream bg | ~12:1 | ✓ AAA |
| Plum text on cream bg | ~7:1 | ✓ AAA |
| White text on charcoal bg | ~12:1 | ✓ AAA |
| White text on plum bg | ~5:1 | ✓ AA |
| Coral on charcoal bg | ~4.5:1 | ✓ AA |
| Muted gray on cream | ~4.5:1 | ✓ AA |
| Plum on white bg | ~7:1 | ✓ AAA |
