/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import type { BlockAlignment } from '@wordpress/blocks';
import { ProductResponseItem, isEmpty } from '@woocommerce/types';
import { Icon, Placeholder, Spinner } from '@wordpress/components';
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
import type { ComponentType, Dispatch, SetStateAction } from 'react';
import { trimCharacters } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { CallToAction } from './call-to-action';
import { ConstrainedResizable } from './constrained-resizable';
import { EditorBlock, GenericBlockUIConfig } from './types';
import { BgImageDimensions, useBackgroundImage } from './use-background-image';
import {
	getBackgroundColorVisibilityStatus,
	dimRatioToClass,
	getBackgroundImageStyles,
	getClassPrefixFromName,
} from './utils';

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
	editMode: boolean;
	backgroundColor: string | undefined;
	style: { color: { background: string } };
	backgroundColorVisibilityStatus: {
		isBackgroundVisible: boolean;
		message?: string | null;
	};
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

		const renderButton = () => {
			const { categoryId, linkText, productId } = attributes;

			return (
				<CallToAction
					itemId={ categoryId || productId }
					linkText={ linkText }
					permalink={ ( category || product ).permalink as string }
				/>
			);
		};

		const renderNoItemButton = () => {
			return (
				<>
					<p>{ emptyMessage }</p>
					<div style={ { flexBasis: '100%', height: '0' } }></div>
					<button
						type="button"
						className="components-button is-secondary"
						onClick={ () => setAttributes( { editMode: true } ) }
					>
						{ noSelectionButtonLabel }
					</button>
				</>
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
				showDesc,
				showPrice,
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

			const containerStyle = {
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
							<h2
								className={ `${ className }__title` }
								dangerouslySetInnerHTML={ {
									__html: item.name,
								} }
							/>
							{ ! isEmpty( product?.variation ) && (
								<h3
									className={ `${ className }__variation` }
									dangerouslySetInnerHTML={ {
										__html: product.variation,
									} }
								/>
							) }
							{ showDesc && (
								<div
									className={ `${ className }__description` }
									dangerouslySetInnerHTML={ {
										__html:
											category?.description ||
											product?.short_description ||
											// Returning max 400 character to match the frontend block logic in PHP: see https://github.com/woocommerce/woocommerce/blob/027bf00f291967608abbbd6408193c970dffdd2a/plugins/woocommerce/src/Blocks/BlockTypes/FeaturedProduct.php#L88
											( product?.description?.length > 0
												? trimCharacters(
														product.description,
														400
												  )
												: '' ),
									} }
								/>
							) }
							{ showPrice && (
								<div
									className={ `${ className }__price` }
									dangerouslySetInnerHTML={ {
										__html: product.price_html,
									} }
								/>
							) }
							<div className={ `${ className }__link` }>
								{ renderButton() }
							</div>
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
