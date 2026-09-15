/**
 * External dependencies
 */
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { getSetting, PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
import clsx from 'clsx';

function migrateFromV1( innerBlocks: BlockInstance[] ): BlockInstance[] {
	return [
		createBlock(
			'core/group',
			{
				className: 'wc-block-featured-category__inner-blocks',
				style: {
					spacing: {
						padding: {
							top: '0px',
							right: '48px',
							bottom: '16px',
							left: '48px',
						},
					},
				},
				layout: { type: 'default' },
			},
			innerBlocks
		),
	];
}

function migrateFromV0(
	attributes: BlockInstance[ 'attributes' ],
	innerBlocks: BlockInstance[]
): BlockInstance[] {
	const legacySpacing = {
		padding: { top: '0px', right: '48px', bottom: '16px', left: '48px' },
	};
	const historicalStyle = {
		spacing: legacySpacing,
		typography: { lineHeight: '1.25' },
	};
	// Fallback for v0: title and description weren't saved as inner blocks.
	const historicalContent = [
		createBlock( 'woocommerce/category-title', {
			level: 2,
			isLink: false,
			textAlign: attributes.contentAlign || 'center',
			style: historicalStyle,
		} ),
	];
	if ( attributes.showDesc !== false ) {
		historicalContent.push(
			createBlock( 'woocommerce/category-description', {
				textAlign: attributes.contentAlign || 'center',
				style: historicalStyle,
			} )
		);
	}

	return [ ...historicalContent, ...migrateFromV1( innerBlocks ) ];
}

export function migrateToCover(
	attributes: BlockInstance[ 'attributes' ],
	innerBlocks: BlockInstance[]
): [ BlockInstance[ 'attributes' ], BlockInstance[] ] {
	if ( attributes.layout === 'cover' ) {
		return [ { ...attributes, layout: 'cover' }, innerBlocks ];
	}
	const {
		align,
		ariaLabel,
		categoryId,
		metadata,
		lock,
		anchor,
		className,
		style = {},
	} = attributes;
	const customImage = Boolean( attributes.mediaId || attributes.mediaSrc );
	// Mirrors FeaturedItem::get_image_size() to preserve the old image size.
	const size =
		( ! align || align === 'none' ) && ( attributes.height || 500 ) <= 800
			? 'large'
			: 'full';
	const imageUrl =
		attributes.mediaSrc ||
		getSetting( 'placeholderImgSrcFullSize', PLACEHOLDER_IMG_SRC );
	const cover = createBlock( 'core/cover', {
		className: clsx( 'wc-block-featured-category__legacy', {
			'wc-block-featured-category__natural-image':
				attributes.imageFit !== 'cover',
		} ),
		backgroundType: 'image',
		id: attributes.mediaId || undefined,
		url: imageUrl,
		alt: attributes.alt || '',
		contentPosition: `center ${ attributes.contentAlign || 'center' }`,
		dimRatio: attributes.dimRatio ?? 50,
		customOverlayColor: attributes.overlayColor || '#000000',
		isUserOverlayColor: true,
		customGradient: attributes.overlayGradient,
		focalPoint: attributes.focalPoint || { x: 0.5, y: 0.5 },
		hasParallax: attributes.hasParallax || false,
		isRepeated: attributes.isRepeated || false,
		minHeight:
			attributes.minHeight ??
			attributes.height ??
			getSetting( 'defaultHeight', 500 ),
		minHeightUnit: 'px',
		fontSize: attributes.fontSize,
		borderColor: attributes.borderColor,
		backgroundColor: attributes.backgroundColor,
		textColor: attributes.textColor,
		style: {
			...style,
			spacing: {
				...style.spacing,
				padding: style.spacing?.padding ?? '0px',
			},
			typography: {
				...style.typography,
				lineHeight:
					attributes.lineHeight ?? style.typography?.lineHeight,
			},
		},
		...( customImage && ! attributes.mediaId
			? {}
			: {
					metadata: {
						'woocommerce/featured-category-image': {
							id: attributes.mediaId || 0,
							url: imageUrl,
							size,
							noPlaceholder: true,
							...( customImage
								? { attachmentId: attributes.mediaId }
								: {} ),
						},
					},
			  } ),
	} );
	const migratedAttributes = {
		align,
		ariaLabel,
		categoryId,
		metadata,
		lock,
		anchor,
		className,
		layout: 'cover',
		termTaxonomy: 'product_cat',
	};

	if ( typeof attributes.editMode === 'boolean' ) {
		cover.innerBlocks = migrateFromV0( attributes, innerBlocks );
		return [ migratedAttributes, [ cover ] ];
	}

	cover.innerBlocks = migrateFromV1( innerBlocks );
	return [ migratedAttributes, [ cover ] ];
}
