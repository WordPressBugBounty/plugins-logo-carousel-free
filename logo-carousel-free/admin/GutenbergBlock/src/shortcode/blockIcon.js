import { escapeAttribute } from "@wordpress/escape-html";
import { createElement } from '@wordpress/element';
const el = createElement;
const icons = {};
icons.teamPro = el('img', {src: escapeAttribute( sp_logo_carousel_free_g.path + 'admin/GutenbergBlock/assets/logo-carousel.svg' )})
export default icons;