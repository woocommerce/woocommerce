/**
 * External dependencies
 */
const config = require( 'config' );

/**
 * Internal dependencies
 */
const { clearAndFillInput, setCheckbox } = require( '../page-utils' );
const {
	WP_ADMIN_ALL_ORDERS_VIEW,
	WP_ADMIN_ALL_PRODUCTS_VIEW,
	WP_ADMIN_DASHBOARD,
	WP_ADMIN_LOGIN,
	WP_ADMIN_NEW_COUPON,
	WP_ADMIN_NEW_ORDER,
	WP_ADMIN_NEW_PRODUCT,
	WP_ADMIN_PERMALINK_SETTINGS,
	WP_ADMIN_PLUGINS,
	WP_ADMIN_SETUP_WIZARD,
	WP_ADMIN_WC_HOME,
	WP_ADMIN_WC_SETTINGS,
	WP_ADMIN_WC_EXTENSIONS,
	WP_ADMIN_WC_HELPER,
	WP_ADMIN_NEW_SHIPPING_ZONE,
	WP_ADMIN_ANALYTICS_PAGES,
	WP_ADMIN_ALL_USERS_VIEW,
	WP_ADMIN_IMPORT_PRODUCTS,
	WP_ADMIN_PLUGIN_INSTALL,
	WP_ADMIN_WP_UPDATES,
	IS_RETEST_MODE,
} = require( './constants' );

const { getSlug, waitForTimeout } = require('./utils');

const baseUrl = config.get( 'url' );
const WP_ADMIN_SINGLE_CPT_VIEW = ( postId ) => baseUrl + `wp-admin/post.php?post=${ postId }&action=edit`;

