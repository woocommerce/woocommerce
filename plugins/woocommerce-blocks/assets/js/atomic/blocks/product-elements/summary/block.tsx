/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import Summary from '@woocommerce/base-components/summary';
import { blocksConfig } from '@woocommerce/block-settings';

import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlockProps } from './types';

const getSource = (
	product: { short_description?: string; description?: string },
	showDescriptionIfEmpty: boolean
) => {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	const { short_description, description } = product;

	if ( short_description ) {
		return short_description;
	}

	if ( showDescriptionIfEmpty && description ) {
		return description;
	}

	return '';
};

const Block = ( props: BlockProps ): JSX.Element | null => {
	const {
		className,
		showDescriptionIfEmpty,
		summaryLength,
		showLink,
		linkText,
		isDescendantOfAllProducts,
	} = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const styleProps = useStyleProps( props );

	const source = getSource( product, showDescriptionIfEmpty );
	const maxLength = summaryLength || Infinity;

	if ( ! product ) {
		return (
			<div
				className={ clsx(
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

	if ( ! source ) {
		return isDescendantOfAllProducts ? null : (
			<p>{ __( 'No product summary to show.', 'woocommerce' ) }</p>
		);
	}

	return (
		<>
			<Summary
				className={ clsx(
					className,
					styleProps.className,
					`wc-block-components-product-summary`,
					{
						[ `${ parentClassName }__product-summary` ]:
							parentClassName,
					}
				) }
				source={ source }
				maxLength={ maxLength }
				countType={ blocksConfig.wordCountType || 'words' }
				style={ styleProps.style }
			/>
			{ isDescendantOfAllProducts && showLink && linkText ? (
				<a href={ `${ product.permalink }#tab-description` }>
					{ linkText }
				</a>
			) : null }
		</>
	);
};

export default ( props: BlockProps ) => {
	// It is necessary because this block has to support serveral contexts:
	// - Inside `All Products Block` -> `withProductDataContext` HOC
	// - Inside `Products Block` -> Gutenberg Context
	// - Inside `Single Product Template` -> Gutenberg Context
	// - Without any parent -> `WithSelector` and `withProductDataContext` HOCs
	// For more details, check https://github.com/woocommerce/woocommerce-blocks/pull/8609
	if ( props.isDescendentOfSingleProductTemplate ) {
		return <Block { ...props } />;
	}
	return withProductDataContext( Block )( props );
};
