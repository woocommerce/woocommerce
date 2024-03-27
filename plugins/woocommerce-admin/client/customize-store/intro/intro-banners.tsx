/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Button } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Intro } from '.';
import { IntroSiteIframe } from './intro-site-iframe';
import { getAdminSetting } from '~/utils/admin-settings';
import { navigateOrParent } from '../utils';
import { ThemeSwitchWarningModal } from '~/customize-store/intro/warning-modals';

export const BaseIntroBanner = ( {
	bannerTitle,
	bannerText,
	bannerClass,
	showAIDisclaimer,
	buttonIsLink,
	bannerButtonOnClick,
	bannerButtonText,
	secondaryButton,
	previewBanner,
	children,
}: {
	bannerTitle: string;
	bannerText: string;
	bannerClass: string;
	showAIDisclaimer: boolean;
	buttonIsLink?: boolean;
	bannerButtonOnClick?: () => void;
	bannerButtonText?: string;
	secondaryButton?: React.ReactNode;
	previewBanner?: React.ReactNode;
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
				<div className="banner-actions">
					<h1>{ bannerTitle }</h1>
					<p>{ bannerText }</p>
					{ bannerButtonText && (
						<Button
							onClick={ () =>
								bannerButtonOnClick && bannerButtonOnClick()
							}
							variant={ buttonIsLink ? 'link' : 'primary' }
						>
							{ bannerButtonText }
						</Button>
					) }
					{ secondaryButton }
					{ showAIDisclaimer && (
						<p className="ai-disclaimer">
							{ interpolateComponents( {
								mixedString: __(
									'Powered by experimental AI. {{link}}Learn more{{/link}}',
									'woocommerce'
								),
								components: {
									link: (
										<Link
											href="https://automattic.com/ai-guidelines"
											target="_blank"
											type="external"
										/>
									),
								},
							} ) }
						</p>
					) }
				</div>
				{ children }
			</div>
			{ previewBanner }
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
			showAIDisclaimer={ true }
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
			showAIDisclaimer={ true }
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
			showAIDisclaimer={ true }
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
			showAIDisclaimer={ true }
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
			showAIDisclaimer={ true }
		/>
	);
};

export const NoAIBanner = ( {
	redirectToCYSFlow,
}: {
	redirectToCYSFlow: () => void;
} ) => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	interface Theme {
		stylesheet?: string;
	}

	const currentTheme = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( 'core' ).getCurrentTheme() as Theme;
	}, [] );

	const isDefaultTheme = currentTheme?.stylesheet === 'twentytwentyfour';

	return (
		<>
			<BaseIntroBanner
				bannerTitle={ __( 'Design your own', 'woocommerce' ) }
				bannerText={ __(
					'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style, and much more.',
					'woocommerce'
				) }
				bannerClass="no-ai-banner"
				bannerButtonText={ __( 'Start designing', 'woocommerce' ) }
				bannerButtonOnClick={ () => {
					if ( ! isDefaultTheme ) {
						setIsModalOpen( true );
					} else {
						redirectToCYSFlow();
					}
				} }
				showAIDisclaimer={ false }
			/>
			{ isModalOpen && (
				<ThemeSwitchWarningModal
					setIsModalOpen={ setIsModalOpen }
					redirectToCYSFlow={ redirectToCYSFlow }
				/>
			) }
		</>
	);
};

export const ExistingAiThemeBanner = ( {
	setOpenDesignChangeWarningModal,
}: {
	setOpenDesignChangeWarningModal: ( arg0: boolean ) => void;
} ) => {
	const secondaryButton = (
		<Button
			className=""
			onClick={ () => {
				recordEvent(
					'customize_your_store_intro_create_a_new_one_click'
				);
				setOpenDesignChangeWarningModal( true );
			} }
			variant={ 'secondary' }
		>
			{ __( 'Create a new one', 'woocommerce' ) }
		</Button>
	);
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?cys-hide-admin-bar=1';

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
				recordEvent( 'customize_your_store_intro_customize_click' );
				navigateOrParent(
					window,
					getNewPath(
						{ customizing: true },
						'/customize-store/assembler-hub',
						{}
					)
				);
			} }
			bannerButtonText={ __( 'Customize', 'woocommerce' ) }
			secondaryButton={ secondaryButton }
			showAIDisclaimer={ true }
		>
			<div className={ 'woocommerce-block-preview-container' }>
				<div className="iframe-container">
					<IntroSiteIframe siteUrl={ siteUrl } />
				</div>
			</div>
		</BaseIntroBanner>
	);
};

export const ExistingNoAiThemeBanner = () => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?cys-hide-admin-bar=1';

	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Edit your custom theme', 'woocommerce' ) }
			bannerText={ __(
				'Continue to customize your store using the store designer. Change your color palette, fonts, page layouts, and more.',
				'woocommerce'
			) }
			bannerClass="existing-no-ai-theme-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				recordEvent( 'customize_your_store_intro_customize_click' );
				navigateOrParent(
					window,
					getNewPath(
						{ customizing: true },
						'/customize-store/assembler-hub',
						{}
					)
				);
			} }
			bannerButtonText={ __( 'Customize your theme', 'woocommerce' ) }
			showAIDisclaimer={ false }
			previewBanner={ <IntroSiteIframe siteUrl={ siteUrl } /> }
		></BaseIntroBanner>
	);
};
