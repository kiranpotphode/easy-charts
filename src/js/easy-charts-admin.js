// phpcs:disable
import jspreadsheet from "jspreadsheet-ce";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import '../scss/easy-charts-admin.scss';
import chartJs from "./chart-js-adapter"

// Import all of Font Awesome
import "@fortawesome/fontawesome-free/js/all.js";

import "pwstabs/assets/jquery.pwstabs.css";
import "pwstabs/assets/jquery.pwstabs.js";

import "jquery-ui/themes/base/all.css";

document.addEventListener( 'DOMContentLoaded', function () {
	let chartJsChart = null;

	if ( typeof( ec_chart_data ) != 'undefined' ) {
		var graphdef = {
			categories: [],
			dataset: {}
		};

		var chartLib = ec_chart_data.chart_lib;
		var chartType = ec_chart_data.chart_type;
		var chartCategories = ec_chart_data.chart_categories;
		var chartDataset = ec_chart_data.chart_data;
		var chartConfiguration = ec_chart_data.chart_configuration;
		console.log( 'chartlib', chartLib );
		graphdef = {
			categories: chartCategories,
			dataset: chartDataset,
		};

		if ( 'ec_chartjs_chart' === ec_chart_data.chart_lib ) {
			console.log( 'load chart js' );
			 try {
				// Code that might throw an error.
				chartJsChart = chartJs( 'canvas.chart-js-canvas-' + ec_chart.chart_id, ec_chart_data );
			} catch (error) {
				// Handle the error
				console.error( 'Failed to load module', error );
			}
		} else {
			var chartObject = uv.chart( chartType, graphdef, chartConfiguration );
		}
		var chartObject = uv.chart( chartType, graphdef, chartConfiguration );
	}

	var jspreadsheetid = document.getElementById( "jspreadsheet" );
	var data = [
		['', 'Kia', 'Nissan', 'Toyota', 'Honda'],
		['2008', 10, 11, 12, 13],
		['2009', 20, 11, 14, 13],
		['2010', 30, 15, 12, 13]
	];

	if ( typeof( ec_chart ) != 'undefined' ) {
		if ( ec_chart.chart_data != null ) {
			data = ec_chart.chart_data;
		}
	}

	let spreadsheet = jspreadsheet( jspreadsheetid, {
		worksheets: [{
			data: data,
		}],
		tableOverflow: true,
		tableWidth: "200px",
	} );

	document.getElementById( "ec-button-add-col" ).onclick = ( event ) => { event.preventDefault(); spreadsheet[0].insertColumn() };
	document.getElementById( "ec-button-remove-col" ).onclick = ( event ) => { event.preventDefault(); spreadsheet[0].deleteColumn() };
	document.getElementById( "ec-button-add-row" ).onclick = ( event ) => { event.preventDefault(); spreadsheet[0].insertRow() };
	document.getElementById( "ec-button-remove-row" ).onclick = ( event ) => { event.preventDefault(); spreadsheet[0].deleteRow() };

	function update_chart_data(callback = () => {}) {
		// Prepare data to send
		const formData = new FormData();
		formData.append( "action", "easy_charts_save_chart_data" ); // Must match the PHP action
		formData.append( "chart_id", ec_chart.chart_id );
		formData.append( '_nonce_check', ec_chart.ec_ajax_nonce );
		formData.append( "chart_data", JSON.stringify( data ) );

		// Send AJAX request
		fetch( ajaxurl, {
			method: "POST",
			body: formData
		} )
			.then( response => response.json() )
			.then( updated_data => {
				document.querySelector( '.uv-div-' + ec_chart.chart_id ).innerHTML = '';

				var graphdef = {
					categories: updated_data.chart_categories,
					dataset: updated_data.chart_data,
				};

				var chartObject = uv.chart( updated_data.chart_type, graphdef, chartConfiguration );
				callback(); // execute js function after success.

				console.log( 'load chart js' );
				chartJsChart.destroy();

				try {
					// Code that might throw an error.
					console.log('updated chart data',ec_chart_data.chart_data);
					ec_chart_data['chart_data']= updated_data.chart_data;
					chartJsChart = chartJs( 'canvas.chart-js-canvas-' + ec_chart.chart_id, ec_chart_data );
				} catch (error) {
					// Handle the error.
					console.error( 'Failed to load module', error );
				}
			} )
			.catch( error => console.error( "Error:", error ) );
	}

	jQuery('#post').on('submit', function(e) {
		e.preventDefault(); // stop default submit.

		update_chart_data(function () {
			jQuery('#post').off('submit').submit();
		});
	});

	document.getElementById( "ec-button-save-data" ).addEventListener( 'click', function ( event ) {
		event.preventDefault();
		let data = spreadsheet[0].getData();

		if ( data.flat().every( cell => cell === "" || cell === null ) ) {
			jQuery( "#dialog-confirm" ).dialog( {
				resizable: false,
				height: 400,
				modal: true,
				buttons: {
					"Ok": function() {
						jQuery( this ).dialog( "close" );
					}
				}
			} );
		} else {
			update_chart_data();
		}
	} );

	// jQuery implementation.
	jQuery( '.ec-color-picker' ).wpColorPicker();
	jQuery( ".ec-field-buttonset" ).buttonset();
	jQuery( '.ec-field-slider' ).each( function( index, el ) {

		jQuery( this ).slider( {
			range: "max",
			min: 0,
			max: 1,
			value: jQuery( jQuery( this ).data( 'attach' ) ).val(),
			step: 0.1,
			slide: function( event, ui ) {
				jQuery( jQuery( this ).data( 'attach' ) ).val( ui.value );
			}
		} );
	} );

	jQuery( '.resp-tabs-container' ).pwstabs( {
		tabsPosition: 'vertical',
		responsive: false,
		containerWidth: '100%',
		theme: 'pws_theme_orange',
		effect: 'slidedown'
	} );

	jQuery( '.uv-chart-div svg.uv-frame g.uv-download-options' ).bind( 'mouseenter', function( event ) {
		var svg = jQuery( this ).parents( '.uv-chart-div svg.uv-frame' );

		svg[0].setAttribute( 'width', svg[0].getBoundingClientRect().width );
		svg[0].setAttribute( 'height', svg[0].getBoundingClientRect().height );

	} );

} );