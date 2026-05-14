/**
 * External dependencies
 */
import { chevronRightSmall, chevronDownSmall } from '@wordpress/icons';
import clsx from 'clsx';
import { CheckboxControl } from '@wordpress/components';
import { Icon, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { ARROW_LEFT, ARROW_RIGHT, ROOT_VALUE } from './constants';
import type { OptionsProps, InnerOption } from './types';

/**
 * This component renders a list of options and its children recursively
 */
const Options = ( {
	options = [],
	onChange = () => {},
	onExpanderClick = () => {},
	onToggleExpanded = () => {},
	parent = null,
	level = 0,
}: OptionsProps ) => {
	/**
	 * Alters the node with some keys for accessibility
	 * ArrowRight - Expands the node
	 * ArrowLeft - Collapses the node
	 */
	const handleKeyDown = (
		event: React.KeyboardEvent,
		option: InnerOption
	) => {
		if ( ! option.hasChildren ) {
			return;
		}
		if ( event.key === ARROW_RIGHT && ! option.expanded ) {
			onToggleExpanded( option );
		} else if ( event.key === ARROW_LEFT && option.expanded ) {
			onToggleExpanded( option );
		}
	};

	return options.map( ( option ) => {
		const isRoot = option.value === ROOT_VALUE;
		const { hasChildren, checked, partialChecked, expanded } = option;

		if ( ! option?.value ) {
			return null;
		}
		if ( ! isRoot && ! option?.isVisible ) {
			return null;
		}

		return (
			<div
				key={ `${ option.key ?? option.value }` }
				role="treeitem"
				aria-expanded={ hasChildren ? expanded : undefined }
				className={ clsx(
					'woocommerce-tree-select-control__node',
					level && 'woocommerce-tree-select-control__children'
				) }
				aria-checked={ partialChecked ? 'mixed' : checked }
			>
				<Stack align="center">
					{ ! isRoot && (
						<button
							className={ clsx(
								'woocommerce-tree-select-control__expander',
								! hasChildren && 'is-hidden'
							) }
							tabIndex={ -1 }
							onClick={ ( e ) => {
								e.preventDefault();
								onExpanderClick( e );
								onToggleExpanded( option );
							} }
						>
							<Icon
								icon={
									expanded
										? chevronDownSmall
										: chevronRightSmall
								}
							/>
						</button>
					) }

					<CheckboxControl
						className="woocommerce-tree-select-control__option"
						label={ option.label as string }
						value={ option.value }
						checked={ checked }
						aria-labelledby={ undefined }
						indeterminate={ partialChecked }
						onChange={ ( value ) => {
							onChange( value, option, parent );
						} }
						onKeyDown={ ( e ) => {
							handleKeyDown( e, option );
						} }
					/>
				</Stack>

				{ hasChildren && expanded && (
					<Options
						options={ option.children }
						onChange={ onChange }
						onExpanderClick={ onExpanderClick }
						onToggleExpanded={ onToggleExpanded }
						parent={ option }
						level={ isRoot ? level : level + 1 }
					/>
				) }
			</div>
		);
	} );
};

export default Options;
