/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Children, cloneElement } from '@wordpress/element';
import { Dropdown, NavigableMenu } from '@wordpress/components';
import PropTypes from 'prop-types';
import { uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import { isMobileViewport, isTabletViewport } from 'lib/ui';
import './style.scss';

/**
 * A container element for a list of SummaryNumbers. This component handles detecting & switching to
 * the mobile format on smaller screens.
 *
 * @return { object } -
 */
const SummaryList = ( { children, label } ) => {
	if ( ! label ) {
		label = __( 'Performance Indicators', 'wc-admin' );
	}
	const isDropdownBreakpoint = isTabletViewport() || isMobileViewport();

	// We default to "one" because we can't have empty children.
	const itemCount = Children.count( children ) || 1;
	const hasItemsClass = itemCount < 10 ? `has-${ itemCount }-items` : 'has-10-items';
	const classes = classnames( 'woocommerce-summary', {
		[ hasItemsClass ]: ! isDropdownBreakpoint,
	} );

	const instanceId = uniqueId( 'woocommerce-summary-helptext-' );
	const menu = (
		<NavigableMenu
			aria-label={ label }
			aria-describedby={ instanceId }
			orientation={ isDropdownBreakpoint ? 'vertical' : 'horizontal' }
			stopNavigationEvents
		>
			<p id={ instanceId } className="screen-reader-text">
				{ __(
					'List of data points available for filtering. Use arrow keys to cycle through ' +
						'the list. Click a data point for a detailed report.',
					'wc-admin'
				) }
			</p>
			<ul className={ classes }>{ children }</ul>
		</NavigableMenu>
	);

	// On large screens, or if there are not multiple SummaryNumbers, we'll display the plain list.
	if ( ! isDropdownBreakpoint || itemCount < 2 ) {
		return menu;
	}

	const items = Children.toArray( children );
	const selected = items.find( item => !! item.props.selected );
	if ( ! selected ) {
		return menu;
	}

	return (
		<Dropdown
			className="woocommerce-summary"
			position="bottom"
			headerTitle={ label }
			renderToggle={ ( { isOpen, onToggle } ) => cloneElement( selected, { onToggle, isOpen } ) }
			renderContent={ () => menu }
		/>
	);
};

SummaryList.propTypes = {
	/**
	 * A list of `<SummaryNumber />`s
	 */
	children: PropTypes.node.isRequired,
	/**
	 * An optional label of this group, read to screen reader users. Defaults to "Performance Indicators".
	 */
	label: PropTypes.string,
};

export default SummaryList;
