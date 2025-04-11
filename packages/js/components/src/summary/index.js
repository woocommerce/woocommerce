/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Children, cloneElement } from '@wordpress/element';
import { Dropdown } from '@wordpress/components';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * Internal dependencies
 */
import Menu from './menu';

/**
 * A container element for a list of SummaryNumbers. This component handles detecting & switching to
 * the mobile format on smaller screens.
 *
 * @param {Object} props
 * @param {Node}   props.children
 * @param {string} props.isDropdownBreakpoint
 * @param {string} props.label
 * @return {Object} -
 */
const SummaryList = ( {
	children,
	isDropdownBreakpoint,
	label = __( 'Performance Indicators', 'woocommerce' ),
} ) => {
	const items = children( {} );
	// We default to "one" because we can't have empty children.
	const itemCount = Children.count( items ) || 1;
	const orientation = isDropdownBreakpoint ? 'vertical' : 'horizontal';
	const summaryMenu = (
		<Menu
			label={ label }
			orientation={ orientation }
			itemCount={ itemCount }
			items={ items }
		/>
	);

	// On large screens, or if there are not multiple SummaryNumbers, we'll display the plain list.
	if ( ! isDropdownBreakpoint || itemCount < 2 ) {
		return summaryMenu;
	}

	const selected = items.find( ( item ) => !! item.props.selected );
	if ( ! selected ) {
		return summaryMenu;
	}

	return (
		<Dropdown
			className="woocommerce-summary"
			popoverProps={ {
				placement: 'bottom',
			} }
			headerTitle={ label }
			renderToggle={ ( { isOpen, onToggle } ) =>
				cloneElement( selected, { onToggle, isOpen } )
			}
			renderContent={ ( renderContentArgs ) => (
				<Menu
					label={ label }
					orientation={ orientation }
					itemCount={ itemCount }
					items={ children( renderContentArgs ) }
				/>
			) }
		/>
	);
};

SummaryList.propTypes = {
	/**
	 * A function returning a list of `<SummaryNumber />`s
	 */
	children: PropTypes.func.isRequired,
	/**
	 * An optional label of this group, read to screen reader users.
	 */
	label: PropTypes.string,
};

export default withViewportMatch( {
	isDropdownBreakpoint: '< large',
} )( SummaryList );
