/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PluginCardBody } from '~/marketing/components';
import { RecommendedPlugin } from '~/marketing/types';
import { getRecommendationSource } from '~/marketing/utils';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import { createNoticesFromResponse } from '~/lib/notices';
import { useIsPluginInstalledNotActivated } from './useIsPluginInstalledNotActivated';
import './PluginCardBody.scss';

type SmartPluginCardBodyProps = {
	plugin: RecommendedPlugin;
	onInstalledAndActivated?: ( pluginSlug: string ) => void;
};

/**
 * A smart wrapper around PluginCardBody that accepts a `plugin` prop.
 *
 * It knows how to render the action button for the plugin,
 * and has the logic for installing and activating plugin.
 * This allows users to install and activate multiple plugins at the same time.
 */
export const SmartPluginCardBody = ( {
	plugin,
	onInstalledAndActivated = () => {},
}: SmartPluginCardBodyProps ) => {
	const [ currentPlugin, setCurrentPlugin ] = useState< string | null >(
		null
	);
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { isPluginInstalledNotActivated } =
		useIsPluginInstalledNotActivated();

	/**
	 * Install and activate a plugin.
	 *
	 * When the process is successful, `onInstalledAndActivated` will be called.
	 * A success notice will be displayed.
	 *
	 * When the process is not successful, an error notice will be displayed.
	 */
	const installAndActivate = async () => {
		setCurrentPlugin( plugin.product );

		try {
			recordEvent( 'marketing_recommended_extension', {
				name: plugin.title,
				source: getRecommendationSource(),
			} );

			const response = await installAndActivatePlugins( [
				plugin.product,
			] );

			onInstalledAndActivated( plugin.product );
			createNoticesFromResponse( response );
		} catch ( error ) {
			createNoticesFromResponse( error );
		}

		setCurrentPlugin( null );
	};

	const renderButton = () => {
		const buttonDisabled = !! currentPlugin;

		if ( isPluginInstalledNotActivated( plugin.product ) ) {
			return (
				<Button
					variant="secondary"
					isBusy={ currentPlugin === plugin.product }
					disabled={ buttonDisabled }
					onClick={ installAndActivate }
				>
					{ __( 'Activate', 'woocommerce' ) }
				</Button>
			);
		}

		if ( plugin.direct_install ) {
			return (
				<Button
					variant="secondary"
					isBusy={ currentPlugin === plugin.product }
					disabled={ buttonDisabled }
					onClick={ installAndActivate }
				>
					{ __( 'Install extension', 'woocommerce' ) }
				</Button>
			);
		}

		return (
			<Button
				variant="secondary"
				href={ getInAppPurchaseUrl( plugin.url ) }
				disabled={ buttonDisabled }
				onClick={ () => {
					recordEvent( 'marketing_recommended_extension', {
						name: plugin.title,
						source: getRecommendationSource(),
					} );
				} }
			>
				{ __( 'View details', 'woocommerce' ) }
			</Button>
		);
	};

	return (
		<PluginCardBody
			icon={ <img src={ plugin.icon } alt={ plugin.title } /> }
			name={ plugin.title }
			pills={ plugin.tags.map( ( tag ) => (
				<Pill key={ tag.slug }>{ tag.name }</Pill>
			) ) }
			description={ plugin.description }
			button={ renderButton() }
		/>
	);
};
