/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type Entity = {
	name: string;
	kind: string;
	baseURL: string;
	label: string;
	plural: string;
	key: string;
	supportsPagination: boolean;
};
