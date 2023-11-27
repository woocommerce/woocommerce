/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __, sprintf } from '@wordpress/i18n';
import { box, plus, settings } from '@wordpress/icons';
import { useMemo } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { addQueryArgs } from '@wordpress/url';
import { queueRecordEvent } from '@woocommerce/tracks';
import { store as commandsStore } from '@wordpress/commands';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { registerCommandWithTracking } from './register-command-with-tracking';

const registerWooCommerceSettingsCommand = ( { label, tab } ) => {
	registerCommandWithTracking( {
		name: `woocommerce/settings-${ tab }`,
		label: sprintf(
			// translators: %s is the title of the Settings Tab. This is used as a command in the Command Palette.
			__( 'WooCommerce Settings: %s', 'woocommerce' ),
			label
		),
		icon: settings,
		callback: () => {
			document.location = addQueryArgs( 'admin.php', {
				page: 'wc-settings',
				tab,
			} );
		},
	} );
};

// Code adapted from the equivalent in Gutenberg:
// https://github.com/WordPress/gutenberg/blob/8863b49b7e686f555e8b8adf70cc588c4feebfbf/packages/core-commands/src/site-editor-navigation-commands.js#L36C7-L36C44
function useProductCommandLoader( { search } ) {
	const postType = 'product';
	const { records, isLoading } = useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );
			const query = {
				search: !! search ? search : undefined,
				per_page: 10,
				orderby: search ? 'relevance' : 'date',
				status: [ 'publish', 'future', 'draft', 'pending', 'private' ],
			};
			return {
				records: getEntityRecords( 'postType', postType, query ),
				isLoading: ! select( coreStore ).hasFinishedResolution(
					'getEntityRecords',
					[ 'postType', postType, query ]
				),
			};
		},
		[ search ]
	);

	const commands = useMemo( () => {
		return ( records ?? [] ).map( ( record ) => {
			const command = {
				name: postType + '-' + record.id,
				searchLabel: record.title?.rendered + ' ' + record.id,
				label: record.title?.rendered
					? decodeEntities( record.title?.rendered )
					: __( '(no title)', 'woocommerce' ),
				icon: box,
			};
			return {
				...command,
				callback: ( { close } ) => {
					queueRecordEvent( 'woocommerce_command_palette_submit', {
						name: 'woocommerce/product',
					} );

					const args = {
						post: record.id,
						action: 'edit',
					};
					const targetUrl = addQueryArgs( 'post.php', args );
					document.location = targetUrl;
					close();
				},
			};
		} );
	}, [ records ] );

	return {
		commands,
		isLoading,
	};
}

const WooCommerceCommands = () => {
	registerCommandWithTracking( {
		name: 'woocommerce/add-new-product',
		label: __( 'Add new product', 'woocommerce' ),
		icon: plus,
		callback: () => {
			document.location = addQueryArgs( 'post-new.php', {
				post_type: 'product',
			} );
		},
	} );
	registerCommandWithTracking( {
		name: 'woocommerce/add-new-order',
		label: __( 'Add new order', 'woocommerce' ),
		icon: plus,
		callback: () => {
			document.location = addQueryArgs( 'admin.php', {
				page: 'wc-orders',
				action: 'new',
			} );
		},
	} );
	registerCommandWithTracking( {
		name: 'woocommerce/view-products',
		label: __( 'Products', 'woocommerce' ),
		icon: box,
		callback: () => {
			document.location = addQueryArgs( 'edit.php', {
				post_type: 'product',
			} );
		},
	} );
	registerCommandWithTracking( {
		name: 'woocommerce/view-orders',
		label: __( 'Orders', 'woocommerce' ),
		icon: box,
		callback: () => {
			document.location = addQueryArgs( 'admin.php', {
				page: 'wc-orders',
			} );
		},
	} );
	dispatch( commandsStore ).registerCommandLoader( {
		name: 'woocommerce/product',
		hook: useProductCommandLoader,
	} );

	if (
		window.hasOwnProperty( 'wcCommandPaletteSettings' ) &&
		window.wcCommandPaletteSettings.hasOwnProperty( 'settingsTabs' ) &&
		Array.isArray( window.wcCommandPaletteSettings.settingsTabs )
	) {
		const settingsCommands = window.wcCommandPaletteSettings.settingsTabs;

		settingsCommands.forEach( ( settingsCommand ) => {
			registerWooCommerceSettingsCommand( {
				label: settingsCommand.label,
				tab: settingsCommand.key,
			} );
		} );
	}

	return null;
};

registerPlugin( 'woocommerce-commands-registration', {
	render: WooCommerceCommands,
} );
