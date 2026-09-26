=== Easy Charts ===
Contributors: kiranpotphode
Tags: chart, charts, graph, bar chart, pie chart
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn a table of numbers into a clear, interactive chart. 12 chart types, a Chart block, a shortcode, and a data table for every chart.

== Description ==

Easy Charts turns a table of numbers into an interactive chart that you can place on any post or page.

Version 2.0 draws charts with [Apache ECharts](https://echarts.apache.org/) and adds a Chart block for the block editor. Charts made with Easy Charts 1.x, and the `[easy_chart]` shortcodes that show them, keep working.

### Make a chart in the block editor

Add the **Chart** block and choose how to start:

* **New chart**: pick one of the 12 chart types and start from sample data.
* **Paste data**: copy cells from Excel, Google Sheets or Numbers, or paste CSV.
* **Use an existing chart**: embed a saved chart, or copy its data or its settings into a new one.

Edit the data in a spreadsheet-style table, or paste it as text. Style the chart in the block sidebar: title, size, orientation, axis labels, legend, tooltips, value labels with a prefix and suffix, colours, background and a download button.

### Reusable charts

**Easy Charts → Add New Chart** saves a chart that you can show in many places, with the Chart block ("Use an existing chart") or with its shortcode, for example `[easy_chart chart_id="123"]`. The shortcode also works in the Classic Editor and in widgets. Change the chart once and every page that shows it is updated.

### Site-wide chart settings

**Easy Charts → Settings** sets the defaults for every chart on the site: colours, legend, tooltips, value labels, size and more. A chart's own settings override them.

### 12 chart types

* Bar, stacked bar, percent bar and step-up bar
* Line
* Area, stacked area and percent area
* Pie, donut and polar area
* Waterfall

Every chart type uses the same data table, so you can switch types without changing the data. Bar, line, area and waterfall charts can be drawn vertically or horizontally.

### Accessible

* Every chart comes with its data as a table: screen readers read it, visitors without JavaScript see it, and search engines can index it.
* Each chart has a short text description, and animations are turned off for visitors who prefer reduced motion.
* The editor works with the keyboard. Charts, the editor and the settings page are tested with axe against WCAG 2.2 AA.

### Download as PNG

Turn on a download button to let visitors save a chart as a PNG image.

### Light on your pages

Chart scripts load only on pages that show a chart. Easy Charts adds no database tables: saved charts are posts, block charts live in the block, and site-wide settings are one option.

### Upgrading from 1.x

Updating keeps all your charts and shortcodes. Nothing is changed in the database when you update: each 1.x chart is converted when it is shown or opened. A chart is saved in the 2.0 format only when you edit and save it, and its 1.x data is kept as it was.

Charts look a little different, because 2.0 draws them with Apache ECharts instead of uvCharts. Charts that used the 1.x "Default" colours follow the site-wide colours from **Easy Charts → Settings**.

**Options that 2.0 no longer has:** fonts, margins, axis tick, opacity and line settings, logarithmic, power and square-root scales, curved lines, area opacity, donut hole size, plot background, axis sub-labels, legend type, start and symbol size, and tooltip format.

**Behaviour changes:**

* Private charts are shown only to people who are allowed to read them. 1.x showed them to every visitor.
* Empty cells are drawn as gaps. 1.x drew them as 0.
* Numbers written with thousands separators, such as "1,234", are read as text. 1.x plotted them as 1.
* The Easy Charts button in the Classic Editor toolbar is gone. Use the shortcode, shown in the "Use this chart" panel when you edit a chart.
* A chart that has no data shows nothing to visitors. People who can edit it see a notice with a link to fix it.

**For developers:**

* The uvCharts, D3, canvg and FileSaver scripts and the `Easy_Charts` class are removed.
* The `easy_charts_render_chart` filter is kept. It now filters the ECharts chart markup.
* `easy_charts_get_chart_configurations` is still applied when a 1.x chart is converted.
* `easy_charts_get_chart_data` is deprecated, because the uvCharts data it filtered is no longer used. Use `easy_charts_convert_v1_chart` to change a converted chart.
* The `easy_charts_fonts` and `easy_chart_dropdown` filters are removed.

**Going back to 1.2.5:** charts you haven't edited in 2.0 are unchanged. Charts you edited and saved in 2.0 go back to how they were before those edits, because their 1.x data was kept. Charts created in 2.0, and Chart blocks, don't show in 1.x.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New**, search for "Easy Charts", then install and activate it.
2. Add a **Chart** block to a post or page, or go to **Easy Charts → Add New Chart** to make a reusable chart.

Easy Charts needs WordPress 6.6 or later and PHP 7.4 or later.

== Frequently Asked Questions ==

= How do I show a chart in a post or page? =

In the block editor, add a Chart block. To show a chart saved under Easy Charts, choose "Use an existing chart" and then "Embed". Anywhere else, use the chart's shortcode from the "Use this chart" panel on the chart's edit screen.

= Can I use Easy Charts with the Classic Editor? =

Yes. Charts are made in the block editor, and you place them in classic content with their shortcode, for example `[easy_chart chart_id="123"]`.

= What happens to my 1.x charts? =

They keep working. See "Upgrading from 1.x" above.

= Where is my data stored? =

Saved charts are posts of the `easy_charts` type, with the chart in post meta. Charts made in a Chart block are stored in that block. Site-wide settings are stored in the `easy_charts_settings` option. Easy Charts creates no database tables.

= Does the chart work without JavaScript? =

Visitors without JavaScript see the chart's data as a table.

= Where is the source code? =

The plugin includes its JavaScript source in the `src` folder, next to the built files in `build`. Development happens on [GitHub](https://github.com/kiranpotphode/easy-charts). To build it yourself, run `npm ci` and `npm run build`, then `composer install --no-dev`.

== Screenshots ==

1. The Chart block: start a new chart, paste data or use an existing chart.
2. Editing chart data in the table.
3. Chart settings in the block sidebar.
4. Site-wide chart settings.
5. Charts on a page.

== Third-party libraries ==

Easy Charts includes these libraries, all under licences compatible with the GPL:

* [Apache ECharts](https://echarts.apache.org/) 6.1.0: Apache License 2.0
* [ZRender](https://github.com/ecomfe/zrender) 6.1.0: BSD 3-Clause
* [tslib](https://github.com/microsoft/tslib) 2.3.0: 0BSD
* [Jspreadsheet CE](https://github.com/jspreadsheet/ce) 5.0.4: MIT (editor only)
* [jSuites](https://github.com/jsuites/jsuites) 5.13.5: MIT (editor only)

== Changelog ==

= 2.0.0-beta.1 =

* New: charts are drawn with Apache ECharts.
* New: Chart block with a setup wizard, a spreadsheet-style data editor, paste from spreadsheets or CSV, and a settings sidebar with live preview.
* New: embed a saved chart in the Chart block, or copy its data or settings.
* New: site-wide chart settings under Easy Charts → Settings.
* New: saved charts are edited in the block editor, with a "Use this chart" panel showing the shortcode.
* New: every chart comes with a data table for screen readers, visitors without JavaScript and search engines.
* New: horizontal orientation for line, area, step-up and waterfall charts, and a custom chart title.
* Changed: 1.x charts are converted when shown or opened, and stored in the 2.0 format only when saved. Their 1.x data is kept.
* Removed: uvCharts and its scripts, the 1.x chart editor, the Classic Editor toolbar button, and the options listed under "Upgrading from 1.x".
* Security: chart data is validated and escaped on output. The 1.x admin-ajax handlers are removed.
* Requires WordPress 6.6 and PHP 7.4.

= 1.2.5 =
*Release Date - 25th Aug, 2025*

* Replace handsontable with jspreadsheet js

= 1.2.4 =
*Release Date - 18th Feb, 2025*

* Add Sanitization for shortcode attributes.

= 1.2.3 =
*Release Date - 4th May, 2024*

* PHPCS coding standard fixes.

= 1.2.2 =
*Release Date - 6th May, 2023*

* Fix Deprecation warnings.

= 1.2.1 =
*Release Date - 20th September, 2018*

* PHPCS coding standard fixes.

= 1.2 =
*Release Date - 10th June, 2017*

* Update the charts library.
* New color palletes.

= 1.1 =
*Release Date - 4th September, 2016*

* Support added for Legend configuration.
* Code refactor to match coding standards.

= 1.0.3 =
*Release Date - 18th April, 2016*

* Support added for changing output using filters.
* Duplicate files moved to common folder.

= 1.0.2 =
*Release Date - 28th January, 2016*

* Fonts added in chart configuration.
* Visual Editor button - chart title added for blank chart title.

= 1.0.1 =
*Release Date - 25th January, 2016*

* Broken shortcode bug fixed.
* Download responsive chart bug fixed.
* Load scripts on  shortcode screen only.

= 1.0.0 =
* Plugin released.

== Upgrade Notice ==

= 2.0.0 =
Major update: charts are drawn with Apache ECharts and there is a new Chart block. Existing charts and shortcodes keep working, but some 1.x styling options are gone. See "Upgrading from 1.x" before updating.
