/**
 * External dependencies
 */
import { useCommandLoader } from '@wordpress/commands';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { box, plus, settings } from '@wordpress/icons';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { addQueryArgs } from '@wordpress/url';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useCommandWithTracking } from './use-command-with-tracking';

const useWooCommerceSettingsCommand = ( { label, tab } ) => {
	useCommandWithTracking( {
		name: `woocommerce/settings-${ tab }`,
		label,
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
					? record.title?.rendered
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
	useCommandWithTracking( {
		name: 'woocommerce/add-new-product',
		label: __( 'Add new product', 'woocommerce' ),
		icon: plus,
		callback: () => {
			document.location = addQueryArgs( 'post-new.php', {
				post_type: 'product',
			} );
		},
	} );
	useCommandWithTracking( {
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
	useCommandWithTracking( {
		name: 'woocommerce/view-products',
		label: __( 'Products', 'woocommerce' ),
		icon: box,
		callback: () => {
			document.location = addQueryArgs( 'edit.php', {
				post_type: 'product',
			} );
		},
	} );
	useCommandWithTracking( {
		name: 'woocommerce/view-orders',
		label: __( 'Orders', 'woocommerce' ),
		icon: box,
		callback: () => {
			document.location = addQueryArgs( 'admin.php', {
				page: 'wc-orders',
			} );
		},
	} );
	useCommandLoader( {
		name: 'woocommerce/product',
		hook: useProductCommandLoader,
	} );

	useWooCommerceSettingsCommand( {
		label: __( 'Products Settings', 'woocommerce' ),
		tab: 'products',
	} );

	useWooCommerceSettingsCommand( {
		label: __( 'Shipping Settings', 'woocommerce' ),
		tab: 'shipping',
	} );

	useWooCommerceSettingsCommand( {
		label: __( 'Payments Settings', 'woocommerce' ),
		tab: 'checkout',
	} );

	useWooCommerceSettingsCommand( {
		label: __( 'Accounts & Privacy Settings', 'woocommerce' ),
		tab: 'account',
	} );

	useWooCommerceSettingsCommand( {
		label: __( 'WooCommerce Email Settings', 'woocommerce' ),
		tab: 'email',
	} );

	return null;
};

registerPlugin( 'woocommerce-commands-registration', {
	render: WooCommerceCommands,
} );
