/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';

export const fetchSurveyCompletedOption = async () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_admin_customize_store_survey_completed'
	);
