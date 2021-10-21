/**
 * External dependencies
 */
import classnames from 'classnames';
import { HTMLAttributes } from 'react';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { getColorClassName, getFontSizeClass } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { gatedStyledText } from '@woocommerce/atomic-utils';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import ProductName from '@woocommerce/base-components/product-name';
import { useStoreEvents } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import './style.scss';
import { Attributes } from './types';

type Props = Attributes & HTMLAttributes< HTMLDivElement >;

interface TagNameProps extends HTMLAttributes< HTMLOrSVGElement > {
	headingLevel: number;
	elementType?: keyof JSX.IntrinsicElements;
}

const TagName = ( {
	children,
	headingLevel,
	elementType: ElementType = `h${ headingLevel }` as keyof JSX.IntrinsicElements,
	...props
}: TagNameProps ): JSX.Element => {
	return <ElementType { ...props }>{ children }</ElementType>;
};

/**
 * Product Title Block Component.
 *
 * @param {Object}  props                  Incoming props.
 * @param {string}  [props.className]      CSS Class name for the component.
 * @param {number}  [props.headingLevel]   Heading level (h1, h2 etc)
 * @param {boolean} [props.showProductLink]    Whether or not to display a link to the product page.
 * @param {string}  [props.align]          Title alignment.
 * @param {string}  [props.color]          Title color name.
 * @param {string}  [props.customColor]    Custom title color value.
 * @param {string}  [props.fontSize]       Title font size name.
 * @param {number } [props.customFontSize] Custom font size value.
 * will be used if this is not provided.
 * @return {*} The component.
 */
export const Block = ( {
	className,
	headingLevel = 2,
	showProductLink = true,
	align,
	color,
	customColor,
	fontSize,
	customFontSize,
}: Props ): JSX.Element => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const { dispatchStoreEvent } = useStoreEvents();

	const colorClass = getColorClassName( 'color', color );
	const fontSizeClass = getFontSizeClass( fontSize );

	const titleClasses = classnames( {
		'has-text-color': color || customColor,
		'has-font-size': fontSize || customFontSize,
		[ colorClass ]: colorClass,
		[ fontSizeClass ]: fontSizeClass,
	} );

	if ( ! product.id ) {
		return (
			<TagName
				headingLevel={ headingLevel }
				className={ classnames(
					className,
					'wc-block-components-product-title',
					{
						[ `${ parentClassName }__product-title` ]: parentClassName,
						[ `wc-block-components-product-title--align-${ align }` ]:
							align && isFeaturePluginBuild(),
						[ titleClasses ]: isFeaturePluginBuild(),
					}
				) }
				style={ gatedStyledText( {
					color: customColor,
					fontSize: customFontSize,
				} ) }
			/>
		);
	}

	return (
		<TagName
			headingLevel={ headingLevel }
			className={ classnames(
				className,
				'wc-block-components-product-title',
				{
					[ `${ parentClassName }__product-title` ]: parentClassName,
					[ `wc-block-components-product-title--align-${ align }` ]:
						align && isFeaturePluginBuild(),
				}
			) }
		>
			<ProductName
				className={ classnames( {
					[ titleClasses ]: isFeaturePluginBuild(),
				} ) }
				disabled={ ! showProductLink }
				name={ product.name }
				permalink={ product.permalink }
				rel={ showProductLink ? 'nofollow' : '' }
				style={ gatedStyledText( {
					color: customColor,
					fontSize: customFontSize,
				} ) }
				onClick={ () => {
					dispatchStoreEvent( 'product-view-link', {
						product,
					} );
				} }
			/>
		</TagName>
	);
};

export default withProductDataContext( Block );
