/**
 * External dependencies
 */
import { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ProductFieldDefinition } from '../../store/types';
import render from './render';

export const checkboxSettings: ProductFieldDefinition = {
	name: 'checkbox',
	render: render as ComponentType,
};
