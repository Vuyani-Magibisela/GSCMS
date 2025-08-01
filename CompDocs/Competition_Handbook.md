# GDE SciBOTICS Platform - Complete Style Guide

## 1. Brand Identity & Partnership

### Core Brand Elements
The GDE SciBOTICS platform represents a partnership between:
- **Gauteng Department of Education** - Educational excellence and accessibility
- **Sci-Bono Discovery Centre** - Scientific innovation and discovery

### Brand Personality
- **Educational**: Promoting STEM learning and academic excellence
- **Innovative**: Embracing cutting-edge technology and scientific discovery
- **Inspiring**: Motivating young minds to pursue science and technology careers
- **Accessible**: Inclusive design for all learners and educational levels
- **Professional**: Maintaining government and institutional standards
- **Dynamic**: Energetic and engaging for youth audiences

---
## 2. Color Palette System

### Primary Brand Colors

#### **Gauteng Education Blue Palette**
```css
/* Primary Gauteng Blue */
--gauteng-blue-primary: #1E4B8C;     /* RGB(30, 75, 140) */
--gauteng-blue-dark: #163B73;        /* RGB(22, 59, 115) */
--gauteng-blue-light: #2D5FA3;       /* RGB(45, 95, 163) */
--gauteng-blue-lighter: #4A7BC4;     /* RGB(74, 123, 196) */

/* Supporting Gauteng Colors */
--gauteng-gold: #D4AF37;             /* RGB(212, 175, 55) */
--gauteng-gold-light: #E6C55A;       /* RGB(230, 197, 90) */
--gauteng-gold-dark: #B8961F;        /* RGB(184, 150, 31) */
```

#### **Sci-Bono Discovery Palette**
```css
/* Sci-Bono Blue Variations */
--scibono-blue-primary: #2E5B9B;     /* RGB(46, 91, 155) */
--scibono-blue-dark: #1F3E6B;        /* RGB(31, 62, 107) */
--scibono-blue-accent: #3D6BAE;      /* RGB(61, 107, 174) */

/* Sci-Bono Orange/Discovery */
--scibono-orange: #FF8C42;           /* RGB(255, 140, 66) */
--scibono-orange-light: #FFA366;     /* RGB(255, 163, 102) */
--scibono-orange-dark: #E6752A;      /* RGB(230, 117, 42) */
```

#### **SciBOTICS Competition Gradients**
```css
/* Primary Competition Gradients */
--primary-gradient: linear-gradient(135deg, #1E4B8C 0%, #2E5B9B 50%, #FF8C42 100%);
--secondary-gradient: linear-gradient(45deg, #2E5B9B, #1E4B8C);
--discovery-gradient: linear-gradient(135deg, #FF8C42 0%, #D4AF37 100%);
--education-gradient: linear-gradient(135deg, #1E4B8C 0%, #D4AF37 100%);
```
### Secondary Colors

#### **Success & Achievement Colors**
```css
--success-green: #2ECC71;            /* RGB(46, 204, 113) */
--success-green-light: #58D68D;      /* RGB(88, 214, 141) */
--success-green-dark: #27AE60;       /* RGB(39, 174, 96) */
```

#### **Warning & Attention Colors**
```css
--warning-orange: #F39C12;           /* RGB(243, 156, 18) */
--warning-orange-light: #F5B041;     /* RGB(245, 176, 65) */
--warning-orange-dark: #E67E22;      /* RGB(230, 126, 34) */
```

#### **Error & Critical Colors**
```css
--error-red: #E74C3C;                /* RGB(231, 76, 60) */
--error-red-light: #EC7063;          /* RGB(236, 112, 99) */
--error-red-dark: #C0392B;           /* RGB(192, 57, 43) */
```

#### **Information & Neutral Colors**
```css
--info-blue: #3498DB;                /* RGB(52, 152, 219) */
--info-blue-light: #5DADE2;          /* RGB(93, 173, 226) */
--info-blue-dark: #2980B9;           /* RGB(41, 128, 185) */
```

### Neutral Color System

#### **Light Theme (Primary)**
```css
--background-primary: #FFFFFF;       /* Pure White */
--background-secondary: #F8F9FA;     /* Light Gray */
--background-tertiary: #E9ECEF;      /* Medium Light Gray */
--background-accent: #F1F3F5;        /* Subtle Gray */

--text-primary: #2C3E50;             /* Dark Blue-Gray */
--text-secondary: #566573;           /* Medium Gray */
--text-tertiary: #85929E;            /* Light Gray */
--text-inverse: #FFFFFF;             /* White for dark backgrounds */
--text-muted: #95A5A6;               /* Muted Gray */
```

