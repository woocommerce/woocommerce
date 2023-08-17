module.exports = ( { core } ) => {
	const {
		API_RESULT,
		E2E_RESULT,
		k6_RESULT,
		PLUGINS_BLOCKS_PATH,
		GITHUB_REF_NAME,
		GITHUB_RUN_ID,
	} = process.env;
	const {
		selectEmoji,
		readContextBlocksFromJsonFiles,
	} = require( './utils' );

	const create_blockGroup_header = () => {
		const date = new Intl.DateTimeFormat( 'en-US', {
			dateStyle: 'full',
		} ).format( Date.now() );

		const blocks = [
			{
				type: 'header',
				text: {
					type: 'plain_text',
					text: 'Daily test results',
					emoji: true,
				},
			},
			{
				type: 'divider',
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: `*Date:* ${ date }`,
					},
				],
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: `*Branch:* \`${ GITHUB_REF_NAME }\``,
					},
				],
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: `*GitHub run logs:* <https://github.com/woocommerce/woocommerce/actions/runs/${ GITHUB_RUN_ID }|${ GITHUB_RUN_ID }>`,
					},
				],
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: '*Test reports dashboard:* <https://woocommerce.github.io/woocommerce-test-reports/daily/|Daily smoke tests>',
					},
				],
			},
			{
				type: 'divider',
			},
		];

		return blocks;
	};

	const create_blockGroup_nightlySite = () => {
		const emoji_API = selectEmoji( API_RESULT );
		const emoji_E2E = selectEmoji( E2E_RESULT );
		const emoji_k6 = selectEmoji( k6_RESULT );

		const blocks = [
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

		return blocks;
	};

	const blockGroup_header = create_blockGroup_header();
	const blockGroup_nightlySite = create_blockGroup_nightlySite();
	const blockGroups_plugins =
		readContextBlocksFromJsonFiles( PLUGINS_BLOCKS_PATH );
	const payload = {
		blocks: [
			...blockGroup_header,
			...blockGroup_nightlySite,
			...blockGroups_plugins.flat(),
		],
	};
	const payload_stringified = JSON.stringify( payload );

	core.setOutput( 'payload', payload_stringified );
};
