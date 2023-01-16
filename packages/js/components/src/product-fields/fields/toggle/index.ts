/**
 * External dependencies
 */
import { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ProductFieldDefinition } from '../../store/types';
import render from './render';

export const toggleSettings: ProductFieldDefinition = {
	name: 'toggle',
	render: render as ComponentType,
};
