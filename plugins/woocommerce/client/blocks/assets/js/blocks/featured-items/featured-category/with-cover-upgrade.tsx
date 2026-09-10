/**
 * External dependencies
 */
import { BlockControls } from '@wordpress/block-editor';
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import type { ComponentType } from 'react';
import type { WP_REST_API_Category } from 'wp-types';

/**
 * Internal dependencies
 */
import type { EditorBlock } from '../types';
import { getCategoryImageId, getCategoryImageSrc } from './utils';
import { bindCategoryButtonUrl } from './upgrade-utils';

interface Props< T > {
	attributes: EditorBlock< T >[ 'attributes' ] & {
		align?: string;
		alt?: string;
		backgroundColor?: string;
		categoryId?: number;
		className?: string;
		contentAlign?: string;
		dimRatio?: number;
		focalPoint?: { x: number; y: number };
		fontSize?: string;
		hasParallax?: boolean;
		isRepeated?: boolean;
		layout?: string;
		lineHeight?: string;
		mediaId?: number;
		mediaSrc?: string;
		minHeight?: number;
		overlayColor?: string;
		overlayGradient?: string;
		style?: Record< string, unknown >;
		textColor?: string;
	};
	category?: WP_REST_API_Category;
	clientId: string;
	context?: { termId?: number };
}

export const withCoverUpgrade =
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: T & Props< T > ) => {
		const { replaceBlock } = useDispatch( 'core/block-editor' );
		const block = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getBlock( props.clientId ) as
					| BlockInstance
					| undefined,
			[ props.clientId ]
		);
		if (
			props.attributes.layout === 'cover' ||
			( ! props.attributes.categoryId && props.context?.termId ) ||
			! props.category
		) {
			return <Component { ...props } />;
		}

		const upgrade = () => {
			const {
				align,
				alt,
				backgroundColor,
				className,
				contentAlign,
				dimRatio,
				focalPoint,
				fontSize,
				hasParallax,
				isRepeated,
				lineHeight,
				mediaId,
				mediaSrc,
				minHeight,
				overlayColor,
				overlayGradient,
				style,
				textColor,
				...remainingAttributes
			} = props.attributes;
			const usesCustomImage = Boolean( mediaId && mediaSrc );
			const imageId = usesCustomImage
				? mediaId
				: getCategoryImageId( props.category );
			const imageUrl = usesCustomImage
				? mediaSrc
				: getCategoryImageSrc( props.category ) ||
				  getSetting< string >( 'placeholderImgSrcFullSize', '' );
			const typography =
				typeof style?.typography === 'object' && style.typography
					? ( style.typography as Record< string, unknown > )
					: {};
			const coverStyle =
				typeof style === 'object' && style
					? ( style as Record< string, unknown > )
					: {};
			const coverAttributes: Record< string, unknown > = {
				align,
				alt,
				backgroundColor,
				backgroundType: 'image',
				className: [ className, 'wc-block-featured-category__cover' ]
					.filter( Boolean )
					.join( ' ' ),
				contentPosition: `${ contentAlign || 'center' } center`,
				customGradient: overlayGradient,
				customOverlayColor: overlayColor,
				dimRatio,
				focalPoint,
				fontSize,
				hasParallax,
				id: imageId || undefined,
				isRepeated,
				minHeight,
				minHeightUnit: 'px',
				style: {
					...coverStyle,
					typography: {
						...typography,
						lineHeight,
					},
				},
				textColor,
				url: imageUrl,
				...( usesCustomImage
					? {}
					: {
							metadata: {
								bindings: {
									id: {
										source: 'woocommerce/term-image',
									},
									url: {
										source: 'woocommerce/term-image',
									},
								},
							},
					  } ),
			};
			const cover = createBlock(
				'core/cover',
				coverAttributes,
				bindCategoryButtonUrl(
					block?.innerBlocks || [],
					props.category.permalink
				)
			);
			const upgradedBlock = createBlock(
				'woocommerce/featured-category',
				{
					...remainingAttributes,
					layout: 'cover',
					source: 'selected',
					termTaxonomy: 'product_cat',
				} as Record< string, unknown >,
				[ cover ]
			);

			void replaceBlock( props.clientId, upgradedBlock );
		};

		return (
			<>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="update"
							label={ __(
								'Upgrade to Cover layout',
								'woocommerce'
							) }
							onClick={ upgrade }
						/>
					</ToolbarGroup>
				</BlockControls>
				<Component { ...props } />
			</>
		);
	};
