import { Chart, Scale } from 'chart.js/auto';

class SqrtScale extends Scale {
	// Set a unique ID.
	static id = 'sqrt';

	parse(raw) {
		// Use linear parsing—validate positive values if needed.
		return Chart.LinearScale.prototype.parse.call(this, raw);
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax(true);
		this.min = min >= 0 ? min : 0;
		this.max = max;
	}

	buildTicks() {
		const ticks = [];
		const min = Math.sqrt(this.min || 0);
		const max = Math.sqrt(this.max || 1);
		const step = (max - min) / 5; // customize step as needed.
		for (let r = min; r <= max; r += step) {
			ticks.push({ value: r * r });
		}
		return ticks;
	}

	configure() {
		super.configure();
		this._start = Math.sqrt(this.min);
		this._range = Math.sqrt(this.max) - this._start;
	}

	getPixelForValue(value) {
		const v = Math.sqrt(value);
		const ratio = (v - this._start) / this._range;
		return this.getPixelForDecimal(ratio);
	}

	getValueForPixel(pixel) {
		const ratio = this.getDecimalForPixel(pixel);
		const v = this._start + ratio * this._range;
		return v * v;
	}
}

export default SqrtScale;
