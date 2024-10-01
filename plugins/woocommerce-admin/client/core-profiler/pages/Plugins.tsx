/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { Extension, ExtensionList } from '@woocommerce/data';
import { useState } from 'react';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import {
	PluginsLearnMoreLinkClickedEvent,
	PluginsInstallationRequestedEvent,
	PluginsPageSkippedEvent,
} from '../events';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
import { PluginCard } from '../components/plugin-card/plugin-card';
import { getAdminSetting } from '~/utils/admin-settings';

const locale = ( getAdminSetting( 'locale' )?.siteLocale || 'en_US' ).replace(
	'_',
	'-'
);
const joinWithAnd = ( items: string[] ) => {
	return new Intl.ListFormat( locale, {
		style: 'long',
		type: 'conjunction',
	} ).formatToParts( items );
};

export const Plugins = ( {
	context,
	navigationProgress,
	sendEvent,
}: {
	context: Pick<
		CoreProfilerStateMachineContext,
		'pluginsAvailable' | 'pluginsInstallationErrors' | 'pluginsSelected'
	>;
	sendEvent: (
		payload:
			| PluginsInstallationRequestedEvent
			| PluginsPageSkippedEvent
			| PluginsLearnMoreLinkClickedEvent
	) => void;
	navigationProgress: number;
} ) => {
	const [ selectedPlugins, setSelectedPlugins ] = useState<
		ExtensionList[ 'plugins' ]
	>(
		context.pluginsAvailable.filter(
			context.pluginsInstallationErrors.length
				? ( plugin ) => context.pluginsSelected.includes( plugin.key )
				: ( plugin ) => ! plugin.is_activated
		)
	);

	const setSelectedPlugin = ( plugin: Extension ) => {
		setSelectedPlugins(
			selectedPlugins.some( ( item ) => item.key === plugin.key )
				? selectedPlugins.filter( ( item ) => item.key !== plugin.key )
				: [ ...selectedPlugins, plugin ]
		);
	};

	const skipPluginsPage = () => {
		return sendEvent( {
			type: 'PLUGINS_PAGE_SKIPPED',
		} );
	};

	const submitInstallationRequest = () => {
		const selectedPluginSlugs = selectedPlugins.map( ( plugin ) =>
			plugin.key.replace( ':alt', '' )
		);

		const pluginsShown: string[] = [];
		const pluginsUnselected: string[] = [];

		context.pluginsAvailable.forEach( ( plugin ) => {
			const pluginSlug = plugin.key.replace( ':alt', '' );
			pluginsShown.push( pluginSlug );

			if (
				! plugin.is_activated &&
				! selectedPluginSlugs.includes( pluginSlug )
			) {
				pluginsUnselected.push( pluginSlug );
			}
		} );

		return sendEvent( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				pluginsShown,
				pluginsSelected: selectedPluginSlugs,
				pluginsUnselected,
			},
		} );
	};

	const composeListFormatParts = ( part: {
		type: string;
		value: string;
	} ) => {
		if ( part.type === 'element' ) {
			return '{{span}}' + part.value + '{{/span}}';
		}
		return part.value;
	};
	const errorMessage = context.pluginsInstallationErrors.length
		? interpolateComponents( {
				mixedString: sprintf(
					// Translators: %s is a list of plugins that does not need to be translated
					__(
						'Oops! We encountered a problem while installing %s. {{link}}Please try again{{/link}}.',
						'woocommerce'
					),
					joinWithAnd(
						context.pluginsInstallationErrors.map(
							( error ) => error.plugin
						)
					)
						.map( composeListFormatParts )
						.join( '' )
				),
				components: {
					span: <span />,
					link: (
						<Button isLink onClick={ submitInstallationRequest } />
					),
				},
		  } )
		: null;

	const pluginsWithAgreement = selectedPlugins.filter( ( plugin ) =>
		[
			'jetpack',
			'woocommerce-services:shipping',
			'woocommerce-services:tax',
		].includes( plugin.key )
	);

	const pluginsCardRowCount = Math.ceil(
		context.pluginsAvailable.length / 2
	);

	return (
		<div
			className="woocommerce-profiler-plugins"
			data-testid="core-profiler-plugins"
		>
			<Navigation
				percentage={ navigationProgress }
				onSkip={ skipPluginsPage }
			/>
			<div className="woocommerce-profiler-page__content woocommerce-profiler-plugins__content">
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __(
						'Get a boost with our free features',
						'woocommerce'
					) }
					subTitle={ __(
						'Enhance your store by installing these free business features. No commitment required – you can remove them at any time.',
						'woocommerce'
					) }
				/>
				{ errorMessage && (
					<p className="plugin-error">{ errorMessage }</p>
				) }
				<div
					className={ clsx(
						'woocommerce-profiler-plugins__list',
						`rows-${ pluginsCardRowCount }`
					) }
				>
					{ context.pluginsAvailable.map( ( plugin ) => {
						const learnMoreLink = plugin.learn_more_link ? (
							<Link
								onClick={ ( e ) => {
									sendEvent( {
										type: 'PLUGINS_LEARN_MORE_LINK_CLICKED',
										payload: {
											plugin: plugin.key,
											learnMoreLink:
												plugin.learn_more_link ?? '',
										},
									} );
									e.stopPropagation();
								} }
								href={ plugin.learn_more_link }
								target="_blank"
								type="external"
							>
								{ __( 'Learn More', 'woocommerce' ) }
							</Link>
						) : null;
						return (
							<PluginCard
								key={ `checkbox-control-${ plugin.key }` }
								installed={ plugin.is_activated }
								onChange={ () => {
									setSelectedPlugin( plugin );
								} }
								checked={
									selectedPlugins.filter(
										( item ) => item.key === plugin.key
									).length > 0
								}
								icon={
									plugin.image_url ? (
										<img
											src={ plugin.image_url }
											alt={ plugin.key }
										/>
									) : null
								}
								title={ plugin.label }
								description={ plugin.description }
								learnMoreLink={ learnMoreLink }
							/>
						);
					} ) }
				</div>
				<div
					className={ clsx(
						'woocommerce-profiler-plugins__footer',
						`rows-${ pluginsCardRowCount }`
					) }
				>
					<div className="woocommerce-profiler-plugins-continue-button-container">
						<Button
							className="woocommerce-profiler-plugins-continue-button"
							variant="primary"
							onClick={
								selectedPlugins.length
									? submitInstallationRequest
									: skipPluginsPage
							}
						>
							{ __( 'Continue', 'woocommerce' ) }
						</Button>
					</div>
					{ pluginsWithAgreement.length > 0 && (
						<p className="woocommerce-profiler-plugins-jetpack-agreement">
							{ interpolateComponents( {
								mixedString: sprintf(
									/* translators: %s: a list of plugins, e.g. Jetpack */
									_n(
										'By installing %s plugin for free you agree to our {{link}}Terms of Service{{/link}}.',
										'By installing %s plugins for free you agree to our {{link}}Terms of Service{{/link}}.',
										pluginsWithAgreement.length,
										'woocommerce'
									),
									joinWithAnd(
										pluginsWithAgreement.map(
											( plugin ) => plugin.name
										)
									)
										.map( composeListFormatParts )
										.join( '' )
								),
								components: {
									span: <span />,
									link: (
										<Link
											href="https://wordpress.com/tos/"
											target="_blank"
											type="external"
										/>
									),
								},
							} ) }
						</p>
					) }
				</div>
			</div>
		</div>
	);
};
