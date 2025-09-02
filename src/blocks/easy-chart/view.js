import chartJs from '../../js/chart-js-adapter';
import "../../scss/easy-charts-public.scss";

document.querySelectorAll('.ec-chartjs-chart-container').forEach((canvas) => {
	// Access the data-object attribute using dataset.
	const chartId = canvas.dataset.object;  // e.g., "ec_object_1988".

	// Remove prefix. we need just the numeric part.
	const idNumber = chartId.split('_').pop();

	// Now fetch data (e.g., via REST endpoint or inline logic).
	fetch(`/wp-json/wp/v2/easy_charts/${idNumber}/chart-data`)
		.then(res => res.json())
		.then((data) => {
			try {
				chartJs( 'canvas.ec-chartjs-chart-container.' + chartId, data );
			} catch(err) {
				console.error( 'Failed to load module', err );
			}
		})
		.catch(err => console.error('Chart data fetch error:', err));
});
