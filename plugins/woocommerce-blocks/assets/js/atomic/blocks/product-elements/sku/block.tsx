/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import type { Attributes } from './types';

type Props = Attributes & HTMLAttributes< HTMLDivElement >;

const Preview = ( {
	parentClassName,
	sku,
	className,
}: {
	parentClassName: string;
	sku: string;
	className?: string | undefined;
} ) => (
	<div
		className={ classnames( className, 'wc-block-components-product-sku', {
			[ `${ parentClassName }__product-sku` ]: parentClassName,
		} ) }
	>
		{ __( 'SKU:', 'woo-gutenberg-products-block' ) }{ ' ' }
		<strong>{ sku }</strong>
	</div>
);

const Block = ( props: Props ): JSX.Element | null => {
	const { className } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const sku = product.sku;

	if ( props.isDescendentOfSingleProductTemplate ) {
		return (
			<Preview
				parentClassName={ parentClassName }
				className={ className }
				sku={ 'Product SKU' }
			/>
		);
	}

	if ( ! sku ) {
		return null;
	}

	return (
		<Preview
			className={ className }
			parentClassName={ parentClassName }
			sku={ sku }
		/>
	);
};

export default withProductDataContext( Block );
