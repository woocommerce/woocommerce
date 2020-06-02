/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { useState } from '@wordpress/element';
import classnames from 'classnames';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/block-settings';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import ProductSaleBadge from '../sale-badge/block.js';

/**
 * Product Image Block Component.
 *
 * @param {Object} props                  Incoming props.
 * @param {string} [props.className]      CSS Class name for the component.
 * @param {boolean} [props.productLink]   Whether or not to display a link to the product page.
 * @param {boolean} [props.showSaleBadge] Whether or not to display the on sale badge.
 * @param {string} [props.saleBadgeAlign] How should the sale badge be aligned if displayed.
 * @param {Object} [props.product]        Optional product object. Product from context will be used if
 *                                        this is not provided.
 * @return {*} The component.
 */
const ProductImage = ( {
	className,
	productLink = true,
	showSaleBadge = true,
	saleBadgeAlign = 'right',
	...props
} ) => {
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	const { layoutStyleClassPrefix } = useInnerBlockLayoutContext();
	const componentClass = `${ layoutStyleClassPrefix }__product-image`;

	const [ imageLoaded, setImageLoaded ] = useState( false );

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					componentClass,
					'is-loading'
				) }
			>
				<ImagePlaceholder componentClass={ componentClass } />
			</div>
		);
	}

	const image =
		product?.images && product.images.length ? product.images[ 0 ] : null;

	return (
		<div className={ classnames( className, componentClass ) }>
			{ productLink ? (
				<a href={ product.permalink } rel="nofollow">
					{ showSaleBadge && (
						<ProductSaleBadge align={ saleBadgeAlign } />
					) }
					<Image
						componentClass={ componentClass }
						image={ image }
						onLoad={ () => setImageLoaded( true ) }
						loaded={ imageLoaded }
					/>
				</a>
			) : (
				<>
					{ showSaleBadge && (
						<ProductSaleBadge align={ saleBadgeAlign } />
					) }
					<Image
						componentClass={ componentClass }
						image={ image }
						onLoad={ () => setImageLoaded( true ) }
						loaded={ imageLoaded }
					/>
				</>
			) }
		</div>
	);
};

const ImagePlaceholder = ( { componentClass } ) => {
	return (
		<img
			className={ classnames(
				`${ componentClass }__image`,
				`${ componentClass }__image_placeholder`
			) }
			src={ PLACEHOLDER_IMG_SRC }
			alt=""
		/>
	);
};

const Image = ( { componentClass, image, onLoad, loaded } ) => {
	const { thumbnail, srcset, sizes, alt } = image || {};

	return (
		<>
			<img
				className={ classnames( `${ componentClass }__image` ) }
				src={ thumbnail }
				srcSet={ srcset }
				sizes={ sizes }
				alt={ alt }
				onLoad={ onLoad }
				hidden={ ! loaded }
			/>
			{ ! loaded && (
				<ImagePlaceholder componentClass={ componentClass } />
			) }
		</>
	);
};

ProductImage.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
	productLink: PropTypes.bool,
	showSaleBadge: PropTypes.bool,
	saleBadgeAlign: PropTypes.string,
};

export default ProductImage;
