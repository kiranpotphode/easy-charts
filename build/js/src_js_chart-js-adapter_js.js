"use strict";
(self["webpackChunkeasy_charts"] = self["webpackChunkeasy_charts"] || []).push([["src_js_chart-js-adapter_js"],{

/***/ "./src/js/chart-js-adapter.js":
/*!************************************!*\
  !*** ./src/js/chart-js-adapter.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ chartJs)
/* harmony export */ });
/* harmony import */ var chart_js_auto__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! chart.js/auto */ "./node_modules/chart.js/auto/auto.js");
/* harmony import */ var chartjs_plugin_datalabels__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! chartjs-plugin-datalabels */ "./node_modules/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.esm.js");
/* harmony import */ var chartjs_plugin_stacked100__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! chartjs-plugin-stacked100 */ "./node_modules/chartjs-plugin-stacked100/build/index.js");
/* harmony import */ var chartjs_plugin_stacked100__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(chartjs_plugin_stacked100__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./chart-js-adapter/helpers */ "./src/js/chart-js-adapter/helpers.js");
/* harmony import */ var _chart_js_adapter_plugin_squrt_scale__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./chart-js-adapter/plugin-squrt-scale */ "./src/js/chart-js-adapter/plugin-squrt-scale.js");
/* harmony import */ var _chart_js_adapter_plugin_symlog_scale__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./chart-js-adapter/plugin-symlog-scale */ "./src/js/chart-js-adapter/plugin-symlog-scale.js");
/* harmony import */ var _chart_js_adapter_plugin_pow_scale__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./chart-js-adapter/plugin-pow-scale */ "./src/js/chart-js-adapter/plugin-pow-scale.js");
/* harmony import */ var _chart_js_adapter_plugin_canvas_background__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./chart-js-adapter/plugin-canvas-background */ "./src/js/chart-js-adapter/plugin-canvas-background.js");
/* harmony import */ var _chart_js_adapter_plugin_plot_area_background__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./chart-js-adapter/plugin-plot-area-background */ "./src/js/chart-js-adapter/plugin-plot-area-background.js");
/* harmony import */ var _chart_js_adapter_plugin_download_chart_image__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./chart-js-adapter/plugin-download-chart-image */ "./src/js/chart-js-adapter/plugin-download-chart-image.js");
/* harmony import */ var chart_js_helpers__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! chart.js/helpers */ "./node_modules/chart.js/helpers/helpers.js");













let chartJS;

function parseChartJSData ( rawData, rawConfig, extraConfig ) {

	console.log(rawConfig);
	// Extract all unique labels dynamically.
	const labels = (0,_chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.getChartLabels)( rawData );

	const colorPalette = _chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.chartJSColorPalette[rawConfig.graph.palette];

	// Generate datasets dynamically.
	const datasets = (0,_chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.getDataSets)( rawData, labels, colorPalette, extraConfig );

	const scaleGrid = {
		tickColor: rawConfig.axis.strokecolor,
		color: (0,_chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.hexToRgba)( rawConfig.axis.strokecolor, rawConfig.axis.opacity )
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
				color: (0,_chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.hexToRgba)( rawConfig.frame.bgcolor, rawConfig.graph.opacity )
			},

			customPlotAreaBackgroundColor: {
				color: (0,_chart_js_adapter_helpers__WEBPACK_IMPORTED_MODULE_3__.hexToRgba)( rawConfig.graph.bgcolor,rawConfig.graph.opacity )
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
					if ( (0,chart_js_helpers__WEBPACK_IMPORTED_MODULE_10__.isNumber)( value ) && rawConfig.label.precision && value % 1 !== 0 ) {
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
				enable: 'PercentBar' === extraConfig.chartType || 'PercentArea' === extraConfig.chartType
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

function chartJs( chartSelector, ec_chart_data ) {
	let chartType = ec_chart_data.chart_type;
	let extraConfig = {};
	let chartDataset = ec_chart_data.chart_data;
	let chartConfiguration = ec_chart_data.chart_configuration;

	extraConfig['opacity'] = chartConfiguration.graph.opacity ? parseFloat( chartConfiguration.graph.opacity ) : 1;

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
		extraConfig['chartType'] = 'PercentBar';
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
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_squrt_scale__WEBPACK_IMPORTED_MODULE_4__["default"] );
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_pow_scale__WEBPACK_IMPORTED_MODULE_6__["default"] );
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_symlog_scale__WEBPACK_IMPORTED_MODULE_5__["default"] );

	// Register custom background color plugins.
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_canvas_background__WEBPACK_IMPORTED_MODULE_7__["default"] );
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_plot_area_background__WEBPACK_IMPORTED_MODULE_8__["default"] );

	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( _chart_js_adapter_plugin_download_chart_image__WEBPACK_IMPORTED_MODULE_9__["default"] );

	// Register custom chart plugins.
	//Chart.register(stepUpBar);

	// Register ChartDataLabels plugin.
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( chartjs_plugin_datalabels__WEBPACK_IMPORTED_MODULE_1__["default"] );
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].register( (chartjs_plugin_stacked100__WEBPACK_IMPORTED_MODULE_2___default()) );


	// Change default options for ALL charts.
	chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"].defaults.set( 'plugins.datalabels', { anchor: 'center' } );

	chartJS = new chart_js_auto__WEBPACK_IMPORTED_MODULE_0__["default"](
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

	return chartJS;
}

