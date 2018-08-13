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
	delta: PropTypes.number,
	href: PropTypes.string.isRequired,
	isOpen: PropTypes.bool,
	label: PropTypes.string.isRequired,
	onToggle: PropTypes.func,
	prevLabel: PropTypes.string,
	prevValue: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	reverseTrend: PropTypes.bool,
	selected: PropTypes.bool,
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
