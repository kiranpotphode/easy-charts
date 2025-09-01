import '../scss/easy-charts-public.scss';
import chartJs from "./chart-js-adapter";

const canvases = document.querySelectorAll( '.ec-chartjs-chart-container' );

canvases.forEach( ( canvas, index ) => {
	const dataObject = canvas.dataset.object;
	const ec_chart_data = window[dataObject];

	try {
		chartJs( 'canvas.ec-chartjs-chart-container.' + dataObject, ec_chart_data )
	} catch(err) {
		console.error( 'Failed to load module', err );
	}
} );
