/* global HTMLElement, HTMLInputElement, HTMLSelectElement, HTMLTextAreaElement */
/* eslint-disable testing-library/no-unnecessary-act -- This smoke test owns the React root directly so it can verify clean unmounting. */

/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import { DataForm } from '../dataform-runtime';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

type TestItem = {
	title: string;
	choice: string;
	multipleChoices: string[];
	enabled: boolean;
	notes: string;
	amount: number;
	publishDate: string;
	publishAt: string;
};

const renderDataForm = (
	fields: Field< TestItem >[],
	data: TestItem,
	onChange: jest.Mock = jest.fn()
) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render(
			<DataForm
				data={ data }
				fields={ fields }
				form={ { fields: fields.map( ( field ) => field.id ) } }
				onChange={ onChange }
			/>
		);
	} );

	return { container, onChange, root };
};

const getControlByLabel = (
	container: HTMLElement,
	labelText: string
): HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement => {
	const label = Array.from( container.querySelectorAll( 'label' ) ).find(
		( candidate ) => candidate.textContent?.trim() === labelText
	);
	const control = label?.control;

	if (
		! (
			control instanceof HTMLInputElement ||
			control instanceof HTMLSelectElement ||
			control instanceof HTMLTextAreaElement
		)
	) {
		throw new Error( `Expected a form control labeled "${ labelText }".` );
	}

	return control;
};

const testData: TestItem = {
	title: 'Original title',
	choice: 'one',
	multipleChoices: [ 'one' ],
	enabled: true,
	notes: 'Initial notes',
	amount: 2.5,
	publishDate: '2026-08-03',
	publishAt: '2026-08-03T10:00:00Z',
};

describe( 'DataForm runtime facade', () => {
	it( 'renders a labeled text field, reports changes, and unmounts cleanly', () => {
		const consoleError = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		const { container, onChange, root } = renderDataForm(
			[ { id: 'title', label: 'Title', type: 'text' } ],
			testData
		);
		const title = getControlByLabel( container, 'Title' );

		expect( title ).toBeInstanceOf( HTMLInputElement );
		expect( title ).toHaveValue( 'Original title' );

		act( () => {
			const valueSetter = Object.getOwnPropertyDescriptor(
				HTMLInputElement.prototype,
				'value'
			)?.set;
			valueSetter?.call( title, 'Updated title' );
			title.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );

		expect( onChange ).toHaveBeenLastCalledWith( {
			title: 'Updated title',
		} );

		act( () => root.unmount() );
		container.remove();
		expect( consoleError ).not.toHaveBeenCalled();
	} );

	it( 'mounts every control family required by the settings adapter', () => {
		const consoleError = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		const fields: Field< TestItem >[] = [
			{ id: 'title', label: 'Title', type: 'text' },
			{
				id: 'choice',
				label: 'Choice',
				type: 'text',
				elements: [
					{ value: 'one', label: 'One' },
					{ value: 'two', label: 'Two' },
				],
			},
			{
				id: 'multipleChoices',
				label: 'Multiple choices',
				type: 'array',
				elements: [
					{ value: 'one', label: 'One' },
					{ value: 'two', label: 'Two' },
				],
			},
			{
				id: 'enabled',
				label: 'Enabled',
				type: 'boolean',
				Edit: 'checkbox',
			},
			{
				id: 'notes',
				label: 'Notes',
				type: 'text',
				Edit: 'textarea',
			},
			{ id: 'amount', label: 'Amount', type: 'number' },
			{ id: 'publishDate', label: 'Publish date', type: 'date' },
			{
				id: 'publishAt',
				label: 'Publish at',
				type: 'datetime',
				Edit: { control: 'datetime', compact: true },
			},
		];
		const { container, root } = renderDataForm( fields, testData );

		expect( getControlByLabel( container, 'Title' ) ).toBeInstanceOf(
			HTMLInputElement
		);
		expect( getControlByLabel( container, 'Choice' ) ).toBeInstanceOf(
			HTMLSelectElement
		);
		expect(
			getControlByLabel( container, 'Multiple choices' )
		).toBeInstanceOf( HTMLInputElement );
		expect( getControlByLabel( container, 'Enabled' ) ).toHaveAttribute(
			'type',
			'checkbox'
		);
		expect( getControlByLabel( container, 'Notes' ) ).toBeInstanceOf(
			HTMLTextAreaElement
		);
		expect( getControlByLabel( container, 'Amount' ) ).toHaveAttribute(
			'type',
			'number'
		);
		expect( container.textContent ).toContain( 'Publish date' );
		expect(
			container.querySelector( 'input[type="date"]' )
		).not.toBeNull();
		expect( container.textContent ).toContain( 'Publish at' );
		expect(
			container.querySelector( 'input[type="datetime-local"]' )
		).not.toBeNull();

		act( () => root.unmount() );
		container.remove();
		expect( consoleError ).not.toHaveBeenCalled();
	} );
} );
