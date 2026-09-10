/**
 * External dependencies
 */
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';

/**
 * Convert each historical format directly to the current Cover structure.
 * Keep this mapping stable: saved posts depend on its defaults and styling.
 */
export function migrateToCover(
	attributes: BlockInstance[ 'attributes' ],
	innerBlocks: BlockInstance[]
): [ BlockInstance[ 'attributes' ], BlockInstance[] ] {
	if (
		attributes.layout === 'cover' ||
		innerBlocks.some( ( block ) => block.name === 'core/cover' )
	) {
		return [ { ...attributes, layout: 'cover' }, innerBlocks ];
	}
	const {
		align,
		ariaLabel,
		categoryId,
		source,
		metadata,
		lock,
		anchor,
		className,
		style = {},
	} = attributes;
	const legacySpacing = {
		padding: { top: '0px', right: '48px', bottom: '16px', left: '48px' },
	};
	const historicalContent: BlockInstance[] = [];
	if ( typeof attributes.editMode === 'boolean' ) {
		const historicalStyle = {
			spacing: legacySpacing,
			typography: { lineHeight: '1.25' },
		};
		historicalContent.push(
			createBlock( 'woocommerce/category-title', {
				level: 2,
				isLink: false,
				textAlign: attributes.contentAlign || 'center',
				style: historicalStyle,
			} )
		);
		if ( attributes.showDesc !== false ) {
			historicalContent.push(
				createBlock( 'woocommerce/category-description', {
					textAlign: attributes.contentAlign || 'center',
					style: historicalStyle,
				} )
			);
		}
	}
	const customImage = Boolean( attributes.mediaId || attributes.mediaSrc );
	const size =
		( ! align || align === 'none' ) && ( attributes.height || 500 ) <= 800
			? 'large'
			: 'full';
	const imageBinding = {
		source: 'woocommerce/term-image',
		args: {
			size,
			noPlaceholder: true,
			...( customImage ? { attachmentId: attributes.mediaId } : {} ),
		},
	};
	const cover = createBlock(
		'core/cover',
		{
			className: [
				'wc-block-featured-category__cover',
				'wc-block-featured-category__legacy',
				attributes.imageFit !== 'cover' &&
					'wc-block-featured-category__natural-image',
			]
				.filter( Boolean )
				.join( ' ' ),
			backgroundType: 'image',
			id: attributes.mediaId || undefined,
			url:
				attributes.mediaSrc ||
				getSetting< string >( 'placeholderImgSrcFullSize', '' ),
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
					...( attributes.lineHeight !== undefined
						? { lineHeight: attributes.lineHeight }
						: {} ),
				},
			},
			...( customImage && ! attributes.mediaId
				? {}
				: {
						metadata: {
							bindings: { id: imageBinding, url: imageBinding },
						},
				  } ),
		},
		[
			...historicalContent,
			createBlock(
				'core/group',
				{
					className: 'wc-block-featured-category__inner-blocks',
					style: { spacing: legacySpacing },
					layout: { type: 'default' },
				},
				innerBlocks
			),
		]
	);
	return [
		{
			align,
			ariaLabel,
			categoryId,
			source: source || ( categoryId ? 'selected' : 'context' ),
			metadata,
			lock,
			anchor,
			className,
			layout: 'cover',
			termTaxonomy: 'product_cat',
		},
		[ cover ],
	];
}
