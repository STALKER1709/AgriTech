---
name: AgriTech Design System
colors:
  surface: '#fbf9f4'
  surface-dim: '#dbdad5'
  surface-bright: '#fbf9f4'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f3ee'
  surface-container: '#f0eee9'
  surface-container-high: '#eae8e3'
  surface-container-highest: '#e4e2dd'
  on-surface: '#1b1c19'
  on-surface-variant: '#404940'
  inverse-surface: '#30312e'
  inverse-on-surface: '#f2f1ec'
  outline: '#707a6f'
  outline-variant: '#bfc9bd'
  surface-tint: '#1c6c3b'
  primary: '#005127'
  on-primary: '#ffffff'
  primary-container: '#1b6b3a'
  on-primary-container: '#9ae9ab'
  inverse-primary: '#8ad89c'
  secondary: '#954a00'
  on-secondary: '#ffffff'
  secondary-container: '#fe9b52'
  on-secondary-container: '#703500'
  tertiary: '#584300'
  on-tertiary: '#ffffff'
  tertiary-container: '#755a00'
  on-tertiary-container: '#ffd25c'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a5f4b6'
  primary-fixed-dim: '#8ad89c'
  on-primary-fixed: '#00210c'
  on-primary-fixed-variant: '#005227'
  secondary-fixed: '#ffdcc6'
  secondary-fixed-dim: '#ffb786'
  on-secondary-fixed: '#311400'
  on-secondary-fixed-variant: '#723600'
  tertiary-fixed: '#ffdf94'
  tertiary-fixed-dim: '#f0c038'
  on-tertiary-fixed: '#251a00'
  on-tertiary-fixed-variant: '#594400'
  background: '#fbf9f4'
  on-background: '#1b1c19'
  surface-variant: '#e4e2dd'
  surface-white: '#FFFFFF'
  text-primary: '#1F2421'
  text-secondary: '#555F58'
  border-warm: '#E5E0D8'
  status-success: '#2E7D32'
  status-warning: '#ED6C02'
  status-error: '#D32F2F'
  payment-mtn: '#FFCC00'
  payment-orange: '#FF7900'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md-bold:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
  price-tag:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '700'
    lineHeight: 22px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-desktop: 2rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
---

## Brand & Style

The design system embodies a modern, agrarian, and highly functional mobile-first marketplace built specifically for agricultural commerce in Cameroon. It connects rural producers with urban consumers, distributors, and trainees. The aesthetic rejects generic sterile corporate tropes and overly cartoonish metaphors in favor of an authentic, earthy, and trustworthy visual identity rooted in West-Central African vitality.

The visual style blends modern utilitarianism with warm organic tactility:
- **Tone:** Grounded, reassuring, productive, community-centric, and industrious.
- **Visual Mechanics:** Pure white surfaces floating gently over warm ivory foundations, crisp borders reminiscent of agricultural partitions, pill-shaped interactions for organic ergonomics, and rich botanical deep greens counterweighted by clay terracotta and golden sunshine accents.
- **Locale Sensitivity:** Built natively for French language UI and CFA Franc (XAF / FCFA) transactions formatted strictly with non-breaking space separators and no decimal figures (e.g., `12 500 FCFA`). Touch interactions prioritize rapid thumb-reach zones, generous tapping surfaces (min 48px), and scannable visual indicators suitable for outdoor sunlight use.

## Colors

The color architecture reflects the living cycle of agriculture—foliage, fertile earth, crop yields, and tropical sunlight:

- **Primary (`#1B6B3A` - Forest Green):** Anchors main primary CTAs, active tab indicators, core navigation bars, and seller/buyer verification marks. Represents agricultural vitality, trust, and growth.
- **Secondary (`#C8702A` - Terracotta Earth):** Conveys soil, harvest warmth, and manual trade. Used for secondary CTAs, training badges, contextual links, and cart accents.
- **Tertiary (`#E8B931` - Golden Harvest):** Reserved for high-value micro-interactions: rating stars, verification badges, subscription promotion highlights, and promotional ribbon elements.
- **Neutral & Canvas (`#FAF8F3` Canvas, `#FFFFFF` Surface):** The app uses an organic off-white/ivory canvas that prevents eye fatigue in high outdoor brightness. Elevated cards, modals, and sheets use pure white `#FFFFFF` with warm border strokes.
- **Typography Neutrals (`#1F2421` & `#555F58`):** Text avoids pure black (`#000000`). Near-black `#1F2421` provides high contrast without harshness; `#555F58` supplies warm, legibly muted secondary copy.
- **Transaction & System Accents:** Standard semantics adhere to `#2E7D32` (Success), `#ED6C02` (Warning), and `#D32F2F` (Error). Regional mobile money flows leverage dedicated identifier tokens (`#FFCC00` for MTN Mobile Money, `#FF7900` for Orange Money) within structured preview modules.

