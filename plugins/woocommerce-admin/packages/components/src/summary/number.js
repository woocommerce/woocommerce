/**
 * External dependencies
 */
import { Button, Tooltip } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import classnames from 'classnames';
import ChevronDownIcon from 'gridicons/dist/chevron-down';
import { isNil, noop } from 'lodash';
import PropTypes from 'prop-types';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import Link from '../link';

/**
 * A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.
 *
 * @param {Object} props
 * @param {Node} props.children
 * @param {number} props.delta Change percentage. Float precision is rendered as given.
 * @param {string} props.href
 * @param {string} props.hrefType
 * @param {boolean} props.isOpen
 * @param {string} props.label
 * @param {Function} props.onToggle
 * @param {string} props.prevLabel
 * @param {number|string} props.prevValue
 * @param {boolean} props.reverseTrend
 * @param {boolean} props.selected
 * @param {number|string} props.value
 * @param {Function} props.onLinkClickCallback
 * @return {Object} -
 */
const SummaryNumber = ( {
	children,
	delta,
	href,
	hrefType,
	isOpen,
	label,
	onToggle,
	prevLabel,
	prevValue,
	reverseTrend,
	selected,
	value,
	onLinkClickCallback,
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

	let screenReaderLabel =
		delta > 0
			? sprintf(
					__( 'Up %f%% from %s', 'woocommerce-admin' ),
					delta,
					prevLabel
			  )
			: sprintf(
					__( 'Down %f%% from %s', 'woocommerce-admin' ),
					Math.abs( delta ),
					prevLabel
			  );
	if ( ! delta ) {
		screenReaderLabel = sprintf(
			__( 'No change from %s', 'woocommerce-admin' ),
			prevLabel
		);
	}

	let Container;
	const containerProps = {
		className: classes,
		'aria-current': selected ? 'page' : null,
	};

	if ( onToggle || href ) {
		const isButton = !! onToggle;
		Container = isButton ? Button : Link;
		if ( isButton ) {
			containerProps.onClick = onToggle;
			containerProps[ 'aria-expanded' ] = isOpen;
		} else {
			containerProps.href = href;
			containerProps.role = 'menuitem';
			containerProps.onClick = onLinkClickCallback;
			containerProps.type = hrefType;
		}
	} else {
		Container = 'div';
	}

	return (
		<li className={ liClasses }>
			<Container { ...containerProps }>
				<div className="woocommerce-summary__item-label">
					<Text variant="body.small">{ label }</Text>
				</div>

				<div className="woocommerce-summary__item-data">
					<div className="woocommerce-summary__item-value">
						<Text variant="title.small">
							{ ! isNil( value )
								? value
								: __( 'N/A', 'woocommerce-admin' ) }
						</Text>
					</div>

					<Tooltip
						text={
							! isNil( prevValue )
								? `${ prevLabel } ${ prevValue }`
								: __( 'N/A', 'woocommerce-admin' )
						}
						position="top center"
					>
						<div
							className="woocommerce-summary__item-delta"
							role="presentation"
							aria-label={ screenReaderLabel }
						>
							<Text variant="caption">
								{ ! isNil( delta )
									? sprintf(
											__( '%f%%', 'woocommerce-admin' ),
											delta
									  )
									: __( 'N/A', 'woocommerce-admin' ) }
							</Text>
						</div>
					</Tooltip>
				</div>
				{ onToggle ? (
					<ChevronDownIcon
						className="woocommerce-summary__toggle"
						size={ 24 }
					/>
				) : null }
				{ children }
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
	href: PropTypes.string,
	/**
	 * The type of the link
	 */
	hrefType: PropTypes.oneOf( [ 'wp-admin', 'wc-admin', 'external' ] )
		.isRequired,
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
	value: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	/**
	 * A function to be called after a SummaryNumber, rendered as a link, is clicked.
	 */
	onLinkClickCallback: PropTypes.func,
};

SummaryNumber.defaultProps = {
	href: '',
	hrefType: 'wc-admin',
	isOpen: false,
	prevLabel: __( 'Previous Period:', 'woocommerce-admin' ),
	reverseTrend: false,
	selected: false,
	onLinkClickCallback: noop,
};

export default SummaryNumber;
