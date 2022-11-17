/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { Icon, trendingUp } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import { Pill, Spinner } from '@woocommerce/components';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { flatMapDeep, uniqBy } from 'lodash';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardDivider,
	CardBody,
	PluginCardBody,
} from '~/marketing/components';
import { useInstalledPlugins } from '~/marketing/hooks';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import { createNoticesFromResponse } from '~/lib/notices';
import { Plugin } from './types';
import { useRecommendedPlugins } from './useRecommendedPlugins';
import './DiscoverTools.scss';

/**
 * Return tabs (`{ name, title }`) for the TabPanel.
 *
 * Subcategories that have no plugins
 * will not be displayed as a tab in the UI.
 * This is done by doing the following:
 *
 * 1. Get an array of unique subcategories from the list of plugins.
 * 2. Map the subcategories schema into tabs schema.
 */
const getTabs = ( plugins: Plugin[] ) => {
	const pluginSubcategories = uniqBy(
		flatMapDeep( plugins, ( p ) => p.subcategories ),
		( subcategory ) => subcategory.slug
	);

	return pluginSubcategories.map( ( subcategory ) => ( {
		name: subcategory.slug,
		title: subcategory.name,
	} ) );
};

export const DiscoverTools = () => {
	const { isLoading, plugins, refetch } = useRecommendedPlugins();
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { loadInstalledPluginsAfterActivation } = useInstalledPlugins();

	/**
	 * Install and activate a plugin.
	 *
	 * When the process is successful, the plugin will disappear in the recommended list,
	 * and appear in the installed extension list. A success notice will be displayed.
	 *
	 * When the process is not successful, an error notice will be displayed.
	 *
	 * @param  plugin Plugin to be installed and activated.
	 */
	const installAndActivate = async ( plugin: Plugin ) => {
		setCurrentPlugin( plugin.product );

		try {
			recordEvent( 'marketing_recommended_extension', {
				name: plugin.title,
			} );

			const response = await installAndActivatePlugins( [
				plugin.product,
			] );

			refetch();
			loadInstalledPluginsAfterActivation( plugin.product );
			createNoticesFromResponse( response );
		} catch ( error ) {
			createNoticesFromResponse( error );
		}

		setCurrentPlugin( null );
	};

	/**
	 * Renders card body.
	 *
	 * - If loading is in progress, it renders a loading indicator.
	 * - If there are zero plugins, it renders an empty content.
	 * - Otherwise, it renders a TabPanel. Each tab is a subcategory displaying the plugins.
	 */
	const renderCardContent = () => {
		if ( isLoading ) {
			return (
				<CardBody>
					<Spinner />
				</CardBody>
			);
		}

		if ( plugins.length === 0 ) {
			return (
				<CardBody className="woocommerce-marketing-discover-tools-card-body-empty-content">
					<Icon icon={ trendingUp } size={ 32 } />
					<div>
						{ __(
							'Continue to reach the right audiences and promote your products in ways that matter to them with our range of marketing solutions.',
							'woocommerce'
						) }
					</div>
					<Button
						variant="tertiary"
						href="https://woocommerce.com/product-category/woocommerce-extensions/marketing-extensions/"
						onClick={ () => {
							recordEvent( 'marketing_explore_more_extensions' );
						} }
					>
						{ __(
							'Explore more marketing extensions',
							'woocommerce'
						) }
					</Button>
				</CardBody>
			);
		}

		return (
			<TabPanel tabs={ getTabs( plugins ) }>
				{ ( tab ) => {
					const subcategoryPlugins = plugins.filter( ( plugin ) =>
						plugin.subcategories.some(
							( subcategory ) => subcategory.slug === tab.name
						)
					);

					return (
						<>
							{ subcategoryPlugins.map( ( plugin ) => {
								return (
									<Fragment key={ plugin.product }>
										<CardDivider />
										<PluginCardBody
											icon={
												<img
													src={ plugin.icon }
													alt={ plugin.title }
												/>
											}
											name={ plugin.title }
											pills={ plugin.tags.map(
												( tag ) => (
													<Pill key={ tag.slug }>
														{ tag.name }
													</Pill>
												)
											) }
											description={ plugin.description }
											button={
												plugin.direct_install ? (
													<Button
														variant="secondary"
														isBusy={
															currentPlugin ===
															plugin.product
														}
														disabled={
															!! currentPlugin
														}
														onClick={ () => {
															installAndActivate(
																plugin
															);
														} }
													>
														{ __(
															'Install plugin',
															'woocommerce'
														) }
													</Button>
												) : (
													<Button
														variant="secondary"
														href={ getInAppPurchaseUrl(
															plugin.url
														) }
														disabled={
															!! currentPlugin
														}
														onClick={ () => {
															recordEvent(
																'marketing_recommended_extension',
																{
																	name: plugin.title,
																}
															);
														} }
													>
														{ __(
															'View details',
															'woocommerce'
														) }
													</Button>
												)
											}
										/>
									</Fragment>
								);
							} ) }
						</>
					);
				} }
			</TabPanel>
		);
	};

	return (
		<CollapsibleCard
			className="woocommerce-marketing-discover-tools-card"
			header={ __( 'Discover more marketing tools', 'woocommerce' ) }
		>
			{ renderCardContent() }
		</CollapsibleCard>
	);
};
