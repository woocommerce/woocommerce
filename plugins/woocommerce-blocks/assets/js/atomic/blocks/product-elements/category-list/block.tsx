/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isEmpty } from 'lodash';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { HTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import { Attributes } from './types';
import {
	useColorProps,
	useTypographyProps,
} from '../../../../hooks/style-attributes';

type Props = Attributes & HTMLAttributes< HTMLDivElement >;

/**
 * Product Category Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @return {*} The component.
 */
const Block = ( props: Props ): JSX.Element | null => {
	const { className } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	const colorProps = useColorProps( props );
	const typographyProps = useTypographyProps( props );

	if ( isEmpty( product.categories ) ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-category-list',
				colorProps.className,
				{
					[ `${ parentClassName }__product-category-list` ]: parentClassName,
				}
			) }
			style={ { ...colorProps.style, ...typographyProps.style } }
		>
			{ __( 'Categories:', 'woo-gutenberg-products-block' ) }{ ' ' }
			<ul>
				{ Object.values( product.categories ).map(
					( { name, link, slug } ) => {
						return (
							<li key={ `category-list-item-${ slug }` }>
								<a href={ link }>{ name }</a>
							</li>
						);
					}
				) }
			</ul>
		</div>
	);
};

export default withProductDataContext( Block );
