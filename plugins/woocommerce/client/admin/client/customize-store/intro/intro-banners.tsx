/**
 * External dependencies
 */
import { Sender } from 'xstate';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Button } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { IntroSiteIframe } from './intro-site-iframe';
import type { customizeStoreStateMachineEvents } from '~/customize-store';
import { ADMIN_URL, getAdminSetting } from '~/utils/admin-settings';
import { navigateOrParent } from '../utils';
import { trackEvent } from '../tracking';

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
	isSecondaryBanner,
}: {
	bannerTitle: string;
	bannerText: string | React.ReactNode;
	bannerClass: string;
	showAIDisclaimer: boolean;
	buttonIsLink?: boolean;
	bannerButtonOnClick?: () => void;
	bannerButtonText?: string;
	secondaryButton?: React.ReactNode;
	previewBanner?: React.ReactNode;
	children?: React.ReactNode;
	isSecondaryBanner?: boolean;
} ) => {
	const TextTag = typeof bannerText === 'string' ? 'p' : 'div';
	const TitleTag = isSecondaryBanner ? 'h2' : 'h1';
	return (
		<div
			className={ clsx(
				'woocommerce-customize-store-banner',
				bannerClass
			) }
		>
			<div className={ `woocommerce-customize-store-banner-content` }>
				<div className="banner-actions">
					<TitleTag>{ bannerTitle }</TitleTag>
					<TextTag>{ bannerText }</TextTag>
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
	sendEvent: Sender< customizeStoreStateMachineEvents >;
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

export const NoAIBanner = ( {
	redirectToCYSFlow,
}: {
	redirectToCYSFlow: () => void;
} ) => {
	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Design your own', 'woocommerce' ) }
			bannerText={ __(
				'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style, and much more.',
				'woocommerce'
			) }
			bannerClass="no-ai-banner"
			bannerButtonText={ __( 'Start designing', 'woocommerce' ) }
			bannerButtonOnClick={ () => {
				redirectToCYSFlow();
			} }
			showAIDisclaimer={ false }
		/>
	);
};

export const ExistingNoAiThemeBanner = () => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?cys-hide-admin-bar=1';

	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Customize your theme', 'woocommerce' ) }
			bannerText={ __(
				'Customize everything from the color palette and the fonts to the page layouts, making sure every detail aligns with your brand.',
				'woocommerce'
			) }
			bannerClass="existing-no-ai-theme-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				trackEvent( 'customize_your_store_intro_customize_click', {
					theme_type: 'block',
				} );
				navigateOrParent(
					window,
					getNewPath(
						{ customizing: true },
						'/customize-store/assembler-hub',
						{}
					)
				);
			} }
			bannerButtonText={ __( 'Customize your store', 'woocommerce' ) }
			showAIDisclaimer={ false }
			previewBanner={ <IntroSiteIframe siteUrl={ siteUrl } /> }
		></BaseIntroBanner>
	);
};

export const ClassicThemeBanner = () => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?cys-hide-admin-bar=1';

	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Customize your theme', 'woocommerce' ) }
			bannerText={ __(
				'Customize everything from the color palette and the fonts to the page layouts, making sure every detail aligns with your brand.',
				'woocommerce'
			) }
			bannerClass="existing-no-ai-theme-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				trackEvent( 'customize_your_store_intro_customize_click', {
					theme_type: 'classic',
				} );
				navigateOrParent(
					window,
					'customize.php?return=/wp-admin/themes.php'
				);
			} }
			bannerButtonText={ __( 'Go to the Customizer', 'woocommerce' ) }
			showAIDisclaimer={ false }
			previewBanner={ <IntroSiteIframe siteUrl={ siteUrl } /> }
		></BaseIntroBanner>
	);
};

export const NonDefaultBlockThemeBanner = () => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?cys-hide-admin-bar=1';

	return (
		<BaseIntroBanner
			bannerTitle={ __( 'Customize your theme', 'woocommerce' ) }
			bannerText={ __(
				'Customize everything from the color palette and the fonts to the page layouts, making sure every detail aligns with your brand.',
				'woocommerce'
			) }
			bannerClass="existing-no-ai-theme-banner"
			buttonIsLink={ false }
			bannerButtonOnClick={ () => {
				trackEvent( 'customize_your_store_intro_customize_click', {
					theme_type: 'block',
				} );
				navigateOrParent( window, `${ ADMIN_URL }site-editor.php` );
			} }
			bannerButtonText={ __( 'Go to the Editor', 'woocommerce' ) }
			showAIDisclaimer={ false }
			previewBanner={ <IntroSiteIframe siteUrl={ siteUrl } /> }
		></BaseIntroBanner>
	);
};

export const PickYourThemeBanner = ( {
	sendEvent,
}: {
	sendEvent: Sender< customizeStoreStateMachineEvents >;
} ) => {
	return (
		<BaseIntroBanner
			isSecondaryBanner
			bannerTitle={ __( 'Pick your perfect theme', 'woocommerce' ) }
			bannerText={
				<div className="pick-your-theme-banner__content">
					<p>
						{ __(
							'Bring your vision to life — no coding required. Explore hundreds of free and paid ecommerce-optimized themes.',
							'woocommerce'
						) }
					</p>
					<ul>
						<li>
							{ __( 'Themes for every industry', 'woocommerce' ) }
						</li>
						<li>
							{ __(
								'Ready to use out of the box',
								'woocommerce'
							) }
						</li>
						<li>
							{ __(
								'30-day money-back guarantee',
								'woocommerce'
							) }
						</li>
					</ul>
				</div>
			}
			bannerButtonText={ __( 'Browse the Marketplace', 'woocommerce' ) }
			bannerButtonOnClick={ () => {
				sendEvent( {
					type: 'SELECTED_BROWSE_ALL_THEMES',
				} );
			} }
			showAIDisclaimer={ false }
			bannerClass="pick-your-theme-banner"
		/>
	);
};
