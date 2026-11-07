# Split stores: product input inside woocommerce/products

```typescript
type WooCommerceStores = {
	'woocommerce/products': {
		state: {
			products: Record< number, ProductResponseItem >;
			productVariations: Record< number, ProductResponseItem >;
			productInputsByProductId: Record<
				number,
				{
					productId: number;
					selectedAttributes: Array< {
						raw_attribute: string;
						attribute: string;
						value: string;
					} >;
					draftLinesByKey: Record<
						string,
						{
							draftKey: string; // Distinguishes same purchasable product with different any-attribute selections.
							purchasableProductId: number;
							quantity: number;
							variation?: Array< {
								raw_attribute: string;
								attribute: string;
								value: string;
							} >;
							extensionData?: Record< string, unknown >;
						}
					>;
					extensionData?: Record< string, unknown >;
				}
			>;

			productId: number;
			variationId: number | null;
			mainProductInContext: ProductResponseItem | null;
			productVariationInContext: ProductResponseItem | null;
			productInContext: ProductResponseItem | null;
			productInputInContext: {
				productId: number;
				selectedAttributes: Array< {
					raw_attribute: string;
					attribute: string;
					value: string;
				} >;
				draftLinesByKey: Record<
					string,
					{
						draftKey: string; // Distinguishes same purchasable product with different any-attribute selections.
						purchasableProductId: number;
						quantity: number;
						variation?: Array< {
							raw_attribute: string;
							attribute: string;
							value: string;
						} >;
						extensionData?: Record< string, unknown >;
					}
				>;
				extensionData?: Record< string, unknown >;
			} | null;

			findProduct: ( args: {
				id: number;
				selectedAttributes?:
					| Array< {
							raw_attribute: string;
							attribute: string;
							value: string;
					  } >
					| null;
			} ) => ProductResponseItem | null;
		};
		actions: {
			setProductInputAttributes: ( args: {
				productId?: number;
				selectedAttributes: Array< {
					raw_attribute: string;
					attribute: string;
					value: string;
				} >;
			} ) => void;
			upsertProductInputLine: ( args: {
				productId?: number;
				line: {
					draftKey: string; // Distinguishes same purchasable product with different any-attribute selections.
					purchasableProductId: number;
					quantity: number;
					variation?: Array< {
						raw_attribute: string;
						attribute: string;
						value: string;
					} >;
					extensionData?: Record< string, unknown >;
				};
			} ) => void;
			removeProductInputLine: ( args: {
				productId?: number;
				draftKey: string;
			} ) => void;
			clearProductInput: ( args?: { productId?: number } ) => void;
		};
	};

	'woocommerce/cart': {
		state: {
			cart: Omit< Cart, 'items' > & {
				items: Array<
					| CartItem
					| {
							key?: string;
							id: number;
							quantity: number;
							variation?: Array< {
								raw_attribute: string;
								attribute: string;
								value: string;
							} >;
							type: string;
					  }
				>;
			};
			cartItemInContext:
				| CartItem
				| {
						key?: string;
						id: number;
						quantity: number;
						variation?: Array< {
							raw_attribute: string;
							attribute: string;
							value: string;
						} >;
						type: string;
				  }
				| undefined;

			findCartItem: ( args: {
				key?: string;
				purchasableProductId?: number;
			} ) =>
				| CartItem
				| {
						key?: string;
						id: number;
						quantity: number;
						variation?: Array< {
							raw_attribute: string;
							attribute: string;
							value: string;
						} >;
						type: string;
				  }
				| undefined;
		};
		actions: {
			addCartItem: ( args: {
				key?: string;
				id: number;
				quantity?: number;
				quantityToAdd?: number;
				variation?: Array< {
					raw_attribute: string;
					attribute: string;
					value: string;
				} >;
			} ) => Promise< void >;
			batchAddCartItems: (
				items: Array< {
					key?: string;
					id: number;
					quantity?: number;
					quantityToAdd?: number;
					variation?: Array< {
						raw_attribute: string;
						attribute: string;
						value: string;
					} >;
				} >
			) => Promise< void >;
			removeCartItem: ( key: string ) => Promise< void >;
			refreshCartItems: () => Promise< void >;
		};
	};
};
```
