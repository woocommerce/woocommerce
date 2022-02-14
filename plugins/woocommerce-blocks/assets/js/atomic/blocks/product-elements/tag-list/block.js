/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isEmpty } from 'lodash';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	useColorProps,
	useTypographyProps,
} from '../../../../hooks/style-attributes';

/**
 * Product Tag List Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @return {*} The component.
 */
const Block = ( props ) => {
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
					[ `${ parentClassName }__product-tag-list` ]: parentClassName,
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

Block.propTypes = {
	className: PropTypes.string,
};

export default withProductDataContext( Block );
