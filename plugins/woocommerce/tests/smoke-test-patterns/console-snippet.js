/**
 * WooCommerce Block Patterns Smoke Test — Browser Console Snippet
 *
 * Usage: Open a page in the Site Editor, paste this into the browser console, and run it.
 *        Make sure the console context is set to "top" (not the iframe).
 * All registered WooCommerce block patterns will be inserted with heading separators.
 */
( async () => {
	const { select, dispatch } = wp.data;
	const { parse } = wp.blocks;

	// Fetch all block patterns from the store.
	const allPatterns = select( 'core' ).getBlockPatterns();

	if ( ! allPatterns || allPatterns.length === 0 ) {
		console.error( 'No block patterns found. Make sure WooCommerce is active.' );
		return;
	}

	// Filter to WooCommerce patterns by category (matches the editor's "WooCommerce" sidebar count).
	// PTK patterns use the "woo-commerce" category but may not have a "woocommerce/" name prefix.
	// Exclude email-specific patterns (not meant for page content).
	const wooPatterns = allPatterns.filter(
		( p ) =>
			p.categories?.includes( 'woo-commerce' ) &&
			! p.name.includes( 'email' )
	);

	if ( wooPatterns.length === 0 ) {
		console.error( 'No WooCommerce block patterns found. Is WooCommerce active?' );
		return;
	}

	console.log( `Found ${ wooPatterns.length } WooCommerce patterns. Inserting...` );

	const blocksToInsert = [];

	wooPatterns.forEach( ( pattern, index ) => {
		// Add a separator heading with the pattern number and name.
		const headingMarkup = `<!-- wp:heading {"level":2,"style":{"color":{"background":"#7f54b3","text":"#ffffff"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"15px","right":"15px"}}}} -->
<h2 class="wp-block-heading has-text-color has-background" style="color:#ffffff;background-color:#7f54b3;padding-top:10px;padding-right:15px;padding-bottom:10px;padding-left:15px">${ index + 1 }/${ wooPatterns.length } — ${ pattern.name }</h2>
<!-- /wp:heading -->`;

		const headingBlocks = parse( headingMarkup );
		blocksToInsert.push( ...headingBlocks );

		// Parse and add the pattern content.
		try {
			const patternBlocks = parse( pattern.content );
			blocksToInsert.push( ...patternBlocks );
		} catch ( e ) {
			console.warn( `Failed to parse pattern "${ pattern.name }":`, e );
			const errorMarkup = `<!-- wp:paragraph {"style":{"color":{"text":"#cc0000"}}} -->
<p class="has-text-color" style="color:#cc0000">⚠ Failed to parse pattern: ${ pattern.name }</p>
<!-- /wp:paragraph -->`;
			blocksToInsert.push( ...parse( errorMarkup ) );
		}

		// Add a spacer between patterns.
		const spacerMarkup = `<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->`;
		blocksToInsert.push( ...parse( spacerMarkup ) );
	} );

	// Insert all blocks at the end of the editor.
	dispatch( 'core/block-editor' ).insertBlocks( blocksToInsert );

	console.log(
		`✅ Inserted ${ wooPatterns.length } WooCommerce patterns. Preview the page to review.`
	);
} )();
