/**
 * External dependencies
 */
import { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ProductFieldDefinition } from '../../store/types';
import render from './render';

export const radioSettings: ProductFieldDefinition = {
	name: 'radio',
	render: render as ComponentType,
};
