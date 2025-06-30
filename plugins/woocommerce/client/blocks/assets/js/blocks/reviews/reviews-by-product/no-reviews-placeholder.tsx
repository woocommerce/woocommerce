/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Placeholder, Spinner } from '@wordpress/components';
import ErrorPlaceholder, {
	ErrorObject,
} from '@woocommerce/editor-components/error-placeholder';
import { Icon, commentContent } from '@wordpress/icons';
import { withProduct } from '@woocommerce/block-hocs';
import { decodeEntities } from '@wordpress/html-entities';

interface Product {
	name: string;
	review_count: number;
}

interface NoReviewsPlaceholderProps {
	error?: ErrorObject;
	getProduct: () => void;
	isLoading: boolean;
	product?: Product;
}

const NoReviewsPlaceholder = ( {
	error,
	getProduct,
	isLoading,
	product,
}: NoReviewsPlaceholderProps ) => {
	const renderApiError = () => (
		<ErrorPlaceholder
			className="wc-block-featured-product-error"
			error={ error as ErrorObject }
			isLoading={ isLoading }
			onRetry={ getProduct }
		/>
	);

	if ( error ) {
		return renderApiError();
	}

	const content =
		! product || isLoading ? (
			<Spinner />
		) : (
			sprintf(
				/* translators: %s is the product name. */
				__(
					"This block lists reviews for a selected product. %s doesn't have any reviews yet, but they will show up here when it does.",
					'woocommerce'
				),
				decodeEntities( product.name )
			)
		);

	return (
		<Placeholder
			className="wc-block-reviews-by-product"
			icon={
				<Icon
					icon={ commentContent }
					className="block-editor-block-icon"
				/>
			}
			label={ __( 'Reviews by Product', 'woocommerce' ) }
		>
			{ content }
		</Placeholder>
	);
};

export default withProduct( NoReviewsPlaceholder );
