/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Dashicon } from '@wordpress/components';
import PropTypes from 'prop-types';

const SummaryNumber = ( { context, delta, label, selected, value } ) => {
	if ( ! context ) {
		context = __( 'vs Previous Period', 'woo-dash' );
	}

	const classes = classnames( 'woocommerce-summary__item', {
		'is-selected': selected,
	} );

	return (
		<li className={ classes }>
			<span className="woocommerce-summary__item-label">{ label }</span>
			<span className="woocommerce-summary__item-value">{ value }</span>
			{ delta && (
				<span className="woocommerce-summary__item-delta">
					<Dashicon
						className="woocommerce-summary__item-delta-icon"
						icon={ delta > 0 ? 'arrow-up-alt' : 'arrow-down-alt' }
					/>
					<span className="woocommerce-summary__item-delta-value">{ delta }%</span>
					<span className="woocommerce-summary__item-delta-label">{ context }</span>
				</span>
			) }
		</li>
	);
};

SummaryNumber.propTypes = {
	context: PropTypes.string,
	delta: PropTypes.number,
	label: PropTypes.string.isRequired,
	selected: PropTypes.bool,
	value: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ).isRequired,
};

export default SummaryNumber;
