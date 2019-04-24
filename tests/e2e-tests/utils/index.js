const flows = require( './flows' );

/**
 * Click a tab (on post type edit screen).
 * 
 * @param {string} tabName Tab label.
 */
const clickTab = async ( tabName ) => {
    await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

module.exports = {
    clickTab,
    StoreOwnerFlow: flows.StoreOwnerFlow,
};
