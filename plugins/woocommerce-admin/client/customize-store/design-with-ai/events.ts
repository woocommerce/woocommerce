export type aiWizardClosedBeforeCompletionEvent = {
	type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION';
	payload: {
		step: string;
	};
};
