/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BACKSPACE, DOWN, UP } from '@wordpress/keycodes';
import { createElement, Component, createRef } from '@wordpress/element';
import { Icon, search } from '@wordpress/icons';
import classnames from 'classnames';
import { isArray } from 'lodash';
import {
	RefObject,
	ChangeEvent,
	FocusEvent,
	KeyboardEvent,
	InputHTMLAttributes,
} from 'react';

/**
 * Internal dependencies
 */
import Tags from './tags';
import { Selected, Option } from './types';

type Props = {
	/**
	 * Bool to determine if tags should be rendered.
	 */
	hasTags?: boolean;
	/**
	 * Help text to be appended beneath the input.
	 */
	help?: React.ReactNode;
	/**
	 * Render tags inside input, otherwise render below input.
	 */
	inlineTags?: boolean;
	/**
	 * Allow the select options to be filtered by search input.
	 */
	isSearchable?: boolean;
	/**
	 * ID of the main SelectControl instance.
	 */
	instanceId?: number;
	/**
	 * A label to use for the main input.
	 */
	label?: string;
	/**
	 * ID used for a11y in the listbox.
	 */
	listboxId?: string;
	/**
	 * Function called when the input is blurred.
	 */
	onBlur?: () => void;
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: ( selected: Option[] ) => void;
	/**
	 * Function called when input field is changed or focused.
	 */
	onSearch: ( query: string ) => void;
	/**
	 * A placeholder for the search input.
	 */
	placeholder?: string;
	/**
	 * Search query entered by user.
	 */
	query?: string | null;
	/**
	 * An array of objects describing selected values. If the label of the selected
	 * value is omitted, the Tag of that value will not be rendered inside the
	 * search box.
	 */
	selected?: Selected;
	/**
	 * Show all options on focusing, even if a query exists.
	 */
	showAllOnFocus?: boolean;
	/**
	 * Control input autocomplete field, defaults: off.
	 */
	autoComplete?: string;
	/**
	 * Function to execute when the control should be expanded or collapsed.
	 */
	setExpanded: ( expanded: boolean ) => void;
	/**
	 * Function to execute when the search value changes.
	 */
	updateSearchOptions: ( query: string ) => void;
	/**
	 * Function to execute when keyboard navigation should decrement the selected index.
	 */
	decrementSelectedIndex: () => void;
	/**
	 * Function to execute when keyboard navigation should increment the selected index.
	 */
	incrementSelectedIndex: () => void;
	/**
	 * Multi-select mode allows multiple options to be selected.
	 */
	multiple?: boolean;
	/**
	 * Is the control currently focused.
	 */
	isFocused?: boolean;
	/**
	 * ID for accessibility purposes. aria-activedescendant will be set to this value.
	 */
	activeId?: string;
	/**
	 * Disable the control.
	 */
	disabled?: boolean;
	/**
	 * Is the control currently expanded. This is for accessibility purposes.
	 */
	isExpanded?: boolean;
	/**
	 * The type of input to use for the search field.
	 */
	searchInputType?: InputHTMLAttributes< HTMLInputElement >[ 'type' ];
	/**
	 * The aria label for the search input.
	 */
	ariaLabel?: string;
	/**
	 * Class name to be added to the input.
	 */
	className?: string;
	/**
	 * Show the clear button.
	 */
	showClearButton?: boolean;
};

type State = {
	isActive: boolean;
};

/**
 * A search control to allow user input to filter the options.
 */
class Control extends Component< Props, State > {
	input: RefObject< HTMLInputElement >;

	constructor( props: Props ) {
		super( props );
		this.state = {
			isActive: false,
		};

		this.input = createRef();

		this.updateSearch = this.updateSearch.bind( this );
		this.onFocus = this.onFocus.bind( this );
		this.onBlur = this.onBlur.bind( this );
		this.onKeyDown = this.onKeyDown.bind( this );
	}

	updateSearch( onSearch: ( query: string ) => void ) {
		return ( event: ChangeEvent< HTMLInputElement > ) => {
			onSearch( event.target.value );
		};
	}

	onFocus( onSearch: ( query: string ) => void ) {
		const {
			isSearchable,
			setExpanded,
			showAllOnFocus,
			updateSearchOptions,
		} = this.props;

		return ( event: FocusEvent< HTMLInputElement > ) => {
			this.setState( { isActive: true } );
			if ( isSearchable && showAllOnFocus ) {
				event.target.select();
				updateSearchOptions( '' );
			} else if ( isSearchable ) {
				onSearch( event.target.value );
			} else {
				setExpanded( true );
			}
		};
	}

	onBlur() {
		const { onBlur } = this.props;

		if ( typeof onBlur === 'function' ) {
			onBlur();
		}

		this.setState( { isActive: false } );
	}

