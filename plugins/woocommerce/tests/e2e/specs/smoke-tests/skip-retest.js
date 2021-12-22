const { IS_RETEST_MODE, utils } = require( '@woocommerce/e2e-utils' );

const skipOnRetest = ( testCallback, description ) => {
	if ( IS_RETEST_MODE ) {
		describe.skip( description, () => {
			it( description, async () => {
				expect( 1 ).toBeTruthy();
			} );
		} );
	} else {
		testCallback();
	}
};

module.exports = skipOnRetest;
