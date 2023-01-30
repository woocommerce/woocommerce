export type RuleProcessor = {
	type: RuleType;
	value?: string | number | boolean;
	default?: string | number | boolean;
	index?: string;
	operation?: RuleOperation;
	status?: string;
	operand?: RuleProcessor;
	operands?: RuleProcessor[] | RuleProcessor[][];
	option_name?: string;
	plugin?: string;
	plugins?: string[];
	publish_after?: string;
};

export type RuleType =
	| 'plugins_activated'
	| 'publish_after_time'
	| 'publish_before_time'
	| 'not'
	| 'or'
	| 'fail'
	| 'pass'
	| 'plugin_version'
	| 'stored_state'
	| 'order_count'
	| 'wcadmin_active_for'
	| 'product_count'
	| 'onboarding_profile'
	| 'is_ecommerce'
	| 'base_location_country'
	| 'base_location_state'
	| 'note_status'
	| 'option'
	| 'wca_updated';

export type RuleOperation =
	| '='
	| '<'
	| '<='
	| '>'
	| '>='
	| '!='
	| 'contains'
	| '!contains';
