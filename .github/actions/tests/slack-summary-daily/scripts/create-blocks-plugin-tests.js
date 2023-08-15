const { UPLOAD_RESULT, E2E_RESULT, PLUGIN_NAME, PLUGIN_SLUG } = process.env;
const { selectEmoji } = require( './utils' );
const fs = require( 'fs' );

const emoji_UPLOAD = selectEmoji( UPLOAD_RESULT );
const emoji_E2E = selectEmoji( E2E_RESULT );

const block = {
	type: 'context',
	elements: [
		{
			type: 'mrkdwn',
			text: PLUGIN_NAME,
		},
		{
			type: 'mrkdwn',
			text: ' ',
		},
		{
			type: 'mrkdwn',
			text: ' ',
		},
		{
			type: 'mrkdwn',
			text: `Upload test ${ emoji_UPLOAD }`,
		},
		{
			type: 'mrkdwn',
			text: `E2E ${ emoji_E2E }`,
		},
	],
};
const block_stringified = JSON.stringify( block );

const path = `/tmp/${ PLUGIN_SLUG }.json`;
fs.writeFileSync( path, block_stringified );

core.setOutput( 'path', path );
