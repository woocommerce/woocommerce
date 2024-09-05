/**
 * External dependencies
 */
import { Popover, Spinner } from '@wordpress/components';
import classnames from 'classnames';
import {
	createElement,
	useEffect,
	useRef,
	useLayoutEffect,
	useState,
} from '@wordpress/element';
import { escapeRegExp } from 'lodash';

/**
 * Internal dependencies
 */
import {
	LinkedTree,
	Tree,
	TreeControlProps,
} from '../experimental-tree-control';

type MenuProps = {
	isEventOutside: ( event: React.FocusEvent ) => boolean;
	isOpen: boolean;
	isLoading?: boolean;
	position?: Popover.Position;
	scrollIntoViewOnOpen?: boolean;
	highlightedIndex?: number;
	items: LinkedTree[];
	treeRef?: React.ForwardedRef< HTMLOListElement >;
	onClose?: () => void;
} & Omit< TreeControlProps, 'items' >;

export const SelectTreeMenu = ( {
	isEventOutside,
	isLoading,
	isOpen,
	className,
	position = 'bottom center',
	scrollIntoViewOnOpen = false,
	items,
	treeRef: ref,
	onClose = () => {},
	onEscape,
	shouldShowCreateButton,
	onFirstItemLoop,
	onExpand,
	...props
}: MenuProps ) => {
	const [ boundingRect, setBoundingRect ] = useState< DOMRect >();
	const selectControlMenuRef = useRef< HTMLDivElement >( null );

	useLayoutEffect( () => {
		if (
			selectControlMenuRef.current?.parentElement &&
			selectControlMenuRef.current?.parentElement.clientWidth > 0
		) {
			setBoundingRect(
				selectControlMenuRef.current.parentElement.getBoundingClientRect()
			);
		}
	}, [
		selectControlMenuRef.current,
		selectControlMenuRef.current?.clientWidth,
	] );

	// Scroll the selected item into view when the menu opens.
	useEffect( () => {
		if ( isOpen && scrollIntoViewOnOpen ) {
			selectControlMenuRef.current?.scrollIntoView?.();
		}
	}, [ isOpen, scrollIntoViewOnOpen ] );

	const shouldItemBeExpanded = ( item: LinkedTree ): boolean => {
		if ( ! props.createValue || ! item.children?.length ) return false;
		return item.children.some( ( child ) => {
			if (
				new RegExp(
					escapeRegExp( props.createValue || '' ),
					'ig'
				).test( child.data.label )
			) {
				return true;
			}
			return shouldItemBeExpanded( child );
		} );
	};

	/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
	/* Disabled because of the onmouseup on the ul element below. */
	return (
		<div
			ref={ selectControlMenuRef }
			className="woocommerce-experimental-select-tree-control__menu"
		>
			<div>
				<Popover
					focusOnMount={ false }
					// @ts-expect-error this prop does exist
					inline
					className={ classnames(
						'woocommerce-experimental-select-tree-control__popover-menu',
						className,
						{
							'is-open': isOpen,
							'has-results': items.length > 0,
						}
					) }
					position={ position }
					flip={ false }
					resize={ false }
					animate={ false }
					onFocusOutside={ ( event ) => {
						if ( isEventOutside( event ) ) {
							onClose();
						}
					} }
				>
					{ isOpen && (
						<div>
							{ isLoading ? (
								<div
									style={ {
										width: boundingRect?.width,
									} }
								>
									<Spinner />
								</div>
							) : (
								<Tree
									{ ...props }
									ref={ ref }
									items={ items }
									onTreeBlur={ onClose }
									onExpand={ onExpand }
									shouldItemBeExpanded={
										shouldItemBeExpanded
									}
									shouldShowCreateButton={
										shouldShowCreateButton
									}
									onFirstItemLoop={ onFirstItemLoop }
									onEscape={ onEscape }
									style={ {
										width: boundingRect?.width,
									} }
								/>
							) }
						</div>
					) }
				</Popover>
			</div>
		</div>
	);
	/* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
