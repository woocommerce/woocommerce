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
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { isEmpty } from 'lodash';

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
 * @param {string} [props.imageSizing]    Size of image to use.
 * @param {boolean} [props.productLink]   Whether or not to display a link to the product page.
 * @param {boolean} [props.showSaleBadge] Whether or not to display the on sale badge.
 * @param {string} [props.saleBadgeAlign] How should the sale badge be aligned if displayed.
 * @return {*} The component.
 */
const Block = ( {
	className,
	imageSizing = 'full-size',
	productLink = true,
	showSaleBadge,
	saleBadgeAlign = 'right',
} ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const [ imageLoaded, setImageLoaded ] = useState( false );

	if ( ! product.id ) {
		return (
			<div
				className={ classnames(
					className,
					'wc-block-components-product-image',
					'wc-block-components-product-image--placeholder',
					{
						[ `${ parentClassName }__product-image` ]: parentClassName,
					}
				) }
			>
				<ImagePlaceholder />
			</div>
		);
	}

	const image = ! isEmpty( product.images ) ? product.images[ 0 ] : null;

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-image',
				{
					[ `${ parentClassName }__product-image` ]: parentClassName,
				}
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
	return (
		<img src={ PLACEHOLDER_IMG_SRC } alt="" width={ 500 } height={ 500 } />
	);
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
	productLink: PropTypes.bool,
	showSaleBadge: PropTypes.bool,
	saleBadgeAlign: PropTypes.string,
};

export default withProductDataContext( Block );
