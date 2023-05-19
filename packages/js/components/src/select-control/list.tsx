/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { RefObject } from 'react';
import classnames from 'classnames';
import { createElement, Component, createRef } from '@wordpress/element';
import { isEqual, isNumber } from 'lodash';
import { ENTER, ESCAPE, UP, DOWN, LEFT, RIGHT, TAB } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import { Option } from './types';

type Props = {
	/**
	 * ID of the main SelectControl instance.
	 */
	listboxId?: string;
	/**
	 * ID used for a11y in the listbox.
	 */
	instanceId: number;
	/**
	 * Parent node to bind keyboard events to.
	 */
	node: HTMLElement | null;
	/**
	 * Function to execute when an option is selected.
	 */
	onSelect: ( option: Option ) => void;
	/**
	 * Array of options to display.
	 */
	options: Array< Option >;
	/**
	 * Integer for the currently selected item.
	 */
	selectedIndex: number | null | undefined;
	/**
	 * Bool to determine if the list should be positioned absolutely or staticly.
	 */
	staticList: boolean;
	/**
	 * Function to execute when keyboard navigation should decrement the selected index.
	 */
	decrementSelectedIndex: () => void;
	/**
	 * Function to execute when keyboard navigation should increment the selected index.
	 */
	incrementSelectedIndex: () => void;
	/**
	 * Function to execute when the search value changes.
	 */
	onSearch: ( option: string | null ) => void;
	/**
	 * Function to execute when the list should be expanded or collapsed.
	 */
	setExpanded: ( expanded: boolean ) => void;
};

/**
 * A list box that displays filtered options after search.
 */
class List extends Component< Props > {
	optionRefs: { [ key: number ]: RefObject< HTMLButtonElement > };
	listbox: RefObject< HTMLDivElement >;

	constructor( props: Props ) {
		super( props );

		this.handleKeyDown = this.handleKeyDown.bind( this );
		this.select = this.select.bind( this );
		this.optionRefs = {};
		this.listbox = createRef();
	}

	componentDidUpdate( prevProps: Props ) {
		const { options, selectedIndex } = this.props;

		// Remove old option refs to avoid memory leaks.
		if ( ! isEqual( options, prevProps.options ) ) {
			this.optionRefs = {};
		}

		if (
			selectedIndex !== prevProps.selectedIndex &&
			isNumber( selectedIndex )
		) {
			this.scrollToOption( selectedIndex );
		}
	}

	getOptionRef( index: number ) {
		if ( ! this.optionRefs.hasOwnProperty( index ) ) {
			this.optionRefs[ index ] = createRef();
		}

		return this.optionRefs[ index ];
	}

	select( option: Option ) {
		const { onSelect } = this.props;

		if ( option.isDisabled ) {
			return;
		}

		onSelect( option );
	}

	scrollToOption( index: number ) {
		const listbox = this.listbox.current;

		if ( ! listbox ) {
			return;
		}

		if ( listbox.scrollHeight <= listbox.clientHeight ) {
			return;
		}

		if ( ! this.optionRefs[ index ] ) {
			return;
		}

		const option = this.optionRefs[ index ].current;
		if ( ! option ) {
			// eslint-disable-next-line no-console
			console.warn( 'Option not found, index:', index );
			return;
		}

		const scrollBottom = listbox.clientHeight + listbox.scrollTop;
		const elementBottom = option.offsetTop + option.offsetHeight;
		if ( elementBottom > scrollBottom ) {
			listbox.scrollTop = elementBottom - listbox.clientHeight;
		} else if ( option.offsetTop < listbox.scrollTop ) {
			listbox.scrollTop = option.offsetTop;
		}
	}

	handleKeyDown( event: KeyboardEvent ) {
		const {
			decrementSelectedIndex,
			incrementSelectedIndex,
			options,
			onSearch,
			selectedIndex,
			setExpanded,
		} = this.props;
		if ( options.length === 0 ) {
			return;
		}

		switch ( event.keyCode ) {
			case UP:
				decrementSelectedIndex();
				event.preventDefault();
				event.stopPropagation();
				break;

			case DOWN:
				incrementSelectedIndex();
				event.preventDefault();
				event.stopPropagation();
				break;

			case ENTER:
				if ( isNumber( selectedIndex ) && options[ selectedIndex ] ) {
					this.select( options[ selectedIndex ] );
				}
				event.preventDefault();
				event.stopPropagation();
				break;

			case LEFT:
			case RIGHT:
				setExpanded( false );
				break;

			case ESCAPE:
				setExpanded( false );
				onSearch( null );
				return;

			case TAB:
				if ( isNumber( selectedIndex ) && options[ selectedIndex ] ) {
					this.select( options[ selectedIndex ] );
				}
				setExpanded( false );
				break;

			default:
		}
	}

	toggleKeyEvents( isListening: boolean ) {
		const { node } = this.props;
		if ( ! node ) {
			// eslint-disable-next-line no-console
			console.warn( 'No node to bind events to.' );
			return;
		}

		// This exists because we must capture ENTER key presses before RichText.
		// It seems that react fires the simulated capturing events after the
		// native browser event has already bubbled so we can't stopPropagation
		// and avoid RichText getting the event from TinyMCE, hence we must
		// register a native event handler.
		const handler = isListening
			? 'addEventListener'
			: 'removeEventListener';
		node[ handler ](
			'keydown',
			this.handleKeyDown as ( e: Event ) => void,
			true
		);
	}

	componentDidMount() {
		const { selectedIndex } = this.props;
		if ( isNumber( selectedIndex ) && selectedIndex > -1 ) {
			this.scrollToOption( selectedIndex );
		}
		this.toggleKeyEvents( true );
	}

	componentWillUnmount() {
		this.toggleKeyEvents( false );
	}

	render() {
		const { instanceId, listboxId, options, selectedIndex, staticList } =
			this.props;
		const listboxClasses = classnames(
			'woocommerce-select-control__listbox',
			{
				'is-static': staticList,
			}
		);

		return (
			<div
				ref={ this.listbox }
				id={ listboxId }
				role="listbox"
				className={ listboxClasses }
				tabIndex={ -1 }
			>
				{ options.map( ( option, index ) => (
					<Button
						ref={ this.getOptionRef( index ) }
						key={ option.key }
						id={ `woocommerce-select-control__option-${ instanceId }-${ option.key }` }
						role="option"
						aria-selected={ index === selectedIndex }
						disabled={ option.isDisabled }
						className={ classnames(
							'woocommerce-select-control__option',
							{
								'is-selected': index === selectedIndex,
							}
						) }
						onClick={ () => this.select( option ) }
						tabIndex={ -1 }
					>
						{ option.label }
					</Button>
				) ) }
			</div>
		);
	}
}

export default List;
