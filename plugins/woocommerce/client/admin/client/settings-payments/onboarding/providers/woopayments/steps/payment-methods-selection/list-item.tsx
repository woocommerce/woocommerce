/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { type RecommendedPaymentMethod } from '@woocommerce/data';
import { ToggleControl } from '@wordpress/components';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';
import { shouldRenderPaymentMethodInMainList } from '~/settings-payments/utils';

type PaymentMethodListItemProps = {
	/**
	 * The recommended payment method to display.
	 */
	method: RecommendedPaymentMethod;
	/**
	 * Current state of payment methods, mapping method IDs to their enabled status.
	 */
	paymentMethodsState: Record< string, boolean >;
	/**
	 * Callback to update the state of payment methods. Receives a new state object as a parameter.
	 */
	setPaymentMethodsState: ( state: Record< string, boolean > ) => void;
	/**
	 * Indicates whether the payment methods list is currently expanded.
	 */
	isExpanded: boolean;
	/**
	 * The pre-calculated initial visibility status passed from the parent.
	 * If undefined, the component calculates its own initial visibility.
	 */
	initialVisibilityStatus?: boolean | null;
	/**
	 * Indicates whether this payment method is currently being saved.
	 */
	isLoading?: boolean;
};

/**
 * A component that renders a recommended payment method as a list item.
 * Displays the payment method's icon, title, description, and a toggle control to enable or disable it.
 */
export const PaymentMethodListItem = ( {
	method,
	paymentMethodsState,
	setPaymentMethodsState,
	isExpanded,
	initialVisibilityStatus,
	isLoading = false,
	...props
}: PaymentMethodListItemProps ) => {
	// Internal ref for fallback mechanism when prop is not provided
	const shouldRenderInMainListRef = useRef< boolean | null >( null );

	// Fallback: Calculate initial visibility internally if prop is not provided
	if ( initialVisibilityStatus === undefined ) {
		// Only initialize the ref once the state for this method is available.
		if (
			shouldRenderInMainListRef.current === null &&
			paymentMethodsState[ method.id ] !== undefined
		) {
			shouldRenderInMainListRef.current =
				shouldRenderPaymentMethodInMainList(
					method,
					paymentMethodsState[ method.id ]
				);
		}
	}

	// Determine final rendering decision:
	// Prioritize the prop if provided, otherwise use the internal ref state.
	const baseVisibility =
		initialVisibilityStatus !== undefined
			? initialVisibilityStatus ?? false
			: shouldRenderInMainListRef.current ?? false;

	const shouldRender = isExpanded || baseVisibility;

	if ( ! shouldRender ) {
		return null;
	}

	return (
		<div
			id={ method.id }
			className="woocommerce-list__item woocommerce-list__item-enter-done"
			{ ...props }
		>
			<div className="woocommerce-list__item-inner">
				{ /* Default layout for regular payment methods */ }
				{ method.id !== 'apple_google' && (
					<>
						<div className="woocommerce-list__item-before">
							<img
								src={ method.icon }
								alt={ method.title + ' logo' }
							/>
						</div>
						<div className="woocommerce-list__item-text">
							<span className="woocommerce-list__item-title">
								{ method.title }
							</span>
							<span
								className="woocommerce-list__item-content"
								// eslint-disable-next-line react/no-danger -- This string is sanitized by the PaymentGateway class.
								dangerouslySetInnerHTML={ sanitizeHTML(
									decodeEntities( method.description )
								) }
							/>
						</div>
					</>
				) }
				{ /* Special layout for "apple_google" payment methods */ }
				{ method.id === 'apple_google' && (
					<div className="woocommerce-list__item-multi">
						<div className="woocommerce-list__item-multi-row multi-row-space">
							<div className="woocommerce-list__item-before">
								<img
									src={ method.icon }
									alt={ method.title + ' logo' }
								/>
							</div>
							<div className="woocommerce-list__item-text">
								<span className="woocommerce-list__item-title">
									{ method.title }
								</span>
								<span
									className="woocommerce-list__item-content"
									// eslint-disable-next-line react/no-danger -- This string is sanitized by the PaymentGateway class.
									dangerouslySetInnerHTML={ sanitizeHTML(
										decodeEntities( method.description )
									) }
								/>
							</div>
						</div>
						<div className="woocommerce-list__item-multi-row">
							<div className="woocommerce-list__item-before">
								<img
									src={ method.extraIcon }
									alt={ method.extraTitle + ' logo' }
								/>
							</div>
							<div className="woocommerce-list__item-text">
								<span className="woocommerce-list__item-title">
									{ method.extraTitle }
								</span>
								<span
									className="woocommerce-list__item-content"
									// eslint-disable-next-line react/no-danger -- This string is sanitized by the PaymentGateway class.
									dangerouslySetInnerHTML={ sanitizeHTML(
										decodeEntities(
											method.extraDescription ?? ''
										)
									) }
								/>
							</div>
						</div>
					</div>
				) }
				<div className="woocommerce-list__item-after">
					<div className="woocommerce-list__item-after__actions wc-settings-prevent-change-event">
						<ToggleControl
							checked={
								paymentMethodsState[ method.id ] ?? false
							}
							onChange={ ( isChecked: boolean ) => {
								setPaymentMethodsState( {
									...paymentMethodsState,
									[ method.id ]: isChecked,
								} );
							} }
							disabled={
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore disabled prop exists
								( method.required ?? false ) || isLoading
							}
							label=""
						/>
					</div>
				</div>
			</div>
		</div>
	);
};
