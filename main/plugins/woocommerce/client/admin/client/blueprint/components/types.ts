export type BlueprintQueueResponse = {
	error_type?: string;
	errors?: string[];
	settings_to_overwrite?: string[];
};

export type BlueprintImportResponse = {
	// TODO: flesh out this type with more concrete values
	processed: boolean;
	message: string;
	data: {
		redirect: string;
		result: {
			is_success: boolean;
			messages: {
				step: string;
				type: string;
				message: string;
			}[];
		};
	};
};

export type BlueprintStep = {
	step: string;
	options?: Record< string, string >;
};

export type BlueprintImportStepResponse = {
	success: boolean;
	messages: {
		step: string;
		type: string;
		message: string;
	}[];
	sessionToken?: string;
};
