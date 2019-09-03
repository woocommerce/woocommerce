/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, withFocusOutside, withSpokenMessages } from '@wordpress/components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { debounce, escapeRegExp } from 'lodash';
import { ENTER, ESCAPE, UP, DOWN, LEFT, TAB, RIGHT } from '@wordpress/keycodes';
import { withInstanceId, compose } from '@wordpress/compose';

function filterOptions( search, options = [], exclude = [], maxResults = 10 ) {
	const filtered = [];
	for ( let i = 0; i < options.length; i++ ) {
		const option = options[ i ];

		if ( exclude.includes( option.value.id ) ) {
			continue;
		}

		// Merge label into keywords
		let { keywords = [] } = option;
		if ( 'string' === typeof option.label ) {
			keywords = [ ...keywords, option.label ];
		}

		const isMatch = keywords.some( keyword => search.test( keyword ) );
		if ( ! isMatch ) {
			continue;
		}

		filtered.push( option );

		// Abort early if max reached
		if ( filtered.length === maxResults ) {
			break;
		}
	}

	return filtered;
}

export class Autocomplete extends Component {
	static getInitialState() {
		return {
			search: /./,
			selectedIndex: 0,
			query: undefined,
			filteredOptions: [],
		};
	}

	constructor() {
		super( ...arguments );

		this.bindNode = this.bindNode.bind( this );
		this.select = this.select.bind( this );
		this.reset = this.reset.bind( this );
		this.search = this.search.bind( this );
		this.handleKeyDown = this.handleKeyDown.bind( this );
		this.debouncedLoadOptions = debounce( this.loadOptions, 400 );

		this.state = this.constructor.getInitialState();
	}

	bindNode( node ) {
		this.node = node;
	}

	select( option ) {
		const { onSelect, completer: { getOptionCompletion } } = this.props;
		const { query } = this.state;

		if ( option.isDisabled ) {
			return;
		}

		if ( getOptionCompletion ) {
			const completion = getOptionCompletion( option.value, query );
			onSelect( completion );
		}

		// Reset autocomplete state after insertion rather than before
		// so insertion events don't cause the completion menu to redisplay.
		this.reset();
	}

	reset() {
		const isMounted = !! this.node;

		// Autocompletions may replace the block containing this component,
		// so we make sure it is mounted before resetting the state.
		if ( isMounted ) {
			this.setState( this.constructor.getInitialState() );
		}
	}

	handleFocusOutside() {
		this.reset();
	}

	announce( filteredOptions ) {
		const { debouncedSpeak } = this.props;
		if ( ! debouncedSpeak ) {
			return;
		}
		if ( !! filteredOptions.length ) {
			debouncedSpeak(
				sprintf(
					_n(
						'%d result found, use up and down arrow keys to navigate.',
						'%d results found, use up and down arrow keys to navigate.',
						filteredOptions.length,
						'woocommerce-admin'
					),
					filteredOptions.length
				),
				'assertive'
			);
		} else {
			debouncedSpeak( __( 'No results.', 'woocommerce-admin' ), 'assertive' );
		}
	}

	/**
	 * Load options for an autocompleter.
	 *
	 * @param {Completer} completer The autocompleter.
	 * @param {string}    query     The query, if any.
	 */
	loadOptions( completer, query ) {
		const { options } = completer;

		if ( ! query ) {
			this.setState( {
				options: [],
				filteredOptions: [],
				selectedIndex: 0,
			} );

			return;
		}

		/*
		 * We support both synchronous and asynchronous retrieval of completer options
		 * but internally treat all as async so we maintain a single, consistent code path.
		 *
		 * Because networks can be slow, and the internet is wonderfully unpredictable,
		 * we don't want two promises updating the state at once. This ensures that only
		 * the most recent promise will act on `optionsData`. This doesn't use the state
		 * because `setState` is batched, and so there's no guarantee that setting
		 * `activePromise` in the state would result in it actually being in `this.state`
		 * before the promise resolves and we check to see if this is the active promise or not.
		 */
		const promise = ( this.activePromise = Promise.resolve(
			typeof options === 'function' ? options( query ) : options
		).then( optionsData => {
			if ( ! optionsData || ! this.state.query ) {
				return;
			}
			const { selected } = this.props;
			if ( promise !== this.activePromise ) {
				// Another promise has become active since this one was asked to resolve, so do nothing,
				// or else we might end triggering a race condition updating the state.
				return;
			}
			const keyedOptions = optionsData.map( ( optionData, optionIndex ) => ( {
				key: optionIndex,
				value: optionData,
				label: completer.getOptionLabel( optionData, query ),
				keywords: completer.getOptionKeywords ? completer.getOptionKeywords( optionData ) : [],
				isDisabled: completer.isOptionDisabled ? completer.isOptionDisabled( optionData ) : false,
			} ) );

			const filteredOptions = filterOptions( this.state.search, keyedOptions, selected );
			const selectedIndex =
				filteredOptions.length === this.state.filteredOptions.length ? this.state.selectedIndex : 0;
			this.setState( {
				options: keyedOptions,
				filteredOptions,
				selectedIndex,
			} );
			this.announce( filteredOptions );
		} ) );
	}

