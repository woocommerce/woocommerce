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
 * @param {Object}  props                   Incoming props.
 * @param {string}  [props.className]       CSS Class name for the component.
 * @param {number}  [props.headingLevel]    Heading level (h1, h2 etc)
 * @param {boolean} [props.showProductLink] Whether or not to display a link to the product page.
 * @param {string}  [props.align]           Title alignment.
 * @param {string}  [props.textColor]       Title color name.
 * @param {string}  [props.fontSize]        Title font size name.
 * @param {string}  [props.style]           Title inline style.
 * will be used if this is not provided.
 * @return {*} The component.
 */
export const Block = ( {
	className,
	headingLevel = 2,
	showProductLink = true,
	align,
	textColor,
	fontSize,
	style,
}: Props ): JSX.Element => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const { dispatchStoreEvent } = useStoreEvents();

	const colorClass = getColorClassName( 'color', textColor );
	const fontSizeClass = getFontSizeClass( fontSize );
	const titleClasses = classnames( 'wp-block-woocommerce-product-title', {
		'has-text-color': textColor || style?.color?.text || style?.color,
		[ `has-font-size` ]:
			fontSize || style?.typography?.fontSize || style?.fontSize,
		[ colorClass ]: colorClass,
		[ fontSizeClass ]: fontSizeClass,
	} );

	const titleStyle = {
		fontSize: style?.fontSize || style?.typography?.fontSize,
		color: style?.color?.text || style?.color,
	};

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
				onClick={ () => {
					dispatchStoreEvent( 'product-view-link', {
						product,
					} );
				} }
				style={ isFeaturePluginBuild() ? titleStyle : {} }
			/>
		</TagName>
	);
};

export default withProductDataContext( Block );
