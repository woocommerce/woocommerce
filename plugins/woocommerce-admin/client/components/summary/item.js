/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import classnames from 'classnames';
import Gridicon from 'gridicons';
import { isUndefined } from 'lodash';
import PropTypes from 'prop-types';

/**
 * External dependencies
 */
import Link from 'components/link';

/**
 * A component to show a value, label, and an optional change percentage. Can also act as a link to a specific report focus.
 *
 * @return { object } -
 */
const SummaryNumber = ( {
	delta,
	href,
	isOpen,
	label,
	onToggle,
	prevLabel,
	prevValue,
	reverseTrend,
	selected,
	value,
} ) => {
	const liClasses = classnames( 'woocommerce-summary__item-container', {
		'is-dropdown-button': onToggle,
		'is-dropdown-expanded': isOpen,
	} );
	const classes = classnames( 'woocommerce-summary__item', {
		'is-selected': selected,
		'is-good-trend': reverseTrend ? delta < 0 : delta > 0,
		'is-bad-trend': reverseTrend ? delta > 0 : delta < 0,
	} );

	let icon = delta > 0 ? 'arrow-up' : 'arrow-down';
	let screenReaderLabel =
		delta > 0
			? sprintf( __( 'Up %d%% from %s', 'wc-admin' ), delta, prevLabel )
			: sprintf( __( 'Down %d%% from %s', 'wc-admin' ), Math.abs( delta ), prevLabel );
	if ( ! delta ) {
		// delta is zero or falsey
		icon = 'arrow-right';
		screenReaderLabel = sprintf( __( 'No change from %s', 'wc-admin' ), prevLabel );
	}

	const Container = onToggle ? Button : Link;
	const containerProps = {
		className: classes,
		'aria-current': selected ? 'page' : null,
	};
	if ( ! onToggle ) {
		containerProps.href = href;
		containerProps.role = 'menuitem';
	} else {
		containerProps.onClick = onToggle;
		containerProps[ 'aria-expanded' ] = isOpen;
	}

	return (
		<li className={ liClasses }>
			<Container { ...containerProps }>
				<span className="woocommerce-summary__item-label">{ label }</span>

				<span className="woocommerce-summary__item-data">
					<span className="woocommerce-summary__item-value">{ value }</span>
					<div
						className="woocommerce-summary__item-delta"
						role="presentation"
						aria-label={ screenReaderLabel }
					>
						<Gridicon className="woocommerce-summary__item-delta-icon" icon={ icon } size={ 18 } />
						<span className="woocommerce-summary__item-delta-value">
							{ ! isUndefined( delta )
								? sprintf( __( '%d%%', 'wc-admin' ), delta )
								: __( 'N/A', 'wc-admin' ) }
						</span>
					</div>
				</span>
				<span className="woocommerce-summary__item-prev-label">{ prevLabel }</span>
				{ ' ' /* Add a real space so the line breaks here, and not in the label text. */ }
				<span className="woocommerce-summary__item-prev-value">
					{ ! isUndefined( prevValue ) ? prevValue : __( 'N/A', 'wc-admin' ) }
				</span>

				{ onToggle ? (
					<Gridicon className="woocommerce-summary__toggle" icon="chevron-down" size={ 24 } />
				) : null }
			</Container>
		</li>
	);
};

SummaryNumber.propTypes = {
	/**
	 * A number to represent the percentage change since the last comparison period - positive numbers will show
	 * a green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow.
	 * If omitted, no change value will display.
	 */
	delta: PropTypes.number,
	/**
	 * An internal link to the report focused on this number.
	 */
	href: PropTypes.string.isRequired,
	/**
	 * Boolean describing whether the menu list is open. Only applies in mobile view,
	 * and only applies to the toggle-able item (first in the list).
	 */
	isOpen: PropTypes.bool,
	/**
	 * A string description of this value, ex "Revenue", or "New Customers"
	 */
	label: PropTypes.string.isRequired,
	/**
	 * A function used to switch the given SummaryNumber to a button, and called on click.
	 */
	onToggle: PropTypes.func,
	/**
	 * A string description of the previous value's timeframe, ex "Previous Year:".
	 */
	prevLabel: PropTypes.string,
	/**
	 * A string or number value to display - a string is allowed so we can accept currency formatting.
	 * If omitted, this section won't display.
	 */
	prevValue: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	/**
	 * A boolean used to indicate that a negative delta is "good", and should be styled like a positive (and vice-versa).
	 */
	reverseTrend: PropTypes.bool,
	/**
	 * A boolean used to show a highlight style on this number.
	 */
	selected: PropTypes.bool,
	/**
	 * A string or number value to display - a string is allowed so we can accept currency formatting.
	 */
	value: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ).isRequired,
};

SummaryNumber.defaultProps = {
	href: '/analytics',
	isOpen: false,
	prevLabel: __( 'Previous Period:', 'wc-admin' ),
	reverseTrend: false,
	selected: false,
};

export default SummaryNumber;
