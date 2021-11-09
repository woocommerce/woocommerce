/**
 * External dependencies
 */
import {
	createNewPost,
	visitAdminPage,
	insertBlock,
	getEditedPostContent,
} from '@wordpress/e2e-test-utils';
import { outputFile } from 'fs-extra';
import { dirname } from 'path';
import kebabCase from 'lodash/kebabCase';

/**
 *
 * @param {string} link the page or post you want to visit.
 *
 * This will visit a GB page or post, and will hide the welcome guide.
 */
async function visitPage( link ) {
	await page.goto( link );
	await page.waitForSelector( '.edit-post-layout' );
	const isWelcomeGuideActive = await page.evaluate( () =>
		wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' )
	);

	if ( isWelcomeGuideActive ) {
		await page.evaluate( () =>
			wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' )
		);
		await page.reload();
		await page.waitForSelector( '.edit-post-layout' );
	}
}

/**
 *
 * @param {string} title the page title, written as `BLOCK_NAME block`
 *
 * This function will attempt to search for a page with the `title`
 * if that block is found, it will open it, if it's not found, it will open
 * a new page, insert the block, save the page content and title as a fixture file.
 * In both cases, this page will end up with a page open with the block inserted.
 */
export async function visitBlockPage( title ) {
	let link = '';
	// Visit Import Products page.
	await visitAdminPage( 'edit.php', 'post_type=page' );
	// If the website has no pages, `#post-search-input` will not render.
	if ( await page.$( '#post-search-input' ) ) {
		// search for the page.
		await page.type( '#post-search-input', title );
		await page.click( '#search-submit' );
		await page.waitForNavigation( { waitUntil: 'domcontentloaded' } );
		const pageLink = await page.$x( `//a[contains(text(), '${ title }')]` );
		if ( pageLink.length > 0 ) {
			// clicking the link directly caused racing issues, so I used goto.
			link = await page.evaluate(
				( a ) => a.getAttribute( 'href' ),
				pageLink[ 0 ]
			);
		}
	}
	if ( link ) {
		await visitPage( link );
	} else {
		await createNewPost( {
			postType: 'page',
			title,
			showWelcomeGuide: false,
		} );
		await insertBlock( title.replace( /block/i, '' ).trim() );
		const pageContent = await getEditedPostContent();
		await outputFile(
			`${ dirname(
				// we want to fetch the path of the test file who triggered this function
				// this could be two levels up, or one level up, depending on how you launch the test.
				module.parent.parent.filename ||
					module.parent.filename ||
					module.filename
			) }/__fixtures__/${ kebabCase(
				title.replace( /block/i, '' ).trim()
			) }.fixture.json`,
			JSON.stringify( {
				title,
				pageContent,
			} )
		);
	}
}

/**
 * This function will attempt to navigate to a page in the WordPress dashboard
 *
 * @param {string} title 	The title of the page/post you want to visit.
 * @param {string} postType The post type of the entity you want to visit.
 * @return {Promise<void>}
 */
export async function visitPostOfType( title, postType ) {
	let link = '';
	// Visit Import Products page.
	await visitAdminPage( 'edit.php', `post_type=${ postType }` );
	// If the website has no pages, `#post-search-input` will not render.
	if ( await page.$( '#post-search-input' ) ) {
		// search for the page.
		await page.type( '#post-search-input', title );
		await page.click( '#search-submit' );
		await page.waitForNavigation( { waitUntil: 'domcontentloaded' } );
		const pageLink = await page.$x( `//a[contains(text(), '${ title }')]` );
		if ( pageLink.length > 0 ) {
			// clicking the link directly caused racing issues, so I used goto.
			link = await page.evaluate(
				( a ) => a.getAttribute( 'href' ),
				pageLink[ 0 ]
			);
		}
	}
	if ( link ) {
		await page.goto( link );
	} else throw new Error( `Unable to find page with name ${ title }` );
}

export default visitBlockPage;