## Typography

The type system prioritizes high-legibility geometry and structural stability across varying daylight conditions:
- **Headings (Plus Jakarta Sans):** Selected for its contemporary geometric balance, open apertures, and welcoming warmth. Bold titles provide crisp hierarchy in French phrasing, which frequently requires more horizontal room than English.
- **Body & Labels (Inter):** Utilitarian and systematic. Provides clean optical distinction between numbers, abbreviations (e.g., `FCFA`, `kg`, `vol`), and standard text.
- **Legibility Rules:**
  - Standard body text never drops below `16px` (`body-md`) on mobile screens to preserve accessibility for outdoor mobile workers.
  - Price tags (`price-tag`) use bold Plus Jakarta Sans with tabular numbering to maintain alignment across product catalogues and financial checkout ledgers.
  - Currency representation strictly adheres to Cameroonian and CEMAC standard formatting: numerals grouped with non-breaking whitespace followed by the currency code: `25 000 FCFA`.

## Layout & Spacing

The layout model is driven by an 8pt architectural rhythm with deliberate mobile-first adaptations:

- **Mobile Viewport (up to 767px):**
  - Fluid single-column or 2-column card grids.
  - Screen outer margin: `1rem` (16px).
  - Column gutter: `1rem` (16px).
  - Sticky bottom sheets and persistent bottom navigation bar (64px height + safe-area insets).
  - Touch targets maintain a minimum dimension of `48px x 48px`.

- **Tablet Viewport (768px - 1023px):**
  - 6-column to 8-column layout.
  - Screen outer margin: `1.5rem` (24px).
  - Column gutter: `1.25rem` (20px).
  - Transition from bottom nav bar to top header bar or left compact rail.

- **Desktop & Back-Office Viewport (1024px+):**
  - 12-column layout with fixed left sidebar navigation (260px width).
  - Screen outer margin: `2rem` (32px).
  - Column gutter: `1.5rem` (24px).
  - Max content container width for standard marketplace feeds: `1200px`.
  - Full-width dense data tables for administrator back-office screens.

## Elevation & Depth

Visual hierarchy uses warm ambient lighting paired with subtle outlines rather than heavy industrial drop-shadows:

- **Surface Levels:**
  - **Level 0 (Canvas):** `#FAF8F3` (warm ivory backdrop).
  - **Level 1 (Default Cards & Tiles):** `#FFFFFF` surface with a `1px solid #E5E0D8` low-contrast outline and ambient shadow `0px 2px 8px rgba(31, 36, 33, 0.04)`.
  - **Level 2 (Hover / Active Cards / Dropdowns):** `#FFFFFF` surface with `0px 6px 16px rgba(31, 36, 33, 0.08)` and border `#E5E0D8`.
  - **Level 3 (Sticky Bars, Floating Buttons & Modals):** `#FFFFFF` surface with `0px 12px 28px rgba(31, 36, 33, 0.12)`.
  - **Level 4 (Toasts & Overlays):** `#1F2421` dark neutral background or white with `0px 16px 36px rgba(31, 36, 33, 0.18)`.

- **Elevation Tinting:** Shadows carry a trace of the near-black `#1F2421` tone rather than neutral cold grey, preserving an organic, warm paper-and-sun feel.

## Shapes

The design system applies a deliberate hybrid approach that balances structural card geometry with pill-shaped ergonomics:

- **Cards, Containers & Modals:** Use `12px` to `16px` border radii (`rounded-lg` / `rounded-xl`). This provides structural containment for product images, producer profiles, and transaction summaries without appearing brittle.
- **Interactive Action Elements (Buttons, Filters, Category Chips, Badges):** Fully rounded pill geometry (`rounded-full` / 9999px). The continuous round curve communicates tap-friendliness, physical grip, and organic ease on handheld devices.
- **Form Controls & Inputs:** `12px` corner radius (`rounded-lg`) offering a comfortable middle ground that pairs with standard mobile keyboards.