#### **Dark Theme (Optional)**
```css
--dark-background-primary: #1A1A1A;  /* Dark Gray */
--dark-background-secondary: #2D2D2D; /* Medium Dark Gray */
--dark-background-tertiary: #404040; /* Light Dark Gray */

--dark-text-primary: #FFFFFF;        /* White */
--dark-text-secondary: #CCCCCC;      /* Light Gray */
--dark-text-tertiary: #999999;       /* Medium Gray */
```

---

## 3. Typography System

### Font Hierarchy

#### **Primary Font Stack**
```css
--font-primary: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
```
**Rationale**: Modern, highly legible, excellent for digital interfaces

#### **Secondary Font Stack (Display)**
```css
--font-display: 'Poppins', 'SF Pro Display', 'Helvetica Neue', Arial, sans-serif;
```
**Rationale**: Bold, engaging headlines and display text

#### **Monospace Font (Technical)**
```css
--font-mono: 'JetBrains Mono', 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
```
### Typography Scale

#### **Display Typography**
```css
/* Hero Titles */
--font-size-hero: 4rem;              /* 64px */
--line-height-hero: 1.1;
--font-weight-hero: 800;

/* Display Titles */
--font-size-display: 3.5rem;         /* 56px */
--line-height-display: 1.15;
--font-weight-display: 700;
```

#### **Heading Hierarchy**
```css
/* H1 - Page Titles */
--font-size-h1: 3rem;                /* 48px */
--line-height-h1: 1.2;
--font-weight-h1: 700;

/* H2 - Section Titles */
--font-size-h2: 2.5rem;              /* 40px */
--line-height-h2: 1.25;
--font-weight-h2: 600;

/* H3 - Subsection Titles */
--font-size-h3: 2rem;                /* 32px */
--line-height-h3: 1.3;
--font-weight-h3: 600;

/* H4 - Component Titles */
--font-size-h4: 1.5rem;              /* 24px */
--line-height-h4: 1.4;
--font-weight-h4: 500;

/* H5 - Small Headings */
--font-size-h5: 1.25rem;             /* 20px */
--line-height-h5: 1.45;
--font-weight-h5: 500;

/* H6 - Micro Headings */
--font-size-h6: 1rem;                /* 16px */
--line-height-h6: 1.5;
--font-weight-h6: 500;
```

#### **Body Text Hierarchy**
```css
/* Large Body Text */
--font-size-xl: 1.25rem;             /* 20px */
--line-height-xl: 1.6;

/* Regular Body Text */
--font-size-lg: 1.125rem;            /* 18px */
--line-height-lg: 1.6;

/* Standard Body Text */
--font-size-base: 1rem;              /* 16px */
--line-height-base: 1.6;

/* Small Body Text */
--font-size-sm: 0.875rem;            /* 14px */
--line-height-sm: 1.5;

/* Extra Small Text */
--font-size-xs: 0.75rem;             /* 12px */
--line-height-xs: 1.4;
```

#### **Responsive Typography**
```css
@media (max-width: 768px) {
    --font-size-hero: 3rem;           /* 48px */
    --font-size-display: 2.5rem;      /* 40px */
    --font-size-h1: 2.5rem;           /* 40px */
    --font-size-h2: 2rem;             /* 32px */
    --font-size-h3: 1.75rem;          /* 28px */
}

@media (max-width: 480px) {
    --font-size-hero: 2.5rem;         /* 40px */
    --font-size-display: 2rem;        /* 32px */
    --font-size-h1: 2rem;             /* 32px */
    --font-size-h2: 1.75rem;          /* 28px */
}
```

---

## 4. Component Design System

### Button System

#### **Primary Buttons (Competition Actions)**
```css
.btn-primary {
    background: var(--education-gradient);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-family: var(--font-primary);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(30, 75, 140, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(30, 75, 140, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}
```

#### **Secondary Buttons (Discovery Actions)**
```css
.btn-secondary {
    background: var(--discovery-gradient);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(255, 140, 66, 0.4);
}
```

#### **Outline Buttons**
```css
.btn-outline-primary {
    background: transparent;
    color: var(--gauteng-blue-primary);
    border: 2px solid var(--gauteng-blue-primary);
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: var(--gauteng-blue-primary);
    color: white;
    transform: translateY(-1px);
}
```

