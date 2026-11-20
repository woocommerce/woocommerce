/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { CouponInput } from '@woocommerce/base-components/cart-checkout/totals/coupon';
import type { JSX } from 'react';

export const Edit = (): JSX.Element => (
	<div { ...useBlockProps() }>
		<Noninteractive>
			<CouponInput />
		</Noninteractive>
	</div>
);

export const Save = (): JSX.Element => <div { ...useBlockProps.save() } />;