## Components

### Buttons
- **Primary Button:** Pill-shaped (`rounded-full`), `#1B6B3A` background, pure white text (`#FFFFFF`), `font-weight: 600`, minimum height `48px`, horizontal padding `24px`. On tap/active: darkened to `#15542E`.
- **Secondary Button:** Pill-shaped, `#C8702A` background, `#FFFFFF` text. Used for buyer actions, training unlocks, and secondary CTAs.
- **Outline Button:** Pill-shaped, background `transparent`, `1.5px solid #1B6B3A` border, `#1B6B3A` text.
- **Ghost / Neutral Button:** Soft warm gray tint or transparent, `#1F2421` label, minimum height `48px`.

### Chips & Filters
- **Category Chips:** Pill-shaped (`rounded-full`), height `36px` to `40px`, padding `0 16px`. Default state: `#FFFFFF` background, `1px solid #E5E0D8`, `#555F58` text. Selected state: `#1B6B3A` background, `#FFFFFF` text, bold weight.
- **Counter / Status Badges:** Pill-shaped micro-badges (`rounded-full`), height `24px`, padding `0 10px`, typography `label-sm`.
  - *Publié / Livré:* `#E8F5E9` background, `#2E7D32` text.
  - *En cours / En préparation:* `#FFF3E0` background, `#ED6C02` text.
  - *Refusé / Annulé:* `#FFEBEE` background, `#D32F2F` text.
  - *Abonnement / VIP:* `#FEF9E7` background, `#8D6B00` text, border `1px solid #E8B931`.

### Cards
- **Product Card (Catalogue):** `#FFFFFF` surface, `14px` radius, `1px solid #E5E0D8`. Features a 1:1 ratio image header with subtle inner border, producer avatar and region tag (`Bafoussam, Ouest`), product title in `headline-sm`, pricing clearly presented in `price-tag` style (e.g. `8 000 FCFA / sac`), and a quick circular tap button `+` for cart addition.
- **Training Card (Formations):** `#FFFFFF` surface, `14px` radius, 16:9 thumbnail ratio, format chip overlay ("Vidéo" / "PDF"), instructor name, duration, and access mode tag ("Incluse dans l'abonnement").
- **Payment Method Card:** Bordered option card (`rounded-lg`), selectable with radio indicator, carrying custom background tints for MTN Mobile Money (`#FFFDF0` with `#FFCC00` border) and Orange Money (`#FFF7F0` with `#FF7900` border).

### Input Fields & Steppers
- **Text Inputs:** Height `50px`, `12px` corner radius, `#FFFFFF` background, border `1.5px solid #E5E0D8`, text in `#1F2421`. Label positioned above in `label-lg` with `#555F58`. On focus: border shifts to `#1B6B3A` with a soft `0 0 0 3px rgba(27, 107, 58, 0.15)` ring.
- **Phone Input with Country Code:** Locked leading badge `+237` separated by a vertical border, paired with numeric-only keypad.
- **Quantity Stepper:** Pill container with `-` and `+` touch controls (minimum `44px` target each) with numerical count displayed in bold tabular font.

### Checkboxes & Radios
- **Radio Buttons:** Circular `22px` diameter, `2px solid #E5E0D8`, centered `#1B6B3A` dot on checked state.
- **Checkboxes:** `20px` square with `4px` corner radius, `#1B6B3A` fill on active with crisp white check glyph.

### Navigation & Bottom Sheet
- **Bottom Navigation Bar:** Height `64px` + device safe-area, `#FFFFFF` surface with top border `1px solid #E5E0D8`. Five navigation items: *Accueil*, *Catalogue*, *Formations*, *Messages*, *Compte*. Active state indicated by primary forest green icon and text.
- **Filter Bottom Sheet:** Drawer sliding from bottom with top drag handle (`4px x 36px` pill in `#E5E0D8`), multi-select filter groups (Région, Catégorie, Fourchette de prix), and fixed dual CTA at the base ("Réinitialiser" and "Appliquer les filtres").