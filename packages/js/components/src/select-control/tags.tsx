/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Icon, cancelCircleFilled } from '@wordpress/icons';
import { createElement, Component, Fragment } from '@wordpress/element';
import { findIndex, isArray } from 'lodash';

/**
 * Internal dependencies
 */
import Tag from '../tag';
import { Option, Selected } from './types';

type Props = {
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: ( selected: Option[] ) => void;
	/**
	 * An array of objects describing selected values. If the label of the selected
	 * value is omitted, the Tag of that value will not be rendered inside the
	 * search box.
	 */
	selected?: Selected;
	/**
	 * Render a 'Clear' button next to the input box to remove its contents.
	 */
	showClearButton?: boolean;
};

/**
 * A list of tags to display selected items.
 */
class Tags extends Component< Props > {
	constructor( props: Props ) {
		super( props );
		this.removeAll = this.removeAll.bind( this );
		this.removeResult = this.removeResult.bind( this );
	}

	removeAll() {
		const { onChange } = this.props;
		onChange( [] );
	}

	removeResult( key: string | undefined ) {
		return () => {
			const { selected, onChange } = this.props;
			if ( ! isArray( selected ) ) {
				return;
			}

			const i = findIndex( selected, { key } );
			onChange( [
				...selected.slice( 0, i ),
				...selected.slice( i + 1 ),
			] );
		};
	}

	render() {
		const { selected, showClearButton } = this.props;
		if ( ! isArray( selected ) || ! selected.length ) {
			return null;
		}

		return (
			<Fragment>
				<div className="woocommerce-select-control__tags">
					{ selected.map( ( item, i ) => {
						if ( ! item.label ) {
							return null;
						}
						const screenReaderLabel = sprintf(
							/* translators: %1$s: tag label, %2$s: tag number, %3$s: total number of tags */
							__( '%1$s (%2$s of %3$s)', 'woocommerce' ),
							item.label,
							i + 1,
							selected.length
						);
						return (
							<Tag
								key={ item.key }
								id={ item.key }
								label={ item.label }
								// @ts-expect-error key is a string or undefined here
								remove={ this.removeResult }
								screenReaderLabel={ screenReaderLabel }
							/>
						);
					} ) }
				</div>
				{ showClearButton && (
					<Button
						className="woocommerce-select-control__clear"
						isLink
						onClick={ this.removeAll }
					>
						<Icon
							icon={ cancelCircleFilled }
							className="clear-icon"
						/>
						<span className="screen-reader-text">
							{ __( 'Clear all', 'woocommerce' ) }
						</span>
					</Button>
				) }
			</Fragment>
		);
	}
}

export default Tags;
