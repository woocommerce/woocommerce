/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import type { PaymentMethodConfigInstance } from '@woocommerce/types';
import { PaymentMethodIcons } from '@woocommerce/base-components/cart-checkout';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import { usePaymentMethods } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import './style.scss';

export interface Attributes {
	showAsIcons: boolean;
	formattedPaymentMethods: Record< string, PaymentMethodConfigInstance >;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

/**
 * Extracts payment method labels and image sources from the payment methods data.
 */
const extractPaymentMethodInfo = (
	paymentMethods: Record< string, PaymentMethodConfigInstance >
) => {
	return Object.entries( paymentMethods ).map( ( [ methodId, method ] ) => {
		const result = {
			methodId,
			label: '',
			images: [],
		};

		// Handle the label which can be either a string or an object with children
		if ( method.label ) {
			if ( typeof method.label === 'string' ) {
				result.label = method.label;
			} else if ( method.label.props && method.label.props.children ) {
				const children = method.label.props.children;

				// Handle array of children
				if ( Array.isArray( children ) ) {
					children.forEach( ( child ) => {
						if ( typeof child === 'string' ) {
							result.label = child;
						} else if ( Array.isArray( child ) ) {
							// Handle nested array of image objects
							child.forEach( ( imageObj ) => {
								if ( imageObj.props && imageObj.props.src ) {
									result.images.push( imageObj.props.src );
								}
							} );
						} else if ( child.props && child.props.src ) {
							result.images.push( child.props.src );
						}
					} );
				}
				// Handle single child
				else if ( typeof children === 'string' ) {
					result.label = children;
				}
			}
		}

		// Handle icons array if present
		if ( method.icons && Array.isArray( method.icons ) ) {
			result.images = [ ...result.images, ...method.icons ];
		}

		return result;
	} );
};

const Edit = ( { attributes, setAttributes }: Props ) => {
	const { showAsIcons, formattedPaymentMethods } = attributes;
	const paymentMethods = getPaymentMethods();
	const blockProps = useBlockProps();

	//console.log( paymentMethods );

	const extractedInfo = extractPaymentMethodInfo( paymentMethods );
	console.log( extractedInfo );

	//const { paymentMethods: paymentMethodsFromHook } = usePaymentMethods();
	//console.log( { paymentMethodsFromHook } );

	useEffect( () => {
		setAttributes( {
			formattedPaymentMethods: paymentMethods,
		} );
	}, [ paymentMethods, setAttributes ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Show as icons', 'woocommerce' ) }
						checked={ showAsIcons }
						onChange={ ( value ) =>
							setAttributes( { showAsIcons: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div className="wp-block-woocommerce-payment-methods">
				{ Object.keys( formattedPaymentMethods ).length === 0 ? (
					<p>
						<small>
							{ __(
								'No payment methods are currently active.',
								'woocommerce'
							) }
						</small>
					</p>
				) : (
					<div className="wc-block-payment-methods__content">
						<PaymentMethodIcons
							icons={ getIconsFromPaymentMethods(
								paymentMethods
							) }
						/>
						<ul className="wc-block-payment-methods__list">
							{ Object.values( formattedPaymentMethods ).map(
								( method ) => {
									return (
										<li
											key={ method.name }
											className="wc-block-payment-methods__list-item"
										>
											{ showAsIcons &&
												method.icons &&
												method.icons[ 0 ] && (
													<img
														src={
															typeof method
																.icons[ 0 ] ===
															'string'
																? method
																		.icons[ 0 ]
																: method
																		.icons[ 0 ]
																		.src ||
																  ''
														}
														alt={ method.ariaLabel }
														className="wc-block-payment-methods__list-item-icon"
													/>
												) }
											{ ( ! showAsIcons ||
												! method.icons?.length ||
												! method.icons[ 0 ] ) && (
												<span className="wc-block-payment-methods__list-item-label">
													{ method.ariaLabel }
												</span>
											) }
										</li>
									);
								}
							) }
						</ul>
					</div>
				) }
			</div>
		</div>
	);
};

export default Edit;
