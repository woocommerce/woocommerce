const emojis = {
	PASSED: ':workflow-passed:',
	FAILED: ':workflow-failed:',
	SKIPPED: ':workflow-skipped:',
	CANCELLED: ':workflow-cancelled:',
	UNKNOWN: ':grey_question:',
};

const selectEmoji = ( result ) => {
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

module.exports = {
	selectEmoji,
};
