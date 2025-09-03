import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ComboboxControl } from '@wordpress/components';
import { useBlockProps, blocks } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import chartJs from '../../js/chart-js-adapter';

export default function Edit({ attributes, setAttributes, clientId }) {
	const { blockId, chartTitle } = attributes;
	const blockProps = useBlockProps();
	const canvasRef = useRef();
	const [chartTitles, setchartTitles] = useState([]);
	const [isLoading, setIsLoading] = useState(false);
	const [options, setOptions] = useState([]);

	useEffect(() => {
		const allBlocks = wp.data.select('core/block-editor').getBlocks();

		const duplicateCount = allBlocks.filter(
			(b) => b.attributes.blockId === blockId
		).length;

		// Only set once—when the block is first inserted.
		if (!blockId || duplicateCount > 1) {
			setAttributes({ blockId: clientId });
		}
	}, []); // Run only once on mount

	// Render chart preview when chartId changes
	useEffect(() => {
		const canvas = canvasRef.current;
		const chartId = attributes.chartId;
		if (!canvas || !chartId) return;

		apiFetch({
			path: `/easy-charts/v1/chart/${chartId}/`,
			headers: { 'X-Easy-Charts-Fetch-Nonce': easyChartsSettings.nonce },
		})
			.then((data) => {
				if (canvas._chart) {
					canvas._chart.destroy();
				}
				canvas._chart = chartJs(
					'canvas.chart-js-canvas-' + blockId + chartId,
					data
				);
			})
			.catch(console.error);

		return () => {
			if (canvas && canvas._chart) {
				canvas._chart.destroy();
				delete canvas._chart;
			}
		};
	}, [attributes.chartId]);

	function onFilterValueChange(inputValue) {
		if (!inputValue) {
			setOptions([]);
			return;
		}
		setIsLoading(true);
		apiFetch({
			path:
				'/easy-charts/v1/chart?search=' +
				encodeURIComponent(inputValue) +
				'&per_page=10&fields=id,title',
			headers: { 'X-Easy-Charts-Fetch-Nonce': easyChartsSettings.nonce },
		})
			.then((data) => {
				setOptions(
					data.map((c) => ({
						label: c.title || `#${c.id}`,
						value: c.id,
					}))
				);
				data.map(function (c) {});
				setIsLoading(false);
			})
			.catch(() => setIsLoading(false));
	}

	function onChange(value) {
		apiFetch({
			path: `/easy-charts/v1/chart/${value}/title`,
			headers: { 'X-Easy-Charts-Fetch-Nonce': easyChartsSettings.nonce },
		})
			.then((data) => {
				setAttributes({
					chartId: parseInt(value, 10),
					chartTitle: data,
				});
			})
			.catch(() => setIsLoading(false));
	}

	return (
		<div {...blockProps}>
			<label htmlFor="chart-select">
				{__('Select Chart:', 'easy-charts')}
				<ComboboxControl
					label={__('Choose a chart by title...', 'easy-charts')}
					value={attributes.chartId}
					options={options}
					isLoading={isLoading}
					onFilterValueChange={onFilterValueChange}
					onChange={onChange}
				/>
				{attributes.chartTitle || ''}
			</label>
			<div className="ec-chart-wrapper">
				{attributes.chartId ? (
					<canvas
						ref={canvasRef}
						className={`chart-js-canvas-${blockId + attributes.chartId}`}
					></canvas>
				) : (
					<p>
						{__('Please select a chart to preview.', 'easy-charts')}
					</p>
				)}
			</div>
		</div>
	);
}
