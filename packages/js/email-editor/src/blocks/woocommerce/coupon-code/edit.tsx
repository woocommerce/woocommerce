/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { BlockEditProps } from './types';

/**
 * Edit component for the Coupon Code block.
 *
 * @param {BlockEditProps} props - Block properties.
 * @return {JSX.Element} The edit component.
 */
export function Edit( props: BlockEditProps ): JSX.Element {
	const { attributes } = props;
	const couponCode = attributes.couponCode as string;

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div
				style={ {
					padding: '20px',
					border: '2px dashed #ccc',
					borderRadius: '4px',
					textAlign: 'center',
				} }
			>
				{ couponCode ? (
					<div>
						<p>{ __( 'Coupon Code:', 'woocommerce' ) }</p>
						<strong>{ couponCode }</strong>
					</div>
				) : (
					<p>
						{ __(
							'Coupon Code block - No coupon selected',
							'woocommerce'
						) }
					</p>
				) }
			</div>
		</div>
	);
}
