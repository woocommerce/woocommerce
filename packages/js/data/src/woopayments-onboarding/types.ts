export interface StepContent {
	id: string;
	label: string;
	path: string;
	order: number;
	status: 'completed' | 'started' | 'not_started';
	dependencies: string[];
	actions: Record< string, unknown >;
	context: object;
}

export interface OnboardingState {
	steps: StepContent[];
	isFetching: boolean;
	errors: Record< string, unknown >;
}

export type OnboardingStepsResponse = {
	steps: StepContent[];
};
