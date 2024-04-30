const { test: baseTest } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	getCanvas,
	publishPage,
} = require( '../../utils/editor' );

baseTest.describe( 'Can create a new page', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
	} );

	// eslint-disable-next-line playwright/expect-expect
	test( 'can create new page', async ( { page, testPage } ) => {
		await goToPageEditor( { page } );

		await fillPageTitle( page, testPage.title );

		const canvas = await getCanvas( page );

		await canvas
			.getByRole( 'button', { name: 'Add default block' } )
			.click();

		await canvas
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( 'Test Page' );

		await publishPage( page, testPage.title );
	} );
} );
