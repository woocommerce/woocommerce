/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { createElement, useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DisplayState } from '../display-state';

export type CollapsedProps = {
	initialCollapsed?: boolean;
	toggleText: string;
	persistRender?: boolean;
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const CollapsibleContent: React.FC< CollapsedProps > = ( {
	initialCollapsed = true,
	toggleText,
	children,
	persistRender = false,
	...props
}: CollapsedProps ) => {
	const [ collapsed, setCollapsed ] = useState( initialCollapsed );

	const getState = () => {
		if ( ! collapsed ) {
			return 'visible';
		}

		return persistRender ? 'visually-hidden' : 'hidden';
	};

	const collapsibleToggleId = useInstanceId(
		CollapsibleContent,
		'woocommerce-collapsible-content__toggle'
	) as string;
	const collapsibleContentId = useInstanceId(
		CollapsibleContent,
		'woocommerce-collapsible-content__content'
	) as string;

	const displayState = getState();

	return (
		<div className="woocommerce-collapsible-content">
			<button
				type="button"
				id={ collapsibleToggleId }
				className="woocommerce-collapsible-content__toggle"
				onClick={ () => setCollapsed( ! collapsed ) }
				aria-expanded={ collapsed ? 'false' : 'true' }
				aria-controls={
					displayState !== 'hidden' ? collapsibleContentId : undefined
				}
			>
				<div>
					<span>{ toggleText }</span>

					<Icon
						icon={ collapsed ? chevronDown : chevronUp }
						size={ 16 }
					/>
				</div>
			</button>

			<DisplayState state={ displayState }>
				<div
					{ ...props }
					className="woocommerce-collapsible-content__content"
					id={ collapsibleContentId }
					role="region"
					aria-labelledby={ collapsibleToggleId }
				>
					{ children }
				</div>
			</DisplayState>
		</div>
	);
};
