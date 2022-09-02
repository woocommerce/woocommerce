/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { createElement, Component, createRef } from '@wordpress/element';
import { isEqual } from 'lodash';
import { ENTER, ESCAPE, UP, DOWN, LEFT, RIGHT, TAB } from '@wordpress/keycodes';
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
		const { options, selectedIndex } = this.props;

		// Remove old option refs to avoid memory leaks.
		if ( ! isEqual( options, prevProps.options ) ) {
			this.optionRefs = {};
		}

		if ( selectedIndex !== prevProps.selectedIndex ) {
			this.scrollToOption( selectedIndex );
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

		if ( listbox.scrollHeight <= listbox.clientHeight ) {
			return;
		}

		if ( ! this.optionRefs[ index ] ) {
			return;
		}

		const option = this.optionRefs[ index ].current;
		const scrollBottom = listbox.clientHeight + listbox.scrollTop;
		const elementBottom = option.offsetTop + option.offsetHeight;
		if ( elementBottom > scrollBottom ) {
			listbox.scrollTop = elementBottom - listbox.clientHeight;
		} else if ( option.offsetTop < listbox.scrollTop ) {
			listbox.scrollTop = option.offsetTop;
		}
	}

	handleKeyDown( event ) {
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
				if ( options[ selectedIndex ] ) {
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
				if ( options[ selectedIndex ] ) {
					this.select( options[ selectedIndex ] );
				}
				setExpanded( false );
				break;

			default:
		}
	}

	toggleKeyEvents( isListening ) {
		const { node } = this.props;
		// This exists because we must capture ENTER key presses before RichText.
		// It seems that react fires the simulated capturing events after the
		// native browser event has already bubbled so we can't stopPropagation
		// and avoid RichText getting the event from TinyMCE, hence we must
		// register a native event handler.
		const handler = isListening
			? 'addEventListener'
			: 'removeEventListener';
		node[ handler ]( 'keydown', this.handleKeyDown, true );
	}

	componentDidMount() {
		const { selectedIndex } = this.props;
		if ( selectedIndex > -1 ) {
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
				tabIndex="-1"
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
						tabIndex="-1"
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
	 * ID of the main SelectControl instance.
	 */
	instanceId: PropTypes.number,
	/**
	 * ID used for a11y in the listbox.
	 */
	listboxId: PropTypes.string,
	/**
	 * Parent node to bind keyboard events to.
	 */
	// eslint-disable-next-line no-undef
	node: PropTypes.instanceOf( Element ).isRequired,
	/**
	 * Function to execute when an option is selected.
	 */
	onSelect: PropTypes.func,
	/**
	 * Array of options to display.
	 */
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			isDisabled: PropTypes.bool,
			key: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] )
				.isRequired,
			keywords: PropTypes.arrayOf(
				PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] )
			),
			label: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.object,
			] ),
			value: PropTypes.any,
		} )
	).isRequired,
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
