/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ExternalLink, ComboboxControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import type { CSSProperties } from 'react';
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

	const {
		className: blockClassName = '',
		style: blockStyle,
		...wrapperProps
	} = useBlockProps();
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

	// Strip block-level background/border styles off the wrapper so we can
	// fully control visual presentation on the coupon element itself.
	const { background, backgroundColor, border, ...restStyle } =
		( blockStyle || {} ) as CSSProperties;
	const baseStyle = restStyle;
	const couponStyles: CSSProperties = {
		// Mirror PHP defaults so the editor view matches the previewed email.
		display: 'inline-block',
		boxSizing: 'border-box',
		fontSize: '1.2em',
		padding: '12px 20px',
		borderWidth: '2px',
		borderStyle: 'dashed',
		borderColor: '#cccccc',
		borderRadius: '4px',
		color: '#000000',
		backgroundColor: '#f5f5f5',
		fontWeight: 'bold',
		letterSpacing: '1px',
		textAlign: 'center',
		...baseStyle,
	};

	if ( ! couponStyles.borderStyle ) {
		couponStyles.borderStyle = 'dashed';
	}

	if ( ! couponStyles.padding ) {
		couponStyles.padding = '12px 20px';
	}

	if ( ! couponStyles.borderWidth ) {
		couponStyles.borderWidth = '2px';
	}

	if ( ! couponStyles.borderColor ) {
		couponStyles.borderColor = '#cccccc';
	}

	if ( ! couponStyles.borderRadius ) {
		couponStyles.borderRadius = '4px';
	}

	if ( ! couponStyles.fontSize ) {
		couponStyles.fontSize = '1.2em';
	}

	if ( ! couponStyles.backgroundColor && ! couponStyles.background ) {
		couponStyles.backgroundColor = '#f5f5f5';
	}

	if ( ! couponStyles.color ) {
		couponStyles.color = '#000000';
	}

	if ( ! couponStyles.fontWeight ) {
		couponStyles.fontWeight = 'bold';
	}

	if ( ! couponStyles.letterSpacing ) {
		couponStyles.letterSpacing = '1px';
	}

	couponStyles.display = 'inline-block';
	couponStyles.boxSizing = 'border-box';
	couponStyles.textAlign = 'center';

	const supportedAlignments: Array< CSSProperties['textAlign'] > = [
		'left',
		'center',
		'right',
		'justify',
		'start',
		'end',
	];
	const alignAttribute = attributes.align as string | undefined;
	const wrapperTextAlign = supportedAlignments.includes(
		alignAttribute as CSSProperties['textAlign']
	)
		? ( alignAttribute as CSSProperties['textAlign'] )
		: 'center';
	const wrapperStyle: CSSProperties = {
		textAlign: wrapperTextAlign,
	};

	// Move color/typography utility classes onto the coupon pill so wrapper
	// layout classes remain unaffected.
	const classTokens = blockClassName.split( ' ' ).filter( Boolean );
	const couponClassTokens: string[] = [];
	const wrapperClassTokens: string[] = [];

	classTokens.forEach( ( token ) => {
		if ( token.startsWith( 'has-' ) || token.startsWith( 'wp-elements-' ) ) {
			couponClassTokens.push( token );
			return;
		}
		wrapperClassTokens.push( token );
	} );

	const wrapperClassName =
		wrapperClassTokens.length > 0 ? wrapperClassTokens.join( ' ' ) : undefined;
	const couponClassName =
		couponClassTokens.length > 0 ? couponClassTokens.join( ' ' ) : undefined;

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
			<div
				{ ...wrapperProps }
				className={ wrapperClassName }
				style={ {
					...( wrapperProps.style as CSSProperties ),
					...wrapperStyle,
				} }
			>
				<span className={ couponClassName } style={ couponStyles }>
					{ couponCode
						? couponCode
						: __(
								'Coupon Code block - No coupon selected',
								'woocommerce'
						  ) }
				</span>
			</div>
		</>
	);
}
