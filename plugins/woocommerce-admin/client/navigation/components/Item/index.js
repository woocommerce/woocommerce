/**
 * External dependencies
 */
import { __experimentalNavigationItem as NavigationItem } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { WooNavigationItem, useNavSlot } from '@woocommerce/navigation';

const Item = ( { item } ) => {
	const slot = useNavSlot( 'woocommerce_navigation_' + item.id );
	const hasFills = Boolean( slot.fills && slot.fills.length );

	const trackClick = ( id ) => {
		recordEvent( 'navigation_click', {
			menu_item: id,
		} );
	};

	// Disable reason: The div wrapping the slot item is used for tracking purposes
	// and should not be a tabbable element.
	/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */

	// Only render a slot if a coresponding Fill exists and the item is not a category
	if ( hasFills && ! item.isCategory ) {
		return (
			<NavigationItem key={ item.id } item={ item.id }>
				<div onClick={ () => trackClick( item.id ) }>
					<WooNavigationItem.Slot name={ item.id } />
				</div>
			</NavigationItem>
		);
	}

	return (
		<NavigationItem
			key={ item.id }
			item={ item.id }
			title={ item.title }
			href={ item.url }
			navigateToMenu={ ! item.url && item.id }
			onClick={ () => trackClick( item.id ) }
		/>
	);
	/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
};

export default Item;
