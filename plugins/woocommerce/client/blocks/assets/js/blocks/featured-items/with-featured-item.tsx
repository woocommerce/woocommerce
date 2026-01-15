/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import type { BlockAlignment } from '@wordpress/blocks';
import type { ComponentType, Dispatch, SetStateAction } from 'react';
import { ProductResponseItem } from '@woocommerce/types';
import { Icon, Placeholder, Spinner } from '@wordpress/components';
import { ProductDataContextProvider } from '@woocommerce/shared-context';
import clsx from 'clsx';
import {
	useCallback,
	useState,
	useEffect,
	useRef,
	useMemo,
} from '@wordpress/element';
import { WP_REST_API_Category } from 'wp-types';
import { useStyleProps } from '@woocommerce/base-hooks';
import {
	InnerBlocks,
	// @ts-expect-error BlockContextProvider is not exported from @wordpress/block-editor
	BlockContextProvider,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ConstrainedResizable } from './constrained-resizable';
import { EditorBlock, GenericBlockUIConfig } from './types';
import { BgImageDimensions, useBackgroundImage } from './use-background-image';
import {
	getBackgroundColorVisibilityStatus,
	dimRatioToClass,
	getBackgroundImageStyles,
	getClassPrefixFromName,
} from './utils';
import {
	FEATURED_CATEGORY_DEFAULT_TEMPLATE,
	FEATURED_PRODUCT_DEFAULT_TEMPLATE,
} from './constants';

interface WithFeaturedItemConfig extends GenericBlockUIConfig {
	emptyMessage: string;
	noSelectionButtonLabel: string;
}

export interface FeaturedItemRequiredAttributes {
	contentAlign: BlockAlignment;
	dimRatio: number;
	focalPoint: { x: number; y: number };
	hasParallax: boolean;
	imageFit: 'cover' | 'none';
	isRepeated: boolean;
	linkText: string;
	mediaId: number;
	mediaSrc: string;
	minHeight: number;
	overlayColor: string;
	overlayGradient: string;
	showDesc: boolean;
	showPrice: boolean;
	backgroundColor: string | undefined;
	style: { color: { background: string } };
	backgroundColorVisibilityStatus: {
		isBackgroundVisible: boolean;
		message?: string | null;
	};
	__woocommerceBlockVersion: number;
}

interface FeaturedCategoryRequiredAttributes
	extends FeaturedItemRequiredAttributes {
	categoryId: number | 'preview';
	productId: never;
}

interface FeaturedProductRequiredAttributes
	extends FeaturedItemRequiredAttributes {
	categoryId: never;
	productId: number | 'preview';
}

interface FeaturedItemRequiredProps< T > {
	attributes: (
		| FeaturedCategoryRequiredAttributes
		| FeaturedProductRequiredAttributes
	 ) &
		EditorBlock< T >[ 'attributes' ] & {
			// This is hardcoded because border and color are not yet included
			// in Gutenberg's official types.
			style: {
				border?: { radius?: number };
				color?: { text?: string };
			};
			textColor?: string;
		};
	isLoading: boolean;
	setAttributes: ( attrs: Partial< FeaturedItemRequiredAttributes > ) => void;
	useEditingImage: [ boolean, Dispatch< SetStateAction< boolean > > ];
	useEditMode: [ boolean, Dispatch< SetStateAction< boolean > > ];
}

interface FeaturedCategoryProps< T > extends FeaturedItemRequiredProps< T > {
	category: WP_REST_API_Category;
	product: never;
}

interface FeaturedProductProps< T > extends FeaturedItemRequiredProps< T > {
	category: never;
	product: ProductResponseItem;
}

type FeaturedItemProps< T extends EditorBlock< T > > =
	| ( T & FeaturedCategoryProps< T > )
	| ( T & FeaturedProductProps< T > );

