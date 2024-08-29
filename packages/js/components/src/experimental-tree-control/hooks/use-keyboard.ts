/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { LinkedTree } from '../types';

function getFirstChild(
	currentHeading: HTMLDivElement
): HTMLLabelElement | null {
	const parentTreeItem = currentHeading?.closest< HTMLDivElement >(
		'.experimental-woocommerce-tree-item'
	);
	const firstSubTreeItem = parentTreeItem?.querySelector(
		'.experimental-woocommerce-tree > .experimental-woocommerce-tree-item'
	);
	const label = firstSubTreeItem?.querySelector< HTMLLabelElement >(
		'.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label'
	);
	return label ?? null;
}

function getFirstAncestor(
	currentHeading: HTMLDivElement
): HTMLLabelElement | null {
	const parentTree = currentHeading?.closest< HTMLDivElement >(
		'.experimental-woocommerce-tree'
	);
	const grandParentTreeItem = parentTree?.closest< HTMLDivElement >(
		'.experimental-woocommerce-tree-item'
	);
	const label = grandParentTreeItem?.querySelector< HTMLLabelElement >(
		'.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label'
	);
	return label ?? null;
}

function getAllHeadings(
	currentHeading: HTMLDivElement
): NodeListOf< HTMLDivElement > | undefined {
	const rootTree = currentHeading.closest< HTMLDivElement >(
		'.experimental-woocommerce-tree--level-1'
	);
	return rootTree?.querySelectorAll< HTMLDivElement >(
		'.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading'
	);
}

const step = {
	ArrowDown: 1,
	ArrowUp: -1,
};

function getNextFocusableElement(
	currentHeading: HTMLDivElement,
	code: 'ArrowDown' | 'ArrowUp'
): HTMLLabelElement | null {
	const headingsNodeList = getAllHeadings( currentHeading );
	if ( ! headingsNodeList ) return null;

	let currentHeadingIndex = 0;
	for ( const heading of headingsNodeList.values() ) {
		if ( heading === currentHeading ) break;
		currentHeadingIndex++;
	}
	if (
		currentHeadingIndex < 0 ||
		currentHeadingIndex >= headingsNodeList.length
	) {
		return null;
	}

	const heading = headingsNodeList.item(
		currentHeadingIndex + ( step[ code ] ?? 0 )
	);
	return heading?.querySelector< HTMLLabelElement >(
		'.experimental-woocommerce-tree-item__label'
	);
}

function getFirstFocusableElement(
	currentHeading: HTMLDivElement
): HTMLLabelElement | null {
	const headingsNodeList = getAllHeadings( currentHeading );
	if ( ! headingsNodeList ) return null;
	return headingsNodeList
		.item( 0 )
		.querySelector< HTMLLabelElement >(
			'.experimental-woocommerce-tree-item__label'
		);
}

function getLastFocusableElement(
	currentHeading: HTMLDivElement
): HTMLLabelElement | null {
	const headingsNodeList = getAllHeadings( currentHeading );
	if ( ! headingsNodeList ) return null;
	return headingsNodeList
		.item( headingsNodeList.length - 1 )
		.querySelector< HTMLLabelElement >(
			'.experimental-woocommerce-tree-item__label'
		);
}

export function useKeyboard( {
	item,
	isExpanded,
	onExpand,
	onCollapse,
	onToggleExpand,
	onLastItemLoop,
	onFirstItemLoop,
}: {
	item: LinkedTree;
	isExpanded: boolean;
	onExpand(): void;
	onCollapse(): void;
	onToggleExpand(): void;
	onLastItemLoop?( event: React.KeyboardEvent< HTMLDivElement > ): void;
	onFirstItemLoop?( event: React.KeyboardEvent< HTMLDivElement > ): void;
} ) {
	function onKeyDown( event: React.KeyboardEvent< HTMLDivElement > ) {
		if ( event.code === 'ArrowRight' ) {
			event.preventDefault();
			if ( item.children.length > 0 ) {
				if ( isExpanded ) {
					const element = getFirstChild( event.currentTarget );
					return element?.focus();
				}
				onExpand();
			}
		}

		if ( event.code === 'ArrowLeft' ) {
			event.preventDefault();
			if ( ! isExpanded && item.parent ) {
				const element = getFirstAncestor( event.currentTarget );
				return element?.focus();
			}
			if ( item.children.length > 0 ) {
				onCollapse();
			}
		}

		if ( event.code === 'Enter' ) {
			event.preventDefault();
			if ( item.children.length > 0 ) {
				onToggleExpand();
			}
		}

		if ( event.code === 'ArrowDown' || event.code === 'ArrowUp' ) {
			event.preventDefault();
			const element = getNextFocusableElement(
				event.currentTarget,
				event.code
			);
			element?.focus();
			if ( event.code === 'ArrowDown' && ! element && onLastItemLoop ) {
				onLastItemLoop( event );
			}
			if ( event.code === 'ArrowUp' && ! element && onFirstItemLoop ) {
				onFirstItemLoop( event );
			}
		}

		if ( event.code === 'Home' ) {
			event.preventDefault();
			const element = getFirstFocusableElement( event.currentTarget );
			element?.focus();
		}

		if ( event.code === 'End' ) {
			event.preventDefault();
			const element = getLastFocusableElement( event.currentTarget );
			element?.focus();
		}
	}

	return { onKeyDown };
}