	search( event ) {
		const { query: wasQuery } = this.state;
		const { completer = {}, selected } = this.props;
		const container = event.target;

		// look for the trigger prefix and search query just before the cursor location
		const query = container.value.trim();
		// asynchronously load the options for the open completer
		if ( completer && query !== wasQuery ) {
			if ( completer.isDebounced ) {
				this.debouncedLoadOptions( completer, query );
			} else {
				this.loadOptions( completer, query );
			}
		}
		// create a regular expression to filter the options
		const expression = 'undefined' !== typeof completer.getSearchExpression
			? completer.getSearchExpression( escapeRegExp( query ) )
			: escapeRegExp( query );
		// if there is no expression, match empty string
		const search = expression ? new RegExp( expression, 'i' ) : /^$/;
		// filter the options we already have
		const filteredOptions = filterOptions( search, this.state.options, selected );
		// update the state
		this.setState( { selectedIndex: 0, filteredOptions, search, query } );
		// announce the count of filtered options but only if they have loaded
		if ( this.state.options ) {
			this.announce( filteredOptions );
		}
	}

	getOptions() {
		const { allowFreeText, completer } = this.props;
		const { getFreeTextOptions } = completer;
		const { filteredOptions, query } = this.state;

		const additionalOptions = allowFreeText && getFreeTextOptions ? getFreeTextOptions( query ) : [];
		return additionalOptions.concat( filteredOptions );
	}

	handleKeyDown( event ) {
		const options = this.getOptions();
		const { selectedIndex } = this.state;
		if ( options.length === 0 ) {
			return;
		}
		let nextSelectedIndex;
		switch ( event.keyCode ) {
			case UP:
				nextSelectedIndex = ( selectedIndex === 0 ? options.length : selectedIndex ) - 1;
				this.setState( { selectedIndex: nextSelectedIndex } );
				break;

			case TAB:
			case DOWN:
				nextSelectedIndex = ( selectedIndex + 1 ) % options.length;
				this.setState( { selectedIndex: nextSelectedIndex } );
				break;

			case ENTER:
				this.select( options[ selectedIndex ] );
				break;

			case LEFT:
			case RIGHT:
			case ESCAPE:
				this.reset();
				return;

			default:
				return;
		}

		// Any handled keycode should prevent original behavior. This relies on
		// the early return in the default case.
		event.preventDefault();
		event.stopPropagation();
	}

	toggleKeyEvents( isListening ) {
		// This exists because we must capture ENTER key presses before RichText.
		// It seems that react fires the simulated capturing events after the
		// native browser event has already bubbled so we can't stopPropagation
		// and avoid RichText getting the event from TinyMCE, hence we must
		// register a native event handler.
		const handler = isListening ? 'addEventListener' : 'removeEventListener';
		this.node[ handler ]( 'keydown', this.handleKeyDown, true );
	}

	isExpanded( props, state ) {
		const { filteredOptions, query } = state;

		return filteredOptions.length > 0 || ( props.completer.getFreeTextOptions && query );
	}

	componentDidUpdate( prevProps, prevState ) {
		const isExpanded = this.isExpanded( this.props, this.state );
		const wasExpanded = this.isExpanded( prevProps, prevState );
		if ( isExpanded && ! wasExpanded ) {
			this.toggleKeyEvents( true );
		} else if ( ! isExpanded && wasExpanded ) {
			this.toggleKeyEvents( false );
		}
	}

	componentWillUnmount() {
		this.toggleKeyEvents( false );
		this.debouncedLoadOptions.cancel();
	}

	render() {
		const { children, instanceId, completer: { className = '' }, staticResults } = this.props;
		const { selectedIndex } = this.state;
		const isExpanded = this.isExpanded( this.props, this.state );
		const options = isExpanded ? this.getOptions() : [];
		const { key: selectedKey = '' } = options[ selectedIndex ] || {};
		const listBoxId = isExpanded ? `woocommerce-search__autocomplete-${ instanceId }` : null;
		const activeId = isExpanded
			? `woocommerce-search__autocomplete-${ instanceId }-${ selectedKey }`
			: null;
		const resultsClasses = classnames( 'woocommerce-search__autocomplete-results', {
			'is-static-results': staticResults,
		} );

		return (
			<div ref={ this.bindNode } className="woocommerce-search__autocomplete">
				{ children( { isExpanded, listBoxId, activeId, onChange: this.search } ) }
				{ isExpanded && (
					<div id={ listBoxId } role="listbox" className={ resultsClasses }>
						{ options.map( ( option, index ) => (
								<Button
									key={ option.key }
									id={ `woocommerce-search__autocomplete-${ instanceId }-${ option.key }` }
									role="option"
									aria-selected={ index === selectedIndex }
									disabled={ option.isDisabled }
									className={ classnames( 'woocommerce-search__autocomplete-result', className, {
										'is-selected': index === selectedIndex,
									} ) }
									/*
									 * On FF and Safari, if <Button> contains interior DOM nodes (i.e., <span>)
									 * `handleFocusOutside` will be triggered before `onClick` and close the autocomplete.
									 * Prevent focus from shifting to these nodes with onMouseDown.
									 */
									onMouseDown={ ( e ) => e.preventDefault() }
									onClick={ () => this.select( option ) }
								>
									{ option.label }
								</Button>
							) ) }
					</div>
				) }
			</div>
		);
	}
}

export default compose( [
	withSpokenMessages,
	withInstanceId,
	withFocusOutside, // this MUST be the innermost HOC as it calls handleFocusOutside
] )( Autocomplete );