export const withFeaturedItem =
	( {
		emptyMessage,
		icon,
		label,
		noSelectionButtonLabel,
	}: WithFeaturedItemConfig ) =>
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: FeaturedItemProps< T > ) => {
		const [ isEditingImage ] = props.useEditingImage;
		const [ , setEditMode ] = props.useEditMode;

		const {
			attributes,
			category,
			isLoading,
			isSelected,
			name,
			product,
			setAttributes,
		} = props;
		const { mediaId, mediaSrc, isRepeated, imageFit } = attributes;
		const item = category || product;
		const [ backgroundImageSize, setBackgroundImageSize ] = useState( {} );
		const {
			backgroundImageSrc,
			isImageBgTransparent,
			originalImgDimension,
		} = useBackgroundImage( {
			item,
			mediaId,
			mediaSrc,
			blockName: name,
		} );
		const featuredProductParentRef = useRef( null );
		const [ parentContainerDimension, setParentContainerDimension ] =
			useState< BgImageDimensions >( { height: 0, width: 0 } );

		useEffect( () => {
			// Observes the resizable block's dimension changes.
			const observer = new ResizeObserver( ( entries ) => {
				setParentContainerDimension( {
					height: entries[ 0 ].contentRect.height,
					width: entries[ 0 ].contentRect.width,
				} );
			} );

			if ( isLoading === false ) {
				const element =
					featuredProductParentRef.current as HTMLElement | null;

				if ( ! element ) return;

				observer.observe( element );
			}

			return () => observer.disconnect();
		}, [ isLoading ] );

		const backgroundColorVisibilityStatus = useMemo(
			() =>
				getBackgroundColorVisibilityStatus( {
					isImageBgTransparent,
					originalImgDimension,
					parentContainerDimension,
					isRepeated,
					imageFit,
				} ),
			[
				parentContainerDimension,
				originalImgDimension,
				isRepeated,
				imageFit,
				isImageBgTransparent,
			]
		);

		const className = getClassPrefixFromName( name );

		const onResize = useCallback(
			( _event, _direction, elt ) => {
				setAttributes( {
					minHeight: parseInt( elt.style.height, 10 ),
				} );
			},
			[ setAttributes ]
		);

		const renderNoItemButton = () => {
			return (
				<>
					<p>{ emptyMessage }</p>
					<div style={ { flexBasis: '100%', height: '0' } }></div>
					<button
						type="button"
						className="components-button is-secondary"
						onClick={ () => setEditMode( true ) }
					>
						{ noSelectionButtonLabel }
					</button>
				</>
			);
		};

		const renderInnerBlocks = () => {
			if ( product ) {
				return (
					<BlockContextProvider
						value={ { postId: product.id, postType: 'product' } }
					>
						<ProductDataContextProvider
							product={ product }
							isLoading={ isLoading }
						>
							<div className={ `${ className }__inner-blocks` }>
								<InnerBlocks
									template={ FEATURED_PRODUCT_DEFAULT_TEMPLATE(
										product
									) }
									templateLock={ false }
								/>
							</div>
						</ProductDataContextProvider>
					</BlockContextProvider>
				);
			}

			return (
				<BlockContextProvider
					value={ {
						termId: category.term_id,
						termTaxonomy: 'product_cat',
					} }
				>
					<div className={ `${ className }__inner-blocks` }>
						<InnerBlocks
							template={ FEATURED_CATEGORY_DEFAULT_TEMPLATE(
								category
							) }
							templateLock={ false }
						/>
					</div>
				</BlockContextProvider>
			);
		};

		const renderNoItem = () => (
			<Placeholder
				className={ className }
				icon={ <Icon icon={ icon } /> }
				label={ label }
			>
				{ isLoading ? <Spinner /> : renderNoItemButton() }
			</Placeholder>
		);

		const styleProps = useStyleProps( attributes );

		const renderItem = () => {
			const {
				contentAlign,
				dimRatio,
				focalPoint,
				hasParallax,
				minHeight,
				overlayColor,
				overlayGradient,
				style,
				textColor,
			} = attributes;

			const containerClass = clsx(
				className,
				{
					'is-selected':
						isSelected &&
						attributes.categoryId !== 'preview' &&
						attributes.productId !== 'preview',
					'is-loading': ! item && isLoading,
					'is-not-found': ! item && ! isLoading,
					'has-background-dim': dimRatio !== 0,
					'is-repeated': isRepeated,
				},
				dimRatioToClass( dimRatio ),
				contentAlign !== 'center' && `has-${ contentAlign }-content`,
				styleProps.className
			);

			const containerStyle: React.CSSProperties = {
				borderRadius: style?.border?.radius,
				color: textColor
					? `var(--wp--preset--color--${ textColor })`
					: style?.color?.text,
				boxSizing: 'border-box',
				minHeight,
				...styleProps.style,
			};

			const isImgElement = ! isRepeated && ! hasParallax;

			const backgroundImageStyle = getBackgroundImageStyles( {
				focalPoint,
				imageFit,
				isImgElement,
				isRepeated,
				url: backgroundImageSrc,
			} );

			const overlayStyle = {
				background: overlayGradient,
				backgroundColor: overlayColor,
			};

			return (
				<>
					<ConstrainedResizable
						enable={ { bottom: true } }
						onResize={ onResize }
						showHandle={ isSelected }
						style={ { minHeight } }
					/>
					<div
						className={ containerClass }
						ref={ featuredProductParentRef }
						style={ containerStyle }
					>
						<div className={ `${ className }__wrapper` }>
							<div
								className="background-dim__overlay"
								style={ overlayStyle }
							/>
							{ backgroundImageSrc &&
								( isImgElement ? (
									<img
										alt={ item.name }
										className={ `${ className }__background-image` }
										src={ backgroundImageSrc }
										style={ backgroundImageStyle }
										onLoad={ ( e ) => {
											setBackgroundImageSize( {
												height: e.currentTarget
													?.naturalHeight,
												width: e.currentTarget
													?.naturalWidth,
											} );
										} }
									/>
								) : (
									<div
										className={ clsx(
											`${ className }__background-image`,
											{
												'has-parallax': hasParallax,
											}
										) }
										style={ backgroundImageStyle }
									/>
								) ) }
							{ renderInnerBlocks() }
						</div>
					</div>
				</>
			);
		};

		if ( isEditingImage ) {
			return (
				<Component
					{ ...props }
					backgroundImageSize={ backgroundImageSize }
					backgroundColorVisibilityStatus={
						backgroundColorVisibilityStatus
					}
				/>
			);
		}

		return (
			<>
				<Component
					{ ...props }
					backgroundImageSize={ backgroundImageSize }
					backgroundColorVisibilityStatus={
						backgroundColorVisibilityStatus
					}
				/>
				{ item ? renderItem() : renderNoItem() }
			</>
		);
	};
