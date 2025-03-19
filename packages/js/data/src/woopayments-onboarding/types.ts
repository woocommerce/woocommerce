export interface StepContent {
	id: string;
	label: string;
	path: string;
	order: number;
	status: 'completed' | 'incomplete';
	dependencies: string[];
	actions: Record< string, unknown >;
	data: Record< string, unknown >;
}

export interface OnboardingState {
	steps: StepContent[];
	isFetching: boolean;
	errors: Record< string, unknown >;
}

export type OnboardingStepsResponse = {
	steps: StepContent[];
};
