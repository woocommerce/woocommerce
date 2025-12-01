/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, Icon } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './banner.scss';
import {
	refundPolicyTitle,
	supportTitle,
	paymentTitle,
} from '../footer/footer';
import trustProducts from '../../assets/images/trust-products.svg';
import supportEcosystem from '../../assets/images/support-ecosystem.svg';
import moneyBack from '../../assets/images/money-back.svg';
import getHelp from '../../assets/images/get-help.svg';

const SLIDES = [
	{
		imageUrl: moneyBack,
		title: refundPolicyTitle( 'banner' ),
		textTitle: __( '30-day money-back guarantee', 'woocommerce' ),
	},
	{
		imageUrl: getHelp,
		title: supportTitle( 'banner' ),
		textTitle: __( 'Get help when you need it', 'woocommerce' ),
	},
	{
		imageUrl: trustProducts,
		title: paymentTitle( 'banner' ),
		textTitle: __( 'Products you can trust', 'woocommerce' ),
	},
	{
		imageUrl: supportEcosystem,
		title: __( 'Support the ecosystem', 'woocommerce' ),
		textTitle: __( 'Support the ecosystem', 'woocommerce' ),
	},
];

export default function ProductFeaturedBanner() {
	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const [ isDismissed, setIsDismissed ] = useState( false );
	const [ autoRotate, setAutoRotate ] = useState( true );
	const carouselItemRef = useRef< HTMLLIElement >( null );

	useEffect( () => {
		let interval: NodeJS.Timeout;
		if ( autoRotate ) {
			interval = setInterval( () => {
				setActiveIndex( ( prev ) => ( prev + 1 ) % SLIDES.length );
			}, 5000 );
		}
		return () => clearInterval( interval );
	}, [ autoRotate ] );

	useEffect( () => {
		const dismissed = localStorage.getItem( 'wc_featuredBannerDismissed' );
		setIsDismissed( dismissed === 'true' );
	}, [] );

	const handleDismiss = () => {
		localStorage.setItem( 'wc_featuredBannerDismissed', 'true' );
		setIsDismissed( true );

		recordEvent( 'marketplace_features_banner_dismissed', {
			active_slide: SLIDES[ activeIndex ].textTitle,
		} );
	};

	const handlePause = () => setAutoRotate( false );
	const handleResume = () => setAutoRotate( true );

	const handleKeyPress = ( event: React.KeyboardEvent< HTMLLIElement > ) => {
		if ( event.key === 'ArrowRight' ) {
			setActiveIndex( ( prev ) => ( prev + 1 ) % SLIDES.length );
			setTimeout( () => {
				carouselItemRef.current?.focus();
			}, 100 );
		} else if ( event.key === 'ArrowLeft' ) {
			setActiveIndex(
				( prev ) => ( prev - 1 + SLIDES.length ) % SLIDES.length
			);
			setTimeout( () => {
				carouselItemRef.current?.focus();
			}, 100 );
		}
	};

	if ( isDismissed ) return null;

	return (
		<div
			className="woocommerce-marketplace__banner"
			role="region"
			aria-roledescription="carousel"
			aria-label={ __(
				'Marketplace features with four slides',
				'woocommerce'
			) }
			onMouseEnter={ handlePause }
			onMouseLeave={ handleResume }
			onFocus={ handlePause }
			onBlur={ handleResume }
		>
			<div className="carousel-container">
				<ul className="carousel-list">
					{ SLIDES.map( ( slide, index ) => (
						<li
							ref={
								index === activeIndex ? carouselItemRef : null
							}
							key={ index }
							id={ `carousel-slide-${ index }` }
							className={ `carousel-slide ${
								index === activeIndex ? 'active' : ''
							}` }
							aria-roledescription="slide"
							aria-hidden={ index !== activeIndex }
							aria-live="off"
							aria-posinset={ index + 1 }
							aria-setsize={ SLIDES.length }
							aria-label={ `${ slide.textTitle } - ${ __(
								'Slide',
								'woocommerce'
							) } ${ index + 1 } ${ __( 'of', 'woocommerce' ) } ${
								SLIDES.length
							}` }
							tabIndex={ index === activeIndex ? 0 : -1 }
							onKeyDown={ handleKeyPress }
						>
							<img
								src={ slide.imageUrl }
								alt=""
								className="woocommerce-marketplace__banner-image"
							/>
							<h3 className="woocommerce-marketplace__banner-title">
								{ slide.title }
							</h3>
						</li>
					) ) }
				</ul>
			</div>
			<Button
				className="dismiss-button"
				onClick={ handleDismiss }
				aria-label={ __(
					'Dismiss Marketplace features carousel',
					'woocommerce'
				) }
			>
				<Icon icon="no-alt" />
			</Button>
		</div>
	);
}
