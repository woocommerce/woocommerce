/**
 * Fields of the cheque settings page, in the order the form shows them.
 */

/**
 * Internal dependencies
 */
import type { SettingsField } from '../types';
import { field as enabled } from './enabled/field';
import { field as title } from './title/field';
import { field as description } from './description/field';
import { field as instructions } from './instructions/field';

const fields: SettingsField[] = [ enabled, title, description, instructions ];

export default fields;
