const { API_RESULT, E2E_RESULT, k6_RESULT, PLUGINS_BLOCKS_PATH } = process.env;
const { selectEmoji, readContextBlocksFromJsonFiles } = require( './utils' );

const block_header = {
	type: 'header',
	text: {
		type: 'plain_text',
		text: 'Daily test results',
		emoji: true,
	},
};

const emoji_API = selectEmoji( API_RESULT );
const emoji_E2E = selectEmoji( E2E_RESULT );
const emoji_k6 = selectEmoji( k6_RESULT );

const block_nightlySite = {
	type: 'context',
	elements: [
		{
			type: 'mrkdwn',
			text: '*Smoke tests on daily build*',
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
			text: `API ${ emoji_API }`,
		},
		{
			type: 'mrkdwn',
			text: `E2E ${ emoji_E2E }`,
		},
		{
			type: 'mrkdwn',
			text: `k6 ${ emoji_k6 }`,
		},
	],
};

const blocks_plugins = readContextBlocksFromJsonFiles( PLUGINS_BLOCKS_PATH );

const payload = {
	blocks: [ block_header, block_nightlySite, ...blocks_plugins ],
};

const payload_stringified = JSON.stringify( payload );

core.setOuput( 'payload', payload_stringified );
