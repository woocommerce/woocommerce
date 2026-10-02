/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { PLACEHOLDER_IMG_SRC, getSetting } from '@woocommerce/settings';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useStoreEvents } from '@woocommerce/base-context/hooks';
import type { HTMLAttributes } from 'react';
import { decodeEntities } from '@wordpress/html-entities';
import { isEmpty, ProductResponseItem } from '@woocommerce/types';
import { ProductEntityResponse } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import ProductSaleBadge from '../sale-badge/block';
import './style.scss';
import { BlockAttributes, ProductImageContext } from './types';
import { isTryingToDisplayLegacySaleBadge, resolveAspectRatio } from './utils';

const buildStyles = ( props: Partial< ImageProps > ) => {
	const { aspectRatio, height, width, scale } = props;
	return {
		height,
		width,
		objectFit: scale,
		aspectRatio,
	};
};

const chooseImage = ( product: ProductResponseItem, imageId?: number ) => {
	// Default to placeholder image if no product images are available.
	if ( ! product.images.length ) {
		return null;
	}

	if ( imageId ) {
		// If an image ID is provided, use that image or fallback to featured image.
		const image = product.images.find( ( img ) => img.id === imageId );
		return image || product.images[ 0 ];
	}

	// If no image ID is provided, use the featured image.
	return product.images[ 0 ];
};

const ImagePlaceholder = ( props: {
	style?: Record< string, unknown >;
} ): JSX.Element => {
	const src = getSetting( 'placeholderImgSrcFullSize', PLACEHOLDER_IMG_SRC );

	return (
		<img
			{ ...props }
			src={ src }
			// Decorative image with no value, so alt should be empty.
			alt=""
			width={ undefined }
			height={ undefined }
		/>
	);
};

interface ImageProps {
	image?: null | {
		alt?: string | undefined;
		id: number;
		name: string;
		sizes?: string | undefined;
		src?: string | undefined;
		srcset?: string | undefined;
		thumbnail?: string | undefined;
	};
	loaded: boolean;
	fallbackAlt: string;
	scale: 'cover' | 'contain' | 'fill';
	width?: string | undefined;
	height?: string | undefined;
	aspectRatio: string | undefined;
}

const Image = ( {
	image,
	loaded,
	fallbackAlt,
	width,
	scale,
	height,
	aspectRatio,
}: ImageProps ): JSX.Element => {
	const { src, srcset, sizes, alt } = image || {};
	const imageProps = {
		alt: alt || fallbackAlt,
		hidden: ! loaded,
		src,
		srcSet: srcset,
		sizes,
	};

	const imageStyles = buildStyles( {
		height,
		width,
		scale,
		aspectRatio,
	} );

	if ( ! image ) {
		return <ImagePlaceholder style={ imageStyles } />;
	}

	return (
		/* eslint-disable-next-line jsx-a11y/alt-text */
		<img
			style={ imageStyles }
			data-testid="product-image"
			{ ...imageProps }
		/>
	);
};

type Props = BlockAttributes &
	Pick< ProductImageContext, 'imageId' > &
	Omit< HTMLAttributes< HTMLDivElement >, 'style' > & {
		isAdmin?: boolean;
		product?: ProductResponseItem | ProductEntityResponse;
		isResolving?: boolean;
	};

type LegacyProps = Props & {
	product?: ProductResponseItem;
};

// props.product is not listed in the BlockAttributes explicitly,
// but it is implicitly passed from the All Products block.
// This is what distinguishes this block from the other usage of the Product Image component.
const displayLegacySaleBadge = ( props: LegacyProps ) => {
	const { product } = props;
	const isInAllProducts = ! isEmpty( product );

	if ( isInAllProducts ) {
		return isTryingToDisplayLegacySaleBadge( props.showSaleBadge );
	}

	return false;
};

export const Block = ( props: Props ): JSX.Element | null => {
	const {
		aspectRatio,
		children,
		className,
		height,
		imageId,
		imageSizing,
		scale,
		showProductLink = true,
		style,
		width,
		isAdmin,
		product: productEntity,
		isResolving,
		...restProps
	} = props;

	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product, isLoading } = useProductDataContext( {
		isAdmin,
		product: productEntity,
		isResolving,
	} );
	const { dispatchStoreEvent } = useStoreEvents();

	const storeAspectRatio = getSetting< string | null >(
		'thumbnailAspectRatio',
		null
	);
	const finalAspectRatio = resolveAspectRatio(
		style,
		aspectRatio,
		storeAspectRatio,
		imageSizing
	);
	const aspectRatioClass = `wc-block-components-product-image--aspect-ratio-${
		finalAspectRatio ? finalAspectRatio.replace( '/', '-' ) : 'auto'
	}`;

	if ( ! product?.id ) {
		const imageStyles = buildStyles( {
			height,
			width,
			scale,
			aspectRatio: finalAspectRatio,
		} );

		return (
			<>
				<div
					className={ clsx(
						className,
						'wc-block-components-product-image',
						aspectRatioClass,
						{
							[ `${ parentClassName }__product-image` ]:
								parentClassName,
						},
						styleProps.className
					) }
					style={ styleProps.style }
				>
					<ImagePlaceholder style={ imageStyles } />
				</div>
				{ children }
			</>
		);
	}

	const image = chooseImage( product, imageId );

	if ( image ) {
		image.alt = image.alt || decodeEntities( product.name );
	}

	const ParentComponent = showProductLink ? 'a' : Fragment;
	const anchorLabel = product?.name
		? // translators: %s is the product name.
		  sprintf( __( 'Link to %s', 'woocommerce' ), product.name )
		: '';
	const anchorProps = {
		href: showProductLink ? product?.permalink : undefined,
		...( showProductLink && {
			'aria-label': anchorLabel,
			onClick: () => {
				dispatchStoreEvent( 'product-view-link', {
					product,
				} );
			},
		} ),
	};

	return (
		<>
			<div
				className={ clsx(
					className,
					'wc-block-components-product-image',
					aspectRatioClass,
					{
						[ `${ parentClassName }__product-image` ]:
							parentClassName,
					},
					styleProps.className
				) }
				style={ styleProps.style }
			>
				{ /* For backwards compatibility in All Products blocks. */ }
				{ displayLegacySaleBadge( props ) && (
					<ProductSaleBadge
						align={ props.saleBadgeAlign || 'right' }
						{ ...restProps }
					/>
				) }
				<ParentComponent
					{ ...( ! isAdmin && showProductLink && anchorProps ) }
				>
					<Image
						fallbackAlt={ decodeEntities( product.name ) }
						image={ image }
						loaded={ ! isLoading }
						width={ width }
						height={ height }
						scale={ scale }
						aspectRatio={ finalAspectRatio }
					/>
				</ParentComponent>
			</div>
			{ children }
		</>
	);
};

export default withProductDataContext( Block );
