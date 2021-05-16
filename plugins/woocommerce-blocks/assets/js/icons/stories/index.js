/**
 * External dependencies
 */
import { omitBy, omit } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Icon from '../icon';
import * as icons from '../index';

const availableIcons = omit( icons, 'Icon' );

export default {
	title: 'WooCommerce Blocks/@woocommerce/icons',
	component: Icon,
};

const LibraryExample = () => {
	const [ filter, setFilter ] = useState( '' );
	const filteredIcons = omitBy( availableIcons, ( _icon, name ) => {
		return ! name.includes( filter );
	} );

	return (
		<div style={ { padding: '20px' } }>
			<label htmlFor="filter-icons" style={ { paddingRight: '30px' } }>
				Filter Icons
			</label>
			<input
				// eslint-disable-next-line no-restricted-syntax
				id="filter-icons"
				type="search"
				value={ filter }
				placeholder="Icon name"
				onChange={ ( event ) => setFilter( event.target.value ) }
			/>
			<div
				style={ {
					display: 'flex',
					alignItems: 'bottom',
					flexWrap: 'wrap',
				} }
			>
				{ Object.entries( filteredIcons ).map( ( [ name, icon ] ) => {
					return (
						<div
							key={ name }
							style={ {
								display: 'flex',
								flexDirection: 'column',
								width: '25%',
								padding: '25px 0 25px 0',
							} }
						>
							<strong
								style={ {
									width: '200px',
								} }
							>
								{ name }
							</strong>
							<div
								style={ {
									display: 'flex',
									alignItems: 'center',
								} }
							>
								<Icon srcElement={ icon } />
								<Icon
									style={ { paddingLeft: '10px' } }
									srcElement={ icon }
									size={ 36 }
								/>
								<Icon
									style={ { paddingLeft: '10px' } }
									srcElement={ icon }
									size={ 48 }
								/>
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
};

export const Library = () => <LibraryExample />;