/***/ }),

/***/ "./src/js/chart-js-adapter/helpers.js":
/*!********************************************!*\
  !*** ./src/js/chart-js-adapter/helpers.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   chartJSColorPalette: () => (/* binding */ chartJSColorPalette),
/* harmony export */   getChartLabels: () => (/* binding */ getChartLabels),
/* harmony export */   getDataSets: () => (/* binding */ getDataSets),
/* harmony export */   hexToRgba: () => (/* binding */ hexToRgba)
/* harmony export */ });
const chartJSColorPalette = {
	'Default': ['#00BBC9', '#EC63AB', '#AA8AE4', '#83CE44', '#ff8f25', '#009EAA', '#CA4F7F', '#9C70C0', '#6BAF3B'],
	'OldDefault' : ['#7E6DA1', '#C2CF30', '#FF8900', '#FE2600', '#E3003F', '#8E1E5F', '#FE2AC2', '#CCF030', '#9900EC', '#3A1AA8', '#3932FE', '#3276FF', '#35B9F6', '#42BC6A', '#91E0CB'],
	'Plain' : ['#B1EB68', '#B1B9B5', '#FFA16C', '#9B64E7', '#CEE113', '#2F9CFA', '#CA6877', '#EC3D8C', '#9CC66D', '#C73640', '#7D9532', '#B064DC' ],
	'Android' : ['#33B5E5', '#AA66CC', '#99CC00', '#FFBB33', '#FF4444', '#0099CC', '#9933CC', '#669900', '#FF8800', '#CC0000'],
	'Soft' : [ '#9ED8D2', '#FFD478', '#F16D9A', '#A8D59D', '#FDC180', '#F05133', '#EDED8A', '#F6A0A5', '#9F218B' ],
	'Simple' : [ '#FF8181', '#FFB081', '#FFE081', '#EFFF81', '#BFFF81', '#90FF81', '#81FFA2', '#81FFD1', '#9681FF', '#C281FF', '#FF81DD' ],
	'Egypt' : [ '#3A3E04','#784818','#FCFCA8','#C03C0C','#F0A830','#A8783C','#FCFCFC','#FCE460','#540C00','#C0C084','#3C303C','#1EA34A','#606C54','#F06048' ],
	'Olive' : [ '#18240C','#3C6C18','#60A824','#90D824','#A8CC60','#789C60','#CCF030','#B4CCA8','#D8F078','#40190D','#E4F0CC' ],
	'Candid' : [ '#AF5E14','#81400C','#E5785D','#FEBFBF','#A66363','#C7B752','#EFF1A7','#83ADB7','#528F98','#BCEDF5','#446B3D','#8BD96F','#E4FFB9' ],
	'Sulphide' : [ '#594440','#0392A7','#FFC343','#E2492F','#007257','#B0BC4A','#2E5493','#7C2738','#FF538B','#A593A1','#EBBA86','#E2D9CA' ],
	'Lint' : ['#A8A878','#F0D89C','#60909C','#242418','#E49C30','#54483C','#306090','#C06C00','#C0C0C0','#847854','#6C3C00','#9C3C3C','#183C60','#FCCC00','#840000','#FCFCFC']
};

