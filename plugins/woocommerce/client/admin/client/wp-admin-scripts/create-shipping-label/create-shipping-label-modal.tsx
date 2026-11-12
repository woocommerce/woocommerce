/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Modal, Notice } from '@wordpress/components';
import { pluginsStore } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	EXTENSION_PLUGIN_SLUGS,
	getShippingExtensionsForCountry,
} from '~/shipping/shipping-extensions';
import { ShippingExtensionItem } from '~/shipping/shipping-extension-item';
import { ShippingRecommendationsMarketplaceLink } from '~/shipping/shipping-recommendations-marketplace-link';
import { useInstallPlugin } from '~/shipping/use-install-plugin';
import '../../shipping/shipping-recommendations.scss';
import './style.scss';

type CreateShippingLabelModalProps = {
	storeCountry: string;
	onRequestClose: () => void;
};

export const CreateShippingLabelModal = ( {
	storeCountry,
	onRequestClose,
}: CreateShippingLabelModalProps ) => {
	const [ pluginsBeingSetup, handleInstall, handleActivate ] =
		useInstallPlugin();
	const [ hasSetupError, setHasSetupError ] = useState( false );

	const extensions = getShippingExtensionsForCountry( storeCountry );
	const pluginSlugs = extensions.map(
		( ext ) => EXTENSION_PLUGIN_SLUGS[ ext ]
	);

	const { installedPlugins, activePlugins } = useSelect( ( select ) => {
		const { getInstalledPlugins, getActivePlugins } =
			select( pluginsStore );

		return {
			installedPlugins: getInstalledPlugins(),
			activePlugins: getActivePlugins(),
		};
	}, [] );

	const activePluginsOnOpen = useRef( activePlugins );
	const hasActivatedExtension = pluginSlugs.some(
		( slug ) =>
			activePlugins.includes( slug ) &&
			! activePluginsOnOpen.current.includes( slug )
	);

	const tracking = {
		context: 'order' as const,
		country: storeCountry,
		plugins: pluginSlugs.join( ',' ),
	};

	useEffect( () => {
		recordEvent( 'shipping_partner_impression', tracking );
		// Only record the impression once, when the modal opens.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const withErrorNotice =
		( handler: ( slugs: string[] ) => PromiseLike< void > ) =>
		( slugs: string[] ) => {
			setHasSetupError( false );
			return handler( slugs ).then( undefined, ( error ) => {
				setHasSetupError( true );
				return Promise.reject( error );
			} );
		};

	return (
		<Modal
			className="woocommerce-create-shipping-label-modal"
			title={ __( 'Create shipping labels', 'woocommerce' ) }
			onRequestClose={ onRequestClose }
		>
			{ extensions.length > 0 ? (
				<>
					<p className="woocommerce-create-shipping-label-modal__intro">
						{ __(
							'To create shipping labels, install one of these extensions recommended for your store’s location.',
							'woocommerce'
						) }
					</p>
					<div className="woocommerce-recommended-shipping-extensions">
						<ul className="woocommerce-list">
							{ extensions.map( ( ext ) => (
								<li
									key={ ext }
									className="woocommerce-list__item"
								>
									<ShippingExtensionItem
										extension={ ext }
										isPluginInstalled={ installedPlugins.includes(
											EXTENSION_PLUGIN_SLUGS[ ext ]
										) }
										isPluginActive={ activePlugins.includes(
											EXTENSION_PLUGIN_SLUGS[ ext ]
										) }
										pluginsBeingSetup={ pluginsBeingSetup }
										onInstallClick={ withErrorNotice(
											handleInstall
										) }
										onActivateClick={ withErrorNotice(
											handleActivate
										) }
										tracking={ tracking }
									/>
								</li>
							) ) }
						</ul>
					</div>
				</>
			) : (
				<p className="woocommerce-create-shipping-label-modal__intro">
					{ __(
						'We don’t have a recommended shipping extension for your store’s location yet.',
						'woocommerce'
					) }
				</p>
			) }

			{ hasSetupError && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'We couldn’t set up this extension. Try again, or install it from the Plugins page.',
						'woocommerce'
					) }
				</Notice>
			) }

			{ hasActivatedExtension && (
				<Notice
					status="success"
					isDismissible={ false }
					actions={ [
						{
							label: __( 'Reload page', 'woocommerce' ),
							onClick: () => window.location.reload(),
							variant: 'primary',
						},
					] }
				>
					{ __(
						'Your shipping extension is active. Save any order changes, then reload the page to create labels.',
						'woocommerce'
					) }
				</Notice>
			) }

			<ShippingRecommendationsMarketplaceLink
				textProps={ {
					as: 'p',
					className:
						'woocommerce-create-shipping-label-modal__marketplace-link',
				} }
				eventName="order_create_shipping_label_visit_marketplace_click"
			/>
		</Modal>
	);
};
