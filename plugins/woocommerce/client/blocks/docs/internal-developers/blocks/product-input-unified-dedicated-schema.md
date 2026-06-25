# Unified store: dedicated product input domain

```typescript
type WooCommerceStore = {
	state: {
		products: {
			productsById: Record< number, ProductResponseItem >;
			productVariationsById: Record< number, ProductResponseItem >;

			productId: number;
			variationId: number | null;
			mainProductInContext: ProductResponseItem | null;
			productVariationInContext: ProductResponseItem | null;
			productInContext: ProductResponseItem | null;

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

		productInput: {
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
		};

		cart: {
			cartResponse: Omit< Cart, 'items' > & {
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
	};

	actions: {
		products: Record< string, never >;

		productInput: {
			setAttributes: ( args: {
				productId?: number;
				selectedAttributes: Array< {
					raw_attribute: string;
					attribute: string;
					value: string;
				} >;
			} ) => void;
			upsertLine: ( args: {
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
			removeLine: ( args: {
				productId?: number;
				draftKey: string;
			} ) => void;
			clear: ( args?: { productId?: number } ) => void;
		};

		cart: {
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
