/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useStyleProps } from '@woocommerce/base-hooks';
import { CouponButton } from '@woocommerce/base-components/cart-checkout/totals/coupon';
import type { JSX } from 'react';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './block';

export const Edit = ( {
	attributes,
}: {
	attributes: BlockAttributes;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const buttonStyleProps = useStyleProps( attributes );
	return (
		<div { ...blockProps }>
			<Noninteractive style={ { display: 'contents' } }>
				<CouponButton buttonStyleProps={ buttonStyleProps } />
			</Noninteractive>
		</div>
	);
};

export const Save = (): JSX.Element => <div { ...useBlockProps.save() } />;
