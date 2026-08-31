/**
 * Keyboard helpers for the WooCommerce Backbone modal.
 */

/**
 * Elements that already do something with the Enter key on their own.
 *
 * Form fields accept it as input, and buttons, links and selects are activated
 * by it. Confirming the modal on their behalf would discard what the user
 * actually asked for.
 *
 * @type {string[]}
 */
var MODAL_ENTER_HANDLED_BY_ELEMENT = [
	'input',
	'textarea',
	'button',
	'a',
	'select',
];

/**
 * Controls that handle Enter themselves through script rather than markup.
 *
 * @type {string}
 */
var MODAL_ENTER_HANDLED_BY_SCRIPT =
	'.select2-container, .select2-selection, .select2-search__field, [role="combobox"]';

/**
 * Whether pressing Enter on an element should confirm the modal.
 *
 * @param {EventTarget} target - The element the Enter key was pressed on.
 * @returns {boolean} Whether the modal should be confirmed.
 */
function shouldConfirmModalOnEnter( target ) {
	if ( ! target || ! target.tagName ) {
		return true;
	}

	if (
		-1 !==
		MODAL_ENTER_HANDLED_BY_ELEMENT.indexOf( target.tagName.toLowerCase() )
	) {
		return false;
	}

	// Let selectWoo handle Enter on an enhanced-select control.
	if (
		typeof target.closest === 'function' &&
		target.closest( MODAL_ENTER_HANDLED_BY_SCRIPT )
	) {
		return false;
	}

	return true;
}

if ( typeof module !== 'undefined' && module.exports ) {
	// CommonJS (Node.js)
	module.exports = { shouldConfirmModalOnEnter };
} else if ( typeof define === 'function' && define.amd ) {
	// AMD
	define( [], function () {
		return { shouldConfirmModalOnEnter };
	} );
} else {
	// Browser global
	window.WCModalKeyboard = { shouldConfirmModalOnEnter };
}
