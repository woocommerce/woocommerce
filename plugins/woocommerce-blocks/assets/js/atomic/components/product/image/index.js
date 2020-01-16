/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Fragment, useState } from '@wordpress/element';
import classnames from 'classnames';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/block-settings';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

/**
 * Internal dependencies
 */
import { ProductSaleBadge } from '../../../components/product';

const SaleBadge = ( { product, saleBadgeAlign, shouldRender } ) => {
	return shouldRender ? (
		<ProductSaleBadge product={ product } align={ saleBadgeAlign } />
	) : null;
};

const Image = ( { layoutPrefix, loaded, image, onLoad } ) => {
	const cssClass = classnames( `${ layoutPrefix }__product-image__image`, {
		[ `${ layoutPrefix }__product-image__image_placeholder` ]:
			! loaded && ! image,
	} );
	const { thumbnail, srcset, sizes, alt } = image || {};
	return (
		<Fragment>
			{ image && (
				<img
					className={ cssClass }
					src={ thumbnail }
					srcSet={ srcset }
					sizes={ sizes }
					alt={ alt }
					onLoad={ onLoad }
					hidden={ ! loaded }
				/>
			) }
			{ ! loaded && (
				<img
					className={ cssClass }
					src={ PLACEHOLDER_IMG_SRC }
					alt=""
				/>
			) }
		</Fragment>
	);
};

const ProductImage = ( {
	className,
	product,
	productLink = true,
	showSaleBadge = true,
	saleBadgeAlign = 'right',
} ) => {
	const [ imageLoaded, setImageLoaded ] = useState( false );
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const image =
		product.images && product.images.length ? product.images[ 0 ] : null;

	const renderedSalesAndImage = (
		<Fragment>
			<SaleBadge
				product={ product }
				saleBadgeAlign={ saleBadgeAlign }
				shouldRender={ showSaleBadge }
			/>
			<Image
				layoutPrefix={ layoutStyleClassPrefix }
				loaded={ imageLoaded }
				image={ image }
				onLoad={ () => setImageLoaded( true ) }
			/>
		</Fragment>
	);

	return (
		<div
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-image`
			) }
		>
			{ productLink ? (
				<a href={ product.permalink } rel="nofollow">
					{ renderedSalesAndImage }
				</a>
			) : (
				renderedSalesAndImage
			) }
		</div>
	);
};

ProductImage.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
	productLink: PropTypes.bool,
	showSaleBadge: PropTypes.bool,
	saleBadgeAlign: PropTypes.string,
};

export default ProductImage;
