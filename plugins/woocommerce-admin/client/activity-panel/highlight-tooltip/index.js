/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	Popover,
	Card,
	CardBody,
	CardHeader,
	CardFooter,
	Button,
	IsolatedEventContainer,
} from '@wordpress/components';
import {
	useState,
	useEffect,
	createPortal,
	useLayoutEffect,
} from '@wordpress/element';
import { close } from '@wordpress/icons';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

const SHOW_CLASS = 'highlight-tooltip__show';

function findElement( id, selector ) {
	if ( id ) {
		return document.getElementById( id );
	}
	const items = document.querySelectorAll( selector );
	return items[ 0 ];
}

function HighlightTooltip( {
	title,
	closeButtonText,
	content,
	show = true,
	id,
	selector,
	onClose,
	delay,
	onShow = noop,
	useAnchor = false,
} ) {
	const [ showHighlight, setShowHighlight ] = useState(
		delay > 0 ? null : show
	);
	const [ node, setNode ] = useState( null );
	const [ anchorRect, setAnchorRect ] = useState( null );

	useEffect( () => {
		const element = findElement( id, selector );
		let container;
		if ( element && ! node ) {
			container = setContainerNode( element );
		}
		const timeoutId = triggerShowTooltip( container );

		return () => {
			if ( container ) {
				const parentElement = container.parentElement;
				parentElement.removeChild( container );
				if ( useAnchor ) {
					parentElement.remove();
				}
			}
			if ( timeoutId ) {
				clearTimeout( timeoutId );
			}
		};
	}, [] );

	useEffect( () => {
		if ( ! showHighlight && node ) {
			node.classList.remove( SHOW_CLASS );
		}
	}, [ showHighlight ] );

	useEffect( () => {
		if ( show !== showHighlight && showHighlight !== null && node ) {
			setShowHighlight( show );
			if ( ! show ) {
				node.classList.remove( SHOW_CLASS );
			} else if ( node ) {
				triggerShowTooltip( node );
			}
		}
	}, [ show ] );

	useLayoutEffect( () => {
		window.addEventListener( 'resize', updateSize );
		return () => window.removeEventListener( 'resize', updateSize );
	}, [] );

	function setContainerNode( element ) {
		// Add tooltip container
		let parent;
		if ( ! useAnchor ) {
			parent = element.parentElement;
		} else {
			parent = document.createElement( 'div' );
			document.body.appendChild( parent );
		}
		const container = document.createElement( 'div' );
		container.classList.add( 'highlight-tooltip__container' );
		parent.appendChild( container );
		setNode( container );
		return container;
	}

	function updateSize() {
		if ( useAnchor ) {
			const element = findElement( id, selector );
			setAnchorRect( element.getBoundingClientRect() );
		}
	}

	const triggerShowTooltip = ( container ) => {
		let timeoutId = null;
		if ( delay > 0 ) {
			timeoutId = setTimeout( () => {
				timeoutId = null;
				showTooltip( container );
			}, delay );
		} else if ( ! showHighlight ) {
			showTooltip( container );
		}
		return timeoutId;
	};

	const showTooltip = ( container ) => {
		const element = findElement( id, selector );
		let containerEle = container;
		if ( element && ! node && ! container ) {
			containerEle = setContainerNode( element );
		}
		if ( element && useAnchor ) {
			setAnchorRect( element.getBoundingClientRect() );
		}
		if ( containerEle ) {
			containerEle.classList.add( SHOW_CLASS );
		}
		setShowHighlight( true );
		onShow();
	};

	const triggerClose = () => {
		setShowHighlight( false );
		if ( onClose ) {
			onClose();
		}
	};

	if ( ! node ) {
		return null;
	}

	return createPortal(
		<div className="highlight-tooltip__portal">
			{ showHighlight ? (
				<>
					<IsolatedEventContainer className="highlight-tooltip__overlay" />
					<Popover
						className="highlight-tooltip__popover"
						noArrow={ false }
						anchorRect={ anchorRect }
						focusOnMount="container"
					>
						<Card size="medium">
							<CardHeader>
								{ title }
								<Button
									isSmall
									onClick={ triggerClose }
									icon={ close }
								/>
							</CardHeader>
							<CardBody>{ content || null }</CardBody>
							<CardFooter isBorderless={ true }>
								<Button
									size="small"
									isPrimary
									onClick={ triggerClose }
								>
									{ closeButtonText ||
										__( 'Close', 'woocommerce' ) }
								</Button>
							</CardFooter>
						</Card>
					</Popover>
				</>
			) : null }
		</div>,
		node
	);
}

HighlightTooltip.propTypes = {
	/**
	 * The id of the element it should highlight, should be unique per HighlightTooltip.
	 */
	id: PropTypes.string,
	/**
	 * The selector of the element it should highlight, should be unique per HighlightTooltip.
	 */
	selector: PropTypes.string,
	/**
	 * Title of the popup
	 */
	title: PropTypes.string.isRequired,
	/**
	 * Text of the close button.
	 */
	closeButtonText: PropTypes.string.isRequired,
	/**
	 * Content of the popup, can be either text or react element.
	 */
	content: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	/**
	 * If to show the popup, defaults to true.
	 */
	show: PropTypes.bool,
	/**
	 * Callback for when the user closes the popup.
	 */
	onClose: PropTypes.func,
	/**
	 * This will delay the popup from appearing by the number of ms.
	 */
	delay: PropTypes.number,
	/**
	 * A callback for when the tooltip is shown.
	 */
	onShow: PropTypes.func,
	/**
	 * useAnchor, will append the tooltip to the body tag, and make use of the anchorRect to display the tooltip.
	 * Defaults to false.
	 */
	useAnchor: PropTypes.bool,
};

export { HighlightTooltip };