const merchant = {
	login: async () => {
		await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await clearAndFillInput( '#user_login', ' ' );

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		// Log out link in admin bar is not visible so can't be clicked directly.
		const logoutLinks = await page.$$eval(
			'#wp-admin-bar-logout a',
			( am ) => am.filter( ( e ) => e.href ).map( ( e ) => e.href )
		);

		if ( logoutLinks && logoutLinks[0] ) {
			await page.goto(logoutLinks[0], {
				waitUntil: 'networkidle0',
			});
		}
	},

	openAllOrdersView: async () => {
		await page.goto( WP_ADMIN_ALL_ORDERS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

	openAllProductsView: async () => {
		await page.goto( WP_ADMIN_ALL_PRODUCTS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

	openDashboard: async () => {
		await page.goto( WP_ADMIN_DASHBOARD, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewCoupon: async () => {
		await page.goto( WP_ADMIN_NEW_COUPON, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewOrder: async () => {
		await page.goto( WP_ADMIN_NEW_ORDER, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},

	openPermalinkSettings: async () => {
		await page.goto( WP_ADMIN_PERMALINK_SETTINGS, {
			waitUntil: 'networkidle0',
		} );
	},

	openPlugins: async () => {
		await page.goto( WP_ADMIN_PLUGINS, {
			waitUntil: 'networkidle0',
		} );
	},

	openSettings: async ( tab, section = null ) => {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
	},

	openExtensions: async () => {
		await page.goto( WP_ADMIN_WC_EXTENSIONS, {
			waitUntil: 'networkidle0',
		} );
	},

	openHelper: async () => {
		await page.goto( WP_ADMIN_WC_HELPER, {
			waitUntil: 'networkidle0',
		} );
	},

	runSetupWizard: async () => {
			const setupWizard = IS_RETEST_MODE ? WP_ADMIN_SETUP_WIZARD : WP_ADMIN_WC_HOME;
			await page.goto( setupWizard, {
			waitUntil: 'networkidle0',
		} );
	},


	goToOrder: async ( orderId ) => {
		await page.goto( WP_ADMIN_SINGLE_CPT_VIEW( orderId ), {
			waitUntil: 'networkidle0',
		} );
	},

	goToProduct: async ( productId ) => {
		await page.goto( WP_ADMIN_SINGLE_CPT_VIEW( productId ), {
			waitUntil: 'networkidle0',
		} );
	},

	updateOrderStatus: async ( orderId, status ) => {
		await page.goto( WP_ADMIN_SINGLE_CPT_VIEW( orderId ), {
			waitUntil: 'networkidle0',
		} );
		await expect( page ).toSelect( '#order_status', status );
		await waitForTimeout( 2000 );
		await expect( page ).toClick( 'button.save_order' );
		await page.waitForSelector( '#message' );
		await expect( page ).toMatchElement( '#message', { text: 'Order updated.' } );
	},

	verifyOrder: async (orderId, productName, productPrice, quantity, orderTotal, ensureCustomerRegistered = false) => {
		await merchant.goToOrder(orderId);

		// Verify that the order page is indeed of the order that was placed
		// Verify order number
		await expect(page).toMatchElement('.woocommerce-order-data__heading', {text: 'Order #' + orderId + ' details'});

		// Verify product name
		await expect(page).toMatchElement('.wc-order-item-name', {text: productName});

		// Verify product cost
		await expect(page).toMatchElement('.woocommerce-Price-amount.amount', {text: productPrice});

		// Verify product quantity
		await expect(page).toMatchElement('.quantity', {text: quantity.toString()});

		// Verify total order amount without shipping
		await expect(page).toMatchElement('.line_cost', {text: orderTotal});

		if ( ensureCustomerRegistered ) {
			// Verify customer profile link is present to verify order was placed by a registered customer, not a guest
			await expect( page ).toMatchElement( 'label[for="customer_user"] a[href*=user-edit]', { text: 'Profile' } );
		}
	},

	openNewShipping: async () => {
		await page.goto( WP_ADMIN_NEW_SHIPPING_ZONE, {
			waitUntil: 'networkidle0',
		} );
	},

	openEmailLog: async () => {
		await page.goto( `${baseUrl}wp-admin/tools.php?page=wpml_plugin_log`, {
			waitUntil: 'networkidle0',
		} );
	},

	openAnalyticsPage: async ( pageName ) => {
		await page.goto( WP_ADMIN_ANALYTICS_PAGES + pageName, {
			waitUntil: 'networkidle0',
		} );
	},

	openAllUsersView: async () => {
		await page.goto( WP_ADMIN_ALL_USERS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

  	openImportProducts: async () => {
		await page.goto( WP_ADMIN_IMPORT_PRODUCTS , {
			waitUntil: 'networkidle0',
		} );
	},

	/**
	 * Opens the WordPress updates page at Dashboard > Updates.
	 */
	openWordPressUpdatesPage: async () => {
		await page.goto( WP_ADMIN_WP_UPDATES, {
			waitUntil: 'networkidle0',
		} );
	},

	/**
	 * Installs all pending updates on the Dashboard > Updates page, including WordPress, plugins, and themes.
	 */
	installAllUpdates: async () => {
		await merchant.updateWordPress();
		await merchant.updatePlugins();
		await merchant.updateThemes();
	},

	/**
	 * Updates WordPress if there are any updates available.
	 */
	updateWordPress: async () => {
		await merchant.openWordPressUpdatesPage();
		if ( null !== await page.$( 'form[action="update-core.php?action=do-core-upgrade"][name="upgrade"]' ) ) {
			await Promise.all([
				expect( page ).toClick( 'input.button-primary' ),

				// The WordPress update can take some time, so setting a longer timeout here
				page.waitForNavigation( { waitUntil: 'networkidle0', timeout: 1000000 } ),
			]);
		}
	},

	/**
	 * Updates all installed plugins if there are updates available.
	 */
	updatePlugins: async () => {
		await merchant.openWordPressUpdatesPage();
		if ( null !== await page.$( 'form[action="update-core.php?action=do-plugin-upgrade"][name="upgrade-plugins"]' ) ) {
			await setCheckbox( '#plugins-select-all' );
			await Promise.all([
				expect( page ).toClick( '#upgrade-plugins' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);
		}
	},

	/**
	 * Updates all installed themes if there are updates available.
	 */
	updateThemes: async () => {
		await merchant.openWordPressUpdatesPage();
		if ( null !== await page.$( 'form[action="update-core.php?action=do-theme-upgrade"][name="upgrade-themes"]' )) {
			await setCheckbox( '#themes-select-all' );
			await Promise.all([
				expect( page ).toClick( '#upgrade-themes' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);
		}
	},

	/* Uploads and activates a plugin located at the provided file path. This will also deactivate and delete the plugin if it exists.
	*
	* @param {string} pluginFilePath The location of the plugin zip file to upload.
	* @param {string} pluginName The name of the plugin. For example, `WooCommerce`.
	*/
   uploadAndActivatePlugin: async ( pluginFilePath, pluginName ) => {
	   await merchant.openPlugins();

	   // Deactivate and delete the plugin if it exists
	   let pluginSlug = getSlug( pluginName );
	   if ( await page.$( `a#deactivate-${pluginSlug}` ) !== null ) {
		   await merchant.deactivatePlugin( pluginName, true );
	   }

	   // Open the plugin install page
	   await page.goto( WP_ADMIN_PLUGIN_INSTALL, {
		   waitUntil: 'networkidle0',
	   } );

	   // Upload the plugin zip
	   await page.click( 'a.upload-view-toggle' );

	   await expect( page ).toMatchElement(
		   'p.install-help',
		   {
			   text: 'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
		   }
	   );

	   const uploader = await page.$( 'input[type=file]' );

	   await uploader.uploadFile( pluginFilePath );

	   // Manually update the button to `enabled` so we can submit the file
	   await page.evaluate(() => {
		   document.getElementById( 'install-plugin-submit' ).disabled = false;
		});

	   // Click to upload the file
	   await page.click( '#install-plugin-submit' );

	   await page.waitForNavigation( { waitUntil: 'networkidle0' } );

	   // Click to activate the plugin
	   await page.click( '.button-primary' );

	   await page.waitForNavigation( { waitUntil: 'networkidle0' } );
   },

   /**
	* Activate a given plugin by the plugin's name.
	*
	* @param {string} pluginName The name of the plugin to activate. For example, `WooCommerce`.
	*/
   activatePlugin: async ( pluginName ) => {
	   let pluginSlug = getSlug( pluginName );

	   await expect( page ).toClick( `a#activate-${pluginSlug}` );

	   await page.waitForNavigation( { waitUntil: 'networkidle0' } );
   },

   /**
	* Deactivate a plugin by the plugin's name with the option to delete the plugin as well.
	*
	* @param {string} pluginName The name of the plugin to deactivate. For example, `WooCommerce`.
	* @param {Boolean} deletePlugin Pass in `true` to delete the plugin. Defaults to `false`.
	*/
   deactivatePlugin: async ( pluginName, deletePlugin = false ) => {
	   let pluginSlug = getSlug( pluginName );

	   await expect( page ).toClick( `a#deactivate-${pluginSlug}` );

	   await page.waitForNavigation( { waitUntil: 'networkidle0' } );

	   if ( deletePlugin ) {
		   await merchant.deletePlugin( pluginName );
	   }
   },

   /**
	* Delete a plugin by the plugin's name.
	*
	* @param {string} pluginName The name of the plugin to delete. For example, `WooCommerce`.
	*/
   deletePlugin: async ( pluginName ) => {
	   let pluginSlug = getSlug( pluginName );

	   await expect( page ).toClick( `a#delete-${pluginSlug}` );

	   // Wait for Ajax calls to finish
	   await page.waitForResponse( response => response.status() === 200 );
   },

	/**
	 * Runs the database update if needed. For example, after uploading the WooCommerce plugin or updating WooCommerce.
	 */
   runDatabaseUpdate: async () => {
	   if ( await page.$( '.updated.woocommerce-message.wc-connect' ) !== null ) {
		   await expect( page ).toMatchElement( 'a.wc-update-now', { text: 'Update WooCommerce Database' } );
		   await expect( page ).toClick( 'a.wc-update-now' );
		   await page.waitForNavigation( { waitUntil: 'networkidle0' } );
		   await merchant.checkDatabaseUpdateComplete();
		}
   },

	/**
	 * Checks if the database update is complete, if not, refresh the page until it is.
	 */
   checkDatabaseUpdateComplete: async () => {
	   await page.reload( { waitUntil: [ 'networkidle0', 'domcontentloaded'] } );

		const thanksButtonSelector = 'a.components-button.is-primary';

		if ( await page.$( thanksButtonSelector ) !== null ) {
			await expect( page ).toMatchElement( thanksButtonSelector, { text: 'Thanks!' } );
			await expect( page ).toClick( thanksButtonSelector );
		} else {
			await merchant.checkDatabaseUpdateComplete();
		}
   },
};

module.exports = merchant;
