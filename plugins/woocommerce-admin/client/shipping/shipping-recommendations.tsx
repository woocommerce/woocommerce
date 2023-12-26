/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, Children } from '@wordpress/element';
import { Text } from '@woocommerce/experimental';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import ExternalIcon from 'gridicons/dist/external';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore VisuallyHidden is present, it's just not typed
// eslint-disable-next-line @woocommerce/dependency-group
import { CardFooter, Button, VisuallyHidden } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../lib/notices';
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
import WooCommerceServicesItem from './woocommerce-services-item';
import './shipping-recommendations.scss';

const useInstallPlugin = () => {
	const [ pluginsBeingSetup, setPluginsBeingSetup ] = useState<
		Array< string >
	>( [] );

	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );

	const handleSetup = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return installAndActivatePlugins( slugs )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	return [ pluginsBeingSetup, handleSetup ] as const;
};

export const ShippingRecommendationsList: React.FC = ( { children } ) => (
	<DismissableList
		className="woocommerce-recommended-shipping-extensions"
		dismissOptionName="woocommerce_settings_shipping_recommendations_hidden"
	>
		<DismissableListHeading>
			<Text variant="title.small" as="p" size="20" lineHeight="28px">
				{ __( 'Recommended shipping solutions', 'woocommerce' ) }
			</Text>
			<Text
				className="woocommerce-recommended-shipping__header-heading"
				variant="caption"
				as="p"
				size="12"
				lineHeight="16px"
			>
				{ __(
					'We recommend adding one of the following shipping extensions to your store. The extension will be installed and activated for you when you click "Get started".',
					'woocommerce'
				) }
			</Text>
		</DismissableListHeading>
		<ul className="woocommerce-list">
			{ Children.map( children, ( item ) => (
				<li className="woocommerce-list__item">{ item }</li>
			) ) }
		</ul>
		<CardFooter>
			<Button
				className="woocommerce-recommended-shipping-extensions__more_options_cta"
				href="https://woo.com/product-category/woocommerce-extensions/shipping-methods/?utm_source=shipping_recommendations"
				target="_blank"
				isTertiary
			>
				{ __( 'See more options', 'woocommerce' ) }
				<VisuallyHidden>
					{ __( '(opens in a new tab)', 'woocommerce' ) }
				</VisuallyHidden>
				<ExternalIcon size={ 18 } />
			</Button>
		</CardFooter>
	</DismissableList>
);

const ShippingRecommendations: React.FC = () => {
	const [ pluginsBeingSetup, setupPlugin ] = useInstallPlugin();

	const activePlugins = useSelect( ( select ) =>
		select( PLUGINS_STORE_NAME ).getActivePlugins()
	);

	if ( activePlugins.includes( 'woocommerce-services' ) ) {
		return null;
	}

	return (
		<ShippingRecommendationsList>
			<WooCommerceServicesItem
				pluginsBeingSetup={ pluginsBeingSetup }
				onSetupClick={ setupPlugin }
			/>
		</ShippingRecommendationsList>
	);
};

export default ShippingRecommendations;
