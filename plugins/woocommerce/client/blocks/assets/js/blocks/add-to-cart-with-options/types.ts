export type QuantitySelectorStyleProps = 'input' | 'stepper';

export interface Attributes {
	className?: string;
}

/**
 * Store API product add_to_cart field structure.
 */
export type AddToCartData = {
	text: string;
	description: string;
	url: string;
	minimum: number;
	maximum: number;
	multiple_of: number;
};

/**
 * Store API variation reference (from parent product's variations field).
 */
export type VariationReference = {
	id: number;
	attributes: Array< { name: string; value: string } >;
};

/**
 * Store API product data structure.
 */
export type StoreAPIProduct = {
	id: number;
	name: string;
	type: string;
	parent: number;
	is_in_stock: boolean;
	is_purchasable: boolean;
	sold_individually: boolean;
	add_to_cart: AddToCartData;
	variations?: VariationReference[];
};

/**
 * State shape for the woocommerce/products interactivity store.
 */
export type ProductsStoreState = {
	products: Record< number, StoreAPIProduct >;
	productVariations: Record< number, StoreAPIProduct >;
};
