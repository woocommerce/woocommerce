/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, createElement } from '@wordpress/element';
import {
	debounce,
	escapeRegExp,
	identity,
	isArray,
	isNumber,
	noop,
} from 'lodash';
import { withFocusOutside, withSpokenMessages } from '@wordpress/components';
import { withInstanceId, compose } from '@wordpress/compose';
import { ChangeEvent, InputHTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import { Option, Selected } from './types';
import List from './list';
import Tags from './tags';
import Control from './control';

type Props = {
	/**
	 * Name to use for the autofill field, not used if no string is passed.
	 */
	autofill?: string;
	/**
	 * A renderable component (or string) which will be displayed before the `Control` of this component.
	 */
	children?: React.ReactNode;
	/**
	 * Class name applied to parent div.
	 */
	className?: string;
	/**
	 * Class name applied to control wrapper.
	 */
	controlClassName?: string;
	/**
	 * Allow the select options to be disabled.
	 */
	disabled?: boolean;
	/**
	 * Exclude already selected options from the options list.
	 */
	excludeSelectedOptions?: boolean;
	/**
	 * Add or remove items to the list of options after filtering,
	 * passed the array of filtered options and should return an array of options.
	 */
	onFilter?: (
		options: Array< Option >,
		query: string | null
	) => Array< Option >;
	/**
	 * Function to add regex expression to the filter the results, passed the search query.
	 */
	getSearchExpression?: ( query: string ) => string | RegExp | null;
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
	 * A label to use for the main input.
	 */
	label?: string;
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange?: ( selected: string | Option[], query?: string | null ) => void;
	/**
	 * Function run after search query is updated, passed previousOptions and query,
	 * should return a promise with an array of updated options.
	 */
	onSearch?: (
		previousOptions: Array< Option >,
		query: string | null
	) => Promise< Array< Option > >;
	/**
	 * An array of objects for the options list.  The option along with its key, label and
	 * value will be returned in the onChange event.
	 */
	options: Option[];
	/**
	 * A placeholder for the search input.
	 */
	placeholder?: string;
	/**
	 * Time in milliseconds to debounce the search function after typing.
	 */
	searchDebounceTime?: number;
	/**
	 * An array of objects describing selected values or optionally a string for a single value.
	 * If the label of the selected value is omitted, the Tag of that value will not
	 * be rendered inside the search box.
	 */
	selected?: Selected;
	/**
	 * A limit for the number of results shown in the options menu.  Set to 0 for no limit.
	 */
	maxResults?: number;
	/**
	 * Allow multiple option selections.
	 */
	multiple?: boolean;
	/**
	 * Render a 'Clear' button next to the input box to remove its contents.
	 */
	showClearButton?: boolean;
	/**
	 * The input type for the search box control.
	 */
	searchInputType?: InputHTMLAttributes< HTMLInputElement >[ 'type' ];
	/**
	 * Only show list options after typing a search query.
	 */
	hideBeforeSearch?: boolean;
	/**
	 * Show all options on focusing, even if a query exists.
	 */
	showAllOnFocus?: boolean;
	/**
	 * Render results list positioned statically instead of absolutely.
	 */
	staticList?: boolean;
	/**
	 * autocomplete prop for the Control input field.
	 */
	autoComplete?: string;
	/**
	 * Instance ID for the component.
	 */
	instanceId?: number;
	/**
	 * From withSpokenMessages
	 */
	debouncedSpeak?: ( message: string, assertive?: string ) => void;
	/**
	 * aria-label for the search input.
	 */
	ariaLabel?: string;
	/**
	 * On Blur callback.
	 */
	onBlur?: () => void;
	/**
	 * Enable virtual scrolling for large lists of options.
	 */
	virtualScroll?: boolean;
	/**
	 * Height in pixels for each virtual item. Required when virtualScroll is true.
	 */
	virtualItemHeight?: number;
	/**
	 * Maximum height in pixels for the virtualized list. Default is 300.
	 */
	virtualListHeight?: number;
};

type State = {
	isExpanded: boolean;
	isFocused: boolean;
	query: string | null;
	searchOptions: Option[];
	selectedIndex?: number | null;
};

const initialState: State = {
	isExpanded: false,
	isFocused: false,
	query: '',
	searchOptions: [],
};

/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
export class SelectControl extends Component< Props, State > {
	static defaultProps: Partial< Props > = {
		excludeSelectedOptions: true,
		getSearchExpression: identity,
		inlineTags: false,
		isSearchable: false,
		onChange: noop,
		onFilter: identity,
		onSearch: ( options: Option[] ) => Promise.resolve( options ),
		maxResults: 0,
		multiple: false,
		searchDebounceTime: 0,
		searchInputType: 'search',
		selected: [],
		showAllOnFocus: false,
		showClearButton: false,
		hideBeforeSearch: false,
		staticList: false,
		autoComplete: 'off',
		virtualScroll: false,
		virtualItemHeight: 35,
		virtualListHeight: 300,
	};

	node: HTMLDivElement | null = null;
	activePromise: Promise< void | Option[] > | null = null;
	cacheSearchOptions: Option[] = [];

	constructor( props: Props ) {
		super( props );

		const { selected, options, excludeSelectedOptions } = props;
		this.state = {
			...initialState,
			searchOptions: [],
			selectedIndex:
				selected && options?.length && ! excludeSelectedOptions
					? options.findIndex( ( option ) => option.key === selected )
					: null,
		};

		this.bindNode = this.bindNode.bind( this );
		this.decrementSelectedIndex = this.decrementSelectedIndex.bind( this );
		this.incrementSelectedIndex = this.incrementSelectedIndex.bind( this );
		this.onAutofillChange = this.onAutofillChange.bind( this );
		this.updateSearchOptions = debounce(
			this.updateSearchOptions.bind( this ),
			props.searchDebounceTime
		);
		this.search = this.search.bind( this );
		this.selectOption = this.selectOption.bind( this );
		this.setExpanded = this.setExpanded.bind( this );
		this.setNewValue = this.setNewValue.bind( this );
	}

	componentDidUpdate( prevProps: Props ) {
		const { selected } = this.props;
		if ( selected !== prevProps.selected ) {
			this.reset( selected );
		}
	}

	bindNode( node: HTMLDivElement ) {
		this.node = node;
	}

	reset( selected: Selected | Option[] | undefined = this.getSelected() ) {
		const { multiple, excludeSelectedOptions } = this.props;
		const newState = { ...initialState };
		// Reset selectedIndex if single selection.
		if (
			! multiple &&
			isArray( selected ) &&
			selected.length &&
			selected[ 0 ].key
		) {
			newState.selectedIndex = ! excludeSelectedOptions
				? this.props.options.findIndex(
						( i ) => i.key === selected[ 0 ].key
				  )
				: null;
		}

		this.setState( newState );
	}

	handleFocusOutside() {
		this.reset();
	}

	hasMultiple() {
		const { multiple, selected } = this.props;

		if ( ! multiple ) {
			return false;
		}

		if ( Array.isArray( selected ) ) {
			return selected.some( ( item ) => Boolean( item.label ) );
		}

		return Boolean( selected );
	}

	getSelected(): Selected | undefined {
		const { multiple, options, selected } = this.props;

		// Return the passed value if an array is provided.
		if ( multiple || Array.isArray( selected ) ) {
			return selected;
		}

		const selectedOption = options.find(
			( option ) => option.key === selected
		);
		return selectedOption ? ( [ selectedOption ] as Selected ) : [];
	}

	selectOption( option: Option ) {
		const { multiple, selected } = this.props;
		const newSelected =
			multiple && isArray( selected )
				? [ ...selected, option ]
				: [ option ];

		this.reset( newSelected );

		const oldSelected = Array.isArray( selected )
			? selected
			: [ { key: selected } ];
		const isSelected = oldSelected.findIndex(
			( val ) => val.key === option.key
		);
		if ( isSelected === -1 ) {
			this.setNewValue( newSelected );
		}

		// After selecting option, the list will reset and we'd need to correct selectedIndex.
		const newSelectedIndex = this.props.excludeSelectedOptions
			? // Since we're excluding the selected option, invalidate selection
			  // so re-focusing wont immediately set it to the neighbouring option.
			  null
			: this.getOptions().findIndex( ( i ) => i.key === option.key );

		this.setState( {
			selectedIndex: newSelectedIndex,
		} );
	}

	setNewValue( newValue: Option[] ) {
		const { onChange, selected, multiple } = this.props;
		const { query } = this.state;
		// Trigger a change if the selected value is different and pass back
		// an array or string depending on the original value.
		if ( multiple || Array.isArray( selected ) ) {
			onChange!( newValue, query );
		} else {
			onChange!( newValue.length > 0 ? newValue[ 0 ].key : '', query );
		}
	}

	decrementSelectedIndex() {
		const { selectedIndex } = this.state;
		const options = this.getOptions();
		const nextSelectedIndex = isNumber( selectedIndex )
			? ( selectedIndex === 0 ? options.length : selectedIndex ) - 1
			: options.length - 1;

		this.setState( { selectedIndex: nextSelectedIndex } );
	}

	incrementSelectedIndex() {
		const { selectedIndex } = this.state;
		const options = this.getOptions();
		const nextSelectedIndex = isNumber( selectedIndex )
			? ( selectedIndex + 1 ) % options.length
			: 0;

		this.setState( { selectedIndex: nextSelectedIndex } );
	}

	announce( searchOptions: Option[] ) {
		const { debouncedSpeak } = this.props;
		if ( ! debouncedSpeak ) {
			return;
		}
		if ( !! searchOptions.length ) {
			debouncedSpeak(
				sprintf(
					// translators: %d: number of results.
					_n(
						'%d result found, use up and down arrow keys to navigate.',
						'%d results found, use up and down arrow keys to navigate.',
						searchOptions.length,
						'woocommerce'
					),
					searchOptions.length
				),
				'assertive'
			);
		} else {
			debouncedSpeak( __( 'No results.', 'woocommerce' ), 'assertive' );
		}
	}

	getOptions() {
		const { isSearchable, options, excludeSelectedOptions } = this.props;
		const { searchOptions } = this.state;
		const selected = this.getSelected();
		const selectedKeys = isArray( selected )
			? selected.map( ( option ) => option.key )
			: [];
		const shownOptions = isSearchable ? searchOptions : options;

		if ( excludeSelectedOptions ) {
			return shownOptions?.filter(
				( option ) => ! selectedKeys.includes( option.key )
			);
		}
		return shownOptions;
	}

	getOptionsByQuery( options: Option[], query: string | null ) {
		const { getSearchExpression, maxResults, onFilter } = this.props;
		const filtered = [];

		// Create a regular expression to filter the options.
		const expression = getSearchExpression!(
			escapeRegExp( query ? query.trim() : '' )
		);
		const search = expression ? new RegExp( expression, 'i' ) : /^$/;

		for ( let i = 0; i < options.length; i++ ) {
			const option = options[ i ];

			// Merge label into keywords
			let { keywords = [] } = option;
			if ( typeof option.label === 'string' ) {
				keywords = [ ...keywords, option.label ];
			}

			const isMatch = keywords.some( ( keyword ) =>
				search.test( keyword )
			);
			if ( ! isMatch ) {
				continue;
			}

			filtered.push( option );

			// Abort early if max reached
			if ( maxResults && filtered.length === maxResults ) {
				break;
			}
		}

		return onFilter!( filtered, query );
	}

	setExpanded( value: boolean ) {
		this.setState( { isExpanded: value } );
	}

	search( query: string | null ) {
		const cacheSearchOptions = this.cacheSearchOptions || [];
		const searchOptions =
			query !== null && ! query.length && ! this.props.hideBeforeSearch
				? cacheSearchOptions
				: this.getOptionsByQuery( cacheSearchOptions, query );

		this.setState(
			{
				query,
				isFocused: true,
				searchOptions,
				selectedIndex:
					query && query?.length > 0
						? null
						: this.state.selectedIndex, // Only reset selectedIndex if we're actually searching.
			},
			() => {
				this.setState( {
					isExpanded: Boolean( this.getOptions()?.length ),
				} );
			}
		);

		this.updateSearchOptions( query );
	}

	updateSearchOptions( query: string | null ) {
		const { hideBeforeSearch, options, onSearch } = this.props;

		const promise = ( this.activePromise = Promise.resolve(
			onSearch!( options, query )
		).then( ( promiseOptions ) => {
			if ( promise !== this.activePromise ) {
				// Another promise has become active since this one was asked to resolve, so do nothing,
				// or else we might end triggering a race condition updating the state.
				return;
			}

			this.cacheSearchOptions = promiseOptions;

			// Get all options if `hideBeforeSearch` is enabled and query is not null.
			const searchOptions =
				query !== null && ! query.length && ! hideBeforeSearch
					? promiseOptions
					: this.getOptionsByQuery( promiseOptions, query );

			this.setState(
				{
					searchOptions,
					selectedIndex:
						query && query?.length > 0
							? null
							: this.state.selectedIndex, // Only reset selectedIndex if we're actually searching.
				},
				() => {
					this.setState( {
						isExpanded: Boolean( this.getOptions().length ),
					} );
					this.announce( searchOptions );
				}
			);
		} ) );
	}

	onAutofillChange( event: ChangeEvent< HTMLInputElement > ) {
		const { options } = this.props;
		const searchOptions = this.getOptionsByQuery(
			options,
			event.target.value
		);

		if ( searchOptions.length === 1 ) {
			this.selectOption( searchOptions[ 0 ] );
		}
	}

	render() {
		const {
			autofill,
			children,
			className,
			disabled,
			controlClassName,
			inlineTags,
			instanceId,
			isSearchable,
			options,
			virtualScroll,
			virtualItemHeight,
			virtualListHeight,
		} = this.props;
		const { isExpanded, isFocused, selectedIndex } = this.state;

		const hasMultiple = this.hasMultiple();
		const { key: selectedKey = '' } =
			( isNumber( selectedIndex ) && options[ selectedIndex ] ) || {};
		const listboxId = isExpanded
			? `woocommerce-select-control__listbox-${ instanceId }`
			: undefined;
		const activeId = isExpanded
			? `woocommerce-select-control__option-${ instanceId }-${ selectedKey }`
			: undefined;

		return (
			<div
				className={ classnames(
					'woocommerce-select-control',
					className,
					{
						'has-inline-tags': hasMultiple && inlineTags,
						'is-focused': isFocused,
						'is-searchable': isSearchable,
					}
				) }
				ref={ this.bindNode }
			>
				{ autofill && (
					<input
						onChange={ this.onAutofillChange }
						name={ autofill }
						type="text"
						className="woocommerce-select-control__autofill-input"
						tabIndex={ -1 }
					/>
				) }
				{ children }
				<Control
					help={ this.props.help }
					label={ this.props.label }
					inlineTags={ inlineTags }
					isSearchable={ isSearchable }
					isFocused={ isFocused }
					instanceId={ instanceId }
					searchInputType={ this.props.searchInputType }
					query={ this.state.query }
					placeholder={ this.props.placeholder }
					autoComplete={ this.props.autoComplete }
					multiple={ this.props.multiple }
					ariaLabel={ this.props.ariaLabel }
					onBlur={ this.props.onBlur }
					showAllOnFocus={ this.props.showAllOnFocus }
					activeId={ activeId }
					className={ controlClassName }
					disabled={ disabled }
					hasTags={ hasMultiple }
					isExpanded={ isExpanded }
					listboxId={ listboxId }
					onSearch={ this.search }
					selected={ this.getSelected() }
					onChange={ this.setNewValue }
					setExpanded={ this.setExpanded }
					updateSearchOptions={ this.updateSearchOptions }
					decrementSelectedIndex={ this.decrementSelectedIndex }
					incrementSelectedIndex={ this.incrementSelectedIndex }
					showClearButton={ this.props.showClearButton }
				/>
				{ ! inlineTags && hasMultiple && (
					<Tags
						onChange={ this.props.onChange! }
						showClearButton={ this.props.showClearButton }
						selected={ this.getSelected() }
					/>
				) }
				{ isExpanded && (
					<List
						instanceId={ instanceId! }
						selectedIndex={ selectedIndex }
						staticList={ this.props.staticList! }
						listboxId={ listboxId }
						node={ this.node }
						onSelect={ this.selectOption }
						onSearch={ this.search }
						options={ this.getOptions() }
						decrementSelectedIndex={ this.decrementSelectedIndex }
						incrementSelectedIndex={ this.incrementSelectedIndex }
						setExpanded={ this.setExpanded }
						virtualScroll={ virtualScroll }
						virtualItemHeight={ virtualItemHeight }
						virtualListHeight={ virtualListHeight }
					/>
				) }
			</div>
		);
	}
}

export default compose(
	withSpokenMessages,
	withInstanceId,
	withFocusOutside // this MUST be the innermost HOC as it calls handleFocusOutside
)( SelectControl ) as React.FC< Props >;
