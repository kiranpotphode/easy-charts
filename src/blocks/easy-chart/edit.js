import { useState, useEffect, useRef } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import chartJs from "../../js/chart-js-adapter";

export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const canvasRef = useRef();
	const [charts, setCharts] = useState([]);

	// Fetch chart list once
	useEffect(() => {
		apiFetch({ path: '/wp/v2/easy_charts?per_page=100&_fields=id,title' })
			.then((data) => setCharts(data))
			.catch(console.error);
	}, []);

	// Render chart preview when chartId changes
	useEffect(() => {
		const canvas = canvasRef.current;
		const chartId = attributes.chartId;
		if (!canvas || !chartId) return;

		apiFetch({ path: `/wp/v2/easy_charts/${chartId}/chart-data` })
			.then((data) => {
				if (canvas._chart) {
					canvas._chart.destroy();
				}
				canvas._chart = chartJs( 'canvas.chart-js-canvas-' + chartId, data );
			})
			.catch(console.error);

		return () => {
			if (canvas && canvas._chart) {
				canvas._chart.destroy();
				delete canvas._chart;
			}
		};
	}, [attributes.chartId]);

	return (
		<div {...blockProps}>
			<label htmlFor="chart-select">
				Select Chart:
				<select
					id="chart-select"
					value={attributes.chartId || ''}
					onChange={(e) =>
						setAttributes({ chartId: parseInt(e.target.value) })
					}
					style={{ width: '100%', marginBottom: '12px' }}
				>
					<option value="" disabled>
						Choose a chart...
					</option>
					{charts.map((chart) => (
						<option key={chart.id} value={chart.id}>
							{chart.title?.rendered || chart.id}
						</option>
					))}
				</select>
			</label>
			<div className="ec-chart-wrapper" >
				{attributes.chartId ? (
					<canvas ref={canvasRef} className={`chart-js-canvas-${attributes.chartId}`}
					        ></canvas>
				) : (
					<p>Please select a chart to preview.</p>
				)}
			</div>
		</div>
	);
}
