/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BACKSPACE } from '@wordpress/keycodes';
import { Component, createRef } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Tags from './tags';

/**
 * A search control to allow user input to filter the options.
 */
class SearchControl extends Component {
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
		return event => {
			onSearch( event.target.value );
		};
	}

	onFocus( onSearch ) {
		return event => {
			this.setState( { isActive: true } );
			onSearch( event.target.value );
		};
	}

	onBlur() {
		this.setState( { isActive: false } );
	}

	onKeyDown( event ) {
		const { selected, onChange, query } = this.props;

		if ( BACKSPACE === event.keyCode && ! query && selected.length ) {
			onChange( [ ...selected.slice( 0, -1 ) ] );
		}
	}

	renderInput() {
		const {
			activeId,
			hasTags,
			inlineTags,
			instanceId,
			isExpanded,
			listboxId,
			onSearch,
			placeholder,
			query,
		} = this.props;
		const { isActive } = this.state;

		return <input
			className="woocommerce-autocomplete__control-input"
			id={ `woocommerce-autocomplete-${ instanceId }__control-input` }
			ref={ this.input }
			type={ 'search' }
			value={ query }
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
				hasTags && inlineTags ? `search-inline-input-${ instanceId }` : null
			}
		/>;
	}

	render() {
		const {
			hasTags,
			help,
			inlineTags,
			instanceId,
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
				className={ classnames( 'components-base-control', 'woocommerce-autocomplete__control', {
					empty: ! query.length,
					'is-active': isActive,
					'has-tags': inlineTags && hasTags,
					'with-value': query.length,
				} ) }
				onClick={ () => {
					this.input.current.focus();
				} }
			>
				<i className="material-icons-outlined">search</i>
				{ inlineTags && <Tags { ...this.props } /> }

				<div className="components-base-control__field">
					{ !! label &&
						<label
							htmlFor={ `woocommerce-autocomplete-${ instanceId }__control-input` }
							className="components-base-control__label"
						>
							{ label }
						</label>
					}
					{ this.renderInput() }
					{ inlineTags && <span id={ `search-inline-input-${ instanceId }` } className="screen-reader-text">
						{ __( 'Move backward for selected items', 'woocommerce-admin' ) }
					</span> }
					{ !! help &&
						<p
							id={ `woocommerce-autocomplete-${ instanceId }__help` }
							className="components-base-control__help"
						>
							{ help }
						</p>
					}
				</div>
			</div>
			/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		);
	}
}

SearchControl.propTypes = {
	/**
	 * Bool to determine if tags should be rendered.
	 */
	hasTags: PropTypes.bool,
	/**
	 * Help text to be appended beneath the input.
	 */
	help: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.node,
	] ),
	/**
	 * Render tags inside input, otherwise render below input.
	 */
	inlineTags: PropTypes.bool,
	/**
	 * ID of the main Autocomplete instance.
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
			key: PropTypes.oneOfType( [
				PropTypes.number,
				PropTypes.string,
			] ).isRequired,
			label: PropTypes.string,
		} )
	),
};

export default SearchControl;
