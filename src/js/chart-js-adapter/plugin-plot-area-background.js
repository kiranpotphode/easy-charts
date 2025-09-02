const plotAreaBackgroundPlugin = {
	id: 'customPlotAreaBackgroundColor',
	beforeDraw: (chart, args, options) => {
		const { ctx } = chart;
		const chartArea = chart.chartArea; // Get the axis area
		ctx.save();
		//ctx.globalCompositeOperation = 'destination-over';
		//ctx.fillStyle = options.color ? hexToRgba( options.color, options.opacity ) : '#ffffff';
		//ctx.fillRect(0, 0, chart.width, chart.height);
		ctx.fillStyle = options.color ? options.color : '#ffffff'; // Background color.
		ctx.fillRect(
			chartArea.left,
			chartArea.top,
			chartArea.right - chartArea.left,
			chartArea.bottom - chartArea.top
		);

		ctx.restore();
	},
};

export default plotAreaBackgroundPlugin;
