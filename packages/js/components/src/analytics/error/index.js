/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EmptyContent from '../../empty-content';

/**
 * Component to render when there is an error in an analytics component due to data
 * not being loaded or being invalid.
 *
 * @param {Object} props             React props.
 * @param {string} [props.className] Additional class name to style the component.
 */
function AnalyticsError( { className } ) {
	const title = __(
		'There was an error getting your stats. Please try again.',
		'woocommerce'
	);
	const actionLabel = __( 'Reload', 'woocommerce' );
	const actionCallback = () => {
		// @todo Add tracking for how often an error is displayed, and the reload action is clicked.
		window.location.reload();
	};
	return (
		<EmptyContent
			className={ className }
			title={ title }
			actionLabel={ actionLabel }
			actionCallback={ actionCallback }
		/>
	);
}

AnalyticsError.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
};

export default AnalyticsError;
