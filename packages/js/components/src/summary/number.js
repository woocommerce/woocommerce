/**
 * External dependencies
 */
import { Button, Tooltip } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import classnames from 'classnames';
import ChevronDownIcon from 'gridicons/dist/chevron-down';
import { isNil, noop } from 'lodash';
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';
import { Icon, info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Link from '../link';
import { Text } from '../experimental';

/**
 * A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.
 *
 * @param {Object}        props
 * @param {Node}          props.children
 * @param {number}        props.delta               Change percentage. Float precision is rendered as given.
 * @param {string}        props.href
 * @param {string}        props.hrefType
 * @param {boolean}       props.isOpen
 * @param {string}        props.label
 * @param {string}        props.labelTooltipText
 * @param {Function}      props.onToggle
 * @param {string}        props.prevLabel
 * @param {number|string} props.prevValue
 * @param {boolean}       props.reverseTrend
 * @param {boolean}       props.selected
 * @param {number|string} props.value
 * @param {Function}      props.onLinkClickCallback
 * @return {Object} -
 */
const SummaryNumber = ( {
	children,
	delta,
	href = '',
	hrefType = 'wc-admin',
	isOpen = false,
	label,
	labelTooltipText,
	onToggle,
	prevLabel = __( 'Previous period:', 'woocommerce' ),
	prevValue,
	reverseTrend = false,
	selected = false,
	value,
	onLinkClickCallback = noop,
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
			? // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
			  sprintf(
					/* translators: percentage change upwards */
					__( 'Up %f%% from %s', 'woocommerce' ),
					delta,
					prevLabel
			  )
			: // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
			  sprintf(
					/* translators: percentage change downwards */
					__( 'Down %f%% from %s', 'woocommerce' ),
					Math.abs( delta ),
					prevLabel
			  );
	if ( ! delta ) {
		screenReaderLabel = sprintf(
			/* translators: previous value */
			__( 'No change from %s', 'woocommerce' ),
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
					<Text variant="body.small" size="14" lineHeight="20px">
						{ label }
					</Text>
					{ labelTooltipText && (
						<Tooltip
							text={ labelTooltipText }
							position="top center"
						>
							<div className="woocommerce-summary__info-tooltip">
								<Icon
									width={ 20 }
									height={ 20 }
									icon={ info }
								/>
							</div>
						</Tooltip>
					) }
				</div>

				<div className="woocommerce-summary__item-data">
					<div className="woocommerce-summary__item-value">
						<Text variant="title.small" size="20" lineHeight="28px">
							{ ! isNil( value )
								? value
								: __( 'N/A', 'woocommerce' ) }
						</Text>
					</div>

					<Tooltip
						text={
							! isNil( prevValue )
								? `${ prevLabel } ${ prevValue }`
								: __( 'N/A', 'woocommerce' )
						}
						position="top center"
					>
						<div
							className="woocommerce-summary__item-delta"
							role="presentation"
							aria-label={ screenReaderLabel }
						>
							<Text variant="caption" size="12" lineHeight="16px">
								{ ! isNil( delta )
									? // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
									  sprintf(
											/* translators: percentage change */
											__( '%f%%', 'woocommerce' ),
											delta
									  )
									: __( 'N/A', 'woocommerce' ) }
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
	hrefType: PropTypes.oneOf( [ 'wp-admin', 'wc-admin', 'external' ] ),
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
	 * A string that will displayed via a Tooltip next to the label
	 */
	labelTooltipText: PropTypes.string,
	/**
	 * A function used to switch the given SummaryNumber to a button, and called on click.
	 */
	onToggle: PropTypes.func,
	/**
	 * A string description of the previous value's timeframe, ex "Previous year:".
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

export default SummaryNumber;
