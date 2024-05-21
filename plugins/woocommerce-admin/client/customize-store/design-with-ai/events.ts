export type aiWizardClosedBeforeCompletionEvent = {
	type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION';
	payload: {
		step: string;
	};
};

export type goBackToHomeEvent = { type: 'GO_BACK_TO_HOME' };
