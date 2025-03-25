/**
 * External dependencies
 */
import { optionsStore } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';

export const fetchSurveyCompletedOption = async () =>
	resolveSelect( optionsStore ).getOption(
		'woocommerce_admin_customize_store_survey_completed'
	);
