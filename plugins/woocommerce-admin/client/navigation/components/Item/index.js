/**
 * External dependencies
 */
import {
	__experimentalNavigationItem as NavigationItem,
	__experimentalUseSlot as useSlot,
} from '@wordpress/components';
import { WooNavigationItem } from '@woocommerce/navigation';

const Item = ( { item } ) => {
	const slot = useSlot( item.id );
	const hasFills = Boolean( slot.fills && slot.fills.length );

	// Only render a slot if a coresponding Fill exists and the item is not a category
	if ( hasFills && ! item.isCategory ) {
		return <WooNavigationItem.Slot name={ item.id } />;
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
