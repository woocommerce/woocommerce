/**
 * External dependencies
 */
import {
	unregisterPlugin,
	registerPlugin,
	getPlugins,
} from '@wordpress/plugins';
import {
	WooHeaderNavigationItem,
	WooHeaderPageTitle,
	WooHeaderItem,
} from '@woocommerce/admin-layout';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BackButton } from '../buttons/back-button';
import './header.scss';

interface HeaderProps {
	/**
	 * The title of the header.
	 */
	title: string;
	/**
	 * The link to go back to. If not provided, the back button will not be shown.
	 */
	backLink?: string;
	/**
	 * The description of the header.
	 */
	description?: string;
	/**
	 * Whether to show the button or not.
	 */
	hasButton?: boolean;
	/**
	 * The label of the button.
	 */
	buttonLabel?: string;
	/**
	 * The callback function when the button is clicked.
	 */
	onButtonClick?: () => void;
}

const HEADER_PLUGIN_NAME = 'settings-payments-offline-header';
const ITEMS_TO_REMOVE = [ 'activity-panel-header-item' ];
let hasRegisteredPlugins = false;

/**
 * Registers the header component as a plugin to customize the header of the settings payments page.
 */
export const Header = ( {
	title,
	backLink,
	description,
	hasButton,
	buttonLabel,
	onButtonClick,
}: HeaderProps ) => {
	if ( ! hasRegisteredPlugins ) {
		/**
		 * Unregister existing header plugins since we don't want to show the default items such as activity panel.
		 */
		const unRegisterHeaderItems = () => {
			const plugins = getPlugins( 'woocommerce-admin' );
			plugins.forEach( ( plugin ) => {
				if ( ITEMS_TO_REMOVE.includes( plugin.name ) ) {
					unregisterPlugin( plugin.name );
				}
			} );
		};

		unRegisterHeaderItems();

		registerPlugin( HEADER_PLUGIN_NAME, {
			render: () => (
				<>
					{ backLink && (
						<WooHeaderNavigationItem>
							<BackButton href={ backLink } title={ title } />
						</WooHeaderNavigationItem>
					) }
					<WooHeaderPageTitle>
						<span className="woocommerce-settings-payments-header__title">
							{ title }
						</span>
					</WooHeaderPageTitle>
					{ hasButton && (
						<WooHeaderItem>
							<Button
								variant="primary"
								onClick={ onButtonClick }
								isBusy={ false }
								disabled={ false }
							>
								{ buttonLabel }
							</Button>
						</WooHeaderItem>
					) }
					{ description && (
						<WooHeaderItem>
							<div className="woocommerce-settings-payments-header__description">
								{ description }
							</div>
						</WooHeaderItem>
					) }
				</>
			),
			scope: 'woocommerce-admin',
		} );

		hasRegisteredPlugins = true;
	}

	return null;
};
