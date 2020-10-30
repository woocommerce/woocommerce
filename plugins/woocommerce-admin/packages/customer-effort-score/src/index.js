/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */

/**
 * Use `CustomerEffortScore` to gather a customer effort score.
 *
 * NOTE: This should live in @woocommerce/customer-effort-score to allow
 * reuse.
 *
 * @param {Object}   props               Component props.
 * @param {Function} props.trackCallback Function to call when the modal is activated.
 * @param {boolean}  props.visible       Whether or not the tracks modal is visible.
 * @param {Function} props.toggleVisible Callback to toggle the visible prop.
 * @param {string}   props.label         The label displayed in the modal.
 */
function CustomerEffortScore( {
	trackCallback,
	visible,
	toggleVisible,
	label,
} ) {
	const [ score, setScore ] = useState( 0 );

	if ( ! visible ) {
		return null;
	}

	function close() {
		setScore( 3 ); // TODO let this happen in the UI

		toggleVisible();
		trackCallback( score );
	}

	return (
		<p className="customer-effort-score_modal">
			{ label } <button onClick={ close }>Click me</button>
		</p>
	);
}

CustomerEffortScore.propTypes = {
	/**
	 * The function to call when the modal is actioned.
	 */
	trackCallback: PropTypes.func.isRequired,
	/**
	 * Whether or not the dialog is visible. True is used for when this is
	 * loaded on page load (in client/index.js). False is used if the modal is
	 * loaded as part of the layout and displayed programmatically.
	 */
	visible: PropTypes.bool.isRequired,
	/**
	 * Callback to toggle the visible prop.
	 */
	toggleVisible: PropTypes.func.isRequired,
	/**
	 * The label displayed in the modal.
	 */
	label: PropTypes.string.isRequired,
};

export default CustomerEffortScore;
