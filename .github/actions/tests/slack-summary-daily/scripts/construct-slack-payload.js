module.exports = ( { core } ) => {
	const {
		API_RESULT,
		E2E_RESULT,
		k6_RESULT,
		PLUGINS_BLOCKS_PATH,
		GITHUB_RUN_ID,
	} = process.env;
	const {
		selectEmoji,
		readContextBlocksFromJsonFiles,
	} = require( './utils' );

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
	const blockGroup_nightlySite = [
		{
			type: 'section',
			text: {
				type: 'mrkdwn',
				text: '*Smoke tests on daily build*',
			},
		},
		{
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: `API ${ emoji_API }\tE2E ${ emoji_E2E }\tk6 ${ emoji_k6 }`,
				},
			],
		},
		{
			type: 'divider',
		},
	];

	const blockGroups_plugins =
		readContextBlocksFromJsonFiles( PLUGINS_BLOCKS_PATH );

	const block_github = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: 'View GitHub run logs :github:',
					emoji: true,
				},
				url: `https://github.com/woocommerce/woocommerce/actions/runs/${ GITHUB_RUN_ID }`,
			},
		],
	};
	const block_dashboard = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: 'Open test reports dashboard :colorful-bar-chart:',
					emoji: true,
				},
				url: 'https://woocommerce.github.io/woocommerce-test-reports/daily/',
			},
		],
	};

	const payload = {
		blocks: [
			block_header,
			...blockGroup_nightlySite,
			...blockGroups_plugins.flat(),
			block_github,
			block_dashboard,
		],
	};

	const payload_stringified = JSON.stringify( payload );

	core.setOutput( 'payload', payload_stringified );
};
