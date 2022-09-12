/**
 * External dependencies
 */
import { select } from '@wordpress/data';

import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types exist for this module yet.
	store as richTextStore,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { register as registerBold } from './bold';
import { register as registerItalic } from './italic';
import { register as registerLink } from './link';

export const formatIsRegistered = (formatName: string) => {
	const { getFormatTypes } = select(richTextStore) || {};
	if (!getFormatTypes) {
		return false;
	}
	return !!getFormatTypes().find((format) => format.name === formatName);
};

export const registerFormatTypes = () => {
	[registerBold, registerItalic, registerLink].forEach((register) =>
		register()
	);
};
