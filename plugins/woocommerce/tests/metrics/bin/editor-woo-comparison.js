#!/usr/bin/env node
/* eslint-disable no-console */
/**
 * External dependencies
 */
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const WOO_PLUGIN_PATH = process.cwd().endsWith( 'plugins/woocommerce' )
	? process.cwd()
	: path.resolve( process.cwd(), 'plugins/woocommerce' );
const runId = new Date().toISOString().replace( /[:.]/g, '-' );
const artifactsPath =
	process.env.WP_ARTIFACTS_PATH ||
	path.join( '/tmp/wc-editor-woo-comparison', runId );
const blockFilterMuPlugin = 'wc-editor-heavy-block-filter.php';
const filteredBlockTypes = [
	'Cart',
	'CartOrderSummaryTaxesBlock',
	'CartOrderSummarySubtotalBlock',
	'CartOrderSummaryTotalsBlock',
	'FilledCartBlock',
	'EmptyCartBlock',
	'CartTotalsBlock',
	'CartItemsBlock',
	'CartLineItemsBlock',
	'CartOrderSummaryBlock',
	'CartExpressPaymentBlock',
	'ProceedToCheckoutBlock',
	'CartAcceptedPaymentMethodsBlock',
	'CartOrderSummaryCouponFormBlock',
	'CartOrderSummaryDiscountBlock',
	'CartOrderSummaryFeeBlock',
	'CartOrderSummaryHeadingBlock',
	'CartOrderSummaryShippingBlock',
	'CartCrossSellsBlock',
	'CartCrossSellsProductsBlock',
	'Checkout',
	'CheckoutActionsBlock',
	'CheckoutAdditionalInformationBlock',
	'CheckoutBillingAddressBlock',
	'CheckoutContactInformationBlock',
	'CheckoutExpressPaymentBlock',
	'CheckoutFieldsBlock',
	'CheckoutOrderNoteBlock',
	'CheckoutOrderSummaryBlock',
	'CheckoutOrderSummaryCartItemsBlock',
	'CheckoutOrderSummaryCouponFormBlock',
	'CheckoutOrderSummaryDiscountBlock',
	'CheckoutOrderSummaryFeeBlock',
	'CheckoutOrderSummaryShippingBlock',
	'CheckoutOrderSummarySubtotalBlock',
	'CheckoutOrderSummaryTaxesBlock',
	'CheckoutOrderSummaryTotalsBlock',
	'CheckoutPaymentBlock',
	'CheckoutShippingAddressBlock',
	'CheckoutShippingMethodsBlock',
	'CheckoutShippingMethodBlock',
	'CheckoutPickupOptionsBlock',
	'CheckoutTermsBlock',
	'CheckoutTotalsBlock',
	'MiniCart',
	'MiniCartContents',
	'EmptyMiniCartContentsBlock',
	'FilledMiniCartContentsBlock',
	'MiniCartFooterBlock',
	'MiniCartItemsBlock',
	'MiniCartProductsTableBlock',
	'MiniCartShoppingButtonBlock',
	'MiniCartCartButtonBlock',
	'MiniCartCheckoutButtonBlock',
	'MiniCartTitleBlock',
	'MiniCartTitleItemsCounterBlock',
	'MiniCartTitleLabelBlock',
	'AllProducts',
	'ProductCollection\\Controller',
	'ProductCollection\\NoResults',
	'ProductQuery',
	'HandpickedProducts',
	'ProductBestSellers',
	'ProductCategory',
	'ProductNew',
	'ProductOnSale',
	'ProductTag',
	'ProductTopRated',
	'ProductsByAttribute',
];
const encodedFilteredBlockTypes = Buffer.from(
	JSON.stringify( filteredBlockTypes )
).toString( 'base64' );

function runCommand( command, args, options = {} ) {
	if ( options.logCommand !== false ) {
		console.log(
			options.displayCommand || [ command, ...args ].join( ' ' )
		);
	}

	try {
		return execFileSync( command, args, {
			cwd: WOO_PLUGIN_PATH,
			encoding: 'utf8',
			stdio: options.stdio || 'inherit',
			env: {
				...process.env,
				...( options.env || {} ),
			},
		} );
	} catch ( error ) {
		if ( options.stdio === 'pipe' ) {
			process.stdout.write( error.stdout || '' );
			process.stderr.write( error.stderr || '' );
		}
		throw error;
	}
}

function wpCli( args, options = {} ) {
	runCommand(
		'pnpm',
		[ 'wp-env', 'run', 'tests-cli', 'wp', ...args ],
		options
	);
}

function wpEval( code, label ) {
	wpCli( [ 'eval', code ], {
		displayCommand: `pnpm wp-env run tests-cli wp eval "${ label }"`,
		stdio: 'pipe',
	} );
}