#### **Button Sizes**
```css
.btn-xl {
    padding: 1.25rem 3rem;
    font-size: 1.125rem;
    border-radius: 10px;
}

.btn-lg {
    padding: 1rem 2.5rem;
    font-size: 1.125rem;
}

.btn-sm {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
}

.btn-xs {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}
```
### Form Elements

#### **Input Fields**
```css
.form-input {
    width: 100%;
    padding: 1rem;
    border: 2px solid var(--background-tertiary);
    border-radius: 8px;
    font-size: 1rem;
    font-family: var(--font-primary);
    transition: all 0.3s ease;
    background: var(--background-primary);
}

.form-input:focus {
    border-color: var(--gauteng-blue-primary);
    box-shadow: 0 0 0 3px rgba(30, 75, 140, 0.1);
    outline: none;
}

.form-input.error {
    border-color: var(--error-red);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.form-input.success {
    border-color: var(--success-green);
    box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
}
```

#### **Select Dropdowns**
```css
.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23566573' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem;
    padding-right: 3rem;
}
```

### Card Components

#### **Standard Card**
```css
.card {
    background: var(--background-primary);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}
```

#### **Competition Card**
```css
.card-competition {
    background: linear-gradient(135deg, rgba(30, 75, 140, 0.05) 0%, rgba(255, 140, 66, 0.05) 100%);
    border: 2px solid transparent;
    background-clip: padding-box;
    position: relative;
}

.card-competition::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 12px;
    padding: 2px;
    background: var(--primary-gradient);
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
}
```

#### **Glass Card (Premium)**
```css
.card-glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 2rem;
}
```

---

## 5. Layout & Grid System

### Container System
```css
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.container-fluid {
    width: 100%;
    padding: 0 2rem;
}

.container-sm {
    max-width: 768px;
    margin: 0 auto;
    padding: 0 2rem;
}

.container-lg {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}
```

### Grid System
```css
.grid {
    display: grid;
    gap: 2rem;
}

.grid-cols-1 { grid-template-columns: 1fr; }
.grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
.grid-cols-12 { grid-template-columns: repeat(12, 1fr); }

.grid-responsive {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

@media (max-width: 768px) {
    .grid-cols-2,
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: 1fr;
    }
}
```

### Spacing System
```css
/* Spacing Scale */
.m-0 { margin: 0; }
.m-1 { margin: 0.5rem; }
.m-2 { margin: 1rem; }
.m-3 { margin: 1.5rem; }
.m-4 { margin: 2rem; }
.m-5 { margin: 3rem; }
.m-6 { margin: 4rem; }
.m-8 { margin: 6rem; }

.p-0 { padding: 0; }
.p-1 { padding: 0.5rem; }
.p-2 { padding: 1rem; }
.p-3 { padding: 1.5rem; }
.p-4 { padding: 2rem; }
.p-5 { padding: 3rem; }
.p-6 { padding: 4rem; }
.p-8 { padding: 6rem; }

/* Directional spacing */
.mt-4 { margin-top: 2rem; }
.mb-4 { margin-bottom: 2rem; }
.ml-4 { margin-left: 2rem; }
.mr-4 { margin-right: 2rem; }

.pt-4 { padding-top: 2rem; }
.pb-4 { padding-bottom: 2rem; }
.pl-4 { padding-left: 2rem; }
.pr-4 { padding-right: 2rem; }
```

---
## 6. Navigation & Interface Components

### Main Navigation
```css
.navbar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    width: 100%;
    z-index: 1000;
    transition: all 0.3s ease;
}

.navbar.scrolled {
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
```

### Breadcrumbs
```css
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.breadcrumb-item::after {
    content: '/';
    margin-left: 0.5rem;
    color: var(--text-tertiary);
}

.breadcrumb-item:last-child::after {
    content: '';
}

.breadcrumb-item.active {
    color: var(--gauteng-blue-primary);
    font-weight: 500;
}
```

### Badges & Status Indicators
```css
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-primary {
    background: var(--gauteng-blue-primary);
    color: white;
}

.badge-success {
    background: var(--success-green);
    color: white;
}

.badge-warning {
    background: var(--warning-orange);
    color: white;
}

.badge-error {
    background: var(--error-red);
    color: white;
}

.badge-discovery {
    background: var(--scibono-orange);
    color: white;
}
```

---

## 7. Animation & Interaction Guidelines

