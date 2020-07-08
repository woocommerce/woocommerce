/**
 * External dependencies
 */
import ErrorPlaceholder from '@woocommerce/block-components/error-placeholder';

/**
 * Shown when there is an API error getting a product.
 */
const ApiError = ( { error, isLoading, getProduct } ) => (
	<ErrorPlaceholder
		className="wc-block-single-product-error"
		error={ error }
		isLoading={ isLoading }
		onRetry={ getProduct }
	/>
);

export default ApiError;
