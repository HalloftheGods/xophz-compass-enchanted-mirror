# Xophz Enchanted Mirror

> **Category:** True North · **Version:** 0.0.1

A competitive benchmarking and performance analytics tool that helps peer into how your site's audience fares against other sites side-by-side.

## Description

**Enchanted Mirror** (Magic Mirror) is an SEO, performance, and accessibility benchmarking tool designed to gamify website optimization. Instead of viewing analytics in a vacuum, it lets users directly compare their site's "reflection" against their fiercest competitors.

### Core Capabilities

- **Competitor Comparison** – Input 1–3 rival URLs for side-by-side benchmarking.
- **Core Web Vitals** – LCP, FID, and CLS scores via PageSpeed Insights API integration.
- **"Fairest" Designation** – The winning site earns the crown in a visual ranking.
- **Actionable Insights** – Clear-English recommendations highlighting where competitors are winning.
- **Historical Tracking** – Trend charts showing whether you're gaining or losing ground over time.

### Planned Features

- SEO comparison (meta tags, heading structure, keyword presence)
- Accessibility comparison (ARIA, contrast ratios)
- Security headers check
- Automated weekly checks with notifications

## Requirements

- **Xophz COMPASS** parent plugin (active)
- WordPress 5.8+, PHP 7.4+

## Installation

1. Ensure **Xophz COMPASS** is installed and active.
2. Upload `xophz-compass-enchanted-mirror` to `/wp-content/plugins/`.
3. Activate through the Plugins menu.
4. Access via the My Compass dashboard → **Enchanted Mirror**.

## Frontend Routes

| Route | View | Description |
|---|---|---|
| `/enchanted-mirror` | Dashboard | Competitor comparison matrix with scores and insights |

## Status

🔴 Concept defined — awaiting full implementation.

## Changelog

### 0.0.1

- Initial scaffolding with plugin bootstrap and COMPASS integration
