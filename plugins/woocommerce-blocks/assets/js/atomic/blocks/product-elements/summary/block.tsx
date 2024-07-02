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
	);
};

export default withProductDataContext( Block );
