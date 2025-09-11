import icons from './shortcode/blockIcon';
import DynamicShortcodeInput from './shortcode/dynamicShortcode';
import { escapeAttribute, escapeHTML } from "@wordpress/escape-html";
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, PanelRow } from '@wordpress/components';
import { Fragment, createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
const ServerSideRender = wp.serverSideRender;
const el = createElement;

/**
 * Register: aa Gutenberg Block.
 */
registerBlockType(
	'sp-logo-carousel-pro/shortcode',
	{
		title: __('Logo Carousel', 'logo-carousel-free'),
		description: __('Use Logo Carousel Pro to insert a carousel (shortcode) in your page.', 'logo-carousel-free'),
		icon: icons.teamPro,
		category: 'common',
		supports: {
			html: false,
		},
		edit: props => {
			const { attributes, setAttributes } = props;
			var shortCodeList = sp_logo_carousel_free_g.shortCodeList;
			let scriptLoad = (shortcodeId) => {
				let splcfBlockLoaded = false;
				let splcfBlockLoadedInterval = setInterval(function () {
					let uniqId = jQuery("#logo-carousel-free-" + shortcodeId).parents().attr('id');
					if (document.getElementById(uniqId)) {
						jQuery("#lcp-preloader-" + shortcodeId).css({ 'opacity': 0, 'display': 'none' });
						jQuery("#lcp-preloader-" + shortcodeId).animate({ opacity: 1 }, 600);
						//Actual functions goes here
						jQuery.getScript(sp_logo_carousel_free_g.loadScript);
						splcfBlockLoaded = true;
						uniqId = '';
					}
					if (splcfBlockLoaded) {
						clearInterval(splcfBlockLoadedInterval);
					}
					if (0 == shortcodeId) {
						clearInterval(splcfBlockLoadedInterval);
					}
				}, 100);
			}

			let updateShortcode = (updateShortcode) => {
				setAttributes({ shortcode: updateShortcode.target.value });
			}

			let shortcodeUpdate = (e) => {
				updateShortcode(e);
				let shortcodeId = e.target.value;
				scriptLoad(shortcodeId);
			}

			if (jQuery('.logo-carousel-free-area:not(.splc-logo-carousel-loaded)').length > 0 ) {
				let shortcodeId = escapeAttribute( attributes.shortcode );
				scriptLoad(shortcodeId);
			  }

			if (attributes.preview) {
				return (
					el('div', {},
						el('img', { src: escapeAttribute(sp_logo_carousel_free_g.path + 'admin/GutenbergBlock/assets/logo-carousel-block-preview.svg') })
					)
				)
			}

			if (shortCodeList.length === 0) {
				return (
					<Fragment>
						{
							el('div', { className: 'components-placeholder components-placeholder is-large' },
								el('div', { className: 'components-placeholder__label' },
									el('img', { className: 'block-editor-block-icon', src: escapeAttribute(sp_logo_carousel_free_g.path + 'admin/GutenbergBlock/assets/logo-carousel.svg') }),
									escapeHTML(__('Logo Carousel', 'logo-carousel-free'))
								),
								el('div', { className: 'components-placeholder__instructions' },
									escapeHTML(__("No logo carousel found. ", "logo-carousel-free")),
									el('a', { href: escapeAttribute(sp_logo_carousel_free_g.url) },
										escapeHTML(__("Create a carousel (shortcode) now!", "logo-carousel-free"))
									)
								)
							)
						}
					</Fragment>
				);
			}

			if (!attributes.shortcode || attributes.shortcode == 0) {
				return (
					<Fragment>
						<InspectorControls>
							<PanelBody title="Select a carousel (shortcode)">
								<PanelRow>
									<DynamicShortcodeInput
										attributes={attributes}
										shortCodeList={shortCodeList}
										shortcodeUpdate={shortcodeUpdate}
									/>
								</PanelRow>
							</PanelBody>
						</InspectorControls>
						{
							el('div', { className: 'components-placeholder components-placeholder is-large' },
								el('div', { className: 'components-placeholder__label' },
									el('img', { className: 'block-editor-block-icon', src: escapeAttribute(sp_logo_carousel_free_g.path + 'admin/GutenbergBlock/assets/logo-carousel.svg') }),
									escapeHTML(__("Logo Carousel", "logo-carousel-free"))
								),
								el('div', { className: 'components-placeholder__instructions' }, escapeHTML(__("Select a carousel (shortcode)", "logo-carousel-free"))),
								<DynamicShortcodeInput
									attributes={attributes}
									shortCodeList={shortCodeList}
									shortcodeUpdate={shortcodeUpdate}
								/>
							)
						}
					</Fragment>
				);
			}

			return (
				<Fragment>
					<InspectorControls>
						<PanelBody title="Select a carousel (shortcode)">
							<PanelRow>
								<DynamicShortcodeInput
									attributes={attributes}
									shortCodeList={shortCodeList}
									shortcodeUpdate={shortcodeUpdate}
								/>
							</PanelRow>
						</PanelBody>
					</InspectorControls>
					<ServerSideRender block="sp-logo-carousel-pro/shortcode" attributes={attributes} />
				</Fragment>
			);
		},
		save() {
			// Rendering in PHP
			return null;
		},
	});

