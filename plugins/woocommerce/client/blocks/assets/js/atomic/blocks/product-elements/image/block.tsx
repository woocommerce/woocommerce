/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useStoreEvents } from '@woocommerce/base-context/hooks';
import type { HTMLAttributes } from 'react';
import { decodeEntities } from '@wordpress/html-entities';
import {
	isString,
	objectHasProp,
	isEmpty,
	ProductResponseItem,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ProductSaleBadge from '../sale-badge/block';
import './style.scss';
import { BlockAttributes, ImageSizing, ProductImageContext } from './types';
import { isTryingToDisplayLegacySaleBadge } from './utils';

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

const ImagePlaceholder = ( props ): JSX.Element => {
	return (
		<img
			{ ...props }
			src={ PLACEHOLDER_IMG_SRC }
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
	showFullSize: boolean;
	fallbackAlt: string;
	scale: string;
	width?: string | undefined;
	height?: string | undefined;
	aspectRatio: string | undefined;
}

const Image = ( {
	image,
	loaded,
	showFullSize,
	fallbackAlt,
	width,
	scale,
	height,
	aspectRatio,
}: ImageProps ): JSX.Element => {
	const { thumbnail, src, srcset, sizes, alt } = image || {};
	const imageProps = {
		alt: alt || fallbackAlt,
		hidden: ! loaded,
		src: thumbnail,
		...( showFullSize && { src, srcSet: srcset, sizes } ),
	};

	const imageStyles: Record< string, string | undefined > = {
		height,
		width,
		objectFit: scale,
		aspectRatio,
	};

	return (
		<>
			{ imageProps.src && (
				/* eslint-disable-next-line jsx-a11y/alt-text */
				<img
					style={ imageStyles }
					data-testid="product-image"
					{ ...imageProps }
				/>
			) }
			{ ! image && <ImagePlaceholder style={ imageStyles } /> }
		</>
	);
};

type Props = BlockAttributes &
	Pick< ProductImageContext, 'imageId' > &
	HTMLAttributes< HTMLDivElement > & { style?: Record< string, unknown > };

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
		imageSizing = ImageSizing.SINGLE,
		scale,
		showProductLink = true,
		style,
		width,
		...restProps
	} = props;

	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product, isLoading } = useProductDataContext();
	const { dispatchStoreEvent } = useStoreEvents();

	if ( ! product?.id ) {
		return (
			<>
				<div
					className={ clsx(
						className,
						'wc-block-components-product-image',
						{
							[ `${ parentClassName }__product-image` ]:
								parentClassName,
						},
						styleProps.className
					) }
					style={ styleProps.style }
				>
					<ImagePlaceholder />
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
				<ParentComponent { ...( showProductLink && anchorProps ) }>
					<Image
						fallbackAlt={ decodeEntities( product.name ) }
						image={ image }
						loaded={ ! isLoading }
						showFullSize={ imageSizing !== ImageSizing.THUMBNAIL }
						width={ width }
						height={ height }
						scale={ scale }
						aspectRatio={
							objectHasProp( style, 'dimensions' ) &&
							objectHasProp( style.dimensions, 'aspectRatio' ) &&
							isString( style.dimensions.aspectRatio )
								? style.dimensions.aspectRatio
								: aspectRatio
						}
					/>
				</ParentComponent>
			</div>
			{ children }
		</>
	);
};

export default withProductDataContext( Block );
