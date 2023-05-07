/**
 * External dependencies
 */
import { noop } from 'lodash';
import { Flex } from '@wordpress/components';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ARROW_LEFT, ARROW_RIGHT, ROOT_VALUE } from './constants';
import Checkbox from './checkbox';

/**
 * @typedef {import('./index').InnerOption} InnerOption
 */

/**
 * This component renders a list of options and its children recursively
 *
 * @param {Object}                        props                    Component parameters
 * @param {InnerOption[]}                 props.options            List of options to be rendered
 * @param {InnerOption}                   props.parent             Parent option
 * @param {Function}                      props.onChange           Callback when an option changes
 * @param {Function}                      [props.onExpanderClick]  Callback when an expander is clicked.
 * @param {(option: InnerOption) => void} [props.onToggleExpanded] Callback when requesting an expander to be toggled.
 */
const Options = ( {
	options = [],
	onChange = () => {},
	onExpanderClick = noop,
	onToggleExpanded = noop,
	parent = null,
} ) => {
	/**
	 * Alters the node with some keys for accessibility
	 * ArrowRight - Expands the node
	 * ArrowLeft - Collapses the node
	 *
	 * @param {Event}       event  The KeyDown event
	 * @param {InnerOption} option The option where the event happened
	 */
	const handleKeyDown = ( event, option ) => {
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

		if ( ! option?.value ) return null;

		return (
			<div
				key={ `${ option.key ?? option.value }` }
				role={ hasChildren ? 'treegroup' : 'treeitem' }
				aria-expanded={ hasChildren ? expanded : undefined }
				className={ classnames(
					'woocommerce-tree-select-control__node',
					hasChildren && 'has-children'
				) }
			>
				<Flex justify="flex-start">
					{ ! isRoot && (
						<button
							className={ classnames(
								'woocommerce-tree-select-control__expander',
								! hasChildren && 'is-hidden'
							) }
							tabIndex="-1"
							onClick={ ( e ) => {
								e.preventDefault();
								onExpanderClick( e );
								onToggleExpanded( option );
							} }
						>
							<Icon icon={ expanded ? chevronUp : chevronDown } />
						</button>
					) }

					<Checkbox
						className={ classnames(
							'components-base-control',
							'woocommerce-tree-select-control__option',
							partialChecked && 'is-partially-checked'
						) }
						option={ option }
						checked={ checked }
						onChange={ ( e ) => {
							onChange( e.target.checked, option, parent );
						} }
						onKeyDown={ ( e ) => {
							handleKeyDown( e, option );
						} }
					/>
				</Flex>

				{ hasChildren && expanded && (
					<div
						className={ classnames(
							'woocommerce-tree-select-control__children',
							isRoot && 'woocommerce-tree-select-control__main'
						) }
					>
						<Options
							options={ option.children }
							onChange={ onChange }
							onExpanderClick={ onExpanderClick }
							onToggleExpanded={ onToggleExpanded }
							parent={ option }
						/>
					</div>
				) }
			</div>
		);
	} );
};

export default Options;
