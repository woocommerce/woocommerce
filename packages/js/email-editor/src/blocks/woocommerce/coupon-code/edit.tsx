/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ExternalLink } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { BlockEditProps } from './types';
import { storeName } from '../../../store/constants';

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

	// Get the create coupon URL from the store
	const { createCouponUrl } = useSelect( ( select ) => {
		const urls = select( storeName ).getUrls();
		return {
			createCouponUrl: urls?.createCoupon || '',
		};
	}, [] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'woocommerce' ) }
					initialOpen={ true }
				>
					{ createCouponUrl && (
						<div>
							<ExternalLink href={ createCouponUrl }>
								{ __( 'Create new coupon', 'woocommerce' ) }
							</ExternalLink>
						</div>
					) }
				</PanelBody>
			</InspectorControls>
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
		</>
	);
}
