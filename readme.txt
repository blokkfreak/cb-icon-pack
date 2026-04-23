=== Creativebowl Icon-Pack ===
Contributors: creativebowl
Tags: elementor, icons, svg, icon-pack, custom-icons
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom SVG Icon Pack for Elementor Pro — 1681 Line Icons + 1033 Solid Icons across 78/52 categories.

== Description ==

Creativebowl Icon-Pack integrates a curated collection of SVG icons into Elementor Pro's
native icon picker — available in two separate tabs, **CB Line Icons** and **CB Solid Icons**.
Icons are rendered as inline SVG, supporting full color control through Elementor's color
picker and CSS `currentColor` inheritance.

= Features =

* **1681 line icons** across 78 categories + **1033 solid icons** across 52 categories
* **Two separate tabs** in Elementor's icon picker (Line + Solid)
* **Inline SVG rendering** (not font-based) for crisp display at any size
* **Full color control** via Elementor's color picker
* **CSS currentColor** support — icons inherit text color automatically
* **Auto-updates** via GitHub — updates appear in the WordPress dashboard
* Compatible with **Elementor Pro 3.x / 4.x** (2025/2026)

= How It Works =

1. **Editor (icon picker):** Icons are displayed using CSS `mask-image` so they
   respect the editor's UI colors and remain lightweight.
2. **Frontend (published page):** Icons are rendered as inline `<svg>` elements
   with `fill="currentColor"`, inheriting color from Elementor's style controls.

== Installation ==

1. Upload the `cb-icon-pack` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Ensure **Elementor Pro** is installed and activated
4. Open the Elementor editor, click any icon control — select **CB Line Icons** or **CB Solid Icons**

== Changelog ==

= 2.0.0 =
* Line + Solid icon sets as separate tabs in Elementor picker
* 1681 line icons (78 categories) + 1033 solid icons (52 categories)
* Auto-updates via GitHub releases
* mask-mode: alpha for consistent cross-browser picker rendering
* Automatic cache-busting via file modification timestamps

= 1.0.0 =
* Initial release
* 1890 SVG line icons across 86 categories
* Inline SVG rendering with currentColor support
* Elementor Pro icon picker integration
