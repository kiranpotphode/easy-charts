import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { chartId, blockId } = attributes;
	const blockProps = useBlockProps.save();

	return (
		<div {...blockProps} className="ec-chart-wrapper">
			<canvas
				className={`ec-chartjs-chart-container ec_object_${chartId} chart-js-canvas-${blockId + chartId}`}
				data-object={`ec_object_${chartId}`}
				data-blockid={`${blockId}`}
			></canvas>
		</div>
	);
}
