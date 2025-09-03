import chartJs from '../../js/chart-js-adapter';
import apiFetch from '@wordpress/api-fetch';
import '../../scss/easy-charts-public.scss';

document.querySelectorAll('.ec-chartjs-chart-container').forEach((canvas) => {
	// Access the data-object attribute using dataset.
	const chartId = canvas.dataset.object; // e.g., "ec_object_1988".
	const blockId = canvas.dataset.blockid; // e.g., "xyzabc123".

	// Remove prefix. we need just the numeric part.
	const idNumber = chartId.split('_').pop();

	// Now fetch data (e.g., via REST endpoint or inline logic).
	apiFetch({
		path: `/easy-charts/v1/chart/${idNumber}/`,
		headers: { 'X-Easy-Charts-Fetch-Nonce': easyChartsSettings.nonce },
	})
		.then((data) => {
			try {
				chartJs(
					'canvas.ec-chartjs-chart-container.chart-js-canvas-' +
						blockId +
						idNumber,
					data
				);
			} catch (err) {
				console.error('Failed to load module', err);
			}
		})
		.catch((err) => console.error('Chart data fetch error:', err));
});
