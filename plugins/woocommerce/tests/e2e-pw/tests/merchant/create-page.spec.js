const { test: baseTest } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	getCanvas,
	publishPage,
	closeChoosePatternModal,
} = require( '../../utils/editor' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
} );

test.describe(
	'Can create a new page',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		// eslint-disable-next-line playwright/expect-expect
		test( 'can create new page', async ( { page, testPage } ) => {
			await goToPageEditor( { page } );

			await closeChoosePatternModal( { page } );

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
	}
);
