import Chart  from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

import {chartJSColorPalette, hexToRgba, getChartLabels, getDataSets } from './chart-js-adapter/helpers'
import SqrtScale from './chart-js-adapter/plugin-squrt-scale'
import SymlogScale from './chart-js-adapter/plugin-symlog-scale'
import PowScale from './chart-js-adapter/plugin-pow-scale'
import canvasBackgroundPlugin from "./chart-js-adapter/plugin-canvas-background";
import plotAreaBackgroundPlugin from "./chart-js-adapter/plugin-plot-area-background"

let chartJS;

function parseChartJSData ( rawData, rawConfig ) {
	// Extract all unique labels dynamically.
	const labels = getChartLabels(rawData);

	const colorPalette = chartJSColorPalette[rawConfig.graph.palette];

	// Generate datasets dynamically.
	const datasets = getDataSets( rawData, labels, colorPalette );

	const scaleGrid = {
		tickColor: rawConfig.axis.strokecolor,
		color: hexToRgba( rawConfig.axis.strokecolor, rawConfig.axis.opacity )
	}
	const scaleTicksStyles = {
		maxTicksLimit: rawConfig.axis.ticks, // Limits the number of x-axis ticks.
		padding: rawConfig.axis.padding,
		display: rawConfig.axis.showtext,
		color: rawConfig.label.strokecolor,
		font: {
			size: rawConfig.label.fontsize,
			family: rawConfig.label.fontfamily,
			weight: rawConfig.label.fontweight,
		},
	};
	const scaleTitleFontStyles = {
		font: {
			size: rawConfig.axis.fontsize,
			family: rawConfig.axis.fontfamily,
			weight: rawConfig.axis.fontweight,
		},
	};
	console.log('responsive', rawConfig.graph.responsive);
	const options = {
		responsive: true === rawConfig.graph.responsive,
		//maintainAspectRatio: true === rawConfig.graph.responsive,
		resizeDelay: 1000,
		indexAxis: 'Horizontal' === rawConfig.graph.orientation ? 'y' : 'x',
		categoryPercentage: 1 - parseFloat( rawConfig.scale.ordinality ),
		layout: {
			padding: {
				top: rawConfig.margin.top,
				right: rawConfig.margin.right,
				bottom: rawConfig.margin.bottom,
				left: rawConfig.margin.left
			}
		},
		scales: {
			y: {
				//beginAtZero: true,
				type:  'log' == rawConfig.scale.type ? 'logarithmic' : rawConfig.scale.type,
				title: {
					display: !!rawConfig.meta.vlabel.length,
					text: [rawConfig.meta.vlabel,rawConfig.meta.vsublabel], // Vertical caption.
					font: {
						size: rawConfig.axis.fontsize,
						family: rawConfig.axis.fontfamily,
						weight: rawConfig.axis.fontweight,
					},
				},
				ticks: {
					//stepSize: 10,
					autoSkip: false,
					maxTicksLimit: rawConfig.axis.ticks,
					major: {
						enabled: true
					},
					...scaleTicksStyles,
					//stepSize: 30, // Adjusts tick intervals instead of major/minor
					/*callback: function(value, index, values) {
						return index % 2 === 0 ? value : ''; // Show every alternate tick
					}*/
				},
				grid: scaleGrid
			},
			x: {
				//beginAtZero: true,
				title: {
					display: !!rawConfig.meta.hlabel.length,
					text: [rawConfig.meta.hlabel,rawConfig.meta.hsublabel], // Horizontal caption.
					font: {
						size: rawConfig.axis.fontsize,
						family: rawConfig.axis.fontfamily,
						weight: rawConfig.axis.fontweight,
					},
				},
				ticks: {
					//stepSize: 10,
					autoSkip: false,
					maxTicksLimit: rawConfig.axis.ticks,
					major: {
						enabled: true
					},
					minor: {
						font: {
							size: 10,
							color: '#888'
						},
					},
					...scaleTicksStyles,
				},
				grid: scaleGrid
			},
		},

		plugins: {
			customCanvasBackgroundColor: {
				color: hexToRgba( rawConfig.frame.bgcolor, rawConfig.graph.opacity )
			},

			customPlotAreaBackgroundColor: {
				color: hexToRgba( rawConfig.graph.bgcolor,rawConfig.graph.opacity )
			},
			title: {
				display: !!rawConfig.meta.caption.length,
				text: rawConfig.meta.caption,
				font: {
					family: rawConfig.caption.fontfamily,
					size: rawConfig.caption.fontsize,
					weight: rawConfig.caption.weight,
					//style: rawConfig.caption.style
				},
				color: rawConfig.caption.strokecolor
			},
			subtitle: {
				display: !!rawConfig.meta.subcaption.length,
				text: rawConfig.meta.subcaption,
				font: {
					family: rawConfig.subCaption.fontfamily,
					size: rawConfig.subCaption.fontsize,
					weight: rawConfig.subCaption.weight,
				},
				color: rawConfig.subCaption.strokecolor
			},
			tooltip: {
				enabled: 1 === rawConfig.tooltip.show,
				callbacks: {
					label: ( context ) => {
						if ( ! rawConfig.label.precision ) {
							return  undefined;
						}
						const formattedNumber = context.raw % 1 === 0 ?  context.raw :  Number( context.raw ).toFixed( rawConfig.label.precision );
						return context.dataset.label + ' : ' + formattedNumber;
					}
				}
			},
			datalabels: {
				display: 1 === rawConfig.label.showlabel,
				color: rawConfig.label.strokecolor,
				font: {
					family: rawConfig.label.fontfamily
				},
				formatter: function( value, context ) {
					if ( rawConfig.label.precision && value % 1 !== 0 ) {
						value = value.toFixed( rawConfig.label.precision );
					}
					return rawConfig.label.prefix + value + rawConfig.label.suffix;
				}
			},
			legend: {
				display: 1 === rawConfig.legend.showlegends,
				position: rawConfig.legend.position,
				labels: {
					boxWidth: rawConfig.legend.symbolsize,
					font: {
						family: rawConfig.legend.fontfamily,
						size: rawConfig.legend.fontsize,
						weight: rawConfig.legend.fontweight,
					},
					color: rawConfig.legend.color // label text color.
				}
			}

		}
	}

	console.log( 'options', options );

	return {labels: labels, datasets: datasets, options: options};
};

export default function chartJs( chartSelector, ec_chart_data ) {
	let chartType = ec_chart_data.chart_type;
	let chartDataset = ec_chart_data.chart_data;
	let chartConfiguration = ec_chart_data.chart_configuration;

	let chartJSData = parseChartJSData( chartDataset, chartConfiguration );
	console.log( chartConfiguration );

	// Register the custom scales plugins.
	Chart.register( SqrtScale );
	Chart.register( PowScale );
	Chart.register( SymlogScale );

	// Register custom background color plugins.
	Chart.register( canvasBackgroundPlugin );
	Chart.register( plotAreaBackgroundPlugin );

	// Register ChartDataLabels plugin.
	Chart.register( ChartDataLabels );

	// Change default options for ALL charts.
	Chart.defaults.set( 'plugins.datalabels', { anchor: 'center' } );

	chartJS = new Chart(
		document.querySelector( chartSelector ),
		{
			type: chartType.toLowerCase(),
			data: {
				labels: chartJSData.labels,
				datasets: chartJSData.datasets
			},
			options: chartJSData.options,
			plugins: chartJSData.plugins
		}
	);

	// Responsive resize handler (debounced).
	let resizeTimeout;
	window.addEventListener('resize', () => {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(() => {
			chartJS.resize();
		}, 2500);
	});
}