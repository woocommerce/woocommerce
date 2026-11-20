/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { TotalsCoupon } from '@woocommerce/base-components/cart-checkout';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import type { JSX } from 'react';

/**
 * Internal dependencies
 */
import { COUPON_FORM_TEMPLATE } from './template';
import './style.scss';

export const Edit = ( {
	attributes,
}: {
	attributes: {
		className?: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const { className = '' } = attributes;
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<TotalsWrapper className={ className }>
				<TotalsCoupon instanceId="coupon" displayCouponForm>
					<InnerBlocks
						template={ COUPON_FORM_TEMPLATE }
						allowedBlocks={ COUPON_FORM_TEMPLATE.map(
							( [ name ] ) => name
						) }
						templateLock="all"
					/>
				</TotalsCoupon>
			</TotalsWrapper>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
