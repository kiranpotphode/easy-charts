// phpcs:disable
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

document.addEventListener( 'DOMContentLoaded', function () {
	let chartJS;

	class SqrtScale extends Chart.Scale {
		// Set a unique ID
		static id = 'sqrt';

		parse(raw) {
			// Use linear parsing—validate positive values if needed
			return Chart.LinearScale.prototype.parse.call(this, raw);
		}

		determineDataLimits() {
			const { min, max } = this.getMinMax(true);
			this.min = min >= 0 ? min : 0;
			this.max = max;
		}

		buildTicks() {
			const ticks = [];
			const min = Math.sqrt(this.min || 0);
			const max = Math.sqrt(this.max || 1);
			const step = (max - min) / 5;  // customize step as needed
			for (let r = min; r <= max; r += step) {
				ticks.push({ value: r * r });
			}
			return ticks;
		}

		configure() {
			super.configure();
			this._start = Math.sqrt(this.min);
			this._range = Math.sqrt(this.max) - this._start;
		}

		getPixelForValue(value) {
			const v = Math.sqrt(value);
			const ratio = (v - this._start) / this._range;
			return this.getPixelForDecimal(ratio);
		}

		getValueForPixel(pixel) {
			const ratio = this.getDecimalForPixel(pixel);
			const v = this._start + ratio * this._range;
			return v * v;
		}
	}
	const constant = 1;

	const symlogTransform = (value) => {
		return Math.sign(value) * Math.log10(Math.abs(value) / constant + 1);
	};

	const symlogInverseTransform = (value) => {
		return Math.sign(value) * (Math.pow(10, Math.abs(value)) - 1) * constant;
	};

	class SymlogScale extends Chart.Scale {
		parse(raw, index) {
			const value = super.parse(raw, index);
			return symlogTransform(value);
		}

		determineDataLimits() {
			const { min, max } = this.getMinMax(true);
			this.min = symlogTransform(min);
			this.max = symlogTransform(max);
			this._startValue = this.min;
			this._valueRange = this.max - this.min;
		}

		getPixelForValue(value) {
			const symlogValue = symlogTransform(value);
			const decimal = (symlogValue - this._startValue) / this._valueRange;
			return this.getPixelForDecimal(decimal);
		}

		getLabelForValue(value) {
			return symlogInverseTransform(value).toLocaleString();
		}

		generateTickLabels(ticks) {
			const minimalDecimalPlaces = Math.max(0, Math.ceil(-Math.log10(
				Math.min(...ticks.map(t => Math.abs(t.value - (ticks[0]?.value || 0)))) || 0
			)));
			ticks.forEach((tick) => {
				tick.label = parseFloat(symlogInverseTransform(tick.value)
					.toFixed(minimalDecimalPlaces)).toString();
			});
		}

		buildTicks() {
			const ticks = [];
			const tickCount = 11;
			const min = symlogInverseTransform(this.min);
			const max = symlogInverseTransform(this.max);
			const range = max - min;
			const stepSize = range / (tickCount - 1);

			for (let i = min; i <= max; i += stepSize) {
				ticks.push({ value: i });
			}
			return ticks;
		}
	}

	SymlogScale.id = 'symlog';

	class PowScale extends Chart.Scale {
		static id = 'pow';

		constructor(cfg) {
			super(cfg);
			this._startValue = undefined;
			this._valueRange = 0;
			this.power = cfg.power || 2; // default exponent
		}

		parse(raw, index) {
			const val = Chart.LinearScale.prototype.parse.call(this, raw, index);
			return isFinite(val) ? val : null;
		}

		determineDataLimits() {
			const { min, max } = this.getMinMax(true);
			this.min = isFinite(min) ? min : 0;
			this.max = isFinite(max) ? max : 0;
		}

		buildTicks() {
			const ticks = [];
			const start = Math.pow(this.min, 1 / this.power);
			const end = Math.pow(this.max, 1 / this.power);
			const step = (end - start) / 5;

			for (let r = start; r <= end; r += step) {
				ticks.push({ value: r ** this.power });
			}
			this.min = ticks[0]?.value ?? this.min;
			this.max = ticks[ticks.length - 1]?.value ?? this.max;
			return ticks;
		}

		configure() {
			super.configure();
			this._startValue = Math.pow(this.min, 1 / this.power);
			this._valueRange = Math.pow(this.max, 1 / this.power) - this._startValue;
		}

		getPixelForValue(value) {
			const transformed = Math.pow(value, 1 / this.power);
			const ratio = (transformed - this._startValue) / this._valueRange;
			return this.getPixelForDecimal(ratio);
		}

		getValueForPixel(pixel) {
			const ratio = this.getDecimalForPixel(pixel);
			const val = this._startValue + ratio * this._valueRange;
			return val ** this.power;
		}
	}


	function hexToRgba(hex = '#ffffff', opacity = 1) {
		// Remove the '#' if it exists
		hex = hex.replace(/^#/, "");

		// Parse r, g, b values
		let r, g, b;

		if (hex.length === 3) {
			// If shorthand hex (#abc), expand to full (#aabbcc)
			r = parseInt(hex[0] + hex[0], 16);
			g = parseInt(hex[1] + hex[1], 16);
			b = parseInt(hex[2] + hex[2], 16);
		} else if (hex.length === 6) {
			// If full hex (#aabbcc)
			r = parseInt(hex.substring(0, 2), 16);
			g = parseInt(hex.substring(2, 4), 16);
			b = parseInt(hex.substring(4, 6), 16);
		} else {
			throw new Error("Invalid HEX color.");
		}

		// Ensure opacity is between 0 and 1
		opacity = Math.min(1, Math.max(0, opacity));

		return `rgba(${r}, ${g}, ${b}, ${opacity})`;
	}

	function parseChartJSData ( rawData, rawConfig, chartType ) {
		// Extract all unique years dynamically
		const labels = Array.from(
			new Set(Object.values(rawData).flat().map((entry) => entry.name))
		).sort(); // Sorting for consistency

		const colorPalette = chartJSColorPalette[rawConfig.graph.palette];
		// Generate datasets dynamically
		const datasets = Object.keys(rawData).map((legend, index) => ({
			label: legend,
			data: labels.map(
				(xAxis) => rawData[legend].find((entry) => entry.name === xAxis)?.value || 0
			),
			backgroundColor: colorPalette[index % colorPalette.length] // Cycle through color palette.
		}));

		const canvasBackgroundPlugin = {
			id: 'customCanvasBackgroundColor',
			beforeDraw: (chart, args, options) => {
				const {ctx} = chart;
				ctx.save();
				ctx.globalCompositeOperation = 'destination-over';
				ctx.fillStyle = options.color ? hexToRgba( options.color, options.opacity ) : '#ffffff';
				ctx.fillRect(0, 0, chart.width, chart.height);
				ctx.restore();
			}
		};

		const plotAreaBackgroundPlugin = {
			id: 'customPlotAreaBackgroundColor',
			beforeDraw: (chart, args, options) => {
				const {ctx} = chart;
				const chartArea = chart.chartArea; // Get the axis area
				ctx.save();
				//ctx.globalCompositeOperation = 'destination-over';
				//ctx.fillStyle = options.color ? hexToRgba( options.color, options.opacity ) : '#ffffff';
				//ctx.fillRect(0, 0, chart.width, chart.height);
				ctx.fillStyle = options.color ? hexToRgba( options.color, options.opacity ) : '#ffffff'; // Background color
				ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);

				ctx.restore();
			}
		};

		console.log(rawConfig.scale.type);
		switch (rawConfig.scale.type) {
			case 'log':
				//rawConfig.scale.type = 'logarithmic';
				break;
			case 'pow':
				//rawConfig.scale.type = 'linear';
				break;
			case 'pow':
				//rawConfig.scale.type = 'linear';
				break;
			default:
				break;
		}
		const scaleGrid = {
			tickColor: rawConfig.axis.strokecolor,
			color: hexToRgba(rawConfig.axis.strokecolor, rawConfig.axis.opacity)
		}
		const scaleTicksStyles = {
			maxTicksLimit: rawConfig.axis.ticks, // Limits the number of x-axis ticks
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
			maintainAspectRatio: true === rawConfig.graph.responsive,
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
						text: [rawConfig.meta.vlabel,rawConfig.meta.vsublabel], // Vertical caption
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
						text: [rawConfig.meta.hlabel,rawConfig.meta.hsublabel], // Horizontal caption
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
					color: rawConfig.frame.bgcolor,
					opacity: rawConfig.graph.opacity,
				},

				customPlotAreaBackgroundColor: {
					color: rawConfig.graph.bgcolor,
					opacity: rawConfig.graph.opacity,
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
						label: (context) => {
							if ( ! rawConfig.label.precision ) {
								return  undefined;
							}
							const formattedNumber = context.raw % 1 === 0 ?  context.raw :  Number(context.raw).toFixed(rawConfig.label.precision);
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
					formatter: function(value, context) {
						if ( rawConfig.label.precision && value % 1 !== 0 ) {
							value = value.toFixed(rawConfig.label.precision);
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
						color: rawConfig.legend.color // label text color
					}
				}

				/*legend: {
					labels: {
						generateLabels: (chart) => {
							return chart.data.datasets.map(ds => ({
								text: `${ds.label}: $${ds.data[0]}k`,
								fillStyle: ds.backgroundColor,
								strokeStyle: ds.borderColor,
								hidden: false,
								lineDash: [],
								lineDashOffset: 0,
								lineJoin: ds.borderJoinStyle,
								pointStyle: 'circle'
							}));
						}
					}
				}*/
			}
		}

		console.log('options', options);

		return {labels: labels, datasets: datasets, options: options, plugins: [canvasBackgroundPlugin, plotAreaBackgroundPlugin]};
	};
	if (typeof(ec_chart_data) != 'undefined') {
		var graphdef = {
			categories: [],
			dataset: {}
		};

		var chartType = ec_chart_data.chart_type;
		var chartCategories = ec_chart_data.chart_categories;
		var chartDataset = ec_chart_data.chart_data;
		var chartConfiguration = ec_chart_data.chart_configuration;

		graphdef = {
			categories: chartCategories,
			dataset: chartDataset,
		};

		if ( 'ec_chartjs_chart' === ec_chart_data.chart_lib ) {
			let chartJSData = parseChartJSData( chartDataset, chartConfiguration, ec_chart_data.chart_lib );
			console.log(chartConfiguration);


			// Register the custom scale
			Chart.register(SqrtScale);
			Chart.register(PowScale);
			Chart.register(SymlogScale);
			Chart.register(ChartDataLabels);

			// Change default options for ALL charts
			Chart.defaults.set('plugins.datalabels', {

				anchor: 'center'
,			});

			chartJS = new Chart(
				document.querySelector( 'canvas.uv-div-' + ec_chart.chart_id ),
				{
					type: ec_chart_data.chart_type.toLowerCase(),
					data: {
						labels: chartJSData.labels,
						datasets: chartJSData.datasets
					},
					options: chartJSData.options,
					plugins: chartJSData.plugins
				}
			);


			console.log(chartConfiguration.dimension.width,chartConfiguration.dimension.height)
			chartJS.resize(chartConfiguration.dimension.width, chartConfiguration.dimension.height);
		} else {
			var chartObject = uv.chart(chartType, graphdef, chartConfiguration);
		}
		var chartObject = uv.chart(chartType, graphdef, chartConfiguration);

	}

	var jspreadsheetid = document.getElementById("jspreadsheet");
	var data = [
		['', 'Kia', 'Nissan', 'Toyota', 'Honda'],
		['2008', 10, 11, 12, 13],
		['2009', 20, 11, 14, 13],
		['2010', 30, 15, 12, 13]
	];

	if (typeof(ec_chart) != 'undefined') {
		if (ec_chart.chart_data != null) {
			data = ec_chart.chart_data;
		}
	}

	let spreadsheet = jspreadsheet(jspreadsheetid, {
		worksheets: [{
			data: data,
		}],
		tableOverflow: true,
		tableWidth: "200px",
	});

	document.getElementById("ec-button-add-col").onclick = (event) => { event.preventDefault(); spreadsheet[0].insertColumn() };
	document.getElementById("ec-button-remove-col").onclick = (event) => { event.preventDefault(); spreadsheet[0].deleteColumn() };
	document.getElementById("ec-button-add-row").onclick = (event) => { event.preventDefault(); spreadsheet[0].insertRow() };
	document.getElementById("ec-button-remove-row").onclick = (event) => { event.preventDefault(); spreadsheet[0].deleteRow() };

	document.getElementById("ec-button-save-data").addEventListener('click', function (event) {
		event.preventDefault();
		let data = spreadsheet[0].getData();

		if (data.flat().every(cell => cell === "" || cell === null)) {
			jQuery("#dialog-confirm").dialog({
				resizable: false,
				height: 400,
				modal: true,
				buttons: {
					"Ok": function() {
						jQuery(this).dialog("close");
					}
				}
			});
		} else {
			// Prepare data to send
			const formData = new FormData();
			formData.append("action", "easy_charts_save_chart_data"); // Must match the PHP action
			formData.append("chart_id", ec_chart.chart_id);
			formData.append('_nonce_check', ec_chart.ec_ajax_nonce);
			formData.append("chart_data", JSON.stringify(data));

			// Send AJAX request
			fetch(ajaxurl, {
				method: "POST",
				body: formData
			})
				.then(response => response.json())
				.then(updated_data => {
					document.querySelector( '.uv-div-' + ec_chart.chart_id ).innerHTML = '';

					var graphdef = {
						categories: updated_data.chart_categories,
						dataset: updated_data.chart_data,
					};

					if ( 'ec_chartjs_chart' === ec_chart_data.chart_lib ) {
						let chartJSData = parseChartJSData( updated_data.chart_data );
						chartJS.destroy();

						chartJS = new Chart(
							document.querySelector( 'canvas.uv-div-' + ec_chart.chart_id ),
							{
								type: updated_data.chart_type.toLowerCase(),
								data: {
									labels: chartJSData.labels,
									datasets: chartJSData.datasets
								},
								options: {
									responsive: true,
								},
							}
						);
					} else {
						var chartObject = uv.chart(updated_data.chart_type, graphdef, chartConfiguration);
					}
				})
				.catch(error => console.error("Error:", error));
		}
	});

	// jQuery implementation.
	jQuery('.ec-color-picker').wpColorPicker();
	jQuery(".ec-field-buttonset").buttonset();
	jQuery('.ec-field-slider').each(function(index, el) {

		jQuery(this).slider({
			range: "max",
			min: 0,
			max: 1,
			value: jQuery(jQuery(this).data('attach')).val(),
			step: 0.1,
			slide: function(event, ui) {
				jQuery(jQuery(this).data('attach')).val(ui.value);
			}
		});
	});

	jQuery('.resp-tabs-container').pwstabs({
		tabsPosition: 'vertical',
		responsive: false,
		containerWidth: '100%',
		theme: 'pws_theme_orange',
		effect: 'slidedown'
	});

	jQuery('.uv-chart-div svg.uv-frame g.uv-download-options').bind('mouseenter', function(event) {
		var svg = jQuery(this).parents('.uv-chart-div svg.uv-frame');

		svg[0].setAttribute('width', svg[0].getBoundingClientRect().width);
		svg[0].setAttribute('height', svg[0].getBoundingClientRect().height);

	});

});