/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const SummaryList = ( { children, label } ) => {
	if ( ! label ) {
		label = __( 'Performance Indicators', 'woo-dash' );
	}
	return (
		<ul className="woocommerce-summary" aria-label={ label }>
			{ children }
		</ul>
	);
};

SummaryList.propTypes = {
	children: PropTypes.node.isRequired,
	label: PropTypes.string,
};

export { SummaryList };
export { default as SummaryNumber } from './item';
