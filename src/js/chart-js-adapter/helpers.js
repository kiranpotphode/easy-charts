export const chartJSColorPalette = {
	'Default': ['#00BBC9', '#EC63AB', '#AA8AE4', '#83CE44', '#ff8f25', '#009EAA', '#CA4F7F', '#9C70C0', '#6BAF3B'],
	'OldDefault' : ['#7E6DA1', '#C2CF30', '#FF8900', '#FE2600', '#E3003F', '#8E1E5F', '#FE2AC2', '#CCF030', '#9900EC', '#3A1AA8', '#3932FE', '#3276FF', '#35B9F6', '#42BC6A', '#91E0CB'],
	'Plain' : ['#B1EB68', '#B1B9B5', '#FFA16C', '#9B64E7', '#CEE113', '#2F9CFA', '#CA6877', '#EC3D8C', '#9CC66D', '#C73640', '#7D9532', '#B064DC' ],
	'Android' : ['#33B5E5', '#AA66CC', '#99CC00', '#FFBB33', '#FF4444', '#0099CC', '#9933CC', '#669900', '#FF8800', '#CC0000'],
	'Soft' : [ '#9ED8D2', '#FFD478', '#F16D9A', '#A8D59D', '#FDC180', '#F05133', '#EDED8A', '#F6A0A5', '#9F218B' ],
	'Simple' : [ '#FF8181', '#FFB081', '#FFE081', '#EFFF81', '#BFFF81', '#90FF81', '#81FFA2', '#81FFD1', '#9681FF', '#C281FF', '#FF81DD' ],
	'Egypt' : [ '#3A3E04','#784818','#FCFCA8','#C03C0C','#F0A830','#A8783C','#FCFCFC','#FCE460','#540C00','#C0C084','#3C303C','#1EA34A','#606C54','#F06048' ],
	'Olive' : [ '#18240C','#3C6C18','#60A824','#90D824','#A8CC60','#789C60','#CCF030','#B4CCA8','#D8F078','#40190D','#E4F0CC' ],
	'Candid' : [ '#AF5E14','#81400C','#E5785D','#FEBFBF','#A66363','#C7B752','#EFF1A7','#83ADB7','#528F98','#BCEDF5','#446B3D','#8BD96F','#E4FFB9' ],
	'Sulphide' : [ '#594440','#0392A7','#FFC343','#E2492F','#007257','#B0BC4A','#2E5493','#7C2738','#FF538B','#A593A1','#EBBA86','#E2D9CA' ],
	'Lint' : ['#A8A878','#F0D89C','#60909C','#242418','#E49C30','#54483C','#306090','#C06C00','#C0C0C0','#847854','#6C3C00','#9C3C3C','#183C60','#FCCC00','#840000','#FCFCFC']
};

export function hexToRgba( hex = '#ffffff', opacity = 1 ) {
	// Remove the '#' if it exists
	hex = hex.replace( /^#/, "" );

	// Parse r, g, b values
	let r, g, b;

	if ( hex.length === 3 ) {
		// If shorthand hex (#abc), expand to full (#aabbcc)
		r = parseInt( hex[0] + hex[0], 16 );
		g = parseInt( hex[1] + hex[1], 16 );
		b = parseInt( hex[2] + hex[2], 16 );
	} else if ( hex.length === 6 ) {
		// If full hex (#aabbcc)
		r = parseInt( hex.substring( 0, 2 ), 16 );
		g = parseInt( hex.substring( 2, 4 ), 16 );
		b = parseInt( hex.substring( 4, 6 ), 16 );
	} else {
		throw new Error( "Invalid HEX color." );
	}

	// Ensure opacity is between 0 and 1
	opacity = Math.min( 1, Math.max( 0, opacity ) );

	return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

export function getChartLabels( rawData ) {
	console.log('raw data', rawData);
	// Extract all unique labels dynamically.
	return Array.from(
		new Set( Object.values( rawData ).flat().map( ( entry ) => entry.name ) )
	).sort(); // Sorting for consistency.
}

export function getDataSets( rawData, labels, colorPalette ) {
	return Object.keys( rawData ).map( ( legend, index ) => ( {
		label: legend,
		data: labels.map(
			( xAxis ) => rawData[legend].find( ( entry ) => entry.name === xAxis )?.value || 0
		),
		backgroundColor: colorPalette[index % colorPalette.length] // Cycle through color palette.
	} ) );
}