### Transition Standards
```css
:root {
    --transition-fast: 0.15s ease;
    --transition-base: 0.3s ease;
    --transition-slow: 0.6s ease;
    --transition-spring: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.transition-all {
    transition: all var(--transition-base);
}

.transition-colors {
    transition: background-color var(--transition-base), 
                border-color var(--transition-base), 
                color var(--transition-base);
}

.transition-transform {
    transition: transform var(--transition-base);
}
```

### Hover Effects
```css
.hover-lift {
    transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.hover-glow {
    transition: box-shadow var(--transition-base);
}

.hover-glow:hover {
    box-shadow: 0 0 30px rgba(30, 75, 140, 0.3);
}

.hover-scale:hover {
    transform: scale(1.05);
}
```

### Loading Animations
```css
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes fadeInUp {
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-fadeInUp {
    animation: fadeInUp 0.6s ease-out;
}
```

---

## 8. Icon System & Visual Elements

### Icon Guidelines
- **Style**: Outline icons with 2px stroke weight
- **Library**: Lucide React or Heroicons for consistency
- **Sizes**: 16px, 20px, 24px, 32px, 48px, 64px
- **Colors**: Inherit from parent or use brand colors

```css
.icon {
    width: 1em;
    height: 1em;
    display: inline-block;
    vertical-align: middle;
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
}

.icon-sm { width: 1rem; height: 1rem; }
.icon-md { width: 1.5rem; height: 1.5rem; }
.icon-lg { width: 2rem; height: 2rem; }
.icon-xl { width: 3rem; height: 3rem; }
```

### Logo Usage Guidelines

#### **Primary Logo Combination**
- SciBOTICS branding with both partner logos
- Minimum clear space: 1x logo height on all sides
- Minimum size: 120px width for digital, 1 inch for print

#### **Logo Colors**
- **Primary**: Full color on white or light backgrounds
- **Reversed**: White version on dark backgrounds
- **Monochrome**: Gauteng blue for single-color applications

---

## 9. Responsive Design Framework

### Breakpoint System
```css
/* Mobile First Approach */
:root {
    --breakpoint-xs: 0px;
    --breakpoint-sm: 480px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 1024px;
    --breakpoint-xl: 1200px;
    --breakpoint-xxl: 1440px;
}

@media (min-width: 480px) { /* sm */ }
@media (min-width: 768px) { /* md */ }
@media (min-width: 1024px) { /* lg */ }
@media (min-width: 1200px) { /* xl */ }
@media (min-width: 1440px) { /* xxl */ }
```

### Mobile-First Principles
- **Touch targets**: Minimum 44px × 44px
- **Typography**: Scales appropriately across devices
- **Navigation**: Collapsible mobile menu
- **Images**: Responsive with proper aspect ratios
- **Forms**: Single-column layout on mobile

---
## 10. Accessibility Standards

### WCAG 2.1 AA Compliance
- **Color contrast**: 4.5:1 for normal text, 3:1 for large text
- **Focus indicators**: Visible and high contrast
- **Keyboard navigation**: All interactive elements accessible
- **Screen readers**: Semantic HTML and ARIA labels

```css
.focus-visible {
    outline: 2px solid var(--gauteng-blue-primary);
    outline-offset: 2px;
    border-radius: 4px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
```

---

## 11. Content Guidelines

### Voice & Tone
- **Educational**: Clear, informative, and instructional
- **Inspiring**: Motivational and encouraging
- **Professional**: Authoritative yet approachable
- **Inclusive**: Welcoming to all skill levels and backgrounds

### Writing Style
- **Headlines**: Action-oriented and benefit-focused
- **Body text**: Scannable with bullet points and short paragraphs
- **Calls-to-action**: Clear, specific, and compelling
- **Error messages**: Helpful and solution-oriented

---

## 12. Implementation Guidelines

### CSS Architecture
```css
/* Use CSS Custom Properties for consistency */
:root {
    /* All design tokens defined here */
}

/* Component-based organization */
.component-name {
    /* Base styles */
}

.component-name__element {
    /* Element styles */
}

.component-name--modifier {
    /* Modifier styles */
}
```

### Development Standards
- **CSS**: BEM methodology for naming
- **JavaScript**: ES6+ with modern frameworks
- **Performance**: Optimize for Core Web Vitals
- **Testing**: Cross-browser compatibility
- **Accessibility**: Test with screen readers

This comprehensive style guide ensures consistency across the entire GDE SciBOTICS platform while honoring both partner organizations' brand identities and maintaining professional educational standards.