function hexToRgba( hex = '#ffffff', opacity = 1 ) {
	// Remove the '#' if it exists
	hex = hex.replace( /^#/, "" );

	// Parse r, g, b values
	let r, g, b;

	if ( hex.length === 3 ) {
		// If shorthand hex (#abc), expand to full (#aabbcc)
		r = parseInt( hex[0] + hex[0], 16 );
		g = parseInt( hex[1] + hex[1], 16 );
		b = parseInt( hex[2] + hex[2], 16 );
	} else if ( hex.length === 6 ) {
		// If full hex (#aabbcc)
		r = parseInt( hex.substring( 0, 2 ), 16 );
		g = parseInt( hex.substring( 2, 4 ), 16 );
		b = parseInt( hex.substring( 4, 6 ), 16 );
	} else {
		throw new Error( "Invalid HEX color." );
	}

	// Ensure opacity is between 0 and 1
	opacity = Math.min( 1, Math.max( 0, opacity ) );

	return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

function getChartLabels( rawData ) {

	// Extract all unique labels dynamically.
	return Array.from(
		new Set( Object.values( rawData ).flat().map( ( entry ) => entry.name ) )
	).sort(); // Sorting for consistency.
}

function getDataSets( rawData, labels, colorPalette, extraConfig ) {
	const legends = Object.keys( rawData );

	return legends.map( ( legend, index ) => {

		if (  ( extraConfig.chartType === 'Waterfall'|| extraConfig.chartType === 'Pie'|| extraConfig.chartType === 'PolarArea' ) && legend !== legends[0] ) {
			return null; // Skip non-first legends
		}

		let data = labels.map(
			( xAxis ) =>
				rawData[legend].find( ( entry ) => entry.name === xAxis )?.value || 0
		);

		let cumulative = 0;

		const ranges = data.map( v => {
			const start = cumulative;
			cumulative += v;
			return [start, cumulative];
		} );

		function toRanges(dataArr) {
			const ranges = [];
			let cumulative = 0;
			dataArr.forEach(value => {
				const start = cumulative;
				cumulative += value;
				ranges.push([start, cumulative]);
			});
			return ranges;
		}


		console.log('legend', legend);
		console.log('converted data', data);

		return {
			label: legend,
			data: extraConfig.chartType === 'Waterfall'  ? ranges : data,
			backgroundColor :  extraConfig.chartType === 'Pie' || extraConfig.chartType === 'PolarArea' ? colorPalette:  hexToRgba( colorPalette[index % colorPalette.length], extraConfig['opacity'] ),
			borderColor : extraConfig.chartType === 'Pie' || extraConfig.chartType === 'PolarArea' ? colorPalette:  colorPalette[index % colorPalette.length],
			fill: extraConfig.fill === true,
			// Apply semi-transparent fill if using an Area chart.
			...(
				extraConfig.chartType === 'Area'
					? { backgroundColor : hexToRgba( colorPalette[index % colorPalette.length], 0.5 ) }
					: {}
			),
			//barPercentage: 0.3,

			// Apply tension if requested.
			...( extraConfig.tension && { tension: 0.4 } ),
			//...(extraConfig.chartType === 'StepUpBar' && { barPercentage: 1/legends.length }  ),

			...( extraConfig.stepped && { stepped: extraConfig.stepped } ), // For setp up bar chart.

		};
	} )
		// Remove any null entries so Chart.js only receives valid datasets.
		.filter( ( dataset ) => dataset !== null );

}

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-canvas-background.js":
/*!*************************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-canvas-background.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const canvasBackgroundPlugin         = {
	id: 'customCanvasBackgroundColor',
	beforeDraw: ( chart, args, options ) => {
		const {ctx}                  = chart;
		ctx.save();
		ctx.globalCompositeOperation = 'destination-over';
		ctx.fillStyle                = options.color ? options.color : '#ffffff';
		ctx.fillRect( 0, 0, chart.width, chart.height );
		ctx.restore();
	}
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (canvasBackgroundPlugin);

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-download-chart-image.js":
/*!****************************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-download-chart-image.js ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const downloadChartImagePlugin = {
	id: 'downloadChartImagePlugin',
	afterDraw(chart, _args, options) {
		if ( ! options.enable ) {
			return;
		}

		const { ctx, canvas } = chart;
		const text = options.buttonText || 'Download Chart';
		const padding = 10;
		const fontSize = options.fontSize || 12;

		ctx.save();
		ctx.font = `${fontSize}px sans-serif`;
		ctx.fillStyle = options.buttonColor || '#007bff';
		const textWidth = ctx.measureText(text).width;
		const x = canvas.width - textWidth - padding;
		const y = canvas.height - padding;

		ctx.fillText(text, x, y);
		ctx.restore();

		// Canvas click listener
		canvas.onclick = (e) => {
			const rect = canvas.getBoundingClientRect();
			const clickX = e.clientX - rect.left;
			const clickY = e.clientY - rect.top;
			if (
				clickX >= x &&
				clickX <= x + textWidth &&
				clickY >= y - fontSize &&
				clickY <= y
			) {
				const url = chart.toBase64Image();
				const link = document.createElement('a');
				link.href = url;
				link.download = options.filename || 'chart.png';
				link.click();
			}
		};
	}
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (downloadChartImagePlugin);

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-plot-area-background.js":
/*!****************************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-plot-area-background.js ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const plotAreaBackgroundPlugin = {
	id: 'customPlotAreaBackgroundColor',
	beforeDraw: ( chart, args, options ) => {
		const {ctx} = chart;
		const chartArea = chart.chartArea; // Get the axis area
		ctx.save();
		//ctx.globalCompositeOperation = 'destination-over';
		//ctx.fillStyle = options.color ? hexToRgba( options.color, options.opacity ) : '#ffffff';
		//ctx.fillRect(0, 0, chart.width, chart.height);
		ctx.fillStyle = options.color ? options.color : '#ffffff'; // Background color.
		ctx.fillRect( chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top );

		ctx.restore();
	}
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (plotAreaBackgroundPlugin);

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-pow-scale.js":
/*!*****************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-pow-scale.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var chart_js_auto__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! chart.js/auto */ "./node_modules/chart.js/auto/auto.js");


class PowScale extends chart_js_auto__WEBPACK_IMPORTED_MODULE_0__.Scale {
	static id = 'pow';

	constructor( cfg ) {
		super( cfg );
		this._startValue = undefined;
		this._valueRange = 0;
		this.power = cfg.power || 2; // default exponent.
	}

	parse( raw, index ) {
		const val = chart_js_auto__WEBPACK_IMPORTED_MODULE_0__.Chart.LinearScale.prototype.parse.call( this, raw, index );
		return isFinite( val ) ? val : null;
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax( true );
		this.min = isFinite( min ) ? min : 0;
		this.max = isFinite( max ) ? max : 0;
	}

	buildTicks() {
		const ticks = [];
		const start = Math.pow( this.min, 1 / this.power );
		const end = Math.pow( this.max, 1 / this.power );
		const step = ( end - start ) / 5;

		for ( let r = start; r <= end; r += step ) {
			ticks.push( { value: r ** this.power } );
		}
		this.min = ticks[0]?.value ?? this.min;
		this.max = ticks[ticks.length - 1]?.value ?? this.max;
		return ticks;
	}

	configure() {
		super.configure();
		this._startValue = Math.pow( this.min, 1 / this.power );
		this._valueRange = Math.pow( this.max, 1 / this.power ) - this._startValue;
	}

	getPixelForValue( value ) {
		const transformed = Math.pow( value, 1 / this.power );
		const ratio = ( transformed - this._startValue ) / this._valueRange;
		return this.getPixelForDecimal( ratio );
	}

	getValueForPixel( pixel ) {
		const ratio = this.getDecimalForPixel( pixel );
		const val = this._startValue + ratio * this._valueRange;
		return val ** this.power;
	}
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PowScale);

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-squrt-scale.js":
/*!*******************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-squrt-scale.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var chart_js_auto__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! chart.js/auto */ "./node_modules/chart.js/auto/auto.js");


