/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { uniqueId } from 'lodash';
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getHasItemsClass } from './utils';

const Menu = ( { label, orientation, itemCount, items } ) => {
	const instanceId = uniqueId( 'woocommerce-summary-helptext-' );
	const hasItemsClass = getHasItemsClass( itemCount );
	const classes = classnames( 'woocommerce-summary', {
		[ hasItemsClass ]: orientation === 'horizontal',
	} );

	return (
		<NavigableMenu
			aria-label={ label }
			aria-describedby={ instanceId }
			orientation={ orientation }
			stopNavigationEvents
		>
			<p id={ instanceId } className="screen-reader-text">
				{ __(
					'List of data points available for filtering. Use arrow keys to cycle through ' +
						'the list. Click a data point for a detailed report.',
					'woocommerce'
				) }
			</p>
			<ul className={ classes }>{ items }</ul>
		</NavigableMenu>
	);
};

Menu.propTypes = {
	/**
	 * An optional label of this group, read to screen reader users.
	 */
	label: PropTypes.string,
	/**
	 * Item layout orientation.
	 */
	orientation: PropTypes.oneOf( [ 'vertical', 'horizontal' ] ).isRequired,
	/**
	 * A list of `<SummaryNumber />`s.
	 */
	items: PropTypes.node.isRequired,
	/**
	 * Number of items.
	 */
	itemCount: PropTypes.number.isRequired,
};

export default Menu;
