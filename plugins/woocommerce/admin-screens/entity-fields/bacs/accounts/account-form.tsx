/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Modal } from '@wordpress/components';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Field, Form } from '@wordpress/dataviews';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/use-recommended-components -- Fine on this experimental screen until the repo enables allowUseWithCaution.
import { Button, Stack } from '@wordpress/ui';
import { useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import type { BankAccount } from '../types';
import {
	formatSortCode,
	getSortCodeLabel,
	hasSortCode,
	parseSortCode,
} from './sort-code';

let countriesRequest: Promise< { value: string; label: string }[] > | null =
	null;

/**
 * Load the country list once per page.
 */
function getCountryElements(): Promise< { value: string; label: string }[] > {
	countriesRequest ??= apiFetch< { code: string; name: string }[] >( {
		path: '/wc/v3/data/countries?_fields=code,name',
	} )
		.then( ( countries ) =>
			countries.map( ( { code, name } ) => ( {
				value: code,
				label: decodeEntities( name ),
			} ) )
		)
		.catch( ( error: unknown ) => {
			countriesRequest = null;
			throw error;
		} );
	return countriesRequest;
}

/**
 * Get the account fields, with the sort code named and shown as the country has it.
 */
function getAccountFields( country: string ): Field< BankAccount >[] {
	return [
		{
			id: 'country_code',
			type: 'text',
			Edit: 'combobox',
			label: __( 'Country', 'woocommerce' ),
			getElements: getCountryElements,
			isValid: { required: true },
			// Sort codes differ by country, so changing the country clears it.
			setValue: ( { value } ) => ( {
				country_code: typeof value === 'string' ? value : '',
				sort_code: '',
			} ),
		},
		{
			id: 'account_name',
			type: 'text',
			label: __( 'Account name', 'woocommerce' ),
			isValid: { required: true },
		},
		{
			id: 'bank_name',
			type: 'text',
			label: __( 'Bank name', 'woocommerce' ),
		},
		{
			id: 'account_number',
			type: 'text',
			label: __( 'Account number', 'woocommerce' ),
		},
		...( hasSortCode( country )
			? [
					{
						id: 'sort_code',
						type: 'text' as const,
						label: getSortCodeLabel( country ),
						isValid: { required: true },
						getValue: ( { item }: { item: BankAccount } ) =>
							formatSortCode( item.sort_code, country ),
						setValue: ( { value }: { value: unknown } ) => ( {
							sort_code: parseSortCode(
								typeof value === 'string' ? value : '',
								country
							),
						} ),
					},
				]
			: [] ),
		{
			id: 'iban',
			type: 'text',
			label: __( 'IBAN', 'woocommerce' ),
		},
		{
			id: 'bic',
			type: 'text',
			label: __( 'BIC / SWIFT', 'woocommerce' ),
		},
	];
}

/**
 * Add or edit one bank account in a modal.
 */
export function AccountForm( {
	account,
	defaultCountry,
	onSave,
	onClose,
}: {
	account?: BankAccount | undefined;
	defaultCountry: string;
	onSave: ( account: BankAccount ) => void;
	onClose: () => void;
} ) {
	const [ data, setData ] = useState< BankAccount >( {
		account_name: '',
		account_number: '',
		bank_name: '',
		sort_code: '',
		iban: '',
		bic: '',
		...account,
		// The route stores a missing country as an empty string.
		// eslint-disable-next-line @typescript-eslint/prefer-nullish-coalescing -- An empty country also falls back.
		country_code: account?.country_code || defaultCountry,
	} );
	const country = data.country_code ?? '';
	const fields = useMemo( () => getAccountFields( country ), [ country ] );
	const form: Form = useMemo(
		() => ( {
			layout: { type: 'regular' },
			fields: fields.map( ( field ) => field.id ),
		} ),
		[ fields ]
	);
	const { validity, isValid } = useFormValidity( data, fields, form );
	return (
		<Modal
			title={
				account
					? __( 'Edit bank account', 'woocommerce' )
					: __( 'Add a bank account', 'woocommerce' )
			}
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
		>
			<Stack direction="column" gap="lg">
				<DataForm
					data={ data }
					fields={ fields }
					form={ form }
					validity={ validity }
					onChange={ ( edits ) => {
						setData( ( current ) => ( { ...current, ...edits } ) );
					} }
				/>
				<Stack direction="row" gap="sm" justify="end">
					<Button variant="outline" onClick={ onClose }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="solid"
						disabled={ ! isValid }
						onClick={ () => {
							onSave( data );
						} }
					>
						{ account
							? __( 'Save', 'woocommerce' )
							: __( 'Add account', 'woocommerce' ) }
					</Button>
				</Stack>
			</Stack>
		</Modal>
	);
}
