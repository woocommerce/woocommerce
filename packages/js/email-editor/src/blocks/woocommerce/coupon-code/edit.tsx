/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ExternalLink, ComboboxControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { BlockEditProps } from './types';
import { storeName } from '../../../store/constants';

interface Coupon {
	id: number;
	code: string;
}

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
	const [ coupons, setCoupons ] = useState< Coupon[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Get the create coupon URL from the store
	const { createCouponUrl } = useSelect( ( select ) => {
		const urls = select( storeName ).getUrls();
		return {
			createCouponUrl: urls?.createCoupon || '',
		};
	}, [] );

	// Fetch coupons from WooCommerce API
	useEffect( () => {
		const fetchCoupons = async () => {
			try {
				setIsLoading( true );
				const response = await apiFetch< Coupon[] >( {
					path: '/wc/v3/coupons?per_page=100',
				} );
				setCoupons( response );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error fetching coupons:', error );
				setCoupons( [] );
			} finally {
				setIsLoading( false );
			}
		};

		fetchCoupons();
	}, [] );

	// Convert coupons to options format and filter based on search
	const couponOptions = coupons
		.map( ( coupon ) => ( {
			value: coupon.code,
			label: coupon.code,
		} ) )
		.filter( ( option ) =>
			option.label.toLowerCase().includes( searchValue.toLowerCase() )
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
						{ isLoading ? (
							<div style={ { padding: '10px', textAlign: 'center' } }>
								<Spinner />
							</div>
						) : (
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
								options={ couponOptions }
								__nextHasNoMarginBottom
							/>
						) }
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
