/**
 * External dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createElement, useEffect, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './promo-card.scss';
import { Promotion } from '../promotions/types';
import sanitizeHTML from '../../../lib/sanitize-html';
import PercentSVG from './images/percent';

interface PromoCardProps {
	promotion: Promotion;
	// Extra properties merged into the promotion Tracks events (e.g. order_count, surface).
	eventProperties?: Record< string, unknown >;
	// Called on dismiss. When provided, replaces the default localStorage dismissal so the
	// caller can persist it elsewhere (e.g. server-side, per user).
	onDismiss?: () => void;
}

const imageComponents = {
	percent: PercentSVG,
};

const PromoCard = ( {
	promotion,
	eventProperties = {},
	onDismiss,
}: PromoCardProps ): React.ReactElement | null => {
	const path = window.location.pathname + window.location.search;

	const getDismissedURIs = () =>
		JSON.parse(
			localStorage.getItem( 'wc-marketplaceDismissedPromos' ) || '[]'
		);

	// When a caller provides onDismiss it owns dismissal persistence (e.g. server-side, per user),
	// so the localStorage fallback is bypassed for both the initial visibility check here and the
	// write in handleDismiss. Without a callback, fall back to the localStorage-by-path behavior.
	const [ isVisible, setIsVisible ] = useState(
		onDismiss ? true : ! getDismissedURIs().includes( path )
	);

	useEffect( () => {
		if ( isVisible ) {
			recordEvent( 'marketplace_promotion_viewed', {
				// Custom properties first so the authoritative fields below win.
				...eventProperties,
				path,
				format: 'promo-card',
			} );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ isVisible ] );

	if ( ! isVisible ) return null;

	const handleDismiss = () => {
		setIsVisible( false );

		if ( onDismiss ) {
			onDismiss();
		} else {
			localStorage.setItem(
				'wc-marketplaceDismissedPromos',
				JSON.stringify( getDismissedURIs().concat( path ) )
			);
		}

		recordEvent( 'marketplace_promotion_dismissed', {
			...eventProperties,
			path,
			format: 'promo-card',
		} );
	};

	const handleClick = () => {
		recordEvent( 'marketplace_promotion_actioned', {
			...eventProperties,
			path,
			target_uri: promotion.cta_link,
			format: 'promo-card',
		} );

		return true;
	};

	const classNames =
		'promo-card' + ( promotion.style ? ` ${ promotion.style }` : '' );

	const content = (
		<div className="promo-content">
			<h2 className="promo-title">{ promotion.title?.en_US }</h2>
			<div
				className="promo-text"
				dangerouslySetInnerHTML={ sanitizeHTML(
					promotion.content?.en_US
				) }
			/>
		</div>
	);

	const links = (
		<div className="promo-links">
			<Button
				className="promo-cta"
				href={ promotion.cta_link ?? '' }
				variant="secondary"
				onClick={ handleClick }
			>
				{ promotion.cta_label?.en_US ?? '' }
			</Button>
			<Button
				className="promo-cta-link woocommerce-admin-dismiss-notification"
				onClick={ handleDismiss }
			>
				{ __( 'Dismiss', 'woocommerce' ) }
			</Button>
		</div>
	);

	function getImage() {
		if (
			promotion.icon &&
			Object.hasOwn( imageComponents, promotion.icon )
		) {
			const ImageComponent =
				imageComponents[
					promotion.icon as keyof typeof imageComponents
				];
			return ImageComponent ? (
				<div className="promo-image">
					{ createElement( ImageComponent ) }
				</div>
			) : null;
		}

		return null;
	}

	return (
		<Card className={ classNames }>
			<CardBody className="promo-card__body">
				{ promotion?.style === 'has-background' ? (
					<>
						<div className="promo-content-links">
							{ content }
							{ links }
						</div>
						{ getImage() }
					</>
				) : (
					<>
						<div className="promo-content-image">
							{ content }
							{ getImage() }
						</div>
						{ links }
					</>
				) }
			</CardBody>
		</Card>
	);
};

export default PromoCard;
