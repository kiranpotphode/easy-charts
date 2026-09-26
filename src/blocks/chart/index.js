/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './editor.css';

registerBlockType( metadata.name, {
	edit: Edit,
	// Rendered on the server from the attributes.
	save: () => null,
} );
