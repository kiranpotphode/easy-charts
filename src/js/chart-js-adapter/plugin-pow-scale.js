import { Chart, Scale } from 'chart.js/auto';

class PowScale extends Scale {
	static id = 'pow';

	constructor(cfg) {
		super(cfg);
		this._startValue = undefined;
		this._valueRange = 0;
		this.power = cfg.power || 2; // default exponent.
	}

	parse(raw, index) {
		const val = Chart.LinearScale.prototype.parse.call(this, raw, index);
		return isFinite(val) ? val : null;
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax(true);
		this.min = isFinite(min) ? min : 0;
		this.max = isFinite(max) ? max : 0;
	}

	buildTicks() {
		const ticks = [];
		const start = Math.pow(this.min, 1 / this.power);
		const end = Math.pow(this.max, 1 / this.power);
		const step = (end - start) / 5;

		for (let r = start; r <= end; r += step) {
			ticks.push({ value: r ** this.power });
		}
		this.min = ticks[0]?.value ?? this.min;
		this.max = ticks[ticks.length - 1]?.value ?? this.max;
		return ticks;
	}

	configure() {
		super.configure();
		this._startValue = Math.pow(this.min, 1 / this.power);
		this._valueRange =
			Math.pow(this.max, 1 / this.power) - this._startValue;
	}

	getPixelForValue(value) {
		const transformed = Math.pow(value, 1 / this.power);
		const ratio = (transformed - this._startValue) / this._valueRange;
		return this.getPixelForDecimal(ratio);
	}

	getValueForPixel(pixel) {
		const ratio = this.getDecimalForPixel(pixel);
		const val = this._startValue + ratio * this._valueRange;
		return val ** this.power;
	}
}

export default PowScale;
