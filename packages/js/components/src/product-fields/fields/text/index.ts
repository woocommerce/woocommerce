/**
 * External dependencies
 */
import { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ProductFieldDefinition } from '../../store/types';
import render from './render';

export const textSettings: ProductFieldDefinition = {
	name: 'text',
	render: render as ComponentType,
};
