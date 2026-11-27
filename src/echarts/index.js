/**
 * Shared ECharts runtime (script handle: easy-charts-echarts).
 *
 * Registers only the parts of ECharts that Easy Charts uses, so the bundle stays a fraction of
 * the full library, and exposes it as `window.easyChartsECharts` for the front end and the
 * block editor.
 *
 * - SVG renderer: charts on screen (sharp at any zoom, readable by assistive technology).
 * - Canvas renderer: only for the off-screen render behind the PNG download.
 */
import * as echarts from 'echarts/core';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import {
	AriaComponent,
	GridComponent,
	LegendComponent,
	TitleComponent,
	TooltipComponent,
} from 'echarts/components';
import { LabelLayout } from 'echarts/features';
import { CanvasRenderer, SVGRenderer } from 'echarts/renderers';

echarts.use( [
	BarChart,
	LineChart,
	PieChart,
	AriaComponent,
	GridComponent,
	LegendComponent,
	TitleComponent,
	TooltipComponent,
	LabelLayout,
	CanvasRenderer,
	SVGRenderer,
] );

window.easyChartsECharts = echarts;

// Optimisation plugins can load this file after the front-end script (combined, deferred or
// delayed JavaScript); the front end waits for this event in that case.
document.dispatchEvent( new window.CustomEvent( 'easy-charts:echarts-ready' ) );
