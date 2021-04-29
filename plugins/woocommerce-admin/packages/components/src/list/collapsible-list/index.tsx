/**
 * External dependencies
 */
import { Dashicon } from '@wordpress/components';
import { useState, useCallback, Children } from '@wordpress/element';
import { Transition } from 'react-transition-group';

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
	transition: `max-height 500ms ease-in-out`,
	maxHeight: 0,
	overflow: 'hidden',
};

function getContainerHeight( collapseContainer: HTMLDivElement | null ) {
	let containerHeight = 0;
	if ( collapseContainer ) {
		for ( const child of collapseContainer.children ) {
			containerHeight += child.clientHeight;
		}
	}
	return containerHeight;
}

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
	const [ containerHeight, setContainerHeight ] = useState( 0 );
	const collapseContainerRef = useCallback(
		( containerElement: HTMLDivElement ) => {
			if ( containerElement ) {
				setContainerHeight( getContainerHeight( containerElement ) );
			}
		},
		[ children ]
	);

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

	let shownChildren: React.ReactNode[] = [];
	let hiddenChildren = Children.toArray( children );
	if ( show > 0 ) {
		shownChildren = hiddenChildren.slice( 0, show );
		hiddenChildren = hiddenChildren.slice( show );
	}

	const transitionStyles = {
		entered: { maxHeight: containerHeight },
		entering: { maxHeight: containerHeight },
		exiting: { maxHeight: 0 },
		exited: { maxHeight: 0 },
	};

	return (
		<ExperimentalList { ...listProps }>
			{ shownChildren }
			<Transition
				timeout={ 500 }
				in={ ! isCollapsed }
				mountOnEnter
				unmountOnExit
			>
				{ ( state: 'entering' | 'entered' | 'exiting' | 'exited' ) => (
					<div
						ref={ collapseContainerRef }
						style={ {
							...defaultStyle,
							...transitionStyles[ state ],
						} }
					>
						{ hiddenChildren }
					</div>
				) }
			</Transition>
			{ hiddenChildren.length > 0 ? (
				<ExperimentalListItem
					className="list-item-collapse"
					onClick={ clickHandler }
					animation="none"
				>
					<p>{ isCollapsed ? expandLabel : collapseLabel }</p>

					<Dashicon
						className="list-item-collapse__icon"
						icon={
							isCollapsed ? 'arrow-down-alt2' : 'arrow-up-alt2'
						}
					/>
				</ExperimentalListItem>
			) : null }
		</ExperimentalList>
	);
};
