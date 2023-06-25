/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, Spinner } from '@wordpress/components';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	Extension,
	ExtensionList,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useMemo, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './Marketing.scss';
import { createNoticesFromResponse } from '~/lib/notices';
import { PluginList, PluginListProps } from './PluginList';
import { PluginProps } from './Plugin';
import { getPluginSlug } from '../../../utils';

// We display the list of plugins ordered by this list.
const ALLOWED_PLUGIN_LISTS = [ 'task-list/grow', 'task-list/reach' ];

export const transformExtensionToPlugin = (
	extension: Extension,
	activePlugins: string[],
	installedPlugins: string[]
): PluginProps => {
	const { description, image_url, is_built_by_wc, key, manage_url, name } =
		extension;
	const slug = getPluginSlug( key );
	return {
		description,
		slug,
		imageUrl: image_url,
		isActive: activePlugins.includes( slug ),
		isInstalled: installedPlugins.includes( slug ),
		isBuiltByWC: is_built_by_wc,
		manageUrl: manage_url,
		name,
	};
};

export const getMarketingExtensionLists = (
	freeExtensions: ExtensionList[],
	activePlugins: string[],
	installedPlugins: string[]
): [ PluginProps[], PluginListProps[] ] => {
	const installed: PluginProps[] = [];
	const lists: PluginListProps[] = [];

	freeExtensions
		.sort( ( a: ExtensionList, b: ExtensionList ) => {
			return (
				ALLOWED_PLUGIN_LISTS.indexOf( a.key ) -
				ALLOWED_PLUGIN_LISTS.indexOf( b.key )
			);
		} )
		.forEach( ( list ) => {
			if ( ! ALLOWED_PLUGIN_LISTS.includes( list.key ) ) {
				return;
			}

			const listPlugins: PluginProps[] = [];
			list.plugins.forEach( ( extension ) => {
				const plugin = transformExtensionToPlugin(
					extension,
					activePlugins,
					installedPlugins
				);
				if ( plugin.isInstalled ) {
					installed.push( plugin );
					return;
				}
				listPlugins.push( plugin );
			} );

			if ( ! listPlugins.length ) {
				return;
			}

			const transformedList: PluginListProps = {
				...list,
				plugins: listPlugins,
			};
			lists.push( transformedList );
		} );

	return [ installed, lists ];
};

export type MarketingProps = {
	onComplete: ( option?: { redirectPath: string } ) => void;
};

const Marketing: React.FC< MarketingProps > = ( { onComplete } ) => {
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const { actionTask } = useDispatch( ONBOARDING_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { activePlugins, freeExtensions, installedPlugins, isResolving } =
		useSelect( ( select ) => {
			const { getActivePlugins, getInstalledPlugins } =
				select( PLUGINS_STORE_NAME );
			const { getFreeExtensions, hasFinishedResolution } = select(
				ONBOARDING_STORE_NAME
			);

			return {
				activePlugins: getActivePlugins(),
				freeExtensions: getFreeExtensions(),
				installedPlugins: getInstalledPlugins(),
				isResolving: ! hasFinishedResolution( 'getFreeExtensions' ),
			};
		} );

	const [ installedExtensions, pluginLists ] = useMemo(
		() =>
			getMarketingExtensionLists(
				freeExtensions,
				activePlugins,
				installedPlugins
			),
		[ installedPlugins, activePlugins, freeExtensions ]
	);

	const installAndActivate = ( slug: string ) => {
		setCurrentPlugin( slug );
		actionTask( 'marketing' );
		installAndActivatePlugins( [ slug ] )
			.then( ( response ) => {
				recordEvent( 'tasklist_marketing_install', {
					selected_extension: slug,
					installed_extensions: installedExtensions.map(
						( extension ) => extension.slug
					),
					section_order: pluginLists
						.map( ( list ) => list.key )
						.join( ', ' ),
				} );

				createNoticesFromResponse( response );
				setCurrentPlugin( null );
				onComplete( {
					redirectPath: getNewPath( { task: 'marketing' } ),
				} );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setCurrentPlugin( null );
			} );
	};

	const onManage = () => {
		actionTask( 'marketing' );
	};

	if ( isResolving ) {
		return <Spinner />;
	}

	return (
		<div className="woocommerce-task-marketing">
			{ !! installedExtensions.length && (
				<Card className="woocommerce-task-card">
					<CardHeader>
						<Text
							variant="title.small"
							as="h2"
							className="woocommerce-task-card__title"
						>
							{ __(
								'Installed marketing extensions',
								'woocommerce'
							) }
						</Text>
					</CardHeader>
					<PluginList
						currentPlugin={ currentPlugin }
						installAndActivate={ installAndActivate }
						onManage={ onManage }
						plugins={ installedExtensions }
					/>
				</Card>
			) }
			{ !! pluginLists.length && (
				<Card className="woocommerce-task-card">
					<CardHeader>
						<Text
							variant="title.small"
							as="h2"
							className="woocommerce-task-card__title"
						>
							{ __(
								'Recommended marketing extensions',
								'woocommerce'
							) }
						</Text>
						<Text as="span">
							{ __(
								'We recommend adding one of the following marketing tools for your store. The extension will be installed and activated for you when you click "Get started".',
								'woocommerce'
							) }
						</Text>
					</CardHeader>
					{ pluginLists.map( ( list ) => {
						const { key, title, plugins } = list;
						return (
							<PluginList
								currentPlugin={ currentPlugin }
								installAndActivate={ installAndActivate }
								onManage={ onManage }
								key={ key }
								plugins={ plugins }
								title={ title }
							/>
						);
					} ) }
				</Card>
			) }
		</div>
	);
};

registerPlugin( 'wc-admin-onboarding-task-marketing', {
	// @ts-expect-error @types/wordpress__plugins need to be updated
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="marketing">
			{ ( { onComplete }: MarketingProps ) => {
				return <Marketing onComplete={ onComplete } />;
			} }
		</WooOnboardingTask>
	),
} );
