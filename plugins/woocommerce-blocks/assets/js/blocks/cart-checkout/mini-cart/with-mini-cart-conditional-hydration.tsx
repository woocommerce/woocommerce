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
	// Signals that the HTML placeholder drawer has been opened. Needed
	// to know whether we have to skip the slide in animation.
	isPlaceholderOpen?: boolean;
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
