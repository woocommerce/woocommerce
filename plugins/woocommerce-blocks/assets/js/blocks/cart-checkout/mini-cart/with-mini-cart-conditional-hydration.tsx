/**
 * External dependencies
 */
import {
	withStoreCartApiHydration,
	withRestApiHydration,
} from '@woocommerce/block-hocs';

interface MiniCartBlockInterface {
	// Signals whether the cart data is outdated. That happens when
	// opening the mini cart after adding a product to the cart.
	isDataOutdated?: boolean;
	// Signals whether it should be open when the React component is loaded. For
	// example, when adding a product to the cart, the Mini Cart should open
	// when loaded, but when removing a product from the cart, it shouldn't.
	isInitiallyOpen?: boolean;
}

// Custom HOC to conditionally hydrate API data depending on the isDataOutdated
// prop.
export default (
	OriginalComponent: ( component: MiniCartBlockInterface ) => JSX.Element
) => {
	return ( {
		isDataOutdated,
		...props
	}: MiniCartBlockInterface ): JSX.Element => {
		const Component = isDataOutdated
			? OriginalComponent
			: withStoreCartApiHydration(
					withRestApiHydration( OriginalComponent )
			  );
		return <Component { ...props } />;
	};
};
