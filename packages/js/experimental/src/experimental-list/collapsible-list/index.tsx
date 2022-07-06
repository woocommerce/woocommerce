/**
 * External dependencies
 */
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import {
	createElement,
	useState,
	useCallback,
	useEffect,
	Children,
	useRef,
	isValidElement,
	cloneElement,
} from '@wordpress/element';
import {
	Transition,
	CSSTransition,
	TransitionGroup,
} from 'react-transition-group';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ExperimentalListItem } from '../experimental-list-item';
import { ListProps, ExperimentalList } from '../experimental-list';

type CollapsibleListProps = {
	collapseLabel: string;
	expandLabel: string;
	collapsed?: boolean;
	show?: number;
	onCollapse?: () => void;
	onExpand?: () => void;
} & ListProps;

const defaultStyle = {
	transitionProperty: 'max-height',
	transitionDuration: '500ms',
	maxHeight: 0,
	overflow: 'hidden',
};

function getContainerHeight( collapseContainer: HTMLDivElement | null ) {
	let containerHeight = 0;
	if ( collapseContainer ) {
		for ( const child of collapseContainer.children ) {
			containerHeight += child.clientHeight;
			const style = window.getComputedStyle( child );

			containerHeight += parseInt( style.marginTop, 10 ) || 0;
			containerHeight += parseInt( style.marginBottom, 10 ) || 0;
		}
	}
	return containerHeight;
}

/**
 * This functions returns a new list of shown children depending on the new children updates.
 * If one is removed, it will remove it from the show array.
 * If one is added, it will add it back to the shown list, making use of the new children list to keep order.
 *
 * @param {Array.<import('react').ReactElement>} currentChildren      a list of the current children.
 * @param {Array.<import('react').ReactElement>} currentShownChildren a list of the current shown children.
 * @param {Array.<import('react').ReactElement>} newChildren          a list of the new children.
 * @return {Array.<import('react').ReactElement>} new list of children that should be shown.
 */
function getUpdatedShownChildren(
	currentChildren: React.ReactElement[],
	currentShownChildren: React.ReactElement[],
	newChildren: React.ReactElement[]
): React.ReactElement[] {
	if ( newChildren.length < currentChildren.length ) {
		const newChildrenKeys = newChildren.map( ( child ) => child.key );
		// Filter out removed child
		return currentShownChildren.filter(
			( item ) => item.key && newChildrenKeys.includes( item.key )
		);
	}
	const currentShownChildrenKeys = currentShownChildren.map(
		( child ) => child.key
	);
	const currentChildrenKeys = currentChildren.map( ( child ) => child.key );
	// Add new child back in.
	return newChildren.filter(
		( child ) =>
			child.key &&
			( currentShownChildrenKeys.includes( child.key ) ||
				! currentChildrenKeys.includes( child.key ) )
	);
}

const getTransitionStyle = (
	state: 'entering' | 'entered' | 'exiting' | 'exited',
	isCollapsed: boolean,
	elementRef: HTMLDivElement | null
) => {
	let maxHeight = 0;
	if ( ( state === 'entered' || state === 'entering' ) && elementRef ) {
		maxHeight = getContainerHeight( elementRef );
	}
	const styles: React.CSSProperties = {
		...defaultStyle,
		maxHeight,
	};

	// only include transition styles when entering or exiting.
	if ( state !== 'entering' && state !== 'exiting' ) {
		delete styles.transitionDuration;
		delete styles.transition;
		delete styles.transitionProperty;
	}
	// Remove maxHeight when entered, so we do not need to worry about nested items changing height while expanded.
	if ( state === 'entered' && ! isCollapsed ) {
		delete styles.maxHeight;
	}

	return styles;
};

