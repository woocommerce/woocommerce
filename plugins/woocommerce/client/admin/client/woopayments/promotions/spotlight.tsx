/**
 * External dependencies
 */
import { Button, Card, ExternalLink, Icon } from '@wordpress/components';
import { RawHTML, useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { close } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './data/store';
import { usePmPromotionActions, usePmPromotions } from './data/hooks';
import type { PmPromotion } from './types';
import './style.scss';

const getSafeUrl = ( url?: string ) => {
	if ( ! url ) {
		return '';
	}

	try {
		const parsedUrl = new URL( url );

		return [ 'http:', 'https:' ].includes( parsedUrl.protocol ) ? url : '';
	} catch {
		return '';
	}
};

const getNativeRoutePath = () => {
	try {
		return (
			new URLSearchParams( window.location.search ).get( 'path' ) || ''
		);
	} catch {
		return '';
	}
};

const getPageSource = () => {
	const currentPath = window.location.pathname + window.location.search;
	const routePath = getNativeRoutePath();
	const path = routePath || currentPath;

	if ( path.includes( '/woopayments/overview' ) ) {
		return 'wcpay-overview';
	}
	if ( path.includes( '/woopayments/payouts' ) ) {
		return 'wcpay-payouts';
	}
	if ( path.includes( '/woopayments/transactions' ) ) {
		return 'wcpay-transactions';
	}
	if ( path.includes( '/woopayments/disputes' ) ) {
		return 'wcpay-disputes';
	}
	if ( path.includes( '/woopayments/settings' ) ) {
		return 'wcpay-settings';
	}
	if (
		currentPath.includes( 'page=wc-settings' ) &&
		currentPath.includes( 'tab=checkout' )
	) {
		return 'wc-settings-payments';
	}

	return 'unknown';
};

const getEventProperties = ( promotion: PmPromotion ) => ( {
	promo_id: promotion.promo_id,
	payment_method: promotion.payment_method,
	display_context: 'spotlight',
	source: getPageSource(),
} );

const getFocusRestoreTarget = ( container: HTMLElement ) => {
	const ownerDocument = container.ownerDocument;
	const parent = container.parentElement;
	const candidates = parent
		? Array.from(
				parent.querySelectorAll< HTMLElement >(
					'h1, h2, [role="heading"], button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled])'
				)
		  )
		: [];

	return (
		candidates.find( ( element ) => ! container.contains( element ) ) ||
		ownerDocument.getElementById( 'wpbody-content' ) ||
		ownerDocument.body
	);
};

const focusRestoreTarget = ( target: HTMLElement ) => {
	const hadTabIndex = target.hasAttribute( 'tabindex' );

	if ( ! hadTabIndex ) {
		target.setAttribute( 'tabindex', '-1' );
	}

	target.focus();

	if ( ! hadTabIndex ) {
		target.addEventListener(
			'blur',
			() => target.removeAttribute( 'tabindex' ),
			{ once: true }
		);
	}
};

export const SpotlightPromotion = () => {
	const { pmPromotions, isLoading } = usePmPromotions();
	const { activatePmPromotion, dismissPmPromotion } = usePmPromotionActions();
	const spotlightRef = useRef< HTMLDivElement >( null );
	const spotlightPromotion = useMemo(
		() =>
			pmPromotions?.find(
				( promotion: PmPromotion ) => promotion.type === 'spotlight'
			),
		[ pmPromotions ]
	);

	useEffect( () => {
		if ( ! spotlightPromotion ) {
			return;
		}

		recordEvent(
			'wcpay_payment_method_promotion_view',
			getEventProperties( spotlightPromotion )
		);
	}, [ spotlightPromotion ] );

	if ( isLoading || ! spotlightPromotion ) {
		return null;
	}

	const eventProperties = getEventProperties( spotlightPromotion );
	const termsUrl = getSafeUrl( spotlightPromotion.tc_url );
	const runPromotionAction = ( action: () => unknown ) => {
		const container = spotlightRef.current;
		const activeElement = container?.ownerDocument.activeElement;
		const shouldRestoreFocus = Boolean(
			container && activeElement && container.contains( activeElement )
		);
		const restoreTarget = container
			? getFocusRestoreTarget( container )
			: null;
		const restoreFocus = () => {
			if ( shouldRestoreFocus && restoreTarget ) {
				focusRestoreTarget( restoreTarget );
			}
		};

		try {
			const result = action();
			void Promise.resolve( result ).finally( () => {
				window.setTimeout( restoreFocus, 0 );
			} );
		} catch ( error ) {
			restoreFocus();
			throw error;
		}
	};

	return (
		<Card
			ref={ spotlightRef }
			className="woopayments-promotion-spotlight"
			data-testid="woopayments-promotion-spotlight"
		>
			<div className="woopayments-promotion-spotlight__content">
				{ spotlightPromotion.badge_text && (
					<span
						className={ `woopayments-promotion-spotlight__badge is-${
							spotlightPromotion.badge_type || 'success'
						}` }
					>
						{ spotlightPromotion.badge_text }
					</span>
				) }
				<div className="woopayments-promotion-spotlight__copy">
					<h2>{ spotlightPromotion.title }</h2>
					{ spotlightPromotion.description && (
						<RawHTML>{ spotlightPromotion.description }</RawHTML>
					) }
					{ spotlightPromotion.footnote && (
						<RawHTML className="woopayments-promotion-spotlight__footnote">
							{ spotlightPromotion.footnote }
						</RawHTML>
					) }
				</div>
				<div className="woopayments-promotion-spotlight__actions">
					<Button
						variant="primary"
						onClick={ () => {
							recordEvent(
								'wcpay_payment_method_promotion_activate_click',
								eventProperties
							);
							runPromotionAction( () =>
								activatePmPromotion( spotlightPromotion.id )
							);
						} }
					>
						{ spotlightPromotion.cta_label ||
							__( 'Activate', 'woocommerce' ) }
					</Button>
					{ termsUrl && (
						<ExternalLink
							className="woopayments-promotion-spotlight__terms"
							href={ termsUrl }
							onClick={ () =>
								recordEvent(
									'wcpay_payment_method_promotion_link_click',
									{
										...eventProperties,
										link_type: 'terms',
									}
								)
							}
						>
							{ spotlightPromotion.tc_label ||
								__( 'See terms', 'woocommerce' ) }
						</ExternalLink>
					) }
				</div>
			</div>
			{ spotlightPromotion.image && (
				<img
					className="woopayments-promotion-spotlight__image"
					src={ spotlightPromotion.image }
					alt=""
					aria-hidden="true"
				/>
			) }
			<Button
				className="woopayments-promotion-spotlight__dismiss"
				icon={ <Icon icon={ close } /> }
				label={ __( 'Dismiss promotion', 'woocommerce' ) }
				onClick={ () => {
					recordEvent(
						'wcpay_payment_method_promotion_dismiss_click',
						eventProperties
					);
					runPromotionAction( () =>
						dismissPmPromotion( spotlightPromotion.id )
					);
				} }
			/>
		</Card>
	);
};

export default SpotlightPromotion;
