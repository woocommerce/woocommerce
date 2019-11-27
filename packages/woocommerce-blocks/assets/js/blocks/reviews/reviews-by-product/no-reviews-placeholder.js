/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Placeholder, Spinner } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ApiErrorPlaceholder from '../../../components/api-error-placeholder';
import { IconReviewsByProduct } from '../../../components/icons';
import { withProduct } from '../../../hocs';

const NoReviewsPlaceholder = ( { error, getProduct, isLoading, product } ) => {
	const renderApiError = () => (
		<ApiErrorPlaceholder
			className="wc-block-featured-product-error"
			error={ error }
			isLoading={ isLoading }
			onRetry={ getProduct }
		/>
	);

	if ( error ) {
		return renderApiError();
	}

	const content = ( ! product || isLoading ) ?
		<Spinner /> :
		sprintf(
			__(
				"This block lists reviews for a selected product. %s doesn't have any reviews yet, but they will show up here when it does.",
				'woocommerce'
			),
			product.name
		);

	return (
		<Placeholder
			className="wc-block-reviews-by-product"
			icon={ <IconReviewsByProduct className="block-editor-block-icon" /> }
			label={ __( 'Reviews by Product', 'woocommerce' ) }
		>
			{ content }
		</Placeholder>
	);
};

NoReviewsPlaceholder.propTypes = {
	// from withProduct
	error: PropTypes.object,
	isLoading: PropTypes.bool,
	product: PropTypes.shape( {
		name: PropTypes.node,
		review_count: PropTypes.number,
	} ),
};

export default withProduct( NoReviewsPlaceholder );
