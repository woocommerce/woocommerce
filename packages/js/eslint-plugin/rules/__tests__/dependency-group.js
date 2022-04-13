/**
 * External dependencies
 */
import { RuleTester } from 'eslint';

/**
 * Internal dependencies
 */
import rule from '../dependency-group';

const ruleTester = new RuleTester( {
	parserOptions: {
		sourceType: 'module',
		ecmaVersion: 6,
	},
} );

ruleTester.run( 'dependency-group', rule, {
	valid: [
		{
			code: `
/**
 * External dependencies
 */
import { get } from 'lodash';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { SearchListControl } from '@woocommerce/components';
import { withProductVariations } from '@woocommerce/block-hocs';
/**
 * Internal dependencies
 */
import edit from './edit';
import './style.scss';`,
		},
	],
	invalid: [
		{
			code: `
/**
 * External dependencies
 */
import { get } from 'lodash';
import './style.scss';
import { withProductVariations } from '@woocommerce/block-hocs';
/**
 * Internal dependencies
 */
import edit from './edit';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { SearchListControl } from '@woocommerce/components';`,
			errors: [
				{
					message:
						'Expected preceding "Internal dependencies" comment block',
				},
				{
					message:
						'Expected "External dependencies" to be defined before Internal',
				},
				{
					message:
						'Expected "External dependencies" to be defined before Internal',
				},
				{
					message:
						'Expected preceding "External dependencies" comment block',
				},
				{
					message:
						'Expected preceding "External dependencies" comment block',
				},
				{
					message:
						'Expected preceding "External dependencies" comment block',
				},
			],
		},
	],
} );