	onKeyDown( event: KeyboardEvent< HTMLInputElement > ) {
		const {
			decrementSelectedIndex,
			incrementSelectedIndex,
			selected,
			onChange,
			query,
			setExpanded,
		} = this.props;

		if (
			BACKSPACE === event.keyCode &&
			! query &&
			isArray( selected ) &&
			selected.length
		) {
			onChange( [ ...selected.slice( 0, -1 ) ] );
		}

		if ( DOWN === event.keyCode ) {
			incrementSelectedIndex();
			setExpanded( true );
			event.preventDefault();
			event.stopPropagation();
		}

		if ( UP === event.keyCode ) {
			decrementSelectedIndex();
			setExpanded( true );
			event.preventDefault();
			event.stopPropagation();
		}
	}

	renderButton() {
		const { multiple, selected } = this.props;

		if ( multiple || ! isArray( selected ) || ! selected.length ) {
			return null;
		}

		return (
			<div className="woocommerce-select-control__control-value">
				{ selected[ 0 ].label }
			</div>
		);
	}

	renderInput() {
		const {
			activeId,
			disabled,
			hasTags,
			inlineTags,
			instanceId,
			isExpanded,
			isSearchable,
			listboxId,
			onSearch,
			placeholder,
			searchInputType,
			autoComplete,
		} = this.props;
		const { isActive } = this.state;

		return (
			<input
				autoComplete={ autoComplete || 'off' }
				className="woocommerce-select-control__control-input"
				id={ `woocommerce-select-control-${ instanceId }__control-input` }
				ref={ this.input }
				type={ isSearchable ? searchInputType : 'button' }
				value={ this.getInputValue() }
				placeholder={ isActive ? placeholder : '' }
				onChange={ this.updateSearch( onSearch ) }
				onFocus={ this.onFocus( onSearch ) }
				onBlur={ this.onBlur }
				onKeyDown={ this.onKeyDown }
				role="combobox"
				aria-autocomplete="list"
				aria-expanded={ isExpanded }
				aria-haspopup="true"
				aria-owns={ listboxId }
				aria-controls={ listboxId }
				aria-activedescendant={ activeId }
				aria-describedby={
					hasTags && inlineTags
						? `search-inline-input-${ instanceId }`
						: undefined
				}
				disabled={ disabled }
				aria-label={ this.props.ariaLabel ?? this.props.label }
			/>
		);
	}

	getInputValue() {
		const {
			inlineTags,
			isFocused,
			isSearchable,
			multiple,
			query,
			selected,
		} = this.props;
		const selectedValue =
			isArray( selected ) &&
			selected.length &&
			typeof selected[ 0 ].label === 'string'
				? selected[ 0 ].label
				: '';

		// Show the selected value for simple select dropdowns.
		if ( ! multiple && ! isFocused && ! inlineTags ) {
			return selectedValue;
		}

		// Show the search query when focused on searchable controls.
		if ( isSearchable && isFocused && query ) {
			return query;
		}

		return '';
	}

	render() {
		const {
			className,
			disabled,
			hasTags,
			help,
			inlineTags,
			instanceId,
			isSearchable,
			label,
			query,
			onChange,
			showClearButton,
		} = this.props;
		const { isActive } = this.state;

		return (
			// Disable reason: The div below visually simulates an input field. Its
			// child input is the actual input and responds accordingly to all keyboard
			// events, but click events need to be passed onto the child input. There
			// is no appropriate aria role for describing this situation, which is only
			// for the benefit of sighted users.
			/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
			<div
				className={ classnames(
					'components-base-control',
					'woocommerce-select-control__control',
					className,
					{
						empty: ! query || query.length === 0,
						'is-active': isActive,
						'has-tags': inlineTags && hasTags,
						'with-value': this.getInputValue()?.length,
						'has-error': !! help,
						'is-disabled': disabled,
					}
				) }
				onClick={ ( event ) => {
					// Don't focus the input if the click event is from the error message.
					if (
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore - event.target.className is not in the type definition.
						event.target.className !==
							'components-base-control__help' &&
						this.input.current
					) {
						this.input.current.focus();
					}
				} }
			>
				{ isSearchable && (
					<Icon
						className="woocommerce-select-control__control-icon"
						icon={ search }
					/>
				) }
				{ inlineTags && (
					<Tags
						onChange={ onChange }
						showClearButton={ showClearButton }
						selected={ this.props.selected }
					/>
				) }

				<div className="components-base-control__field">
					{ !! label && (
						<label
							htmlFor={ `woocommerce-select-control-${ instanceId }__control-input` }
							className="components-base-control__label"
						>
							{ label }
						</label>
					) }
					{ this.renderInput() }
					{ inlineTags && (
						<span
							id={ `search-inline-input-${ instanceId }` }
							className="screen-reader-text"
						>
							{ __(
								'Move backward for selected items',
								'woocommerce'
							) }
						</span>
					) }
					{ !! help && (
						<p
							id={ `woocommerce-select-control-${ instanceId }__help` }
							className="components-base-control__help"
						>
							{ help }
						</p>
					) }
				</div>
			</div>
			/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		);
	}
}

export default Control;
