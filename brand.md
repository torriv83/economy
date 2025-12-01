# Brand & Design System

**Design System Name:** Financial Momentum

This document outlines the visual design language used throughout the Economy debt management application.

---

## Color Palette

### Primary Colors

| Name | Value | Usage |
|------|-------|-------|
| **Momentum (Emerald)** | `oklch(0.72 0.17 162)` | Primary actions, progress indicators, CTAs |
| **Momentum Light** | `oklch(0.82 0.14 162)` | Hover states, highlights |
| **Momentum Dark** | `oklch(0.55 0.15 162)` | Active states, emphasis |

### Foundation Colors (Slate)

| Shade | Value | Usage |
|-------|-------|-------|
| slate-50 | Light background | Page backgrounds (light mode) |
| slate-200 | Borders | Dividers, card borders (light mode) |
| slate-400 | Secondary text | Metadata, helper text |
| slate-700 | Labels | Form labels, input text |
| slate-900 | Primary text | Headings, body text (light mode) |
| slate-950 | `oklch(0.13 0.02 265)` | Page background (dark mode) |

### Semantic Colors

| Color | Tailwind | Usage |
|-------|----------|-------|
| **Success** | `emerald-500` | Positive progress, completed actions |
| **Info** | `cyan-500` | Informational, secondary accent |
| **Warning** | `amber-500` | Cautions, debt increases |
| **Danger** | `rose-500` | Errors, validation failures |

### Gradients

| Name | Definition | Usage |
|------|------------|-------|
| **Progress Gradient** | `emerald-500` → `cyan-500` | Key metrics (total debt, timeline), progress rings, accent lines |
| **Warm Gradient** | `orange-500` → `amber-500` | Secondary emphasis (rarely used) |

**Note:** Use `.gradient-text` class for key numerical metrics to create visual consistency.

---

## Typography

### Font Families

| Type | Font | Weights | Usage |
|------|------|---------|-------|
| **Display** | [Sora](https://fonts.google.com/specimen/Sora) | 300-800 | Headings, metrics, page titles |
| **Body** | [Source Sans 3](https://fonts.google.com/specimen/Source+Sans+3) | 300-700 | Body text, labels, descriptions |

### Type Scale

| Class | Size | Usage |
|-------|------|-------|
| `text-xs` | 0.75rem | Helper text, badges |
| `text-sm` | 0.875rem | Labels, metadata |
| `text-base` | 1rem | Body content |
| `text-lg` | 1.125rem | Card titles |
| `text-xl` | 1.25rem | Section metrics |
| `text-2xl` | 1.5rem | Section headers |
| `text-3xl` | 1.875rem | Page titles |
| `text-4xl` | 2.25rem | Large statistics |

### Font Weights

| Weight | Class | Usage |
|--------|-------|-------|
| 400 | `font-normal` | Body text |
| 500 | `font-medium` | Labels, secondary emphasis |
| 600 | `font-semibold` | Card titles |
| 700 | `font-bold` | Headings, key numbers |

---

## Component Patterns

### Cards

```
Light Mode: white background, slate-200 border
Dark Mode:  slate-800/900 background, slate-700 border
```

**Variants:**
- `.glass-card` - Frosted glass with backdrop blur
- `.premium-card` - Gradient background with shadows
- `.debt-card` - Hover glow effect for debt items
- `.card-interactive` - Lift effect on hover

**Card Footer Structure:**
- Metadata row (dates, status) gets subtle background (`bg-slate-50/50`)
- Action buttons sit on clean card background (no tint)
- This separation reduces visual noise

### Buttons

| Type | Colors | Usage |
|------|--------|-------|
| **Primary** | Emerald solid (`emerald-500`) | Main actions (Edit, Save, Confirm) |
| **Secondary** | Slate outline | Secondary actions, Cancel |
| **Danger** | Slate outline → rose on hover | Destructive actions (Delete) |
| **Warning** | amber-600 bg | Caution actions |

**Button Hierarchy:** Primary actions (Edit) should visually outweigh destructive actions (Delete). Delete buttons use subtle outline styling that only turns red on hover.

### Form Inputs

| State | Border | Ring |
|-------|--------|------|
| Default | slate-300 | - |
| Focus | emerald-500 | emerald-500 |
| Error | rose-500 | rose-500 |
| Disabled | slate-100 bg | - |

### Status Indicators

| Status | Color | Example Usage |
|--------|-------|---------------|
| Success | emerald | Paid off, on track |
| Warning | amber | Debt increase, caution |
| Error | rose | Validation error, behind |
| Info | cyan | Informational stats |

---

## Dark Mode

All components support dark mode via Tailwind's `dark:` prefix.

| Element | Light | Dark |
|---------|-------|------|
| Background | slate-50 | slate-950 |
| Cards | white | slate-800/900 |
| Text | slate-900 | white |
| Borders | slate-200 | slate-700/800 |
| Accent | emerald-500 | emerald-400 |

---

## Effects & Animations

### Shadows

- `.momentum-glow` - Emerald glow shadow for emphasis
- Standard Tailwind shadows for elevation

### Animations

| Name | Effect | Duration |
|------|--------|----------|
| `fade-in-up` | Fade + slide up | 500ms |
| `fade-in-scale` | Fade + scale | 400ms |
| `slide-in-right` | Slide from right | 400ms |
| `pulse-soft` | Subtle opacity pulse | 2s infinite |

### Transitions

Default: `transition-all duration-200 ease-out`

---

## Chart Colors (Data Visualization)

These colors are separate from UI colors:

| Data Type | Color |
|-----------|-------|
| Principal paid | emerald |
| Interest | rose-400 |
| Debt increases | amber-400 |
| Secondary data | orange-400 |

---

## Usage Guidelines

### Do

- Use emerald for all progress-related UI
- Use slate as the neutral foundation
- Apply semantic colors consistently (rose = error, amber = warning)
- Maintain dark mode parity for all new components
- Use Sora (display font) for headings and large numbers
- Use Source Sans 3 for body text and labels

### Don't

- Mix warning colors (pick amber OR rose, not both)
- Use sky or orange for UI elements (reserved for charts)
- Create new color variations without updating this guide
- Use inline colors instead of Tailwind classes

---

## File References

- **CSS Configuration:** [resources/css/app.css](resources/css/app.css)
- **Theme Variables:** Lines 17-38 in app.css
- **Custom Components:** Lines 65-440 in app.css
- **Animations:** Lines 152-244 in app.css