class SqrtScale extends chart_js_auto__WEBPACK_IMPORTED_MODULE_0__.Scale {
	// Set a unique ID.
	static id = 'sqrt';

	parse( raw ) {
		// Use linear parsing—validate positive values if needed.
		return chart_js_auto__WEBPACK_IMPORTED_MODULE_0__.Chart.LinearScale.prototype.parse.call( this, raw );
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax( true );
		this.min           = min >= 0 ? min : 0;
		this.max           = max;
	}

	buildTicks() {
		const ticks = [];
		const min   = Math.sqrt( this.min || 0 );
		const max   = Math.sqrt( this.max || 1 );
		const step  = ( max - min ) / 5;  // customize step as needed.
		for ( let r = min; r <= max; r += step ) {
			ticks.push( { value: r * r } );
		}
		return ticks;
	}

	configure() {
		super.configure();
		this._start = Math.sqrt( this.min );
		this._range = Math.sqrt( this.max ) - this._start;
	}

	getPixelForValue( value ) {
		const v     = Math.sqrt( value );
		const ratio = ( v - this._start ) / this._range;
		return this.getPixelForDecimal( ratio );
	}

	getValueForPixel( pixel ) {
		const ratio = this.getDecimalForPixel( pixel );
		const v     = this._start + ratio * this._range;
		return v * v;
	}
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SqrtScale);

