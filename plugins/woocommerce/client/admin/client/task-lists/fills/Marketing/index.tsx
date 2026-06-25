/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, Spinner } from '@wordpress/components';
import {
	onboardingStore,
	pluginsStore,
	Extension,
	ExtensionList,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getNewPath } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './Marketing.scss';
import { createNoticesFromResponse } from '~/lib/notices';
import { PluginList, PluginListProps } from './PluginList';
import { PluginProps } from './Plugin';
import { getPluginSlug } from '../../../utils';
import { TaskPromo } from './TaskPromo';

// We display the list of plugins ordered by this list.
const ALLOWED_PLUGIN_LISTS = [ 'task-list/grow', 'task-list/reach' ];

export const transformExtensionToPlugin = (
	extension: Extension,
	activePlugins: string[],
	installedPlugins: string[]
): PluginProps => {
	const {
		description,
		image_url,
		is_built_by_wc,
		key,
		manage_url,
		name,
		learn_more_link,
		tags,
		install_external,
	} = extension;
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
		tags,
		learnMoreLink: learn_more_link,
		installExternal: install_external,
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

const Marketing = ( { onComplete }: MarketingProps ) => {
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const { actionTask } = useDispatch( onboardingStore );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );
	const { activePlugins, freeExtensions, installedPlugins, isResolving } =
		useSelect( ( select ) => {
			const { getActivePlugins, getInstalledPlugins } =
				select( pluginsStore );
			const { getFreeExtensions, hasFinishedResolution } =
				select( onboardingStore );

			return {
				activePlugins: getActivePlugins(),
				freeExtensions: getFreeExtensions(),
				installedPlugins: getInstalledPlugins(),
				isResolving: ! hasFinishedResolution( 'getFreeExtensions', [] ),
			};
		}, [] );

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
			.then( ( response: unknown ) => {
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

	const trackPromoButtonClick = () => {
		recordEvent( 'task_marketing_marketplace_promo_clicked', {
			task: 'marketing',
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
			{ window?.wcTracks?.isEnabled && (
				<TaskPromo
					title={ __(
						"Boost your store's potential",
						'woocommerce'
					) }
					text={ __(
						'Discover hand-picked extensions to grow your business in' +
							' the WooCommerce marketplace.',
						'woocommerce'
					) }
					buttonHref={ getAdminLink(
						'admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&category=marketing-extensions'
					) }
					buttonText={ __( 'Start growing', 'woocommerce' ) }
					onButtonClick={ trackPromoButtonClick }
				/>
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
