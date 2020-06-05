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
					/>
				</>
			) }
		</div>
	);
};

const ImagePlaceholder = () => {
	return <img src={ PLACEHOLDER_IMG_SRC } alt="" />;
};

const Image = ( { image, onLoad, loaded } ) => {
	const { thumbnail, srcset, sizes, alt } = image || {};

	return (
		<>
			<img
				src={ thumbnail }
				srcSet={ srcset }
				sizes={ sizes }
				alt={ alt }
				onLoad={ onLoad }
				hidden={ ! loaded }
			/>
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
