/**
 * External dependencies
 */
import { FormEvent, KeyboardEvent, useEffect } from 'react';
import { ProductAttribute } from '@woocommerce/data';
import { useDebounce } from '@wordpress/compose';
import { createElement, useState, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Icon,
	chevronDown,
	chevronUp,
	search as searchIcon,
} from '@wordpress/icons';
import {
	Button,
	CheckboxControl,
	Dropdown,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VariationsFilterProps } from './types';

export function VariationsFilter( {
	initialValues,
	attribute,
	onFilter,
}: VariationsFilterProps ) {
	const [ selection, setSelection ] =
		useState< ProductAttribute[ 'options' ] >( initialValues );
	const [ search, setSearch ] = useState( '' );
	const isDisabled = selection.length === 0;

	useEffect( () => setSelection( initialValues ), [ initialValues ] );

	function isOptionChecked( option: string ) {
		return selection.includes( option );
	}

	function optionChangeHandler( option: string ) {
		return function handleOptionChange( value: boolean ) {
			setSelection( ( current ) => {
				if ( value ) {
					return [ ...current, option ];
				}

				return current.reduce< string[] >(
					function removeSelectedOption(
						previousSelection,
						selectedOption
					) {
						if ( selectedOption === option ) {
							return previousSelection;
						}
						return [ ...previousSelection, selectedOption ];
					},
					[]
				);
			} );
		};
	}

	function submitHandler( close: () => void ) {
		return function handleSubmit( event: FormEvent< HTMLFormElement > ) {
			event.preventDefault();

			onFilter( selection );
			close();
		};
	}

	function resetHandler() {
		return function handleReset( event: FormEvent< HTMLFormElement > ) {
			event.preventDefault();

			if ( ! isDisabled ) {
				setSelection( [] );
			}
		};
	}

	function handleKeyDown( event: KeyboardEvent< HTMLInputElement > ) {
		if ( event.code === 'Enter' ) {
			event.preventDefault();
		}
	}

	const handleInputControlChange = useDebounce(
		function handleInputControlChange( value: string ) {
			setSearch( value );
		},
		300
	);

	const options = useMemo(
		function getFilteredOptions() {
			const escapedInputValue = search.replace(
				/[.*+?^${}()|[\]\\]/g,
				'\\$&'
			);
			const regexp = new RegExp( escapedInputValue, 'i' );

			return attribute.options.filter( ( option ) =>
				regexp.test( option )
			);
		},
		[ attribute, search ]
	);

	return (
		<Dropdown
			className="woocommerce-product-variations-filter"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					aria-expanded={ isOpen }
					icon={ isOpen ? chevronUp : chevronDown }
					variant="tertiary"
					onClick={ onToggle }
					className="woocommerce-product-variations-filter__toggle"
				>
					<span>
						{ sprintf(
							__( 'Any %s', 'woocommerce' ),
							attribute.name
						) }
					</span>
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<form
					className="woocommerce-product-variations-filter__form"
					noValidate
					onSubmit={ submitHandler( onClose ) }
					onReset={ resetHandler() }
				>
					{ attribute.options.length >= 5 && (
						<div className="woocommerce-product-variations-filter__form-header">
							<label
								aria-label={ __(
									'Search options',
									'woocommerce'
								) }
							>
								<InputControl
									type="search"
									value={ search }
									suffix={ <Icon icon={ searchIcon } /> }
									onChange={ handleInputControlChange }
									onKeyDown={ handleKeyDown }
								/>
							</label>
						</div>
					) }
					<div className="woocommerce-product-variations-filter__form-body">
						{ options.length > 0 ? (
							<ul
								className="woocommerce-product-variations-filter__form-list"
								role="combobox"
							>
								{ options.map( ( option ) => (
									<li
										key={ option }
										value={ option }
										className="woocommerce-product-variations-filter__form-list-item"
										role="option"
									>
										<label className="woocommerce-product-variations-filter__form-list-item-label">
											<CheckboxControl
												checked={ isOptionChecked(
													option
												) }
												onChange={ optionChangeHandler(
													option
												) }
											/>
											<span>{ option }</span>
										</label>
									</li>
								) ) }
							</ul>
						) : (
							<div className="woocommerce-product-variations-filter__form-list-empty">
								{ __(
									'No options were found for that search',
									'woocommerce'
								) }
							</div>
						) }
					</div>
					<div className="woocommerce-product-variations-filter__form-footer">
						<Button
							type="reset"
							variant="secondary"
							aria-disabled={ isDisabled }
						>
							{ __( 'Reset', 'woocommerce' ) }
						</Button>
						<Button type="submit" variant="primary">
							{ __( 'Filter', 'woocommerce' ) }
						</Button>
					</div>
				</form>
			) }
		/>
	);
}
