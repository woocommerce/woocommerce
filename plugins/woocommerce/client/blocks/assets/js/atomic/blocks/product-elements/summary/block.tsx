/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import Summary from '@woocommerce/base-components/summary';
import { blocksConfig } from '@woocommerce/block-settings';
import { isEmpty, ProductResponseItem } from '@woocommerce/types';

import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { ProductEntityResponse } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlockProps } from './types';

const isLegacyProductSummary = ( props: Partial< BlockProps > ) => {
	const {
		isDescendantOfAllProducts,
		summaryLength,
		showDescriptionIfEmpty,
		showLink,
	} = props;
	return (
		isDescendantOfAllProducts &&
		isEmpty( summaryLength ) &&
		isEmpty( showDescriptionIfEmpty ) &&
		isEmpty( showLink )
	);
};

const getSource = (
	product: ProductResponseItem | ProductEntityResponse,
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
		showDescriptionIfEmpty: showDescriptionIfEmptyAttr,
		summaryLength: summaryLengthAttr,
		showLink: showLinkAttr,
		linkText,
		isDescendantOfAllProducts,
		isDescendentOfSingleProductTemplate,
		product: productEntity,
		isAdmin,
	} = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext( {
		product: productEntity,
		isAdmin,
	} );
	const styleProps = useStyleProps( props );

	// The attributes of this block have been updated. There's migration
	// implemented with Deprecation API but it doesn't have an effect
	// on All Products which is client-side rendered. That means new block.tsx
	// is in use on the frontend even before merchant goes to Editor where
	// migration can happen.
	// In that case we're setting up hardcoded values like summaryLength: 150
	// and showDescriptionIfEmptyFinal: true which corresponds to original
	// Product Summary settings.
	const isLegacy = isLegacyProductSummary( props );
	const allProductsSummaryLength = 150;
	const allProductsshowDescriptionIfEmpty = true;
	const allProductsShowLink = false;

	const summaryLength = isLegacy
		? allProductsSummaryLength
		: summaryLengthAttr;
	const showDescriptionIfEmpty = isLegacy
		? allProductsshowDescriptionIfEmpty
		: showDescriptionIfEmptyAttr;
	const showLink = isLegacy ? allProductsShowLink : showLinkAttr;

	const source = product ? getSource( product, showDescriptionIfEmpty ) : '';
	const maxLength = summaryLength || Infinity;

	const summaryClassName = 'wc-block-components-product-summary';

	if ( isDescendentOfSingleProductTemplate ) {
		return (
			<div className={ summaryClassName }>
				<p>
					{ __(
						'This block displays the product summary and all its customizations.',
						'woocommerce'
					) }
				</p>
			</div>
		);
	}

	if ( ! product ) {
		return (
			<div
				className={ clsx( className, summaryClassName, {
					[ `${ parentClassName }__product-summary` ]:
						parentClassName,
				} ) }
			/>
		);
	}

	if ( ! source ) {
		return isDescendantOfAllProducts ? null : (
			<div className={ summaryClassName }>
				<p>{ __( 'No product summary to show.', 'woocommerce' ) }</p>
			</div>
		);
	}

	return (
		<>
			<Summary
				className={ clsx(
					className,
					styleProps.className,
					summaryClassName,
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
			{ isDescendantOfAllProducts &&
			showLink &&
			linkText &&
			product?.permalink ? (
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
