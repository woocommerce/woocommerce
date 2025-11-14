/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ExternalLink, ComboboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { BlockEditProps } from './types';
import { storeName } from '../../../store/constants';

// Mock coupon data - will be replaced with real data later
const MOCK_COUPONS = [
	{ value: '10-shampoo', label: '10-shampoo' },
	{ value: '15-oils', label: '15-oils' },
	{ value: '20-hair-cut', label: '20-hair-cut' },
	{ value: '5-cart', label: '5-cart' },
	{ value: '10-cart', label: '10-cart' },
];

/**
 * Edit component for the Coupon Code block.
 *
 * @param {BlockEditProps} props - Block properties.
 * @return {JSX.Element} The edit component.
 */
export function Edit( props: BlockEditProps ): JSX.Element {
	const { attributes, setAttributes } = props;
	const couponCode = attributes.couponCode as string;

	const blockProps = useBlockProps();
	const [ searchValue, setSearchValue ] = useState( '' );

	// Get the create coupon URL from the store
	const { createCouponUrl } = useSelect( ( select ) => {
		const urls = select( storeName ).getUrls();
		return {
			createCouponUrl: urls?.createCoupon || '',
		};
	}, [] );

	// Filter coupons based on search
	const filteredCoupons = MOCK_COUPONS.filter( ( coupon ) =>
		coupon.label.toLowerCase().includes( searchValue.toLowerCase() )
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'woocommerce' ) }
					initialOpen={ true }
				>
					<div style={ { marginBottom: '16px' } }>
						<label
							htmlFor="coupon-search"
						>
							{ __( 'SELECT AN EXISTING COUPON', 'woocommerce' ) }
						</label>
						<ComboboxControl
							label={ __( 'Search coupons', 'woocommerce' ) }
							hideLabelFromVision
							value={ couponCode }
							onChange={ ( value ) => {
								setAttributes( { couponCode: value || '' } );
							} }
							onFilterValueChange={ ( value ) => {
								setSearchValue( value );
							} }
							options={ filteredCoupons }
							__nextHasNoMarginBottom
						/>
					</div>
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
