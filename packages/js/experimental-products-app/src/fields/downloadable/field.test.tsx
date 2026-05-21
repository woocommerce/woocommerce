/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { DataFormControlProps } from '@wordpress/dataviews';
import React from 'react';

let onSelectMedia: ( attachment: unknown ) => void;

jest.mock( '@wordpress/components', () => {
	const ReactActual = jest.requireActual( 'react' );

	return {
		Button: ( {
			children,
			onClick,
			'aria-label': ariaLabel,
		}: {
			children?: React.ReactNode;
			onClick?: React.MouseEventHandler< HTMLButtonElement >;
			'aria-label'?: string;
		} ) =>
			ReactActual.createElement(
				'button',
				{ 'aria-label': ariaLabel, onClick, type: 'button' },
				children
			),
		TextControl: ( {
			label,
			value,
			onChange,
			placeholder,
		}: {
			label?: string;
			value?: string;
			onChange?: ( val: string ) => void;
			placeholder?: string;
		} ) =>
			ReactActual.createElement(
				'div',
				null,
				label &&
					ReactActual.createElement( 'label', null, label ),
				ReactActual.createElement( 'input', {
					value: value ?? '',
					placeholder,
					onChange: ( e: React.ChangeEvent< HTMLInputElement > ) =>
						onChange?.( e.target.value ),
				} )
			),
		CheckboxControl: ( {
			label,
			checked,
			onChange,
		}: {
			label?: string;
			checked?: boolean;
			onChange?: ( val: boolean ) => void;
		} ) =>
			ReactActual.createElement(
				'label',
				null,
				ReactActual.createElement( 'input', {
					type: 'checkbox',
					checked: checked ?? false,
					onChange: ( e: React.ChangeEvent< HTMLInputElement > ) =>
						onChange?.( e.target.checked ),
				} ),
				label
			),
	};
} );

jest.mock( '@wordpress/media-utils', () => ( {
	MediaUpload: ( {
		onSelect,
		render: renderProp,
	}: {
		onSelect: ( attachment: unknown ) => void;
		render: ( args: { open: () => void } ) => React.ReactNode;
	} ) => {
		onSelectMedia = onSelect;
		return renderProp( { open: jest.fn() } ) as React.ReactElement;
	},
} ) );

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { fieldExtensions } from './field';

describe( 'downloadable field', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 12,
			name: 'Beanie',
			downloadable: true,
			downloads: [],
			download_limit: -1,
			download_expiry: -1,
			...overrides,
		} as ProductEntityRecord );

	const renderEdit = ( data: ProductEntityRecord, onChange = jest.fn() ) => {
		if ( ! fieldExtensions.Edit ) {
			throw new Error( 'downloadable edit not implemented' );
		}

		const Edit = fieldExtensions.Edit as React.ComponentType<
			DataFormControlProps< ProductEntityRecord >
		>;

		return render(
			<Edit
				data={ data }
				field={
					{
						...fieldExtensions,
						id: 'downloadable',
						label: 'Downloadable',
					} as DataFormControlProps< ProductEntityRecord >[ 'field' ]
				}
				onChange={ onChange }
			/>
		);
	};

	it( 'renders existing download files', () => {
		renderEdit(
			buildProduct( {
				downloads: [
					{
						id: '1',
						name: 'Product manual',
						file: 'https://example.com/manual.pdf',
					},
				],
			} )
		);

		expect(
			screen.getByDisplayValue( 'https://example.com/manual.pdf' )
		).toBeInTheDocument();
		expect(
			screen.getByDisplayValue( 'Product manual' )
		).toBeInTheDocument();
	} );

	it( 'adds a new empty file row when "+ Add file" is clicked', () => {
		const onChange = jest.fn();
		renderEdit( buildProduct(), onChange );

		fireEvent.click( screen.getByText( '+ Add file' ) );

		expect( onChange ).toHaveBeenCalledWith(
			expect.objectContaining( {
				downloads: [
					expect.objectContaining( { name: '', file: '' } ),
				],
			} )
		);
	} );

	it( 'updates the URL when the user types in the URL input', () => {
		const onChange = jest.fn();
		renderEdit(
			buildProduct( {
				downloads: [ { id: '1', name: 'File', file: '' } ],
			} ),
			onChange
		);

		const urlInput = screen.getByPlaceholderText( 'https://' );
		fireEvent.change( urlInput, {
			target: { value: 'https://example.com/file.zip' },
		} );

		expect( onChange ).toHaveBeenCalledWith( {
			downloads: [
				{ id: '1', name: 'File', file: 'https://example.com/file.zip' },
			],
		} );
	} );

	it( 'fills the URL from the media library selection', () => {
		const onChange = jest.fn();
		renderEdit(
			buildProduct( {
				downloads: [ { id: '1', name: '', file: '' } ],
			} ),
			onChange
		);

		onSelectMedia( {
			url: 'https://example.com/wp-content/uploads/audio.mp3',
			title: 'Audio track',
		} );

		expect( onChange ).toHaveBeenCalledWith( {
			downloads: [
				{
					id: '1',
					file: 'https://example.com/wp-content/uploads/audio.mp3',
					name: 'Audio track',
				},
			],
		} );
	} );

	it( 'removes a file row when Remove is clicked', () => {
		const onChange = jest.fn();
		renderEdit(
			buildProduct( {
				downloads: [
					{ id: '1', name: 'File A', file: 'https://example.com/a' },
					{ id: '2', name: 'File B', file: 'https://example.com/b' },
				],
			} ),
			onChange
		);

		fireEvent.click( screen.getAllByText( 'Remove' )[ 0 ] );

		expect( onChange ).toHaveBeenCalledWith( {
			downloads: [
				{ id: '2', name: 'File B', file: 'https://example.com/b' },
			],
		} );
	} );

	it( 'toggles the limit downloads per customer checkbox', () => {
		const onChange = jest.fn();
		renderEdit( buildProduct(), onChange );

		const checkbox = screen.getByLabelText( 'Limit downloads per customer' );
		fireEvent.click( checkbox );

		expect( onChange ).toHaveBeenCalledWith( { download_limit: 1 } );
	} );

	it( 'toggles the expire download link checkbox', () => {
		const onChange = jest.fn();
		renderEdit( buildProduct(), onChange );

		const checkbox = screen.getByLabelText( 'Expire download link' );
		fireEvent.click( checkbox );

		expect( onChange ).toHaveBeenCalledWith( { download_expiry: 1 } );
	} );

	it( 'reflects existing limit and expiry settings', () => {
		renderEdit(
			buildProduct( { download_limit: 3, download_expiry: 30 } )
		);

		expect(
			screen.getByLabelText( 'Limit downloads per customer' )
		).toBeChecked();
		expect(
			screen.getByLabelText( 'Expire download link' )
		).toBeChecked();
	} );
} );
