/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import ProductListHeader from '../product-list-header/product-list-header';
import ProductCard from '../product-card/product-card';

interface ProductLoaderProps {
	hasTitle?: boolean;
	placeholderCount?: number;
	type: string;
}

export default function ProductLoader(
	props: ProductLoaderProps
): JSX.Element {
	const { hasTitle, type } = props;
	const placeholderCount = props.placeholderCount || 12;

	return (
		<div className="woocommerce-marketplace__product-list">
			{ hasTitle !== false && (
				<ProductListHeader title="" groupURL={ null } />
			) }
			<div className="woocommerce-marketplace__product-list-content">
				{ [ ...Array( placeholderCount ) ].map( ( element, i ) => (
					<ProductCard
						key={ i }
						isLoading={ true }
						type={ type }
						tracksData={ {} }
					/>
				) ) }
			</div>
		</div>
	);
}
