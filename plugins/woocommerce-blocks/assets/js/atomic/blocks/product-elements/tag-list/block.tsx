/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useColorProps, useTypographyProps } from '@woocommerce/base-hooks';
import { isEmpty } from 'lodash';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlocksAttributes } from './types';

type Props = BlocksAttributes & HTMLAttributes< HTMLDivElement >;

const Block = ( props: Props ): JSX.Element | null => {
	const { className } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const colorProps = useColorProps( props );
	const typographyProps = useTypographyProps( props );

	if ( isEmpty( product.tags ) ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				colorProps.className,
				'wc-block-components-product-tag-list',
				{
					[ `${ parentClassName }__product-tag-list` ]:
						parentClassName,
				}
			) }
			style={ { ...colorProps.style, ...typographyProps.style } }
		>
			{ __( 'Tags:', 'woo-gutenberg-products-block' ) }{ ' ' }
			<ul>
				{ Object.values( product.tags ).map(
					( { name, link, slug } ) => {
						return (
							<li key={ `tag-list-item-${ slug }` }>
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
