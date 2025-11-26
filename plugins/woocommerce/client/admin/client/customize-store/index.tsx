/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
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
import { isFeatureEnabled } from '~/utils/features';
import { SiteHub } from './site-hub';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';
import banner1Shape from './assets/banner-1-shape.png';
import banner2Shape from './assets/banner-2-shape.png';
import banner1Icon from './assets/banner-1-icon.png';
import banner2Icon from './assets/banner-2-icon.png';
import './style.scss';

const CustomizeStoreController = () => {
	useFullScreen( [ 'woocommerce-customize-store' ] );

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const currentTheme = useSelect( ( select ) => {
		// @ts-ignore
		return select( 'core' ).getCurrentTheme();
	}, [] );

	const isBlockTheme = currentTheme?.is_block_theme;

	// Encoding is needed to carry-over query parameters.
	const encodedReturnUrl = encodeURIComponent(
		'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store'
	);

	const getDesignUrl = () => {
		return isBlockTheme
			? getAdminLink( 'site-editor.php' )
			: getAdminLink( `customize.php?return=${ encodedReturnUrl }` );
	};

	const getMarketplaceUrl = () => {
		if ( isWooExpress() ) {
			return getAdminLink( 'themes.php' );
		}
		if ( isFeatureEnabled( 'marketplace' ) ) {
			return getAdminLink(
				'admin.php?page=wc-admin&tab=themes&path=%2Fextensions'
			);
		}
		return 'https://woocommerce.com/product-category/themes/';
	};

	useEffect( () => {
		document.body.classList.add( 'woocommerce-customize-store' );
		return () => {
			document.body.classList.remove( 'woocommerce-customize-store' );
		};
	}, [] );

	const markTaskComplete = () => {
		updateOptions( {
			woocommerce_admin_customize_store_completed: 'yes',
		} );
	};

	const handleDesignClick = () => {
		recordEvent( 'customize_your_store_intro_customize_click', {
			theme_type: isBlockTheme ? 'block' : 'classic',
		} );
		markTaskComplete();
	};

	const handleMarketplaceClick = () => {
		recordEvent( 'customize_your_store_intro_browse_all_themes_click' );
		markTaskComplete();
	};

	const chevronIcon = isRTL() ? chevronRight : chevronLeft;

	const sidebarTitle = (
		<Button href={ getNewPath( {}, '/', {} ) }>
			{ __( 'Customize your store', 'woocommerce' ) }
		</Button>
	);

	const sidebarDescription = __(
		'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
		'woocommerce'
	);

	return (
		<div className="woocommerce-customize-store__container">
			<div className="mobile-header">
				<Button
					className="mobile-header__back-button"
					onClick={ () => {
						window.location.href = getNewPath( {}, '/', {} );
					} }
					icon={ chevronIcon }
					label={ __( 'Back', 'woocommerce' ) }
				/>
				<h1 className="mobile-header__title">
					{ __( 'Customize your store', 'woocommerce' ) }
				</h1>
			</div>
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
							onClick={ () => {
								window.location.href = getNewPath(
									{},
									'/',
									{}
								);
							} }
							icon={ chevronIcon }
							label={ __( 'Back', 'woocommerce' ) }
							showTooltip={ false }
						/>

						<Heading
							className="woocommerce-edit-site-sidebar-navigation-screen__title"
							level={ 1 }
							as="h1"
						>
							{ sidebarTitle }
						</Heading>
					</HStack>

					<div className="woocommerce-edit-site-sidebar-navigation-screen__content">
						<p className="woocommerce-edit-site-sidebar-navigation-screen__description">
							{ sidebarDescription }
						</p>
					</div>
				</VStack>
			</div>

			<div className="woocommerce-customize-store-main">
				<div className="woocommerce-customize-store-banner">
					<div className="woocommerce-customize-store-banner-content">
						<div className="banner-actions">
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
								href={ getDesignUrl() }
							>
								{ __( 'Start designing', 'woocommerce' ) }
							</Button>
						</div>
					</div>
					<div className="banner-visual">
						<img
							src={ banner1Shape }
							alt=""
							className="banner-shape"
						/>
						<img
							src={ banner1Icon }
							alt=""
							className="banner-icon"
						/>
					</div>
				</div>

				<div className="woocommerce-customize-store-banner pick-your-theme-banner">
					<div className="woocommerce-customize-store-banner-content">
						<div className="banner-actions">
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
								href={ getMarketplaceUrl() }
							>
								{ __(
									'Browse the Marketplace',
									'woocommerce'
								) }
							</Button>
						</div>
					</div>
					<div className="banner-visual">
						<img
							src={ banner2Shape }
							alt=""
							className="banner-shape"
						/>
						<img
							src={ banner2Icon }
							alt=""
							className="banner-icon"
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default CustomizeStoreController;
