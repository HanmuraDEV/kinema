---
name: Kinema
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#414940'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#717970'
  outline-variant: '#c1c9be'
  surface-tint: '#396940'
  primary: '#396940'
  on-primary: '#ffffff'
  primary-container: '#ace1af'
  on-primary-container: '#36653e'
  inverse-primary: '#9fd3a2'
  secondary: '#006a68'
  on-secondary: '#ffffff'
  secondary-container: '#64f8f4'
  on-secondary-container: '#00706f'
  tertiary: '#9f3f39'
  on-tertiary: '#ffffff'
  tertiary-container: '#ffc7c1'
  on-tertiary-container: '#9b3c36'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#baf0bd'
  primary-fixed-dim: '#9fd3a2'
  on-primary-fixed: '#002109'
  on-primary-fixed-variant: '#20502b'
  secondary-fixed: '#64f8f4'
  secondary-fixed-dim: '#3fdbd8'
  on-secondary-fixed: '#00201f'
  on-secondary-fixed-variant: '#00504e'
  tertiary-fixed: '#ffdad6'
  tertiary-fixed-dim: '#ffb4ac'
  on-tertiary-fixed: '#410003'
  on-tertiary-fixed-variant: '#802824'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  headline-xl:
    fontFamily: Quicksand
    fontSize: 40px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Quicksand
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: Quicksand
    fontSize: 28px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Quicksand
    fontSize: 24px
    fontWeight: '500'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Quicksand
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Quicksand
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.4'
    letterSpacing: 0.08em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  unit: 8px
  container-max: 1200px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 40px
---

## Brand & Style
The brand personality is nostalgic yet forward-looking, channeling the optimistic "Aqua" aesthetic of the early 2000s. It captures the social excitement of cinema through a lens of high-tech cleanliness. The design system leverages a "Y2K Clean" style: a fusion of skeuomorphic gloss and modern minimalism.

The goal is to evoke a sense of premium accessibility. The UI should feel like a physical object—polished, tactile, and translucent—inviting users to touch and interact. We achieve this through soft-touch gradients, subtle inner glows that mimic light hitting glass, and a strictly organized layout that prevents the "glossy" elements from feeling cluttered.

## Colors
The palette is anchored in a refreshing "Celadon Green" and "Bondi Blue," reminiscent of early transparent electronics. 

- **Primary (Celadon Green):** Used for success states, active highlights, and primary brand moments.
- **Secondary (Bondi Blue):** The core interactive color, used for primary buttons and navigation icons.
- **Accent (Coral):** Reserved for "social" triggers like hearts, trending indicators, and live cinema events.
- **Background:** A very light "Ice" grey (#F9FAFB) is used to provide enough contrast for white glassmorphic layers to remain visible.

Color applications should frequently use subtle linear gradients (top-to-bottom) to simulate depth, moving from a lighter tint of the hue to the base hex code.

## Typography
The typography system prioritizes softness and legibility. **Quicksand** is the primary typeface, chosen for its rounded terminals that complement the Y2K aesthetic. 

Headlines utilize a semi-bold weight with tight tracking to feel cohesive and "logo-like." For functional data and small UI labels, **Plus Jakarta Sans** is introduced to provide a crisp, modern counterpoint that ensures metadata (like movie runtimes or ratings) remains perfectly legible at small sizes. All type should avoid pure black; use a deep charcoal (#1A1C1E) to maintain the soft aesthetic.

## Layout & Spacing
The layout follows a fluid 12-column grid for desktop and a 4-column grid for mobile. Spacing is generous, utilizing an 8px base unit to ensure elements have plenty of "air," reinforcing the clean Apple-inspired look.

Content is grouped in high-contrast containers. Card-based feeds should use a "Masonry-lite" approach for movie posters, while social interactions are kept in tight, horizontally scrolling carousels. On mobile, margins are reduced to 16px to maximize the visual impact of colorful cinema imagery.

## Elevation & Depth
Depth is the defining characteristic of this system. It is achieved through three specific techniques:

1.  **The Glass Effect:** Navigation bars and overlays use a backdrop filter (`blur: 20px`) with a white semi-transparent fill (`opacity: 70%`). A 1px solid white border with 20% opacity should be applied to simulate a "beveled glass" edge.
2.  **Glossy Gradients:** Buttons and primary containers feature a subtle "highlight" gradient—a nearly invisible white-to-transparent overlay on the top 50% of the element.
3.  **Soft Shadows:** Instead of heavy drop shadows, use "Ambient Glows." Shadows should be colored based on the surface (e.g., a Bondi Blue button casts a soft Bondi Blue shadow) with high diffusion (blur 15px+) and low opacity (15%).

## Shapes
The shape language is dominated by high-radius curves. Every interactive element—from input fields to buttons—should feel "pebble-like."

- **Pill Shapes:** Mandatory for all buttons, tags, and search bars.
- **Large Radius:** Cards and containers use the `rounded-xl` (3rem) setting to avoid any "sharpness" that would break the friendly, liquid-metal feel of the UI.
- **Iconography:** Use rounded-cap icons with a medium stroke weight (2px) to match the Quicksand typeface.

## Components

### Buttons & Chips
Buttons are always pill-shaped. The primary action button uses a Bondi Blue gradient with a subtle inner-white glow at the top edge. Chips (used for movie genres) are semi-transparent with a 1px border matching the text color.

### Cards
Movie cards are minimalist. They do not use heavy borders; instead, they rely on a soft 10% Bondi Blue shadow to separate them from the background. Typography on cards is kept to a minimum—title, year, and a coral-colored rating.

### Glassmorphic Nav
The top and bottom navigation bars are fully translucent. Icons within the nav bar "activate" by glowing with their respective brand color (Celadon for Home, Bondi for Search, Coral for Profile) rather than simple color changes.

### Data Visualization
Charts for "Cinema Stats" (e.g., genre breakdowns) use the core palette:
- **Histograms:** Rounded bar tops with Bondi Blue gradients.
- **Radar Charts:** Soft Celadon fills with 30% opacity.
- **Heatmaps:** A smooth transition from Neutral to Celadon to Bondi Blue.

### Inputs
Input fields are pill-shaped with a faint Bondi Blue inner-shadow to suggest the field is "recessed" into the glass surface.