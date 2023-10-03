/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Intro } from '.';

export const BaseIntroBanner = ( {
	bannerTitle,
	bannerText,
	bannerClass,
	buttonIsLink,
	bannerButtonOnClick,
	bannerButtonText,
}: {
	bannerTitle: string;
	bannerText: string;
	bannerClass: string;
	buttonIsLink: boolean;
	bannerButtonOnClick: () => void;
	bannerButtonText: string;
} ) => {
	return (
		<div
			className={ classNames(
				'woocommerce-customize-store-banner',
				bannerClass
			) }
		>
			<div className={ `woocommerce-customize-store-banner-content` }>
				<h1>{ bannerTitle }</h1>
				<p>{ bannerText }</p>
				<Button
					onClick={ () => bannerButtonOnClick() }
					variant={ buttonIsLink ? 'link' : 'primary' }
				>
					{ bannerButtonText }
				</Button>
			</div>
		</div>
	);
};

export const NetworkOfflineBanner = () => {
	return (
		<BaseIntroBanner
			bannerTitle={ __(
				'Looking to design your store using AI?',
				'woocommerce'
			) }
			bannerText={ __(
				"Unfortunately, the [AI Store designer] isn't available right now as we can't detect your network. Please check your internet connection and try again.",
				'woocommerce'
			) }
			bannerClass="offline-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {} }
			bannerButtonText={ __( 'Retry', 'woocommerce' ) }
		/>
	);
};

export const JetpackOfflineBanner = ( {
	sendEvent,
}: {
	sendEvent: React.ComponentProps< typeof Intro >[ 'sendEvent' ];
} ) => {
	return (
		<BaseIntroBanner
			bannerTitle={ __(
				'Looking to design your store using AI?',
				'woocommerce'
			) }
			bannerText={ __(
				"It looks like you're using Jetpack's offline mode — switch to online mode to start designing with AI.",
				'woocommerce'
			) }
			bannerClass="offline-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				sendEvent( {
					type: 'JETPACK_OFFLINE_HOWTO',
				} );
			} }
			bannerButtonText={ __( 'Find out how', 'woocommerce' ) }
		/>
	);
};

export const DefaultBanner = ( {
	sendEvent,
}: {
	sendEvent: React.ComponentProps< typeof Intro >[ 'sendEvent' ];
} ) => {
	return (
		<BaseIntroBanner
			bannerTitle={ __(
				'Use the power of AI to design your store',
				'woocommerce'
			) }
			bannerText={ __(
				'Design the look of your store, create pages, and generate copy using our built-in AI tools.',
				'woocommerce'
			) }
			bannerClass=""
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				sendEvent( {
					type: 'DESIGN_WITH_AI',
				} );
			} }
			bannerButtonText={ __( 'Design with AI', 'woocommerce' ) }
		/>
	);
};

export const ThemeHasModsBanner = ( {
	setOpenDesignChangeWarningModal,
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
} ) => {
	return (
		<BaseIntroBanner
			bannerTitle={ __(
				'Use the power of AI to design your store',
				'woocommerce'
			) }
			bannerText={ __(
				'Design the look of your store, create pages, and generate copy using our built-in AI tools.',
				'woocommerce'
			) }
			bannerClass=""
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				setOpenDesignChangeWarningModal( true );
			} }
			bannerButtonText={ __( 'Design with AI', 'woocommerce' ) }
		/>
	);
};
