---
name: Pro-Ledger Enterprise
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#45464d'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#76777d'
  outline-variant: '#c6c6cd'
  surface-tint: '#565e74'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#131b2e'
  on-primary-container: '#7c839b'
  inverse-primary: '#bec6e0'
  secondary: '#006a61'
  on-secondary: '#ffffff'
  secondary-container: '#86f2e4'
  on-secondary-container: '#006f66'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#2f1500'
  on-tertiary-container: '#c76c00'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#89f5e7'
  secondary-fixed-dim: '#6bd8cb'
  on-secondary-fixed: '#00201d'
  on-secondary-fixed-variant: '#005049'
  tertiary-fixed: '#ffdcc3'
  tertiary-fixed-dim: '#ffb77d'
  on-tertiary-fixed: '#2f1500'
  on-tertiary-fixed-variant: '#6e3900'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: JetBrains Mono
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: JetBrains Mono
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.03em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 0.25rem
  sm: 0.5rem
  md: 1rem
  lg: 1.5rem
  xl: 2rem
  2xl: 3rem
  gutter: 1.5rem
  margin: 2rem
---

## Brand & Style
The design system is engineered for B2B recurring services management, where reliability and clarity are paramount. The brand personality is **Professional, Systematic, and Precise**. It prioritizes high-utility interfaces that reduce cognitive load for operations managers handling complex financial data.

The visual style is **Corporate / Modern**, heavily influenced by high-performance SaaS environments. It utilizes a refined "Systematic Minimalist" approach: ample whitespace to separate data clusters, subtle borders for structure rather than heavy shadows, and a functional aesthetic that feels native to the modern web. The goal is to evoke a sense of controlled efficiency and long-term stability.

## Colors
The palette is rooted in trust and operational clarity. 
- **Primary (Deep Blue):** Used for navigation, primary actions, and brand identification. It provides a grounded, authoritative foundation.
- **Secondary (Teal):** Represents growth and financial health. Used for success states, "active" service indicators, and positive financial trends.
- **Accent (Amber):** Reserved strictly for operational alerts, pending approvals, and items requiring immediate attention.
- **Neutral (Slate):** A comprehensive range of grays used for text, borders, and secondary UI elements to maintain high readability without visual fatigue.

## Typography
This design system utilizes **Inter** as its primary typeface for its exceptional legibility and neutral, professional tone. To enhance the technical and financial nature of the product, **JetBrains Mono** is used for labels, status badges, and numerical data in tables, ensuring that digits remain distinct and aligned.

Type scales follow a strict hierarchy to manage high-density information. Headlines use tight tracking and bold weights to provide clear landmarks, while body text maintains a generous line height for long-form data scanning.

## Layout & Spacing
The layout follows a **Fluid 12-Column Grid** for desktop views, transitioning to a single-column stack on mobile devices. 
- **Desktop:** 12 columns, 24px gutters, 32px outer margins.
- **Tablet:** 8 columns, 16px gutters, 24px outer margins.
- **Mobile:** 4 columns, 16px gutters, 16px outer margins.

Spacing is based on a **4px base unit** (4, 8, 16, 24, 32, 48, 64). For data-heavy views like recurring billing tables, a "Condensed Mode" should be applied, reducing vertical padding to 8px (sm) to maximize information density. For dashboard summaries, "Relaxed Mode" uses 24px (lg) padding to allow metrics to breathe.

## Elevation & Depth
This design system uses **Low-contrast Outlines** and **Tonal Layers** rather than heavy shadows to create depth. This ensures the UI remains crisp and doesn't feel "heavy" when multiple panels are open.
- **Level 0 (Background):** Slate-50 (#F8FAFC).
- **Level 1 (Cards/Sidebar):** Pure white with a 1px border (#E2E8F0).
- **Level 2 (Modals/Popovers):** Pure white with a 1px border (#CBD5E1) and a soft, transparent ambient shadow (0px 10px 15px -3px rgba(15, 23, 42, 0.05)).

Active states and focus rings use the Primary Blue with a 2px offset to ensure clear keyboard navigation.

## Shapes
In line with a professional B2B aesthetic, the shape language is **Soft (0.25rem)**. This provides just enough curvature to feel modern and accessible without losing the rigid, organized feel of a corporate tool. 
- **Buttons and Inputs:** 4px (0.25rem) radius.
- **Cards and Containers:** 8px (0.5rem) radius.
- **Status Badges:** Fully rounded (pill) to distinguish them from interactive buttons.

## Components
- **Buttons:** Primary buttons use solid Deep Blue with white text. Secondary buttons use a white background with a 1px Slate-200 border. Ghost buttons are reserved for utility actions within tables.
- **Status Badges (Enums):** Defined by specific color logic:
    - `ACTIVE`: Secondary Teal background (10% opacity), Teal text.
    - `PENDING`: Accent Amber background (10% opacity), Amber text.
    - `PAST_DUE`: Red-50 background, Red-700 text.
    - `CANCELED`: Slate-100 background, Slate-600 text.
- **Data Tables:** High-density with 1px horizontal dividers. Header rows use Slate-50 background and JetBrains Mono labels for clarity. Row hover states use a subtle Slate-50 tint.
- **Input Fields:** Standardized height of 38px. Border color is Slate-300, shifting to Primary Blue on focus. Labels are positioned above the field in `body-sm` bold.
- **Financial Projections (Cards):** Feature a `headline-md` value, a `label-sm` title, and a small sparkline or percentage trend indicator using the Secondary (Teal) or Red colors.
- **Progress Bars:** Thin 4px height tracks for service completion or billing cycles.