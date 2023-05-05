const initContextBlock = ( testName ) => {
	return {
		type: 'context',
		elements: [
			{
				type: 'mrkdwn',
				text: `*${ testName }*`,
			},
			{
				type: 'mrkdwn',
				text: ' ',
			},
			{
				type: 'mrkdwn',
				text: ' ',
			},
		],
	};
};

const createElementBlock = ( testType, result ) => {
	const selectEmoji = ( result ) => {
		const emojis = {
			PASSED: ':workflow-passed:',
			FAILED: ':workflow-failed:',
			SKIPPED: ':workflow-skipped:',
			CANCELLED: ':workflow-cancelled:',
			UNKNOWN: ':grey_question:',
		};

		switch ( result ) {
			case 'success':
				return emojis.PASSED;
			case 'failure':
				return emojis.FAILED;
			case 'skipped':
				return emojis.SKIPPED;
			case 'cancelled':
				return emojis.CANCELLED;
			default:
				return emojis.UNKNOWN;
		}
	};

	const emoji = selectEmoji( result );

	return {
		type: 'mrkdwn',
		text: `${ testType.toUpperCase() } ${ emoji }`,
	};
};

module.exports = () => {
	const { API_RESULT, E2E_RESULT, TEST_NAME } = process.env;

	const contextBlock = initContextBlock( TEST_NAME );
	const apiElementBlock = createElementBlock( 'API', API_RESULT );
	const e2eElementBlock = createElementBlock( 'E2E', E2E_RESULT );

	contextBlock.elements.push( apiElementBlock );
	contextBlock.elements.push( e2eElementBlock );

	return contextBlock;
};
