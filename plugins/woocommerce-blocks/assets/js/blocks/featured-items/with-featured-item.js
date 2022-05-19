/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles } from '@wordpress/block-editor';
import { Icon, Placeholder, Spinner } from '@wordpress/components';
import classnames from 'classnames';
import { isEmpty } from 'lodash';
import { useCallback, useState } from 'react';

/**
 * Internal dependencies
 */
import { CallToAction } from './call-to-action';
import { ConstrainedResizable } from './constrained-resizable';
import { useBackgroundImage } from './use-background-image';
import {
	dimRatioToClass,
	getBackgroundImageStyles,
	getClassPrefixFromName,
} from './utils';

export const withFeaturedItem = ( { emptyMessage, icon, label } ) => (
	Component
) => ( props ) => {
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
	const { mediaId, mediaSrc } = attributes;
	const item = category || product;
	const [ backgroundImageSize, setBackgroundImageSize ] = useState( {} );

	const { backgroundImageSrc } = useBackgroundImage( {
		item,
		mediaId,
		mediaSrc,
		blockName: name,
	} );

	const className = getClassPrefixFromName( name );

	const onResize = useCallback(
		( _event, _direction, elt ) => {
			setAttributes( { minHeight: parseInt( elt.style.height, 10 ) } );
		},
		[ setAttributes ]
	);

	const renderButton = () => {
		const { categoryId, linkText, productId } = attributes;

		return (
			<CallToAction
				itemId={ categoryId || productId }
				linkText={ linkText }
				permalink={ ( category || product ).permalink }
			/>
		);
	};

	const renderNoItem = () => (
		<Placeholder
			className={ className }
			icon={ <Icon icon={ icon } /> }
			label={ label }
		>
			{ isLoading ? <Spinner /> : emptyMessage }
		</Placeholder>
	);

	const renderItem = () => {
		const {
			contentAlign,
			dimRatio,
			focalPoint,
			hasParallax,
			isRepeated,
			imageFit,
			minHeight,
			overlayColor,
			overlayGradient,
			showDesc,
			showPrice,
			style,
		} = attributes;

		const classes = classnames(
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
			contentAlign !== 'center' && `has-${ contentAlign }-content`
		);

		const containerStyle = {
			borderRadius: style?.border?.radius,
		};

		const wrapperStyle = {
			...getSpacingClassesAndStyles( attributes ).style,
			minHeight,
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
				<div className={ classes } style={ containerStyle }>
					<div
						className={ `${ className }__wrapper` }
						style={ wrapperStyle }
					>
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
											height: e.target?.naturalHeight,
											width: e.target?.naturalWidth,
										} );
									} }
								/>
							) : (
								<div
									className={ classnames(
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
										product?.short_description,
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
			/>
		);
	}

	return (
		<>
			<Component
				{ ...props }
				backgroundImageSize={ backgroundImageSize }
			/>
			{ item ? renderItem() : renderNoItem() }
		</>
	);
};
