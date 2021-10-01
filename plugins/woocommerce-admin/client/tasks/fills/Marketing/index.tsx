/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, Spinner } from '@wordpress/components';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useMemo, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import './Marketing.scss';
import { createNoticesFromResponse } from '~/lib/notices';
import { PluginList, PluginListProps } from './PluginList';
import { PluginProps } from './Plugin';

const ALLOWED_PLUGIN_LISTS = [ 'reach', 'grow' ];
const EMPTY_ARRAY = [];

export type ExtensionList = {
	key: string;
	title: string;
	plugins: Extension[];
};

export type Extension = {
	description: string;
	key: string;
	image_url: string;
	manage_url: string;
	name: string;
};

export const transformExtensionToPlugin = (
	extension: Extension,
	activePlugins: string[],
	installedPlugins: string[]
): PluginProps => {
	const { description, image_url, key, manage_url, name } = extension;
	const slug = key.split( ':' )[ 0 ];
	return {
		description,
		slug,
		imageUrl: image_url,
		isActive: activePlugins.includes( slug ),
		isInstalled: installedPlugins.includes( slug ),
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
	freeExtensions.forEach( ( list ) => {
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
	onComplete: ( bool: boolean ) => void;
};

const Marketing: React.FC< MarketingProps > = ( { onComplete } ) => {
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		activePlugins,
		freeExtensions,
		installedPlugins,
		isResolving,
		trackedCompletedActions,
	} = useSelect( ( select: WCDataSelector ) => {
		const { getActivePlugins, getInstalledPlugins } = select(
			PLUGINS_STORE_NAME
		);
		const { getFreeExtensions, hasFinishedResolution } = select(
			ONBOARDING_STORE_NAME
		);

		const {
			getOption,
			hasFinishedResolution: optionFinishedResolution,
		} = select( OPTIONS_STORE_NAME );

		const completedActions =
			getOption( 'woocommerce_task_list_tracked_completed_actions' ) ||
			EMPTY_ARRAY;

		return {
			activePlugins: getActivePlugins(),
			freeExtensions: getFreeExtensions(),
			installedPlugins: getInstalledPlugins(),
			isResolving: ! (
				hasFinishedResolution( 'getFreeExtensions' ) &&
				optionFinishedResolution( 'getOption', [
					'woocommerce_task_list_tracked_completed_actions',
				] )
			),
			trackedCompletedActions: completedActions,
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
		installAndActivatePlugins( [ slug ] )
			.then( ( response: { errors: Record< string, string > } ) => {
				recordEvent( 'tasklist_marketing_install', {
					selected_extension: slug,
					installed_extensions: installedExtensions.map(
						( extension ) => extension.slug
					),
				} );

				if ( ! trackedCompletedActions.includes( 'marketing' ) ) {
					updateOptions( {
						woocommerce_task_list_tracked_completed_actions: [
							...trackedCompletedActions,
							'marketing',
						],
					} );
					onComplete();
				}

				createNoticesFromResponse( response );
				setCurrentPlugin( null );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setCurrentPlugin( null );
			} );
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
								'woocommerce-admin'
							) }
						</Text>
					</CardHeader>
					<PluginList
						currentPlugin={ currentPlugin }
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
								'woocommerce-admin'
							) }
						</Text>
						<Text as="span">
							{ __(
								'We recommend adding one of the following marketing tools for your store. The extension will be installed and activated for you when you click "Get started".',
								'woocommerce-admin'
							) }
						</Text>
					</CardHeader>
					{ pluginLists.map( ( list ) => {
						const { key, title, plugins } = list;
						return (
							<PluginList
								currentPlugin={ currentPlugin }
								installAndActivate={ installAndActivate }
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
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="marketing">
			{ ( { onComplete } ) => {
				return <Marketing onComplete={ onComplete } />;
			} }
		</WooOnboardingTask>
	),
} );
