/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	ComboboxControl,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import type { CSSProperties } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { BlockEditProps } from './types';

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
export default function Edit( props: BlockEditProps ): JSX.Element {
	const { attributes, setAttributes } = props;
	const couponCode = attributes.couponCode as string;

	const {
		className: blockClassName = '',
		style: blockStyle,
		...wrapperProps
	} = useBlockProps();
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ coupons, setCoupons ] = useState< Coupon[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const debounceTimerRef = useRef< ReturnType< typeof setTimeout > | null >(
		null
	);
	const abortControllerRef = useRef< AbortController | null >( null );

	// Handler for creating a new coupon - uses a filter so integrators can customize behavior
	const handleCreateCoupon = () => {
		// Get the handler from the filter (integrations provide the default handler)
		// Integrators can customize this filter for SPA routing, custom workflows, etc.
		// Filter: woocommerce_email_editor_create_coupon_handler
		// @since 10.5.0
		// @param {() => void} handler - Function called when user clicks "Create new coupon"
		// @return {() => void} Modified handler function. The returned function should open the coupon creation UI.
		const createCouponHandler = applyFilters(
			'woocommerce_email_editor_create_coupon_handler',
			() => {
				// This is the ultimate fallback if no integration provides a handler
				// May not work correctly in subdirectory installations
				window.open(
					'/wp-admin/post-new.php?post_type=shop_coupon',
					'_blank'
				);
			}
		);

		if ( typeof createCouponHandler === 'function' ) {
			createCouponHandler();
		}
	};

	// Debounced coupon search function
	const searchCoupons = useCallback( ( search: string ) => {
		// Cancel any pending request
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}

		// Don't search if search term is too short
		if ( search.length < 2 ) {
			setCoupons( [] );
			setIsLoading( false );
			return;
		}

		setIsLoading( true );
		abortControllerRef.current = new AbortController();

		apiFetch< Coupon[] >( {
			path: `/wc/v3/coupons?per_page=20&search=${ encodeURIComponent(
				search
			) }`,
			signal: abortControllerRef.current.signal,
		} )
			.then( ( results ) => {
				setCoupons( results );
				setIsLoading( false );
			} )
			.catch( ( error ) => {
				if ( error instanceof Error && error.name === 'AbortError' ) {
					return;
				}
				// Check if it's a permissions error
				if ( error.code === 'rest_forbidden' || error.status === 403 ) {
					dispatch( 'core/notices' ).createErrorNotice(
						__(
							'You do not have permission to view coupons.',
							'woocommerce'
						),
						{
							id: 'coupon-code-permission-error',
							type: 'snackbar',
						}
					);
				}
				setIsLoading( false );
			} );
	}, [] );

	// Handle search value changes with debouncing
	useEffect( () => {
		// Clear any existing timer
		if ( debounceTimerRef.current ) {
			clearTimeout( debounceTimerRef.current );
		}

		// Set new timer for debounced search
		debounceTimerRef.current = setTimeout( () => {
			searchCoupons( searchValue );
		}, 300 );

		// Cleanup function
		return () => {
			if ( debounceTimerRef.current ) {
				clearTimeout( debounceTimerRef.current );
			}
		};
	}, [ searchValue, searchCoupons ] );

	// Cleanup abort controller on unmount
	useEffect( () => {
		return () => {
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [] );

	// Convert coupons to options format
	const couponOptions = coupons.map( ( coupon ) => ( {
		value: coupon.code,
		label: coupon.code,
	} ) );

	// If there's a selected coupon code that's not in the search results, add it to the options
	if (
		couponCode &&
		! couponOptions.some( ( option ) => option.value === couponCode )
	) {
		couponOptions.unshift( {
			value: couponCode,
			label: couponCode,
		} );
	}

	// Strip block-level background/border styles off the wrapper so we can
	// fully control visual presentation on the coupon element itself.
	const { background, backgroundColor, border, ...baseStyle } =
		( blockStyle || {} ) as CSSProperties;

	// Default styles mirror PHP CouponCode::DEFAULT_STYLES for editor/email parity.
	const defaultStyles: CSSProperties = {
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
	};

	// Merge: defaults first, then baseStyle overrides, then forced values.
	const couponStyles: CSSProperties = {
		...defaultStyles,
		...baseStyle,
		// These values must always be set regardless of baseStyle.
		display: 'inline-block',
		boxSizing: 'border-box',
		textAlign: 'center',
	};

	const supportedAlignments: Array< CSSProperties[ 'textAlign' ] > = [
		'left',
		'center',
		'right',
		'justify',
		'start',
		'end',
	];
	const alignAttribute = attributes.align as string | undefined;
	const wrapperTextAlign = supportedAlignments.includes(
		alignAttribute as CSSProperties[ 'textAlign' ]
	)
		? ( alignAttribute as CSSProperties[ 'textAlign' ] )
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
		if (
			token.startsWith( 'has-' ) ||
			token.startsWith( 'wp-elements-' )
		) {
			couponClassTokens.push( token );
			return;
		}
		wrapperClassTokens.push( token );
	} );

	const wrapperClassName =
		wrapperClassTokens.length > 0
			? wrapperClassTokens.join( ' ' )
			: undefined;
	const couponClassName =
		couponClassTokens.length > 0
			? couponClassTokens.join( ' ' )
			: undefined;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'woocommerce' ) }
					initialOpen={ true }
				>
					<div style={ { marginBottom: '16px' } }>
						<div>
							{ __(
								'Search for an existing coupon',
								'woocommerce'
							) }
						</div>
						<ComboboxControl
							label={ __( 'Search coupons', 'woocommerce' ) }
							hideLabelFromVision
							value={ couponCode }
							onChange={ ( value ) => {
								setAttributes( {
									couponCode: value || '',
								} );
							} }
							onFilterValueChange={ ( value ) => {
								setSearchValue( value );
							} }
							options={ couponOptions }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							help={ ( () => {
								if ( isLoading ) {
									return __(
										'Searching coupons…',
										'woocommerce'
									);
								}
								if (
									searchValue.length > 0 &&
									searchValue.length < 2
								) {
									return __(
										'Type at least 2 characters to search',
										'woocommerce'
									);
								}
								return null;
							} )() }
						/>
						{ isLoading && (
							<div
								style={ {
									display: 'flex',
									alignItems: 'center',
									marginTop: '8px',
								} }
							>
								<Spinner />
							</div>
						) }
					</div>
					<div>
						<Button
							variant="link"
							onClick={ handleCreateCoupon }
							style={ { padding: 0, height: 'auto' } }
						>
							{ __( 'Create new coupon', 'woocommerce' ) }
						</Button>
					</div>
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
								'Coupon Code block – No coupon selected',
								'woocommerce'
						  ) }
				</span>
			</div>
		</>
	);
}
