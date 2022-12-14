/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { staticFormDataToObject } from '../static-form-helper';

describe( 'staticFormDataToObject', () => {
	it( 'should create object of all nested input, select, textarea fields within form element', () => {
		render(
			<form id="testform">
				<div>
					<input type="text" name="Name" value="John" />
					<select name="Car">
						<option value="volvo">Volvo</option>
						<option value="saab">Saab</option>
						<option value="mercedes">Mercedes</option>
						<option value="audi" selected>
							Audi
						</option>
					</select>
				</div>
				<textarea name="Description" value="Tall" />
			</form>
		);
		type FormElements = {
			testform?: HTMLFormElement;
		} & HTMLCollectionOf< HTMLFormElement >;
		const forms: FormElements = document.forms;
		let formObject;
		if ( forms.testform ) {
			formObject = staticFormDataToObject( forms.testform );
		}
		expect( formObject ).toEqual( {
			Name: 'John',
			Car: 'audi',
			Description: 'Tall',
		} );
	} );

	it( 'should add array of items for select multiple', () => {
		render(
			<form id="testform">
				<div>
					<select name="Car" multiple>
						<option value="volvo" selected>
							Volvo
						</option>
						<option value="saab">Saab</option>
						<option value="mercedes">Mercedes</option>
						<option value="audi" selected>
							Audi
						</option>
					</select>
				</div>
			</form>
		);
		type FormElements = {
			testform?: HTMLFormElement;
		} & HTMLCollectionOf< HTMLFormElement >;
		const forms: FormElements = document.forms;
		let formObject;
		if ( forms.testform ) {
			formObject = staticFormDataToObject( forms.testform );
		}
		expect( formObject ).toEqual( {
			Car: [ 'volvo', 'audi' ],
		} );
	} );

	it( 'should skip input types of type button, image, and submit', () => {
		render(
			<form id="testform">
				<div>
					<input type="text" name="Name" value="John" />
					<input type="button" value="Add to favorites"></input>
					<input type="image" name="Image" alt="Image" />
					<input type="submit" value="Submit" />
				</div>
			</form>
		);
		type FormElements = {
			testform?: HTMLFormElement;
		} & HTMLCollectionOf< HTMLFormElement >;
		const forms: FormElements = document.forms;
		let formObject;
		if ( forms.testform ) {
			formObject = staticFormDataToObject( forms.testform );
		}
		expect( formObject ).toEqual( {
			Name: 'John',
		} );
	} );
} );
