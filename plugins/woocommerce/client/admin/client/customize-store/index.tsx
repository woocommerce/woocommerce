/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useEffect, useMemo } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import {
	Button,
	__experimentalHStack as HStack,
	__experimentalHeading as Heading,
	__experimentalVStack as VStack,
	__unstableMotion as motion,
} from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
// @ts-ignore No types for this exist yet.
import SidebarButton from '@wordpress/edit-site/build-module/components/sidebar-button';
import { chevronRight, chevronLeft } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useFullScreen } from '~/utils';
import { isWooExpress } from '~/utils/is-woo-express';
import { SiteHub } from './site-hub';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';
import banner1Shape from './assets/banner-1-shape.svg';
import banner2Shape from './assets/banner-2-shape.svg';
import banner1Illu from './assets/banner-1-illu.svg';
import banner2Illu from './assets/banner-2-illu.svg';
import './style.scss';

const CustomizeStoreController = () => {
	useFullScreen( [ 'woocommerce-customize-store' ] );

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const currentTheme = useSelect( ( select ) => {
		// @ts-ignore
		return select( 'core' ).getCurrentTheme();
	}, [] );

	const isBlockTheme = currentTheme?.is_block_theme;

	const designUrl = useMemo( () => {
		// Encoding is needed to carry-over query parameters.
		const encodedReturnUrl = encodeURIComponent(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store'
		);

		return isBlockTheme
			? getAdminLink( 'site-editor.php' )
			: getAdminLink( `customize.php?return=${ encodedReturnUrl }` );
	}, [ isBlockTheme ] );

	const marketplaceUrl = useMemo( () => {
		if ( isWooExpress() ) {
			return getAdminLink( 'themes.php' );
		}
		return getAdminLink(
			'admin.php?page=wc-admin&tab=themes&path=%2Fextensions'
		);
	}, [] );

	useEffect( () => {
		document.body.classList.add( 'woocommerce-customize-store' );
		return () => {
			document.body.classList.remove( 'woocommerce-customize-store' );
		};
	}, [] );

	const markTaskComplete = async () => {
		await updateOptions( {
			woocommerce_admin_customize_store_completed: 'yes',
		} );
	};

	const isNewTabClick = ( event: React.MouseEvent ) => {
		// Middle mouse button, Cmd+Click (Mac), or Ctrl+Click (Windows/Linux)
		return event.button === 1 || event.metaKey || event.ctrlKey;
	};

	const handleClick = async (
		event: React.MouseEvent< HTMLAnchorElement >,
		href: string
	) => {
		if ( isNewTabClick( event ) ) {
			// New tab: page stays open, so fire-and-forget is safe
			markTaskComplete();
			return;
		}

		event.preventDefault();
		await markTaskComplete();
		window.location.href = href;
	};

	const handleDesignClick = async (
		event: React.MouseEvent< HTMLAnchorElement >
	) => {
		recordEvent( 'customize_your_store_intro_customize_click', {
			theme_type: isBlockTheme ? 'block' : 'classic',
		} );

		await handleClick( event, designUrl );
	};

	const handleMarketplaceClick = async (
		event: React.MouseEvent< HTMLAnchorElement >
	) => {
		recordEvent( 'customize_your_store_intro_browse_all_themes_click' );
		await handleClick( event, marketplaceUrl );
	};

	const chevronIcon = isRTL() ? chevronRight : chevronLeft;

	return (
		<div className="woocommerce-customize-store__container">
			<div className="woocommerce-customize-store-sidebar">
				<motion.div
					className="woocommerce-edit-site-layout__header-container"
					animate={ 'view' }
				>
					<SiteHub
						variants={ {
							view: { x: 0 },
						} }
						isTransparent={ false }
						className="woocommerce-edit-site-layout__hub"
					/>
				</motion.div>

				<VStack
					className="woocommerce-edit-site-sidebar-navigation-screen__main"
					spacing={ 0 }
					justify="flex-start"
				>
					<HStack
						spacing={ 4 }
						alignment="flex-start"
						className="woocommerce-edit-site-sidebar-navigation-screen__title-icon"
					>
						<SidebarButton
							href={ getNewPath( {}, '/', {} ) }
							icon={ chevronIcon }
							label={ __( 'Back', 'woocommerce' ) }
							showTooltip={ false }
						/>

						<Heading
							className="woocommerce-edit-site-sidebar-navigation-screen__title"
							level={ 1 }
							as="h1"
						>
							<Button href={ getNewPath( {}, '/', {} ) }>
								{ __( 'Customize your store', 'woocommerce' ) }
							</Button>
						</Heading>
					</HStack>

					<div className="woocommerce-edit-site-sidebar-navigation-screen__content">
						<p className="woocommerce-edit-site-sidebar-navigation-screen__description">
							{ __(
								'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
								'woocommerce'
							) }
						</p>
					</div>
				</VStack>
			</div>

			<div className="woocommerce-customize-store-main">
				<div className="woocommerce-customize-store-banner">
					<div className="woocommerce-customize-store-banner-content">
						<div className="woocommerce-customize-store__banner-actions">
							<h2>{ __( 'Design your own', 'woocommerce' ) }</h2>
							<p>
								{ __(
									'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style, and much more.',
									'woocommerce'
								) }
							</p>
							<Button
								variant="primary"
								onClick={ handleDesignClick }
								href={ designUrl }
							>
								{ __( 'Start designing', 'woocommerce' ) }
							</Button>
						</div>
					</div>
					<div className="woocommerce-banner-visual">
						<img
							src={ banner1Shape }
							alt=""
							className="woocommerce-banner-shape"
						/>
						<img
							src={ banner1Illu }
							alt=""
							className="woocommerce-banner-icon"
						/>
					</div>
				</div>

				<div className="woocommerce-customize-store-banner pick-your-theme-banner">
					<div className="woocommerce-customize-store-banner-content">
						<div className="woocommerce-customize-store__banner-actions">
							<h2>
								{ __(
									'Pick your perfect theme',
									'woocommerce'
								) }
							</h2>
							<div className="pick-your-theme-banner__content">
								<p>
									{ __(
										'Bring your vision to life — no coding required. Explore hundreds of free and paid ecommerce-optimized themes.',
										'woocommerce'
									) }
								</p>
								<ul>
									<li>
										{ __(
											'Themes for every industry',
											'woocommerce'
										) }
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
							<Button
								variant="primary"
								onClick={ handleMarketplaceClick }
								href={ marketplaceUrl }
							>
								{ __(
									'Browse the Marketplace',
									'woocommerce'
								) }
							</Button>
						</div>
					</div>
					<div className="woocommerce-banner-visual">
						<img
							src={ banner2Shape }
							alt=""
							className="woocommerce-banner-shape"
						/>
						<img
							src={ banner2Illu }
							alt=""
							className="woocommerce-banner-icon"
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default CustomizeStoreController;
