import './block.json';
import edit from './edit';
import save from './save';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType('easy-charts/easy-chart', { edit, save });
