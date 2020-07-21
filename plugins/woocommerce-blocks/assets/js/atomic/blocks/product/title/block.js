/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { getColorClassName, getFontSizeClass } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { gatedStyledText } from '@woocommerce/atomic-utils';
/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Title Block Component.
 *
 * @param {Object}  props                  Incoming props.
 * @param {string}  [props.className]      CSS Class name for the component.
 * @param {number}  [props.headingLevel]   Heading level (h1, h2 etc)
 * @param {boolean} [props.productLink]    Whether or not to display a link to the product page.
 * @param {string}  [props.align]          Title alignment.
 * @param {string}  [props.color]          Title color name.
 * @param {string}  [props.customColor]    Custom title color value.
 * @param {string}  [props.fontSize]       Title font size name.
 * @param {number } [props.customFontSize] Custom font size value.
 * @param {Object}  [props.product]        Optional product object. Product from context
 * will be used if this is not provided.
 * @return {*} The component.
 */
export const Block = ( {
	className,
	headingLevel = 2,
	productLink = true,
	align,
	color,
	customColor,
	fontSize,
	customFontSize,
	...props
} ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;
	const TagName = `h${ headingLevel }`;

	const colorClass = getColorClassName( 'color', color );
	const fontSizeClass = getFontSizeClass( fontSize );

	const titleClasses = classnames( {
		'has-text-color': color || customColor,
		'has-font-size': fontSize || customFontSize,
		[ colorClass ]: colorClass,
		[ fontSizeClass ]: fontSizeClass,
	} );

	if ( ! product ) {
		return (
			<TagName
				// @ts-ignore
				className={ classnames(
					className,
					'wc-block-components-product-title',
					`${ parentClassName }__product-title`,
					{
						[ `wc-block-components-product-title--align-${ align }` ]:
							align && isFeaturePluginBuild(),
					},
					{ [ titleClasses ]: isFeaturePluginBuild() }
				) }
				style={ gatedStyledText( {
					color: customColor,
					fontSize: customFontSize,
				} ) }
			/>
		);
	}

	const productName = decodeEntities( product.name );

	return (
		// @ts-ignore
		<TagName
			className={ classnames(
				className,
				'wc-block-components-product-title',
				`${ parentClassName }__product-title`,
				{
					[ `wc-block-components-product-title__align-${ align }` ]:
						align && isFeaturePluginBuild(),
				}
			) }
		>
			{ productLink ? (
				<a
					href={ product.permalink }
					rel="nofollow"
					className={ classnames( {
						[ titleClasses ]: isFeaturePluginBuild(),
					} ) }
					style={ gatedStyledText( {
						color: customColor,
						fontSize: customFontSize,
					} ) }
				>
					{ productName }
				</a>
			) : (
				productName
			) }
		</TagName>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
	headingLevel: PropTypes.number,
	productLink: PropTypes.bool,
	align: PropTypes.string,
	color: PropTypes.string,
	customColor: PropTypes.string,
	fontSize: PropTypes.string,
	customFontSize: PropTypes.number,
};

export default Block;
