#!/usr/bin/env node
'use strict';
/* eslint no-console: 0 */
const chalk = require( 'chalk' );

// Dynamic hook names come out of the generator as the raw PHP expression, e.g.
// `'woocommerce_' . $product_type . '_add_to_cart'`. Render them in the
// interpolated style used by WordPress hook docs: `woocommerce_{$product_type}_add_to_cart`.
// Assumes the generator's spacing (` . ` with surrounding spaces); other
// concatenation styles pass through unnormalized.
const normalizeHookName = ( name ) => {
	if ( ! name.includes( ' . ' ) ) {
		return name.startsWith( '$' ) ? `{${ name }}` : name;
	}

	return name
		.split( ' . ' )
		.map( ( part ) => {
			const trimmed = part.trim();
			return trimmed.startsWith( '$' )
				? `{${ trimmed }}`
				: trimmed.replace( /^['"]|['"]$/g, '' );
		} )
		.join( '' );
};

const skipHook = ( name ) => {
	// A name that is nothing but a variable (e.g. `{$hook}`) documents no real,
	// attachable hook — the value is only known at runtime.
	if ( /^\{\$[^}]+\}$/.test( name ) ) {
		return true;
	}

	// `internal_`-prefixed hooks are explicitly not a third-party contract.
	return name.startsWith( 'internal_' );
};

const groupByHook = ( hooks, hook ) => {
	hook = { ...hook, name: normalizeHookName( hook.name ) };

	if ( skipHook( hook.name ) ) {
		return hooks;
	}

	// The docs scan is scoped by the ignore list in composer.json; a hook from
	// anywhere else means a new plugins/woocommerce/src directory leaked in.
	if (
		! hook.file.startsWith( 'Blocks/' ) &&
		! hook.file.startsWith( 'StoreApi/' )
	) {
		throw new Error(
			`Hook "${ hook.name }" comes from "${ hook.file }", outside src/Blocks and src/StoreApi. ` +
				'Add the directory to extra.wp-hooks.ignore-files in client/blocks/composer.json and regenerate.'
		);
	}

	if ( hooks[ hook.name ] !== undefined ) {
		if ( ! hooks[ hook.name ].file.includes( hook.file ) ) {
			hooks[ hook.name ].file.push( hook.file );
		}
		return hooks; // skip or return updated record.
	}

	hooks[ hook.name ] = {
		...hook,
		file: [ hook.file ], // Use array of files to support hooks used across the codebase.
	};

	return hooks;
};

try {
	const { generate: generateActionDocs } = require( './actions' );
	const { generate: generateFilterDocs } = require( './filters' );

	console.log( chalk.blue( "Let's create some docs!" ) );

	const rawActions = require( './data/actions.json' ).hooks;
	const rawFilters = require( './data/filters.json' ).hooks;

	// Skip duplicates.
	const actions = rawActions.reduce( groupByHook, {} );
	const filters = rawFilters.reduce( groupByHook, {} );

	// Re-sort, since normalizing dynamic hook names can change their sort position.
	// Byte order, matching the generator's strcmp sort (localeCompare would
	// weigh `_` differently and reshuffle the `__experimental_*` entries).
	const byName = ( a, b ) =>
		a.name < b.name ? -1 : Number( a.name > b.name );

	generateActionDocs( Object.values( actions ).sort( byName ) );
	generateFilterDocs( Object.values( filters ).sort( byName ) );
} catch ( error ) {
	// Full error on stderr, so CI logs get the stack trace.
	console.error( error );
	process.exitCode = 1;
}
