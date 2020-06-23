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
import ProductSaleBadge from './../sale-badge/block';
import './style.scss';

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
const Block = ( {
	className,
	imageSizing = 'full-size',
	productLink = true,
	showSaleBadge,
	saleBadgeAlign = 'right',
	...props
} ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;
	const [ imageLoaded, setImageLoaded ] = useState( false );

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					'wc-block-components-product-image',
					'wc-block-components-product-image--placeholder',
					`${ parentClassName }__product-image`
				) }
			>
				<ImagePlaceholder />
			</div>
		);
	}

	const image =
		product?.images && product.images.length ? product.images[ 0 ] : null;

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-image',
				`${ parentClassName }__product-image`
			) }
		>
			{ productLink ? (
				<a href={ product.permalink } rel="nofollow">
					{ !! showSaleBadge && (
						<ProductSaleBadge
							align={ saleBadgeAlign }
							product={ product }
						/>
					) }
					<Image
						image={ image }
						onLoad={ () => setImageLoaded( true ) }
						loaded={ imageLoaded }
						showFullSize={ imageSizing !== 'cropped' }
					/>
				</a>
			) : (
				<>
					{ !! showSaleBadge && (
						<ProductSaleBadge
							align={ saleBadgeAlign }
							product={ product }
						/>
					) }
					<Image
						image={ image }
						onLoad={ () => setImageLoaded( true ) }
						loaded={ imageLoaded }
						showFullSize={ imageSizing !== 'cropped' }
					/>
				</>
			) }
		</div>
	);
};

const ImagePlaceholder = () => {
	return <img src={ PLACEHOLDER_IMG_SRC } alt="" />;
};

const Image = ( { image, onLoad, loaded, showFullSize } ) => {
	const { thumbnail, src, srcset, sizes, alt } = image || {};

	let imageProps = {
		alt,
		onLoad,
		hidden: ! loaded,
		src: thumbnail,
	};
	if ( showFullSize ) {
		imageProps = {
			...imageProps,
			src,
			srcSet: srcset,
			sizes,
		};
	}

	return (
		<>
			{ /* eslint-disable-next-line jsx-a11y/alt-text */ }
			<img { ...imageProps } />
			{ ! loaded && <ImagePlaceholder /> }
		</>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
	productLink: PropTypes.bool,
	showSaleBadge: PropTypes.bool,
	saleBadgeAlign: PropTypes.string,
};

export default Block;
