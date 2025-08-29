import Chart  from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import ChartjsPluginStacked100 from "chartjs-plugin-stacked100";

import {chartJSColorPalette, hexToRgba, getChartLabels, getDataSets } from './chart-js-adapter/helpers'
import SqrtScale from './chart-js-adapter/plugin-squrt-scale'
import SymlogScale from './chart-js-adapter/plugin-symlog-scale'
import PowScale from './chart-js-adapter/plugin-pow-scale'
import canvasBackgroundPlugin from "./chart-js-adapter/plugin-canvas-background";
import plotAreaBackgroundPlugin from "./chart-js-adapter/plugin-plot-area-background";
import downloadChartImagePlugin from "./chart-js-adapter/plugin-download-chart-image";
import {isNumber} from "chart.js/helpers";

let chartJS;

function parseChartJSData ( rawData, rawConfig, extraConfig ) {

	console.log(rawConfig);
	// Extract all unique labels dynamically.
	const labels = getChartLabels( rawData );

	const colorPalette = chartJSColorPalette[rawConfig.graph.palette];

	// Generate datasets dynamically.
	const datasets = getDataSets( rawData, labels, colorPalette, extraConfig );

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
				beginAtZero: true,
				//type: 'category',
				//type:  'log' == rawConfig.scale.type ? 'logarithmic' : rawConfig.scale.type,
				...( extraConfig.isStacked && { stacked: extraConfig.isStacked } ),
				...( extraConfig.stepped && { beginAtZero: true } ), // For steped up bar chart.

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
				...( extraConfig.isStacked && { stacked: extraConfig.isStacked } ),
				beginAtZero: true,
				//offset: true,
				//stacked: true,

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
					style: rawConfig.caption.style
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
					family: rawConfig.label.fontfamily,
					size: rawConfig.label.fontsize,
				},
				formatter: function( value, context ) {
					if ( isNumber( value ) && rawConfig.label.precision && value % 1 !== 0 ) {
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
			},
			stacked100: {
				enable: 'PercentBar' === rawConfig.graph.chartType
			},

			downloadChartImagePlugin: {
				enable: 1 === rawConfig.meta.isDownloadable ? true : false,
				buttonText: rawConfig.meta.downloadLabel,
				buttonColor: '#3932FE',
				fontSize: 14,
				filename: 'my_chart.png'
			},
		}
	}


	return {labels: labels, datasets: datasets, options: options};
};

export default function chartJs( chartSelector, ec_chart_data ) {
	let chartType = ec_chart_data.chart_type;
	let extraConfig = {};
	let chartDataset = ec_chart_data.chart_data;
	let chartConfiguration = ec_chart_data.chart_configuration;

	switch ( ec_chart_data.chart_type ) {
	case 'Bar':
		chartType = 'bar';
		break;
	case 'Waterfall':
		chartType = 'bar';
		extraConfig['chartType'] = 'Waterfall';
		extraConfig['isStacked'] = true;
		break;
	case 'Pie':
		chartType = 'pie';
		extraConfig['chartType'] = 'Pie';
		break;
	case 'Donut':
		chartType = 'doughnut';
		extraConfig['chartType'] = 'Pie'; // dataset is currently kept same as pie chart.
		break;
	case 'StepUpBar':
		chartType = 'bar';
		extraConfig['chartType'] = 'StepUpBar';
		extraConfig['isStacked'] = true;
		break;
	case 'StackedBar':
		chartType = 'bar';
		extraConfig['isStacked'] = true;
		break;
	case 'PercentBar':
		chartType = 'bar';
		break;
	case 'Area':
		chartType = 'line';
		extraConfig['chartType'] = 'Area';
		extraConfig['fill'] = true;
		extraConfig['tension'] = 0.4;
		break;
	case 'PolarArea':
		chartType = 'polarArea';
		extraConfig['chartType'] = 'PolarArea';
		extraConfig['fill'] = true;
		extraConfig['tension'] = 0.4;
		break;
	case 'PercentArea':
		chartType = 'line';
		extraConfig['chartType'] = 'PercentArea';
		extraConfig['fill'] = true;
		extraConfig['isStacked'] = true;
		break;
	case 'Line':
		chartType = 'line';
		extraConfig['chartType'] = 'Line';
		extraConfig['tension'] = 0.4;
		break;
	case 'StackedArea':
		chartType = 'line';
		extraConfig['chartType'] = 'StackedArea';
		extraConfig['fill'] = true;
		extraConfig['isStacked'] = true;
		extraConfig['tension'] = 0.4;
		break;
	}

	let chartJSData = parseChartJSData( chartDataset, chartConfiguration, extraConfig );

	// Register the custom scales plugins.
	Chart.register( SqrtScale );
	Chart.register( PowScale );
	Chart.register( SymlogScale );

	// Register custom background color plugins.
	Chart.register( canvasBackgroundPlugin );
	Chart.register( plotAreaBackgroundPlugin );

	Chart.register( downloadChartImagePlugin );

	// Register custom chart plugins.
	//Chart.register(stepUpBar);

	// Register ChartDataLabels plugin.
	Chart.register( ChartDataLabels );
	Chart.register( ChartjsPluginStacked100 );


	// Change default options for ALL charts.
	Chart.defaults.set( 'plugins.datalabels', { anchor: 'center' } );

	chartJS = new Chart(
		document.querySelector( chartSelector ),
		{
			type: chartType,
			data: {
				labels: chartJSData.labels,
				datasets: chartJSData.datasets
			},
			options: chartJSData.options,
			plugins: chartJSData.plugins
		}
	);


	if ( true != ec_chart_data.chart_configuration.graph.responsive ) {


		chartJS.resize( ec_chart_data.chart_configuration.dimension.width,ec_chart_data.chart_configuration.dimension.height );
	}

	// Responsive resize handler (debounced).
	let resizeTimeout;
	window.addEventListener( 'resize', () => {
		clearTimeout( resizeTimeout );
		resizeTimeout = setTimeout( () => {
			chartJS.resize();
		}, 2500 );
	} );
}