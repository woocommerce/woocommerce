/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BACKSPACE, DOWN, UP } from '@wordpress/keycodes';
import { createElement, Component, createRef } from '@wordpress/element';
import { Icon, search } from '@wordpress/icons';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Tags from './tags';

/**
 * A search control to allow user input to filter the options.
 */
class Control extends Component {
	constructor( props ) {
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

	updateSearch( onSearch ) {
		return ( event ) => {
			onSearch( event.target.value );
		};
	}

	onFocus( onSearch ) {
		const {
			isSearchable,
			setExpanded,
			showAllOnFocus,
			updateSearchOptions,
		} = this.props;

		return ( event ) => {
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

	onKeyDown( event ) {
		const {
			decrementSelectedIndex,
			incrementSelectedIndex,
			selected,
			onChange,
			query,
			setExpanded,
		} = this.props;

		if ( BACKSPACE === event.keyCode && ! query && selected.length ) {
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

		if ( multiple || ! selected.length ) {
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
						: null
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
		const selectedValue = selected.length ? selected[ 0 ].label : '';

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
						'with-value': this.getInputValue().length,
						'has-error': !! help,
						'is-disabled': disabled,
					}
				) }
				onClick={ ( event ) => {
					// Don't focus the input if the click event is from the error message.
					if (
						event.target.className !==
						'components-base-control__help'
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
				{ inlineTags && <Tags { ...this.props } /> }

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

Control.propTypes = {
	/**
	 * Bool to determine if tags should be rendered.
	 */
	hasTags: PropTypes.bool,
	/**
	 * Help text to be appended beneath the input.
	 */
	help: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	/**
	 * Render tags inside input, otherwise render below input.
	 */
	inlineTags: PropTypes.bool,
	/**
	 * Allow the select options to be filtered by search input.
	 */
	isSearchable: PropTypes.bool,
	/**
	 * ID of the main SelectControl instance.
	 */
	instanceId: PropTypes.number,
	/**
	 * A label to use for the main input.
	 */
	label: PropTypes.string,
	/**
	 * ID used for a11y in the listbox.
	 */
	listboxId: PropTypes.string,
	/**
	 * Function called when the input is blurred.
	 */
	onBlur: PropTypes.func,
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: PropTypes.func,
	/**
	 * Function called when input field is changed or focused.
	 */
	onSearch: PropTypes.func,
	/**
	 * A placeholder for the search input.
	 */
	placeholder: PropTypes.string,
	/**
	 * Search query entered by user.
	 */
	query: PropTypes.string,
	/**
	 * An array of objects describing selected values. If the label of the selected
	 * value is omitted, the Tag of that value will not be rendered inside the
	 * search box.
	 */
	selected: PropTypes.arrayOf(
		PropTypes.shape( {
			key: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] )
				.isRequired,
			label: PropTypes.string,
		} )
	),
	/**
	 * Show all options on focusing, even if a query exists.
	 */
	showAllOnFocus: PropTypes.bool,
	/**
	 * Control input autocomplete field, defaults: off.
	 */
	autoComplete: PropTypes.string,
};

export default Control;
