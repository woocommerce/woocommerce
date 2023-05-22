/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { Extension, ExtensionList } from '@woocommerce/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import { PluginsInstallationRequestedEvent, PluginsPageSkippedEvent } from '..';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
import { PluginCard } from '../components/plugin-card/plugin-card';
import { getAdminSetting } from '~/utils/admin-settings';

const locale = getAdminSetting( 'locale' ).siteLocale.replace( '_', '-' );
const joinWithAnd = ( items: string[] ) => {
	return new Intl.ListFormat( locale, {
		style: 'long',
		type: 'conjunction',
	} ).format( items );
};

export const Plugins = ( {
	context,
	navigationProgress,
	sendEvent,
}: {
	context: CoreProfilerStateMachineContext;
	sendEvent: (
		payload: PluginsInstallationRequestedEvent | PluginsPageSkippedEvent
	) => void;
	navigationProgress: number;
} ) => {
	const [ extensionsSelected, setExtensionsSelected ] = useState<
		ExtensionList[ 'plugins' ]
	>( context.pluginsAvailable.filter( ( plugin ) => ! plugin.is_installed ) );

	const setExtensionSelected = ( plugin: Extension ) => {
		setExtensionsSelected(
			extensionsSelected.some( ( item ) => item.key === plugin.key )
				? extensionsSelected.filter(
						( item ) => item.key !== plugin.key
				  )
				: [ ...extensionsSelected, plugin ]
		);
	};

	const skipExtensions = () => {
		return sendEvent( {
			type: 'PLUGINS_PAGE_SKIPPED',
		} );
	};
	const submitExtensionsSelection = () => {
		return sendEvent( {
			type: 'PLUGINS_INSTALLATION_REQUESTED',
			payload: {
				plugins: extensionsSelected.map( ( plugin ) =>
					plugin.key.replace( ':alt', '' )
				),
			},
		} );
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
				),
				components: {
					link: (
						<Button isLink onClick={ submitExtensionsSelection } />
					),
				},
		  } )
		: null;

	const pluginsWithAgreement = extensionsSelected.filter(
		( plugin ) =>
			plugin.key === 'jetpack' || plugin.key === 'woocommerce-services'
	);

	return (
		<div
			className="woocommerce-profiler-extensions"
			data-testid="core-profiler-extensions"
		>
			<Navigation
				percentage={ navigationProgress }
				onSkip={ skipExtensions }
			/>
			<div className="woocommerce-profiler-page__content woocommerce-profiler-extensions__content">
				<Heading
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
				<div className="woocommerce-profiler-extensions__list">
					{ context.pluginsAvailable.map( ( plugin ) => {
						return (
							<PluginCard
								key={ `checkbox-control-${ plugin.key }` }
								installed={ plugin.is_installed }
								onChange={ () => {
									setExtensionSelected( plugin );
								} }
								checked={
									extensionsSelected.filter(
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
								title={ plugin.name }
								description={ plugin.description }
							/>
						);
					} ) }
				</div>
				<Button
					className="woocommerce-profiler-extensions-continue-button"
					variant="primary"
					onClick={
						extensionsSelected.length
							? submitExtensionsSelection
							: skipExtensions
					}
				>
					{ __( 'Continue', 'woocommerce' ) }
				</Button>
				{ pluginsWithAgreement.length > 0 && (
					<p className="woocommerce-profiler-extensions-jetpack-agreement">
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
										( plugin ) => plugin.key
									)
								)
							),
							components: {
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
	);
};
