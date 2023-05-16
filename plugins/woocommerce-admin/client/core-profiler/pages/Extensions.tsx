/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import {
	CoreProfilerStateMachineContext,
	ExtensionsSelectionSubmittedEvent,
	ExtentionsInstallationRetryThresholdReachedEvent,
} from '../index';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
import { ExtensionBox } from '../components/extension-box/extension-box';
import IconWoo from '../assets/images/icon-woo.svg';
import IconTikTok from '../assets/images/icon-tiktok.svg';
import { useState } from 'react';
import { getAdminSetting } from '~/utils/admin-settings';
import { join } from 'path';

const locale = getAdminSetting( 'locale' ).siteLocale.replace( '_', '-' );
const joinWithAnd = ( items: string[] ) => {
	return new Intl.ListFormat( locale, {
		style: 'long',
		type: 'conjunction',
	} ).format( items );
};

export const Extensions = ( {
	context,
	navigationProgress,
	sendEvent,
}: {
	context: CoreProfilerStateMachineContext;
	sendEvent: (
		payload:
			| ExtensionsSelectionSubmittedEvent
			| ExtentionsInstallationRetryThresholdReachedEvent
	) => void;
	navigationProgress: number;
} ) => {
	const maxRetryCount = 2;
	const plugins = [
		'woocommerce-payments',
		'jetpack',
		'mailpoet',
		'tiktok-for-woocommerce',
		'google-listings-and-ads',
		'woocommerce-services',
	];
	const [ extensionsSelected, setExtensionsSelected ] = useState< string[] >(
		plugins
	);

	const setExtensionSelected = ( plugin: string ) => {
		if ( extensionsSelected.includes( plugin ) ) {
			setExtensionsSelected(
				extensionsSelected.filter( ( item ) => item !== plugin )
			);
		} else {
			setExtensionsSelected( [ ...extensionsSelected, plugin ] );
		}
	};
	const submitExtensionsSelection = () => {
		sendEvent( {
			type: 'EXTENSIONS_SELECTION_SUBMITTED',
			payload: { extensionsSelected },
		} );
	};
	const IconWooImage = <img src={ IconWoo } alt="icon-woo" />;
	const errorMessage = context.extensionsErrors.length
		? interpolateComponents( {
				mixedString: sprintf(
					// Translators: %s is a list of plugins that does not need to be translated
					__(
						'Oops! We encountered a problem while installing %s. {{link}}Please try again{{/link}}.',
						'woocommerce'
					),
					joinWithAnd(
						context.extensionsErrors.map(
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
		( plugin ) => plugin === 'jetpack' || plugin === 'woocommerce-services'
	);

	return (
		<div
			className="woocommerce-profiler-extensions"
			data-testid="core-profiler-extensions"
		>
			<Navigation percentage={ navigationProgress } />
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
					{ plugins.map( ( plugin ) => {
						return (
							<ExtensionBox
								key={ `checkbox-control-${ plugin }` }
								onChange={ () => {
									setExtensionSelected( plugin );
								} }
								checked={ extensionsSelected.includes(
									plugin
								) }
								icon={ IconWooImage }
								title="Get paid with WooCommerce Payments"
								description="Securely accept payments and manage payment activity – straight from your store's dashboard. Learn more"
							/>
						);
					} ) }
				</div>
				<Button
					className="woocommerce-profiler-extensions-continue-button"
					variant="primary"
					onClick={
						context.extensionsInstallationRetryCount < maxRetryCount
							? () => submitExtensionsSelection()
							: () =>
									sendEvent( {
										type:
											'EXTENSIONS_INSTALLATION_RETRY_THRESHOLD_REACHED',
									} )
					}
				>
					{ errorMessage &&
					context.extensionsInstallationRetryCount < maxRetryCount
						? __( 'Please try again', 'woocommerce' )
						: __( 'Continue', 'woocommerce' ) }
				</Button>
				{ pluginsWithAgreement.length && (
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
								joinWithAnd( pluginsWithAgreement )
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