function installBlockFilterMuPlugin() {
	const pluginSource = `<?php
/**
 * Plugin Name: WooCommerce editor metrics heavy block filter
 */

add_filter(
	'woocommerce_get_block_types',
	static function ( $block_types ) {
		$filtered_block_types = json_decode( base64_decode( '${ encodedFilteredBlockTypes }' ), true );

		return array_values( array_diff( $block_types, $filtered_block_types ) );
	}
);
`;
	const encodedPluginSource =
		Buffer.from( pluginSource ).toString( 'base64' );

	wpEval(
		`if ( ! is_dir( WPMU_PLUGIN_DIR ) ) { mkdir( WPMU_PLUGIN_DIR, 0777, true ); } file_put_contents( WPMU_PLUGIN_DIR . '/${ blockFilterMuPlugin }', base64_decode( '${ encodedPluginSource }' ) );`,
		'install temporary Woo block filter'
	);
}

function removeBlockFilterMuPlugin() {
	wpEval(
		`if ( file_exists( WPMU_PLUGIN_DIR . '/${ blockFilterMuPlugin }' ) ) { unlink( WPMU_PLUGIN_DIR . '/${ blockFilterMuPlugin }' ); }`,
		'remove temporary Woo block filter'
	);
}

function readResults( stateId ) {
	const resultsPath = path.join(
		artifactsPath,
		`editor_${ stateId }_round-1.performance-results.json`
	);

	return JSON.parse( fs.readFileSync( resultsPath, 'utf8' ) );
}

function runEditorMetrics( stateId ) {
	runCommand(
		'pnpm',
		[
			'playwright',
			'test',
			'--config=tests/metrics/playwright.config.js',
			'editor',
		],
		{
			env: {
				USE_WP_ENV: '1',
				WP_ARTIFACTS_PATH: artifactsPath,
				RESULTS_ID: `editor_${ stateId }_round-1`,
			},
		}
	);

	return readResults( stateId );
}

function buildComparison( baseResults, targetResults, baseLabel, targetLabel ) {
	const comparison = {};
	const metrics = new Set( [
		...Object.keys( baseResults ),
		...Object.keys( targetResults ),
	] );

	for ( const metric of metrics ) {
		const base = baseResults[ metric ];
		const target = targetResults[ metric ];
		const delta = target - base;
		const deltaPercent = base ? ( delta / base ) * 100 : null;

		comparison[ metric ] = {
			[ baseLabel ]: base,
			[ targetLabel ]: target,
			delta,
			deltaPercent,
		};
	}

	return comparison;
}

function printComparison( title, comparison, baseLabel, targetLabel ) {
	const printable = {};

	for ( const [ metric, values ] of Object.entries( comparison ) ) {
		printable[ metric ] = {
			[ baseLabel ]: values[ baseLabel ],
			[ targetLabel ]: values[ targetLabel ],
			delta: values.delta,
			deltaPercent:
				values.deltaPercent === null
					? 'n/a'
					: values.deltaPercent.toFixed( 2 ) + '%',
		};
	}

	console.log( `\n${ title }\n` );
	console.table( printable );
}

function main() {
	fs.mkdirSync( artifactsPath, { recursive: true } );
	console.log( `Writing artifacts to ${ artifactsPath }` );

	let inactiveResults;
	let activeResults;
	let filteredResults;

	try {
		removeBlockFilterMuPlugin();
		wpCli( [ 'plugin', 'deactivate', 'woocommerce' ] );
		inactiveResults = runEditorMetrics( 'woo-inactive' );

		wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
		activeResults = runEditorMetrics( 'woo-active' );
	} finally {
		removeBlockFilterMuPlugin();
		wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
	}

	const activeComparison = buildComparison(
		inactiveResults,
		activeResults,
		'inactive',
		'active'
	);
	const filteredComparison = buildComparison(
		activeResults,
		filteredResults,
		'active',
		'filtered'
	);
	const filteredVsInactiveComparison = buildComparison(
		inactiveResults,
		filteredResults,
		'inactive',
		'filtered'
	);
	const comparisonPath = path.join(
		artifactsPath,
		'editor-woo-comparison.json'
	);
	const filteredComparisonPath = path.join(
		artifactsPath,
		'editor-woo-filtered-comparison.json'
	);
	const resultsPath = path.join( artifactsPath, 'editor-woo-results.json' );

	fs.writeFileSync(
		comparisonPath,
		JSON.stringify( activeComparison, null, 2 )
	);
	fs.writeFileSync(
		filteredComparisonPath,
		JSON.stringify(
			{
				filteredBlockTypes,
				activeVsFiltered: filteredComparison,
				inactiveVsFiltered: filteredVsInactiveComparison,
			},
			null,
			2
		)
	);
	fs.writeFileSync(
		resultsPath,
		JSON.stringify(
			{
				inactive: inactiveResults,
				active: activeResults,
				filtered: filteredResults,
				filteredBlockTypes,
			},
			null,
			2
		)
	);
	printComparison(
		'Woo active vs inactive editor metrics',
		activeComparison,
		'inactive',
		'active'
	);
	printComparison(
		'Woo active with heavy block families filtered vs Woo active',
		filteredComparison,
		'active',
		'filtered'
	);
	console.log( `\nSaved comparison to ${ comparisonPath }` );
	console.log( `Saved filtered comparison to ${ filteredComparisonPath }` );
	console.log( `Saved raw results to ${ resultsPath }` );
}

main();
