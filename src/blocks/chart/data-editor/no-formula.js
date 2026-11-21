/**
 * Stand-in for `@jspreadsheet/formula` (see webpack.config.js).
 *
 * That package is published only as minified code with no public source, so it is not bundled.
 * Charts need values, not spreadsheet formulas: the grid runs with formulas off, and a cell
 * starting with "=" stays plain text.
 *
 * @param {string} expression Formula without the leading "=".
 * @return {string} The formula as typed.
 */
module.exports = function formula( expression ) {
	return `=${ expression }`;
};
