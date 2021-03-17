import { Page } from 'puppeteer';
import {
	setCheckbox,
	clearAndFillInput,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
} from '@woocommerce/e2e-utils';
import { DropdownTypeaheadField } from '../DropdownTypeaheadField';

export class StoreDetailsSection {
	page: Page;
	countryDropdown: DropdownTypeaheadField;

	constructor( page: Page ) {
		this.page = page;
		this.countryDropdown = new DropdownTypeaheadField(
			page,
			'#woocommerce-select-control'
		);
	}

	async fillAddress( address: string ) {
		await clearAndFillInput( '#inspector-text-control-0', address );
	}

	async fillAddressLineTwo( address: string ) {
		await clearAndFillInput( '#inspector-text-control-1', address );
	}

	async selectCountry( search: string, selector: string ) {
		await this.countryDropdown.search( search );
		await this.countryDropdown.select( selector );
	}

	async fillCity( city: string ) {
		await clearAndFillInput( '#inspector-text-control-2', city );
	}

	async fillPostalCode( postalCode: string ) {
		await clearAndFillInput( '#inspector-text-control-3', postalCode );
	}

	async selectSetupForClient() {
		setCheckbox( '.components-checkbox-control__input' );
	}

	async checkClientSetupCheckbox( selected: boolean ) {
		if ( selected ) {
			await verifyCheckboxIsSet( '.components-checkbox-control__input' );
		} else {
			await verifyCheckboxIsUnset(
				'.components-checkbox-control__input'
			);
		}
	}
}
