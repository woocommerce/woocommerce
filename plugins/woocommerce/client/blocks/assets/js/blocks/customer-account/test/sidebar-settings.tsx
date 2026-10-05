import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { BlockSettings } from '../sidebar-settings';
import { DisplayStyle, IconStyle } from '../types';
vi.mock( '@wordpress/block-editor', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/block-editor' ) ),
		InspectorControls: vi.fn( ( { children } ) => <div>{ children }</div> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/components', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/components' ) ),
		__experimentalToggleGroupControl: vi.fn( ( { children } ) => (
			<div>{ children }</div>
		) ),
		__experimentalToggleGroupControlOption: vi.fn( () => null ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Customer Account sidebar settings', () => {
	it.each( [
		{
			option: 'Icon and text',
			initialDisplayStyle: DisplayStyle.TEXT_ONLY,
			expectedDisplayStyle: DisplayStyle.ICON_AND_TEXT,
		},
		{
			option: 'Text-only',
			initialDisplayStyle: DisplayStyle.ICON_ONLY,
			expectedDisplayStyle: DisplayStyle.TEXT_ONLY,
		},
		{
			option: 'Icon-only',
			initialDisplayStyle: DisplayStyle.ICON_AND_TEXT,
			expectedDisplayStyle: DisplayStyle.ICON_ONLY,
		},
	] )(
		'maps "$option" to its serialized display style',
		async ( { option, initialDisplayStyle, expectedDisplayStyle } ) => {
			const user = userEvent.setup();
			const setAttributes = vi.fn();
			render(
				<BlockSettings
					attributes={ {
						displayStyle: initialDisplayStyle,
						iconStyle: IconStyle.DEFAULT,
						iconClass: 'wc-block-customer-account__account-icon',
					} }
					setAttributes={ setAttributes }
				/>
			);
			const displayStyleSelect = screen.getByRole( 'combobox', {
				name: 'Icon options',
			} );
			const targetOption = screen.getByRole( 'option', {
				name: option,
			} );
			await user.selectOptions( displayStyleSelect, targetOption );
			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				displayStyle: expectedDisplayStyle,
			} );
		}
	);
} );
