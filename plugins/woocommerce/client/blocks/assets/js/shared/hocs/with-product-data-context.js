/**
 * External dependencies
 */
import { useStoreProducts } from '@woocommerce/base-context/hooks';
import {
	ProductDataContextProvider,
	useProductDataContext,
} from '@woocommerce/shared-context';

const placeholderProduct = {
	id: true,
	name: 'Product Name',
	parent: 0,
	type: 'simple',
	variation: '',
	permalink: 'http://localhost/product/product-name/',
	sku: 'product-name',
	slug: 'product-name',
	short_description: 'This is example product.',
	description: "This is example product's description.",
	on_sale: true,
	prices: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		price: '100',
		regular_price: '100',
		sale_price: '90',
		price_range: null,
	},
	price: '100',
	regular_price: '100',
	sale_price: '90',
	price_html:
		'<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span>90</bdi></span>',
	average_rating: '4.5',
	review_count: 5,
	images: [],
	categories: [],
	tags: [],
	attributes: [],
	variations: [],
	has_options: false,
	is_purchasable: true,
	is_in_stock: true,
	is_on_backorder: false,
	low_stock_remaining: null,
	stock_availability: {
		text: 'In stock',
		class: 'in-stock',
	},
	sold_individually: false,
	add_to_cart: {
		text: 'Add to cart',
		single_text: 'Add to cart',
		description: 'Add to cart',
		url: 'http://localhost/product/product-name/',
		minimum: 1,
		maximum: 99,
		multiple_of: 1,
	},
	grouped_products: [],
};

const getProductById = ( products, id ) =>
	products.find( ( product ) => product.id === id );

const getProductId = ( isDescendentOfQueryLoop, productId, postId ) => {
	// Keep for backwards compatibility of Products (Beta) block.
	if ( isDescendentOfQueryLoop ) {
		return postId;
	}

	return productId || postId;
};

/**
 * Loads the product from the API and adds to the context provider.
 *
 * @param {Object} props Component props.
 */
const OriginalComponentWithContext = ( props ) => {
	const {
		productId,
		OriginalComponent,
		postId,
		product,
		isDescendentOfQueryLoop,
	} = props;

	const id = getProductId( isDescendentOfQueryLoop, productId, postId );

	const { products, productsLoading } = useStoreProducts( {
		include: id,
	} );

	const productFromAPI = {
		product:
			id > 0 && products.length > 0
				? getProductById( products, id )
				: null,
		isLoading: productsLoading,
	};

	if ( product ) {
		return (
			<ProductDataContextProvider product={ product } isLoading={ false }>
				<OriginalComponent { ...props } />
			</ProductDataContextProvider>
		);
	}

	return (
		<ProductDataContextProvider
			product={ productFromAPI.product }
			isLoading={ productFromAPI.isLoading }
		>
			<OriginalComponent { ...props } />
		</ProductDataContextProvider>
	);
};

/**
 * This HOC sees if the Block is wrapped in Product Data Context, and if not, wraps it with context
 * based on the productId attribute, if set.
 *
 * @param {Function} OriginalComponent Component being wrapped.
 */
export const withProductDataContext = ( OriginalComponent ) => {
	return ( props ) => {
		const productDataContext = useProductDataContext( {
			isAdmin: props.isAdmin,
			product: props.product,
		} );

		// If a product prop was provided, use this as the context for the tree.
		if (
			( !! props.product || ! productDataContext.hasContext ) &&
			! props.isAdmin
		) {
			if ( props.postId === 'placeholder' ) {
				console.log( 'placeholder' );
				return (
					<ProductDataContextProvider
						product={ placeholderProduct }
						isLoading={ false }
					>
						<OriginalComponent { ...props } />
					</ProductDataContextProvider>
				);
			}
			return (
				<OriginalComponentWithContext
					{ ...props }
					OriginalComponent={ OriginalComponent }
				/>
			);
		}

		return <OriginalComponent { ...props } />;
	};
};
