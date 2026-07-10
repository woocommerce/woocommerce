/**
 * External dependencies
 */
import { ShippingMethod } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PluginBanner } from '../../shipping-recommendation/components/plugin-banner';

/**
 * Renders a column layout for the shipping method. Used for rendering a single shipping partner.
 *
 * @param {Object} props                Component props
 * @param {Object} props.shippingMethod Shipping method object
 * @return {JSX.Element} React node
 */
export const ShippingLayoutColumn = ( {
	shippingMethod,
}: {
	shippingMethod: ShippingMethod;
} ) => {
	return (
		<PluginBanner
			description={
				shippingMethod.layout_column?.description ||
				shippingMethod.description
			}
			features={ shippingMethod.layout_column?.features || [] }
			logo={ {
				image: shippingMethod.layout_column?.image || '',
				label: shippingMethod.layout_column?.image_label,
			} }
		/>
	);
};

/**
 * Renders a row layout for the shipping method. Used for rendering two shipping partners side by side.
 *
 * @param {Object} props                Component props
 * @param {Object} props.shippingMethod Shipping method object
 * @param {Array}  props.children       Children to render inside the layout
 * @return {JSX.Element} React node
 */
export const ShippingLayoutRow = ( {
	shippingMethod,
	children,
}: {
	shippingMethod: ShippingMethod;
	children: React.ReactNode;
} ) => {
	return (
		<PluginBanner
			layout="dual"
			features={ shippingMethod.layout_row?.features || [] }
			logo={ {
				image: shippingMethod.layout_row?.image || '',
				label: shippingMethod.layout_row?.image_label,
			} }
			description={
				shippingMethod.layout_row?.description ||
				shippingMethod.description
			}
		>
			{ children }
		</PluginBanner>
	);
};
