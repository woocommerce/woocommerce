/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import { attributeNamesMatch } from '../../../base/utils/variations/attribute-matching';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

export type BackInStockFormStore = {
	state: {
		isVisible: boolean;
		selectedAttributeValue: string;
	};
};

/**
 * Drives the Back in Stock form rendered after the Add to Cart + Options block
 * of a variable product, see templates/single-product/back-in-stock-form.php.
 */
store< BackInStockFormStore >(
	'woocommerce/back-in-stock-form',
	{
		state: {
			/**
			 * Whether the selected variation can't be bought, so shoppers
			 * can sign up for it.
			 */
			get isVisible(): boolean {
				const variation = productsState.productVariationInContext;

				return (
					!! variation &&
					! ( variation.is_in_stock && variation.is_purchasable )
				);
			},
			/**
			 * Selected value of the attribute in context, read from the
			 * Add to Cart + Options block the input renders in.
			 */
			get selectedAttributeValue(): string {
				const { attribute } = getContext< { attribute: string } >();
				const { selectedAttributes = [] } =
					getContext< { selectedAttributes?: SelectedAttributes[] } >(
						'woocommerce/add-to-cart-with-options'
					) ?? {};

				return (
					selectedAttributes.find( ( selected ) =>
						attributeNamesMatch( selected.attribute, attribute )
					)?.value ?? ''
				);
			},
		},
	},
	{ lock: universalLock }
);
