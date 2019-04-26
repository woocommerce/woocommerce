const flows = require( './flows' );

/**
 * Click a tab (on post type edit screen).
 * 
 * @param {string} tabName Tab label.
 */
const clickTab = async ( tabName ) => {
    await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Choose a Select2 option.
 *
 * @param {string} selector 
 * @param {string} optionText 
 */
const selectSelect2Option = async ( selector, optionText ) => {
    await expect( page ).toClick( selector + ' span.select2' );
    const [ option ] = await page.$x(
        `//li[contains(@class, "select2-results__option") and contains(text(), "${ optionText }")]`
    );
    option.click();
};

module.exports = {
    clickTab,
    selectSelect2Option,
    StoreOwnerFlow: flows.StoreOwnerFlow,
};
