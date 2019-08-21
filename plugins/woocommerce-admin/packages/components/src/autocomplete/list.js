/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { Component, createRef } from '@wordpress/element';
import { isEqual } from 'lodash';
import { ENTER, ESCAPE, UP, DOWN, LEFT, TAB, RIGHT } from '@wordpress/keycodes';
import PropTypes from 'prop-types';

/**
 * A list box that displays filtered options after search.
 */
class List extends Component {
	constructor() {
		super( ...arguments );

		this.handleKeyDown = this.handleKeyDown.bind( this );
		this.select = this.select.bind( this );
		this.optionRefs = {};
		this.listbox = createRef();
	}

	componentDidUpdate( prevProps ) {
		const { filteredOptions } = this.props;

		// Remove old option refs to avoid memory leaks.
		if ( ! isEqual( filteredOptions, prevProps.filteredOptions ) ) {
			this.optionRefs = {};
		}
	}

	getOptionRef( index ) {
		if ( ! this.optionRefs.hasOwnProperty( index ) ) {
			this.optionRefs[ index ] = createRef();
		}

		return this.optionRefs[ index ];
	}

	select( option ) {
		const { onSelect } = this.props;

		if ( option.isDisabled ) {
			return;
		}

		onSelect( option );
	}

	scrollToOption( index ) {
		const listbox = this.listbox.current;

		if ( listbox.scrollHeight > listbox.clientHeight ) {
			const option = this.optionRefs[ index ].current;
			const scrollBottom = listbox.clientHeight + listbox.scrollTop;
			const elementBottom = option.offsetTop + option.offsetHeight;
			if ( elementBottom > scrollBottom ) {
				listbox.scrollTop = elementBottom - listbox.clientHeight;
			} else if ( option.offsetTop < listbox.scrollTop ) {
				listbox.scrollTop = option.offsetTop;
			}
		}
	}

	handleKeyDown( event ) {
		const { filteredOptions, onChange, onSearch, selectedIndex } = this.props;
		if ( filteredOptions.length === 0 ) {
			return;
		}

		let nextSelectedIndex;
		switch ( event.keyCode ) {
			case UP:
				nextSelectedIndex = null !== selectedIndex
					? ( selectedIndex === 0 ? filteredOptions.length : selectedIndex ) - 1
					: filteredOptions.length - 1;
				onChange( nextSelectedIndex );
				this.scrollToOption( nextSelectedIndex );
				event.preventDefault();
				event.stopPropagation();
				break;

			case TAB:
			case DOWN:
				nextSelectedIndex = null !== selectedIndex
					? ( selectedIndex + 1 ) % filteredOptions.length
					: 0;
				onChange( nextSelectedIndex );
				this.scrollToOption( nextSelectedIndex );
				event.preventDefault();
				event.stopPropagation();
				break;

			case ENTER:
				this.select( filteredOptions[ selectedIndex ] );
				event.preventDefault();
				event.stopPropagation();
				break;

			case LEFT:
			case RIGHT:
				onChange( null );
				break;

			case ESCAPE:
				onChange( null );
				onSearch( null );
				return;

			default:
				return;
		}
	}

	toggleKeyEvents( isListening ) {
		const { node } = this.props;
		// This exists because we must capture ENTER key presses before RichText.
		// It seems that react fires the simulated capturing events after the
		// native browser event has already bubbled so we can't stopPropagation
		// and avoid RichText getting the event from TinyMCE, hence we must
		// register a native event handler.
		const handler = isListening ? 'addEventListener' : 'removeEventListener';
		node[ handler ]( 'keydown', this.handleKeyDown, true );
	}

	componentDidMount() {
		this.toggleKeyEvents( true );
	}

	componentWillUnmount() {
		this.toggleKeyEvents( false );
	}

	render() {
		const { filteredOptions, instanceId, listboxId, selectedIndex, staticList } = this.props;
		const listboxClasses = classnames( 'woocommerce-autocomplete__listbox', {
			'is-static': staticList,
		} );

		return (
			<div ref={ this.listbox } id={ listboxId } role="listbox" className={ listboxClasses }>
				{ filteredOptions.map( ( option, index ) => (
						<Button
							ref={ this.getOptionRef( index ) }
							key={ option.key }
							id={ `woocommerce-autocomplete__option-${ instanceId }-${ option.key }` }
							role="option"
							aria-selected={ index === selectedIndex }
							disabled={ option.isDisabled }
							className={ classnames( 'woocommerce-autocomplete__option', {
								'is-selected': index === selectedIndex,
							} ) }
							onClick={ () => this.select( option ) }
						>
							{ option.label }
						</Button>
					) ) }
			</div>
		);
	}
}

List.propTypes = {
	/**
	 * Array of filtered options to display.
	 */
	filteredOptions: PropTypes.arrayOf(
		PropTypes.shape( {
			isDisabled: PropTypes.bool,
			key: PropTypes.oneOfType( [
				PropTypes.number,
				PropTypes.string,
			] ).isRequired,
			keywords: PropTypes.arrayOf( PropTypes.string ),
			label: PropTypes.string,
			value: PropTypes.any,
		} )
	).isRequired,
	/**
	 * ID of the main Autocomplete instance.
	 */
	instanceId: PropTypes.number,
	/**
	 * ID used for a11y in the listbox.
	 */
	listboxId: PropTypes.string,
	/**
	 * Parent node to bind keyboard events to.
	 */
	node: PropTypes.instanceOf( Element ).isRequired,
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: PropTypes.func,
	/**
	 * Function to execute when an option is selected.
	 */
	onSelect: PropTypes.func,
	/**
	 * Integer for the currently selected item.
	 */
	selectedIndex: PropTypes.number,
	/**
	 * Bool to determine if the list should be positioned absolutely or staticly.
	 */
	staticList: PropTypes.bool,
};

export default List;
