/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Card, CardHeader, Spinner } from '@wordpress/components';
import { PLUGINS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './Marketing.scss';
import { createNoticesFromResponse } from '~/lib/notices';
import { PluginList, PluginListProps } from './PluginList';
import { PluginProps } from './Plugin';

type ExtensionList = {
	key: string;
	title: string;
	plugins: Extension[];
};

type Extension = {
	description: string;
	key: string;
	image_url: string;
	manage_url: string;
	name: string;
	slug: string;
};

const ALLOWED_PLUGIN_LISTS = [ 'reach', 'grow' ];

export const Marketing: React.FC = () => {
	const [ fetchedExtensions, setFetchedExtensions ] = useState<
		ExtensionList[]
	>( [] );
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const [ isFetching, setIsFetching ] = useState( true );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { activePlugins, installedPlugins } = useSelect(
		( select: WCDataSelector ) => {
			const { getActivePlugins, getInstalledPlugins } = select(
				PLUGINS_STORE_NAME
			);

			return {
				activePlugins: getActivePlugins(),
				installedPlugins: getInstalledPlugins(),
			};
		}
	);

	const transformExtensionToPlugin = (
		extension: Extension
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

	useEffect( () => {
		apiFetch( {
			path: '/wc-admin/onboarding/free-extensions',
		} )
			.then( ( results: ExtensionList[] ) => {
				if ( results?.length ) {
					setFetchedExtensions( results );
				}
				setIsFetching( false );
			} )
			.catch( () => {
				// @todo Handle error checking.
				setIsFetching( false );
			} );
	}, [] );

	const [ installedExtensions, pluginLists ] = useMemo( () => {
		const installed: PluginProps[] = [];
		const lists: PluginListProps[] = [];
		fetchedExtensions.forEach( ( list ) => {
			if ( ! ALLOWED_PLUGIN_LISTS.includes( list.key ) ) {
				return;
			}

			const listPlugins: PluginProps[] = [];
			list.plugins.forEach( ( extension ) => {
				const plugin = transformExtensionToPlugin( extension );
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
	}, [ installedPlugins, activePlugins, fetchedExtensions ] );

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
				createNoticesFromResponse( response );
				setCurrentPlugin( null );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setCurrentPlugin( null );
			} );
	};

	if ( isFetching ) {
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
