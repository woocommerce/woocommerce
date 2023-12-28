/**
 * External dependencies
 */
import {
	// @ts-expect-error no exported member.
	ComponentType,
} from '@wordpress/element';
/**
 * Internal dependencies
 */
import type {
	ProductEditorBlockAttributes,
	ProductEditorBlockEditProps,
} from '../../../types';

export type CrossSellsBlockEditProps =
	ProductEditorBlockEditProps< ProductEditorBlockAttributes >;

export type CrossSellsBlockEditComponent =
	ComponentType< CrossSellsBlockEditProps >;
