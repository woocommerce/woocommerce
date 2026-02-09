/**
 * External dependencies
 */
import { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ProductFieldDefinition } from '../../store/types';
import render from './render';

export const basicSelectControlSettings: ProductFieldDefinition = {
	name: 'basic-select-control',
	render: render as ComponentType,
};
