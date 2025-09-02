const downloadChartImagePlugin = {
	id: 'downloadChartImagePlugin',
	afterDraw(chart, _args, options) {
		if (!options.enable) {
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
	},
};

export default downloadChartImagePlugin;
