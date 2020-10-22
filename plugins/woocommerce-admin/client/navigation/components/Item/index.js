/**
 * External dependencies
 */
import { __experimentalNavigationItem as NavigationItem } from '@wordpress/components';
import { WooNavigationItem, useNavSlot } from '@woocommerce/navigation';

const Item = ( { item } ) => {
	const slot = useNavSlot( 'woocommerce_navigation_' + item.id );
	const hasFills = Boolean( slot.fills && slot.fills.length );

	// Only render a slot if a coresponding Fill exists and the item is not a category
	if ( hasFills && ! item.isCategory ) {
		return (
			<NavigationItem key={ item.id } item={ item.id }>
				<WooNavigationItem.Slot name={ item.id } />
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
		/>
	);
};

export default Item;
