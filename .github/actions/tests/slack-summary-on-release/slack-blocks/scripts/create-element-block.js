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
	createElementBlock;
};
