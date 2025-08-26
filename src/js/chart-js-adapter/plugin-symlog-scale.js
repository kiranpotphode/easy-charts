import {Scale} from "chart.js/auto";

const symlogTransform = ( value ) => {
	return Math.sign( value ) * Math.log10( Math.abs( value ) / constant + 1 );
};

const symlogInverseTransform = ( value ) => {
	return Math.sign( value ) * ( Math.pow( 10, Math.abs( value ) ) - 1 ) * constant;
};

class SymlogScale extends Scale {
	parse( raw, index ) {
		const value = super.parse( raw, index );
		return symlogTransform( value );
	}

	determineDataLimits() {
		const { min, max } = this.getMinMax( true );
		this.min = symlogTransform( min );
		this.max = symlogTransform( max );
		this._startValue = this.min;
		this._valueRange = this.max - this.min;
	}

	getPixelForValue( value ) {
		const symlogValue = symlogTransform( value );
		const decimal = ( symlogValue - this._startValue ) / this._valueRange;
		return this.getPixelForDecimal( decimal );
	}

	getLabelForValue( value ) {
		return symlogInverseTransform( value ).toLocaleString();
	}

	generateTickLabels( ticks ) {
		const minimalDecimalPlaces = Math.max( 0, Math.ceil( -Math.log10(
			Math.min( ...ticks.map( t => Math.abs( t.value - ( ticks[0]?.value || 0 ) ) ) ) || 0
		) ) );
		ticks.forEach( ( tick ) => {
			tick.label = parseFloat( symlogInverseTransform( tick.value )
				.toFixed( minimalDecimalPlaces ) ).toString();
		} );
	}

	buildTicks() {
		const ticks = [];
		const tickCount = 11;
		const min = symlogInverseTransform( this.min );
		const max = symlogInverseTransform( this.max );
		const range = max - min;
		const stepSize = range / ( tickCount - 1 );

		for ( let i = min; i <= max; i += stepSize ) {
			ticks.push( { value: i } );
		}
		return ticks;
	}
}

SymlogScale.id = 'symlog';

export default SymlogScale;