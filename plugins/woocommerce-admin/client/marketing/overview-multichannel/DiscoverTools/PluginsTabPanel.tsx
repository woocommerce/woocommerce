/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { Pill } from '@woocommerce/components';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { flatMapDeep, uniqBy } from 'lodash';

/**
 * Internal dependencies
 */
import { CardDivider, PluginCardBody } from '~/marketing/components';
import { useInstalledPlugins } from '~/marketing/hooks';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import { createNoticesFromResponse } from '~/lib/notices';
import { Plugin } from './types';
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

type PluginsTabPanelType = {
	isLoading: boolean;
	plugins: Plugin[];
	onInstallAndActivate: ( pluginSlug: string ) => void;
};

/**
 * A TabPanel where each tab is a plugin subcategory.
 */
export const PluginsTabPanel = ( {
	isLoading,
	plugins,
	onInstallAndActivate,
}: PluginsTabPanelType ) => {
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

			onInstallAndActivate( plugin.product );
			loadInstalledPluginsAfterActivation( plugin.product );
			createNoticesFromResponse( response );
		} catch ( error ) {
			createNoticesFromResponse( error );
		}

		setCurrentPlugin( null );
	};

	return (
		<TabPanel tabs={ getTabs( plugins ) }>
			{ ( tab ) => {
				const subcategoryPlugins = plugins.filter( ( plugin ) =>
					plugin.subcategories.some(
						( subcategory ) => subcategory.slug === tab.name
					)
				);

				const renderButton = ( plugin: Plugin ) => {
					const buttonDisabled = !! currentPlugin || isLoading;

					if ( plugin.direct_install ) {
						return (
							<Button
								variant="secondary"
								isBusy={ currentPlugin === plugin.product }
								disabled={ buttonDisabled }
								onClick={ () => {
									installAndActivate( plugin );
								} }
							>
								{ __( 'Install plugin', 'woocommerce' ) }
							</Button>
						);
					}

					return (
						<Button
							variant="secondary"
							href={ getInAppPurchaseUrl( plugin.url ) }
							disabled={ buttonDisabled }
							onClick={ () => {
								recordEvent(
									'marketing_recommended_extension',
									{
										name: plugin.title,
									}
								);
							} }
						>
							{ __( 'View details', 'woocommerce' ) }
						</Button>
					);
				};

				return (
					<>
						{ subcategoryPlugins.map( ( plugin ) => (
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
									pills={ plugin.tags.map( ( tag ) => (
										<Pill key={ tag.slug }>
											{ tag.name }
										</Pill>
									) ) }
									description={ plugin.description }
									button={ renderButton( plugin ) }
								/>
							</Fragment>
						) ) }
					</>
				);
			} }
		</TabPanel>
	);
};