export const ExperimentalCollapsibleList: React.FC< CollapsibleListProps > = ( {
	children,
	collapsed = true,
	collapseLabel,
	expandLabel,
	show = 0,
	onCollapse,
	onExpand,
	...listProps
} ): JSX.Element => {
	const [ isCollapsed, setCollapsed ] = useState( collapsed );
	const [ isTransitionComponentCollapsed, setTransitionComponentCollapsed ] =
		useState( collapsed );
	const [ footerLabels, setFooterLabels ] = useState( {
		collapse: collapseLabel,
		expand: expandLabel,
	} );
	const [ displayedChildren, setDisplayedChildren ] = useState< {
		all: React.ReactElement[];
		shown: React.ReactElement[];
		hidden: React.ReactElement[];
	} >( {
		all: [],
		shown: [],
		hidden: [],
	} );
	const collapseContainerRef = useRef< HTMLDivElement >( null );

	const updateChildren = () => {
		let shownChildren: React.ReactElement[] = [];
		const allChildren =
			Children.map( children, ( child ) =>
				isValidElement( child ) && 'key' in child ? child : null
			) || [];
		let hiddenChildren = allChildren;
		if ( show > 0 ) {
			shownChildren = allChildren.slice( 0, show );
			hiddenChildren = allChildren.slice( show );
		}
		if ( hiddenChildren.length > 0 ) {
			// Only update when footer will be shown, this way it won't update mid transition if the outer component
			// updates the label as well.
			setFooterLabels( { expand: expandLabel, collapse: collapseLabel } );
		}
		setDisplayedChildren( {
			all: allChildren,
			shown: shownChildren,
			hidden: hiddenChildren,
		} );
	};

	// This allows for an extra render cycle that adds the maxHeight back in before the exiting transition.
	// This way the exiting transition still works correctly.
	useEffect( () => {
		setTransitionComponentCollapsed( isCollapsed );
	}, [ isCollapsed ] );

	useEffect( () => {
		const allChildren =
			Children.map( children, ( child ) =>
				isValidElement( child ) && 'key' in child ? child : null
			) || [];
		if (
			displayedChildren.all.length > 0 &&
			isCollapsed &&
			listProps.animation !== 'none'
		) {
			setDisplayedChildren( {
				...displayedChildren,
				shown: getUpdatedShownChildren(
					displayedChildren.all,
					displayedChildren.shown,
					allChildren
				),
			} );
			// Update the hidden children after the remove/add transition is done, making the transition less busy.
			setTimeout( () => {
				updateChildren();
			}, 500 );
		} else {
			updateChildren();
		}
	}, [ children ] );

	const triggerCallbacks = ( newCollapseValue: boolean ) => {
		if ( onCollapse && newCollapseValue ) {
			onCollapse();
		}
		if ( onExpand && ! newCollapseValue ) {
			onExpand();
		}
	};

	const clickHandler = useCallback( () => {
		setCollapsed( ! isCollapsed );
		triggerCallbacks( ! isCollapsed );
	}, [ isCollapsed ] );

	const listClasses = classnames(
		listProps.className || '',
		'woocommerce-experimental-list'
	);

	const wrapperClasses = classnames( {
		'woocommerce-experimental-list-wrapper': ! isCollapsed,
	} );

	return (
		<ExperimentalList { ...listProps } className={ listClasses }>
			{ [
				...displayedChildren.shown,
				<Transition
					key="remaining-children"
					timeout={ 500 }
					in={ ! isTransitionComponentCollapsed }
					mountOnEnter={ true }
					unmountOnExit={ false }
				>
					{ (
						state: 'entering' | 'entered' | 'exiting' | 'exited'
					) => {
						const transitionStyles = getTransitionStyle(
							state,
							isCollapsed,
							collapseContainerRef.current
						);
						return (
							<div
								className={ wrapperClasses }
								ref={ collapseContainerRef }
								style={ transitionStyles }
							>
								<TransitionGroup className="woocommerce-experimental-list">
									{ Children.map(
										displayedChildren.hidden,
										( child ) => {
											const {
												onExited,
												in: inTransition,
												enter,
												exit,
												...remainingProps
											} = child.props;
											const animationProp =
												remainingProps.animation ||
												listProps.animation;
											return (
												<CSSTransition
													key={ child.key }
													timeout={ 500 }
													onExited={ onExited }
													in={ inTransition }
													enter={ enter }
													exit={ exit }
													classNames="woocommerce-list__item"
												>
													{ cloneElement( child, {
														animation:
															animationProp,
														...remainingProps,
													} ) }
												</CSSTransition>
											);
										}
									) }
								</TransitionGroup>
							</div>
						);
					} }
				</Transition>,
				displayedChildren.hidden.length > 0 ? (
					<ExperimentalListItem
						key="collapse-item"
						className="list-item-collapse"
						onClick={ clickHandler }
						animation="none"
						disableGutters
					>
						<p>
							{ isCollapsed
								? footerLabels.expand
								: footerLabels.collapse }
						</p>

						<Icon
							className="list-item-collapse__icon"
							size={ 30 }
							icon={ isCollapsed ? chevronDown : chevronUp }
						/>
					</ExperimentalListItem>
				) : null,
			] }
		</ExperimentalList>
	);
};
