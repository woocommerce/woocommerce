/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
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
}

const imageComponents = {
	percent: PercentSVG,
};

const PromoCard = ( {
	promotion,
}: PromoCardProps ): React.ReactElement | null => {
	const uri = window.location.pathname + window.location.search;

	const getDismissedURIs = () =>
		JSON.parse(
			localStorage.getItem( 'wc-marketplaceDismissedPromos' ) || '[]'
		);

	const [ isVisible, setIsVisible ] = useState(
		! getDismissedURIs().includes( uri )
	);

	useEffect( () => {
		if ( isVisible ) {
			recordEvent( 'marketplace_promo_viewed', {
				uri,
			} );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ isVisible ] );

	if ( ! isVisible ) return null;

	const handleDismiss = () => {
		setIsVisible( false );
		localStorage.setItem(
			'wc-marketplaceDismissedPromos',
			JSON.stringify( getDismissedURIs().concat( uri ) )
		);

		recordEvent( 'marketplace_promo_dismissed', {
			uri,
		} );
	};

	const handleClick = () => {
		recordEvent( 'marketplace_promo_actioned', {
			uri,
			target_uri: promotion.cta_link,
		} );

		return true;
	};

	const classNames =
		'promo-card' + ( promotion.style ? ` ${ promotion.style }` : '' );

	const content = (
		<div className="promo-content">
			<h2 className="promo-title">{ promotion.title?.en_US }</h2>
			<p
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
				onClick={ handleClick }
			>
				{ promotion.cta_label?.en_US ?? '' }
			</Button>
			<Button className="promo-cta-link" onClick={ handleDismiss }>
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
		<div className={ classNames }>
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
		</div>
	);
};

export default PromoCard;
