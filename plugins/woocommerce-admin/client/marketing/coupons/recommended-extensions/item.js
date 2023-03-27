/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import { ProductIcon } from '~/marketing/components';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';

const RecommendedExtensionsItem = ( {
	title,
	description,
	url,
	product,
	category,
} ) => {
	const onProductClick = () => {
		recordEvent( 'marketing_recommended_extension', { name: title } );
	};

	const classNameBase = 'woocommerce-marketing-recommended-extensions-item';
	const connectURL = getInAppPurchaseUrl( url );

	// Temporary fix to account for different styles between marketing & coupons
	if ( category === 'coupons' && product === 'automatewoo' ) {
		product = `automatewoo-alt`;
	}

	return (
		<a
			href={ connectURL }
			className={ classNameBase }
			onClick={ onProductClick }
		>
			<ProductIcon product={ product } />

			<div className={ `${ classNameBase }__text` }>
				<h4>{ title }</h4>
				<p>{ description }</p>
			</div>
		</a>
	);
};

RecommendedExtensionsItem.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	url: PropTypes.string.isRequired,
	product: PropTypes.string.isRequired,
	category: PropTypes.string.isRequired,
};

export default RecommendedExtensionsItem;