/***/ }),

/***/ "./src/js/chart-js-adapter/plugin-symlog-scale.js":
/*!********************************************************!*\
  !*** ./src/js/chart-js-adapter/plugin-symlog-scale.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var chart_js_auto__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! chart.js/auto */ "./node_modules/chart.js/auto/auto.js");


const symlogTransform = ( value ) => {
	return Math.sign( value ) * Math.log10( Math.abs( value ) / constant + 1 );
};

const symlogInverseTransform = ( value ) => {
	return Math.sign( value ) * ( Math.pow( 10, Math.abs( value ) ) - 1 ) * constant;
};

class SymlogScale extends chart_js_auto__WEBPACK_IMPORTED_MODULE_0__.Scale {
	parse( raw, index ) {
		const value = super.parse( raw, index );
		return symlogTransform( value );
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax( true );
		this.min = symlogTransform( min );
		this.max = symlogTransform( max );
		this._startValue = this.min;
		this._valueRange = this.max - this.min;
	}

	getPixelForValue( value ) {
		const symlogValue = symlogTransform( value );
		const decimal = ( symlogValue - this._startValue ) / this._valueRange;
		return this.getPixelForDecimal( decimal );
	}

	getLabelForValue( value ) {
		return symlogInverseTransform( value ).toLocaleString();
	}

	generateTickLabels( ticks ) {
		const minimalDecimalPlaces = Math.max( 0, Math.ceil( -Math.log10(
			Math.min( ...ticks.map( t => Math.abs( t.value - ( ticks[0]?.value || 0 ) ) ) ) || 0
		) ) );
		ticks.forEach( ( tick ) => {
			tick.label = parseFloat( symlogInverseTransform( tick.value )
				.toFixed( minimalDecimalPlaces ) ).toString();
		} );
	}

	buildTicks() {
		const ticks = [];
		const tickCount = 11;
		const min = symlogInverseTransform( this.min );
		const max = symlogInverseTransform( this.max );
		const range = max - min;
		const stepSize = range / ( tickCount - 1 );

		for ( let i = min; i <= max; i += stepSize ) {
			ticks.push( { value: i } );
		}
		return ticks;
	}
}

SymlogScale.id = 'symlog';

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SymlogScale);

/***/ })

}]);
//# sourceMappingURL=src_js_chart-js-adapter_js.js.map