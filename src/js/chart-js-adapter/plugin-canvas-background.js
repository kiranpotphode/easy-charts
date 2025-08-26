const canvasBackgroundPlugin         = {
	id: 'customCanvasBackgroundColor',
	beforeDraw: ( chart, args, options ) => {
		const {ctx}                  = chart;
		ctx.save();
		ctx.globalCompositeOperation = 'destination-over';
		ctx.fillStyle                = options.color ? options.color : '#ffffff';
		ctx.fillRect( 0, 0, chart.width, chart.height );
		ctx.restore();
	}
};

export default canvasBackgroundPlugin;