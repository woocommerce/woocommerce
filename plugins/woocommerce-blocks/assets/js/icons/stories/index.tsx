/**
 * External dependencies
 */
import { omitBy } from 'lodash';
import { Story, Meta } from '@storybook/react';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import * as icons from '../index';
import { IconProps } from '../icon';

const { Icon, ...availableIcons } = icons;

export default {
	title: 'WooCommerce Blocks/@woocommerce/icons',
	component: Icon,
	argTypes: {
		size: {
			control: { type: 'range', min: 8, max: 96 },
		},
		srcElement: {
			control: 'select',
			mapping: availableIcons,
			options: Object.keys( availableIcons ),
		},
	},
} as Meta< IconProps >;

const Template: Story< IconProps > = ( args ) => <Icon { ...args } />;

export const BaseIcon = Template.bind( {} );
BaseIcon.args = {
	srcElement: icons.woo,
	size: 24,
};

export const Library: Story< IconProps > = ( args ) => {
	const [ filter, setFilter ] = useState( '' );
	const filteredIcons = omitBy( availableIcons, ( _, name ) => {
		return ! name.includes( filter );
	} );

	return (
		<div style={ { padding: '20px' } }>
			<label htmlFor="filter-icons" style={ { paddingRight: '30px' } }>
				Filter Icons
			</label>
			<input
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
								<Icon
									className={ args.className }
									srcElement={ icon }
								/>
								<Icon
									className={ args.className }
									style={ { paddingLeft: '10px' } }
									srcElement={ icon }
									size={ 36 }
								/>
								<Icon
									className={ args.className }
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
Library.parameters = {
	controls: { include: [], hideNoControlsWarning: true },
};
Library.storyName = 'Icon Library';
