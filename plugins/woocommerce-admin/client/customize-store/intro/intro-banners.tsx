/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Button } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
// @ts-ignore No types for this exist yet.
import useSiteEditorSettings from '@wordpress/edit-site/build-module/components/block-editor/use-site-editor-settings';

/**
 * Internal dependencies
 */
import { Intro } from '.';
import {
	ScaledBlockPreview,
	ScaledBlockPreviewProps,
} from '../assembler-hub/auto-block-preview';

export const BaseIntroBanner = ( {
	bannerTitle,
	bannerText,
	bannerClass,
	buttonIsLink,
	bannerButtonOnClick,
	bannerButtonText,
	bannerSiteRender,
	children,
}: {
	bannerTitle: string;
	bannerText: string;
	bannerClass: string;
	buttonIsLink?: boolean;
	bannerButtonOnClick?: () => void;
	bannerButtonText?: string;
	bannerSiteRender?: boolean;
	children?: React.ReactNode;
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
				{ bannerButtonText ? (
					<Button
						onClick={ () =>
							bannerButtonOnClick && bannerButtonOnClick()
						}
						variant={ buttonIsLink ? 'link' : 'primary' }
					>
						{ bannerButtonText }
					</Button>
				) : null }
				{ children }
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
				"Unfortunately, the [AI Store designer] isn't available right now as we can't detect your network. Please check your internet connection.",
				'woocommerce'
			) }
			bannerClass="offline-banner"
			bannerButtonOnClick={ () => {} }
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

export const ExistingThemeBanner = ( {
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

export const ExistingAiThemeBanner = ( {
	setOpenDesignChangeWarningModal,
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
} ) => {
	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Customize your custom theme', 'woocommerce' ) }
			bannerText={ __(
				'Keep customizing the look of your AI-generated store, or start over and create a new one.',
				'woocommerce'
			) }
			bannerClass="existing-ai-theme-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				window.location.href = getNewPath(
					{},
					'/customize-store/assembler-hub',
					{}
				);
			} }
			bannerButtonText={ __( 'Customize', 'woocommerce' ) }
			bannerSiteRender={ true }
		>
			<Button
				className=""
				onClick={ () => {
					setOpenDesignChangeWarningModal( true );
				} }
				variant={ 'secondary' }
			>
				{ __( 'Create a new one', 'woocommerce' ) }
			</Button>
			<div className={ 'woocommerce-block-preview-container' }>
				<ScaledBlockPreview
					viewportWidth = { 1040 }
					containerWidth = { 400 }
					settings = {{ styles: [] }}
					additionalStyles = { '' }
					onClickNavigationItem = { ( event: MouseEvent ) => null }
					isNavigable = { false }
					isScrollable = { true }
				/>
			</div>
		</BaseIntroBanner>
	);
};
