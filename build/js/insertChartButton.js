/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/scss/inset-chart.scss":
/*!***********************************!*\
  !*** ./src/scss/inset-chart.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./src/js/insert-chart-button.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _scss_inset_chart_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/inset-chart.scss */ "./src/scss/inset-chart.scss");


jQuery( document ).ready(
	function ( $ ) {
		var charts;
		( function () {
			tinymce.PluginManager.add(
				'easy_charts_insert_chart_tc_button',
				function ( editor, url ) {
					editor.addButton(
						'easy_charts_insert_chart_tc_button',
						{
							icon: 'icon dashicons-chart-pie',
							tooltip: 'Insert Easy Chart',
							onclick: function () {
								$.ajax(
									{
										url: ajaxurl,
										type: 'POST',
										dataType: 'json',
										data: {
											'action': 'easy_charts_get_published_charts'
										},
									}
								)
									.done(
										function ( updated_data ) {
											charts = updated_data
											editor.windowManager.open(
												{
													title: 'Insert chart',
													body: [{
														type: 'listbox',
														name: 'level',
														label: 'Select Chart',
														'values': charts
													}],
													onsubmit: function ( e ) {
														editor.insertContent( e.data.level );
													}
												}
											);
										}
									)
									.fail( function () {} )
									.always( function () {} );
							}
						}
					);
				}
			);
		} )();
	}
);

})();

/******/ })()
;
//# sourceMappingURL=insertChartButton.js.map