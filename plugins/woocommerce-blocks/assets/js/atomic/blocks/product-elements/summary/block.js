/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Summary from '@woocommerce/base-components/summary';
import { blocksConfig } from '@woocommerce/block-settings';

import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
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
 * Product Summary Block Component.
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

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					`wc-block-components-product-summary`,
					{
						[ `${ parentClassName }__product-summary` ]:
							parentClassName,
					}
				) }
			/>
		);
	}

	const source = product.short_description
		? product.short_description
		: product.description;

	if ( ! source ) {
		return null;
	}

	return (
		<Summary
			className={ classnames(
				className,
				colorProps.className,
				`wc-block-components-product-summary`,
				{
					[ `${ parentClassName }__product-summary` ]:
						parentClassName,
				}
			) }
			source={ source }
			maxLength={ 150 }
			countType={ blocksConfig.wordCountType || 'words' }
			style={ {
				...colorProps.style,
				...typographyProps.style,
			} }
		/>
	);
};

Block.propTypes = {
	className: PropTypes.string,
};

export default withProductDataContext( Block );
