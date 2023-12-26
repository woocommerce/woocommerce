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
import {
	ProductEditorBlockAttributes,
	ProductEditorBlockEditProps,
} from '../../../types';

export type UpsellsBlockEditProps =
	ProductEditorBlockEditProps< ProductEditorBlockAttributes >;

export type UpsellsBlockEditComponent = ComponentType< UpsellsBlockEditProps >;
