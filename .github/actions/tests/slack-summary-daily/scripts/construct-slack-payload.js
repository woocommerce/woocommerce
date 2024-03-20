module.exports = async ( { context, core, github } ) => {
	const {
		API_RESULT,
		E2E_RESULT,
		k6_RESULT,
		PLUGINS_BLOCKS_PATH,
		PLUGIN_TESTS_RESULT,
		GITHUB_REF_NAME,
		GITHUB_RUN_ID,
	} = process.env;
	const {
		selectEmoji,
		readContextBlocksFromJsonFiles,
	} = require( './utils' );

	const URL_GITHUB_RUN_LOG = `https://github.com/woocommerce/woocommerce/actions/runs/${ GITHUB_RUN_ID }`;

	const create_blockGroup_header = async () => {
		const getRunStartDate = async () => {
			const response = await github.rest.actions.getWorkflowRun( {
				owner: context.repo.owner,
				repo: context.repo.repo,
				run_id: GITHUB_RUN_ID,
			} );
			const runStartedAt = new Date( response.data.run_started_at );
			const intlDateTimeFormatOptions = {
				dateStyle: 'full',
				timeStyle: 'long',
			};
			const date = new Intl.DateTimeFormat(
				'en-US',
				intlDateTimeFormatOptions
			).format( runStartedAt );

			return date;
		};

		const readableDate = await getRunStartDate();

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
						text: `*Run started:* ${ readableDate }`,
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
						text: `*GitHub run logs:* <${ URL_GITHUB_RUN_LOG }|${ GITHUB_RUN_ID }>`,
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
		const url_API =
			'https://woocommerce.github.io/woocommerce-test-reports/daily/nightly-site/api';
		const url_E2E =
			'https://woocommerce.github.io/woocommerce-test-reports/daily/nightly-site/e2e';
		const url_k6 = URL_GITHUB_RUN_LOG;

		const blocks = [
			{
				type: 'section',
				text: {
					type: 'mrkdwn',
					text: `<${ URL_GITHUB_RUN_LOG }|*Smoke tests on daily build*>`,
				},
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: `<${ url_API }|API> ${ emoji_API }\t<${ url_E2E }|E2E> ${ emoji_E2E }\t<${ url_k6 }|k6> ${ emoji_k6 }`,
					},
				],
			},
			{
				type: 'divider',
			},
		];

		return blocks;
	};

	const create_blockGroups_plugins = () => {
		const pluginTestsSkipped = PLUGIN_TESTS_RESULT === 'skipped';
		const blocks_pluginTestsSkipped = [
			{
				type: 'section',
				text: {
					type: 'mrkdwn',
					text: ':warning: *Plugin tests were not run!*',
				},
			},
			{
				type: 'context',
				elements: [
					{
						type: 'mrkdwn',
						text: `Head over to the <${ URL_GITHUB_RUN_LOG }|GitHub workflow run log> to see what went wrong.`,
					},
				],
			},
			{
				type: 'divider',
			},
		];

		return pluginTestsSkipped
			? blocks_pluginTestsSkipped
			: readContextBlocksFromJsonFiles( PLUGINS_BLOCKS_PATH );
	};

	const blockGroup_header = await create_blockGroup_header();
	const blockGroup_nightlySite = create_blockGroup_nightlySite();
	const blockGroups_plugins = create_blockGroups_plugins();
	const blocks_all = [
		...blockGroup_header,
		...blockGroup_nightlySite,
		...blockGroups_plugins.flat(),
	];
	const payload = {
		text: 'Daily test results',
		blocks: blocks_all,
	};
	const payload_stringified = JSON.stringify( payload );

	core.setOutput( 'payload', payload_stringified );
};
