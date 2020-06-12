/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss'
import { ProductIcon } from '../../components/';
import { recordEvent } from 'lib/tracks';
import { getInAppPurchaseUrl } from 'lib/in-app-purchase';

const RecommendedExtensionsItem = ( {
	title,
	description,
	icon,
	url,
} ) => {

	const onProductClick = () => {
		recordEvent( 'marketing_recommended_extension', { name: title } );
	}

	const classNameBase = 'woocommerce-marketing-recommended-extensions-item';
	const connectURL = getInAppPurchaseUrl( url );

	return (
		<a href={ connectURL }
			className={ classNameBase }
			onClick={ onProductClick }
		>
			<ProductIcon src={ icon } />

			<div className={ `${ classNameBase }__text` }>
				<h4>{ title }</h4>
				<p>{ description }</p>
			</div>
		</a>
	)
}

RecommendedExtensionsItem.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	icon: PropTypes.string.isRequired,
	url: PropTypes.string.isRequired,
};

export default RecommendedExtensionsItem;
