/**
 * External dependencies
 */
import { FormEvent, KeyboardEvent, UIEvent, useEffect, useRef } from 'react';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { useDebounce, useInstanceId } from '@wordpress/compose';
import { resolveSelect } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
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
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VariationsFilterProps } from './types';

const MIN_OPTIONS_COUNT_FOR_SEARCHING = 10;
const DEFAULT_TERMS_PER_PAGE = 10;

export function VariationsFilter( {
	initialValues,
	attribute,
	onFilter,
}: VariationsFilterProps ) {
	const [ selection, setSelection ] =
		useState< ProductAttributeTerm[ 'slug' ][] >( initialValues );
	const [ options, setOptions ] = useState< ProductAttributeTerm[] >( [] );
	const [ totalOptions, setTotalOptions ] = useState< number >( 0 );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ search, setSearch ] = useState( '' );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const inputSearchRef = useRef< HTMLInputElement >( null );
	const isDisabled = selection.length === 0;

	async function fetchOptions(
		attributeId: number,
		searchText = '',
		page = 1
	) {
		try {
			setIsLoading( true );

			const {
				getProductAttributeTerms,
				getProductAttributeTermsTotalCount,
			} = resolveSelect(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			);

			const sharedRequestArgs = {
				attribute_id: attributeId,
				hide_empty: true,
				per_page: DEFAULT_TERMS_PER_PAGE,
				page,
				search: searchText,
			};

			const terms = await getProductAttributeTerms<
				ProductAttributeTerm[]
			>( sharedRequestArgs );

			const totalTerms =
				await getProductAttributeTermsTotalCount< number >(
					sharedRequestArgs
				);

			if ( page > 1 ) {
				setOptions( ( current ) => [ ...current, ...terms ] );
			} else {
				setOptions( terms );
			}
			setTotalOptions( totalTerms );
		} catch {
		} finally {
			setIsLoading( false );
		}
	}

	useEffect( () => setSelection( initialValues ), [ initialValues ] );

	function handleClose() {
		setSearch( '' );
		setCurrentPage( 1 );
	}

	function dropdownToggleHandler( isOpen: boolean, onToggle: () => void ) {
		return async function handleClick() {
			onToggle();

			if ( ! isOpen ) {
				await fetchOptions( attribute.id );
			}
		};
	}

	async function handleScroll( event: UIEvent< HTMLDivElement > ) {
		if ( isLoading || options.length >= totalOptions ) return;

		const scrollableElement = event.currentTarget;

		const scrollableHeight =
			scrollableElement.scrollHeight - scrollableElement.clientHeight;

		if ( scrollableElement.scrollTop >= scrollableHeight ) {
			const nextPage = currentPage + 1;
			await fetchOptions( attribute.id, search, nextPage );
			setCurrentPage( nextPage );
		}
	}

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
		return async function handleReset(
			event: FormEvent< HTMLFormElement >
		) {
			event.preventDefault();

			if ( ! isDisabled ) {
				setSearch( '' );
				setSelection( [] );
				setCurrentPage( 1 );

				inputSearchRef.current?.focus();

				await fetchOptions( attribute.id );
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
			setOptions( [] );
			setCurrentPage( 1 );

			fetchOptions( attribute.id, value );
		},
		300
	);

	const searchInputId = useInstanceId( InputControl, 'search' ) as string;
	const optionCheckboxId = useInstanceId(
		CheckboxControl,
		'checkbox'
	) as string;

	return (
		<Dropdown
			className="woocommerce-product-variations-filter"
			// @ts-expect-error Property 'onClose' does not exist
			onClose={ handleClose }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					aria-expanded={ isOpen }
					icon={ isOpen ? chevronUp : chevronDown }
					variant="tertiary"
					onClick={ dropdownToggleHandler( isOpen, onToggle ) }
					className="woocommerce-product-variations-filter__toggle"
				>
					<span>
						{ sprintf(
							// translators: %s is the attribute name to filter by
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
					{ attribute.options.length >
						MIN_OPTIONS_COUNT_FOR_SEARCHING && (
						<div className="woocommerce-product-variations-filter__form-header">
							<label
								htmlFor={ searchInputId }
								aria-label={ __(
									'Search options',
									'woocommerce'
								) }
							>
								<InputControl
									ref={ inputSearchRef }
									id={ searchInputId }
									type="search"
									value={ search }
									suffix={ <Icon icon={ searchIcon } /> }
									onChange={ handleInputControlChange }
									onKeyDown={ handleKeyDown }
								/>
							</label>
						</div>
					) }
					<div
						className="woocommerce-product-variations-filter__form-body"
						onScroll={ handleScroll }
					>
						{ options.length > 0 ? (
							<ul className="woocommerce-product-variations-filter__form-list">
								{ options.map( ( option ) => (
									<li
										key={ option.slug }
										className="woocommerce-product-variations-filter__form-list-item"
									>
										<label
											htmlFor={ `${ optionCheckboxId }-${ option.slug }` }
											className="woocommerce-product-variations-filter__form-list-item-label"
										>
											<CheckboxControl
												id={ `${ optionCheckboxId }-${ option.slug }` }
												checked={ isOptionChecked(
													option.slug
												) }
												onChange={ optionChangeHandler(
													option.slug
												) }
											/>
											<span>{ option.name }</span>
										</label>
									</li>
								) ) }
							</ul>
						) : (
							! isLoading && (
								<div className="woocommerce-product-variations-filter__form-list-empty">
									{ __(
										'No options were found for that search',
										'woocommerce'
									) }
								</div>
							)
						) }

						{ isLoading && (
							<div className="woocommerce-product-variations-filter__loading">
								<Spinner />
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
