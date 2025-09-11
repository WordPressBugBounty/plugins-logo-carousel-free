; (function ($, window, document, undefined) {
	'use strict';

	//
	// Constants
	//
	var SPLC = SPLC || {};

	SPLC.funcs = {};

	SPLC.vars = {
		onloaded: false,
		$body: $('body'),
		$window: $(window),
		$document: $(document),
		$form_warning: null,
		is_confirm: false,
		form_modified: false,
		code_themes: [],
		is_rtl: $('body').hasClass('rtl'),
	};

	//
	// Helper Functions
	//
	SPLC.helper = {

		//
		// Generate UID
		//
		uid: function (prefix) {
			return (prefix || '') + Math.random().toString(36).substr(2, 9);
		},

		// Quote regular expression characters
		//
		preg_quote: function (str) {
			return (str + '').replace(/(\[|\])/g, "\\$1");
		},

		//
		// Reneme input names
		//
		name_nested_replace: function ($selector, field_id) {

			var checks = [];
			var regex = new RegExp(SPLC.helper.preg_quote(field_id + '[\\d+]'), 'g');

			$selector.find(':radio').each(function () {
				if (this.checked || this.orginal_checked) {
					this.orginal_checked = true;
				}
			});

			$selector.each(function (index) {
				$(this).find(':input').each(function () {
					this.name = this.name.replace(regex, field_id + '[' + index + ']');
					if (this.orginal_checked) {
						this.checked = true;
					}
				});
			});

		},

		//
		// Debounce
		//
		debounce: function (callback, threshold, immediate) {
			var timeout;
			return function () {
				var context = this, args = arguments;
				var later = function () {
					timeout = null;
					if (!immediate) {
						callback.apply(context, args);
					}
				};
				var callNow = (immediate && !timeout);
				clearTimeout(timeout);
				timeout = setTimeout(later, threshold);
				if (callNow) {
					callback.apply(context, args);
				}
			};
		},

		//
		// Get a cookie
		//
		get_cookie: function (name) {

			var e, b, cookie = document.cookie, p = name + '=';

			if (!cookie) {
				return;
			}

			b = cookie.indexOf('; ' + p);

			if (b === -1) {
				b = cookie.indexOf(p);

				if (b !== 0) {
					return null;
				}
			} else {
				b += 2;
			}

			e = cookie.indexOf(';', b);

			if (e === -1) {
				e = cookie.length;
			}

			return decodeURIComponent(cookie.substring(b + p.length, e));

		},

		//
		// Set a cookie
		//
		set_cookie: function (name, value, expires, path, domain, secure) {

			var d = new Date();

			if (typeof (expires) === 'object' && expires.toGMTString) {
				expires = expires.toGMTString();
			} else if (parseInt(expires, 10)) {
				d.setTime(d.getTime() + (parseInt(expires, 10) * 1000));
				expires = d.toGMTString();
			} else {
				expires = '';
			}

			document.cookie = name + '=' + encodeURIComponent(value) +
				(expires ? '; expires=' + expires : '') +
				(path ? '; path=' + path : '') +
				(domain ? '; domain=' + domain : '') +
				(secure ? '; secure' : '');

		},

		//
		// Remove a cookie
		//
		remove_cookie: function (name, path, domain, secure) {
			SPLC.helper.set_cookie(name, '', -1000, path, domain, secure);
		},

	};

	//
	// Custom clone for textarea and select clone() bug
	//
	$.fn.splogocarousel_clone = function () {

		var base = $.fn.clone.apply(this, arguments),
			clone = this.find('select').add(this.filter('select')),
			cloned = base.find('select').add(base.filter('select'));

		for (var i = 0; i < clone.length; ++i) {
			for (var j = 0; j < clone[i].options.length; ++j) {

				if (clone[i].options[j].selected === true) {
					cloned[i].options[j].selected = true;
				}

			}
		}

		this.find(':radio').each(function () {
			this.orginal_checked = this.checked;
		});

		return base;

	};

	//
	// Expand All Options
	//
	$.fn.splogocarousel_expand_all = function () {
		return this.each(function () {
			$(this).on('click', function (e) {

				e.preventDefault();
				$('.splogocarousel-wrapper').toggleClass('splogocarousel-show-all');
				$('.splogocarousel-section').splogocarousel_reload_script();
				$(this).find('.fa').toggleClass('fa-indent').toggleClass('fa-outdent');

			});
		});
	};

	//
	// Options Navigation
	//
	$.fn.splogocarousel_nav_options = function () {
		return this.each(function () {

			var $nav = $(this),
				$links = $nav.find('a'),
				$last;

			$(window).on('hashchange splogocarousel.hashchange', function () {

				var hash = window.location.hash.replace('#tab=', '');
				var slug = hash ? hash : $links.first().attr('href').replace('#tab=', '');
				var $link = $('[data-tab-id="' + slug + '"]');

				if ($link.length) {

					$link.closest('.splogocarousel-tab-item').addClass('splogocarousel-tab-expanded').siblings().removeClass('splogocarousel-tab-expanded');

					if ($link.next().is('ul')) {

						$link = $link.next().find('li').first().find('a');
						slug = $link.data('tab-id');

					}

					$links.removeClass('splogocarousel-active');
					$link.addClass('splogocarousel-active');

					if ($last) {
						$last.addClass('hidden');
					}

					var $section = $('[data-section-id="' + slug + '"]');

					$section.removeClass('hidden');
					$section.splogocarousel_reload_script();

					$('.splogocarousel-section-id').val($section.index() + 1);

					$last = $section;

				}

			}).trigger('splogocarousel.hashchange');

		});
	};

	//
	// Metabox Tabs.
	//
	$.fn.splogocarousel_nav_metabox = function () {
		return this.each(function () {

			var $nav = $(this),
				$links = $nav.find('a'),
				unique_id = $nav.data('unique'),
				post_id = $('#post_ID').val() || 'global',
				$last_section,
				$last_link;

			$links.on('click', function (e) {

				e.preventDefault();

				var $link = $(this),
					section_id = $link.data('section');

				if ($last_link !== undefined) {
					$last_link.removeClass('splogocarousel-section-active');
				}

				if ($last_section !== undefined) {
					$last_section.hide();
				}

				$link.addClass('splogocarousel-section-active');

				var $section = $('#splogocarousel-section-' + section_id);
				$section.show();
				$section.splogocarousel_reload_script();

				SPLC.helper.set_cookie('splogocarousel-last-metabox-tab-' + post_id + '-' + unique_id, section_id);

				$last_section = $section;
				$last_link = $link;

			});

			var get_cookie = SPLC.helper.get_cookie('splogocarousel-last-metabox-tab-' + post_id + '-' + unique_id);

			if (get_cookie) {
				$nav.find('a[data-section="' + get_cookie + '"]').trigger('click');
			} else {
				$links.first('a').trigger('click');
			}

		});
	};

	//
	// Metabox Page Templates Listener
	//
	$.fn.splogocarousel_page_templates = function () {
		if (this.length) {

			$(document).on('change', '.editor-page-attributes__template select, #page_template', function () {

				var maybe_value = $(this).val() || 'default';

				$('.splogocarousel-page-templates').removeClass('splogocarousel-metabox-show').addClass('splogocarousel-metabox-hide');
				$('.splogocarousel-page-' + maybe_value.toLowerCase().replace(/[^a-zA-Z0-9]+/g, '-')).removeClass('splogocarousel-metabox-hide').addClass('splogocarousel-metabox-show');

			});

		}
	};

	//
	// Metabox Post Formats Listener
	//
	$.fn.splogocarousel_post_formats = function () {
		if (this.length) {

			$(document).on('change', '.editor-post-format select, #formatdiv input[name="post_format"]', function () {

				var maybe_value = $(this).val() || 'default';

				// Fallback for classic editor version
				maybe_value = (maybe_value === '0') ? 'default' : maybe_value;

				$('.splogocarousel-post-formats').removeClass('splogocarousel-metabox-show').addClass('splogocarousel-metabox-hide');
				$('.splogocarousel-post-format-' + maybe_value).removeClass('splogocarousel-metabox-hide').addClass('splogocarousel-metabox-show');

			});

		}
	};

	//
	// Search
	//
	$.fn.splogocarousel_search = function () {
		return this.each(function () {

			var $this = $(this),
				$input = $this.find('input');

			$input.on('change keyup', function () {

				var value = $(this).val(),
					$wrapper = $('.splogocarousel-wrapper'),
					$section = $wrapper.find('.splogocarousel-section'),
					$fields = $section.find('> .splogocarousel-field:not(.splogocarousel-depend-on)'),
					$titles = $fields.find('> .splogocarousel-title, .splogocarousel-search-tags');

				if (value.length > 3) {

					$fields.addClass('splogocarousel-metabox-hide');
					$wrapper.addClass('splogocarousel-search-all');

					$titles.each(function () {

						var $title = $(this);

						if ($title.text().match(new RegExp('.*?' + value + '.*?', 'i'))) {

							var $field = $title.closest('.splogocarousel-field');

							$field.removeClass('splogocarousel-metabox-hide');
							$field.parent().splogocarousel_reload_script();

						}

					});

				} else {

					$fields.removeClass('splogocarousel-metabox-hide');
					$wrapper.removeClass('splogocarousel-search-all');

				}

			});

		});
	};

	//
	// Sticky Header
	//
	$.fn.splogocarousel_sticky = function () {
		return this.each(function () {

			var $this = $(this),
				$window = $(window),
				$inner = $this.find('.splogocarousel-header-inner'),
				padding = parseInt($inner.css('padding-left')) + parseInt($inner.css('padding-right')),
				offset = 32,
				scrollTop = 0,
				lastTop = 0,
				ticking = false,
				stickyUpdate = function () {

					var offsetTop = $this.offset().top,
						stickyTop = Math.max(offset, offsetTop - scrollTop),
						winWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

					if (stickyTop <= offset && winWidth > 782) {
						$inner.css({ width: $this.outerWidth() - padding });
						$this.css({ height: $this.outerHeight() }).addClass('splogocarousel-sticky');
					} else {
						$inner.removeAttr('style');
						$this.removeAttr('style').removeClass('splogocarousel-sticky');
					}

				},
				requestTick = function () {

					if (!ticking) {
						requestAnimationFrame(function () {
							stickyUpdate();
							ticking = false;
						});
					}

					ticking = true;

				},
				onSticky = function () {

					scrollTop = $window.scrollTop();
					requestTick();

				};

			$window.on('scroll resize', onSticky);

			onSticky();

		});
	};

	//
	// Dependency System
	//
	$.fn.splogocarousel_dependency = function () {
		return this.each(function () {

			var $this = $(this),
				$fields = $this.children('[data-controller]');

			if ($fields.length) {

				var normal_ruleset = $.splogocarousel_deps.createRuleset(),
					global_ruleset = $.splogocarousel_deps.createRuleset(),
					normal_depends = [],
					global_depends = [];

				$fields.each(function () {

					var $field = $(this),
						controllers = $field.data('controller').split('|'),
						conditions = $field.data('condition').split('|'),
						values = $field.data('value').toString().split('|'),
						is_global = $field.data('depend-global') ? true : false,
						ruleset = (is_global) ? global_ruleset : normal_ruleset;

					$.each(controllers, function (index, depend_id) {

						var value = values[index] || '',
							condition = conditions[index] || conditions[0];

						ruleset = ruleset.createRule('[data-depend-id="' + depend_id + '"]', condition, value);

						ruleset.include($field);

						if (is_global) {
							global_depends.push(depend_id);
						} else {
							normal_depends.push(depend_id);
						}

					});

				});

				if (normal_depends.length) {
					$.splogocarousel_deps.enable($this, normal_ruleset, normal_depends);
				}

				if (global_depends.length) {
					$.splogocarousel_deps.enable(SPLC.vars.$body, global_ruleset, global_depends);
				}

			}

		});
	};


	//
	// Field: background
	//
	$.fn.splogocarousel_field_background = function () {
		return this.each(function () {
			$(this).find('.splogocarousel--background-image').splogocarousel_reload_script();
		});
	};

	//
	// Field: code_editor
	//
	$.fn.splogocarousel_field_code_editor = function () {
		return this.each(function () {
			if (typeof wp === 'undefined' || typeof wp.codeEditor === 'undefined') {
				return;
			}

			var $this = $(this),
				$textarea = $this.find('textarea'),
				settings = $textarea.data('editor') || {};

			// Merge with WP defaults
			var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
			editorSettings.codemirror = _.extend(
				{},
				editorSettings.codemirror,
				settings
			);

			// Initialize editor
			var editor = wp.codeEditor.initialize($textarea[0], editorSettings);
			// Sync changes back to textarea
			editor.codemirror.on('change', function () {
				$textarea.val(editor.codemirror.getValue()).trigger('change');
			});
		});
	};


	//
	// Field: link
	//
	$.fn.splogocarousel_field_link = function () {
		return this.each(function () {

			var $this = $(this),
				$link = $this.find('.splogocarousel--link'),
				$add = $this.find('.splogocarousel--add'),
				$edit = $this.find('.splogocarousel--edit'),
				$remove = $this.find('.splogocarousel--remove'),
				$result = $this.find('.splogocarousel--result'),
				uniqid = SPLC.helper.uid('splogocarousel-wplink-textarea-');

			$add.on('click', function (e) {

				e.preventDefault();

				window.wpLink.open(uniqid);

			});

			$edit.on('click', function (e) {

				e.preventDefault();

				$add.trigger('click');

				$('#wp-link-url').val($this.find('.splogocarousel--url').val());
				$('#wp-link-text').val($this.find('.splogocarousel--text').val());
				$('#wp-link-target').prop('checked', ($this.find('.splogocarousel--target').val() === '_blank'));

			});

			$remove.on('click', function (e) {

				e.preventDefault();

				$this.find('.splogocarousel--url').val('').trigger('change');
				$this.find('.splogocarousel--text').val('');
				$this.find('.splogocarousel--target').val('');

				$add.removeClass('hidden');
				$edit.addClass('hidden');
				$remove.addClass('hidden');
				$result.parent().addClass('hidden');

			});

			$link.attr('id', uniqid).on('change', function () {

				var atts = window.wpLink.getAttrs(),
					href = atts.href,
					text = $('#wp-link-text').val(),
					target = (atts.target) ? atts.target : '';

				$this.find('.splogocarousel--url').val(href).trigger('change');
				$this.find('.splogocarousel--text').val(text);
				$this.find('.splogocarousel--target').val(target);

				$result.html('{url:"' + href + '", text:"' + text + '", target:"' + target + '"}');

				$add.addClass('hidden');
				$edit.removeClass('hidden');
				$remove.removeClass('hidden');
				$result.parent().removeClass('hidden');

			});

		});

	};

	//
	// Field: tabbed
	//
	$.fn.splogocarousel_field_tabbed = function () {
		return this.each(function () {
			var $this = $(this),
				$links = $this.find('.splogocarousel-tabbed-nav a'),
				$sections = $this.find('.splogocarousel-tabbed-section');

			$links.on('click', function (e) {
				e.preventDefault();

				var $link = $(this),
					index = $link.index(),
					$section = $sections.eq(index);

				// Store the active tab index in a cookie
				SPLC.helper.set_cookie('activeTabIndex', index);

				$link.addClass('splogocarousel-tabbed-active').siblings().removeClass('splogocarousel-tabbed-active');
				$section.splogocarousel_reload_script();
				$section.removeClass('hidden').siblings().addClass('hidden');
			});
			// Check if there's a stored active tab index in the cookie
			var activeTabIndex = SPLC.helper.get_cookie('activeTabIndex');
			// Check if the cookie exists
			if (activeTabIndex !== null) {
				$links.eq(activeTabIndex).trigger('click');
			} else {
				$links.first().trigger('click');
			}

		});
	};


	//
	// Field: slider
	//
	$.fn.splogocarousel_field_slider = function () {
		return this.each(function () {

			var $this = $(this),
				$input = $this.find('input'),
				$slider = $this.find('.splogocarousel-slider-ui'),
				data = $input.data(),
				value = $input.val() || 0;

			if ($slider.hasClass('ui-slider')) {
				$slider.empty();
			}

			$slider.slider({
				range: 'min',
				value: value,
				min: data.min || 0,
				max: data.max || 100,
				step: data.step || 1,
				slide: function (e, o) {
					$input.val(o.value).trigger('change');
				}
			});

			$input.on('keyup', function () {
				$slider.slider('value', $input.val());
			});

		});
	};

	//
	// Field: spinner
	//
	$.fn.splogocarousel_field_spinner = function () {
		return this.each(function () {

			var $this = $(this),
				$input = $this.find('input'),
				$inited = $this.find('.ui-button'),
				data = $input.data();

			if ($inited.length) {
				$inited.remove();
			}

			$input.spinner({
				min: data.min || 0,
				max: data.max || 100,
				step: data.step || 1,
				create: function (event, ui) {
					if (data.unit) {
						$input.after('<span class="ui-button splogocarousel--unit">' + data.unit + '</span>');
					}
				},
				spin: function (event, ui) {
					$input.val(ui.value).trigger('change');
				}
			});

		});
	};

	//
	// Field: fieldset
	//
	$.fn.splogocarousel_field_fieldset = function () {
		return this.each(function () {
			$(this).find('.splogocarousel-fieldset-content').splogocarousel_reload_script();
		});
	};

	//
	// Field: switcher
	//
	$.fn.splogocarousel_field_switcher = function () {
		return this.each(function () {

			var $switcher = $(this).find('.splogocarousel--switcher');

			$switcher.on('click', function () {

				var value = 0;
				var $input = $switcher.find('input');

				if ($switcher.hasClass('splogocarousel--active')) {
					$switcher.removeClass('splogocarousel--active');
				} else {
					value = 1;
					$switcher.addClass('splogocarousel--active');
				}

				$input.val(value).trigger('change');

			});

		});
	};

	//
	// Confirm
	//
	$.fn.splogocarousel_confirm = function () {
		return this.each(function () {
			$(this).on('click', function (e) {

				var confirm_text = $(this).data('confirm') || window.splogocarousel_vars.i18n.confirm;
				var confirm_answer = confirm(confirm_text);

				if (confirm_answer) {
					SPLC.vars.is_confirm = true;
					SPLC.vars.form_modified = false;
				} else {
					e.preventDefault();
					return false;
				}

			});
		});
	};

	$.fn.serializeObject = function () {

		var obj = {};

		$.each(this.serializeArray(), function (i, o) {
			var n = o.name,
				v = o.value;

			obj[n] = obj[n] === undefined ? v
				: $.isArray(obj[n]) ? obj[n].concat(v)
					: [obj[n], v];
		});

		return obj;

	};

	//
	// Options Save
	//
	$.fn.splogocarousel_save = function () {
		return this.each(function () {

			var $this = $(this),
				$buttons = $('.splogocarousel-save'),
				$panel = $('.splogocarousel-options'),
				flooding = false,
				timeout;

			$this.on('click', function (e) {

				if (!flooding) {

					var $text = $this.data('save'),
						$value = $this.val();

					$buttons.attr('value', $text);

					if ($this.hasClass('splogocarousel-save-ajax')) {

						e.preventDefault();

						$panel.addClass('splogocarousel-saving');
						$buttons.prop('disabled', true);

						window.wp.ajax.post('splogocarousel_' + $panel.data('unique') + '_ajax_save', {
							data: $('#splogocarousel-form').serializeJSONSPLC(),
							nonce: $('#splogocarousel_options_nonce' + $panel.data('unique')).val(),
						})
							.done(function (response) {
								// clear errors
								$('.splogocarousel-error').remove();

								if (Object.keys(response.errors).length) {

									var error_icon = '<i class="splogocarousel-label-error splogocarousel-error">!</i>';

									$.each(response.errors, function (key, error_message) {

										var $field = $('[data-depend-id="' + key + '"]'),
											$link = $('a[href="#tab=' + $field.closest('.splogocarousel-section').data('section-id') + '"]'),
											$tab = $link.closest('.splogocarousel-tab-item');

										$field.closest('.splogocarousel-fieldset').append('<p class="splogocarousel-error splogocarousel-error-text">' + error_message + '</p>');

										if (!$link.find('.splogocarousel-error').length) {
											$link.append(error_icon);
										}

										if (!$tab.find('.splogocarousel-arrow .splogocarousel-error').length) {
											$tab.find('.splogocarousel-arrow').append(error_icon);
										}

									});

								}

								$panel.removeClass('splogocarousel-saving');
								$buttons.prop('disabled', false).attr('value', $value);
								flooding = false;

								SPLC.vars.form_modified = false;
								SPLC.vars.$form_warning.hide();

								clearTimeout(timeout);

								var $result_success = $('.splogocarousel-form-success');
								$result_success.empty().append(response.notice).fadeIn('fast', function () {
									timeout = setTimeout(function () {
										$result_success.fadeOut('fast');
									}, 1000);
								});

							})
							.fail(function (response) {
								alert(response.error);
							});

					} else {

						SPLC.vars.form_modified = false;

					}

				}

				flooding = true;

			});

		});
	};

	//
	// Option Framework
	//
	$.fn.splogocarousel_options = function () {
		return this.each(function () {

			var $this = $(this),
				$content = $this.find('.splogocarousel-content'),
				$form_success = $this.find('.splogocarousel-form-success'),
				$form_warning = $this.find('.splogocarousel-form-warning'),
				$save_button = $this.find('.splogocarousel-header .splogocarousel-save');

			SPLC.vars.$form_warning = $form_warning;

			// Shows a message white leaving theme options without saving
			if ($form_warning.length) {

				window.onbeforeunload = function () {
					return (SPLC.vars.form_modified) ? true : undefined;
				};

				$content.on('change keypress', ':input', function () {
					if (!SPLC.vars.form_modified) {
						$form_success.hide();
						$form_warning.fadeIn('fast');
						SPLC.vars.form_modified = true;
					}
				});

			}

			if ($form_success.hasClass('splogocarousel-form-show')) {
				setTimeout(function () {
					$form_success.fadeOut('fast');
				}, 1000);
			}

			$(document).on('keydown', function (event) {
				if ((event.ctrlKey || event.metaKey) && event.which === 83) {
					$save_button.trigger('click');
					event.preventDefault();
					return false;
				}
			});

		});
	};

	//
	// Taxonomy Framework
	//
	$.fn.splogocarousel_taxonomy = function () {
		return this.each(function () {

			var $this = $(this),
				$form = $this.parents('form');

			if ($form.attr('id') === 'addtag') {

				var $submit = $form.find('#submit'),
					$cloned = $this.find('.splogocarousel-field').splogocarousel_clone();

				$submit.on('click', function () {

					if (!$form.find('.form-required').hasClass('form-invalid')) {

						$this.data('inited', false);

						$this.empty();

						$this.html($cloned);

						$cloned = $cloned.splogocarousel_clone();

						$this.splogocarousel_reload_script();

					}

				});

			}

		});
	};

	//
	// Shortcode Framework
	//
	$.fn.splogocarousel_shortcode = function () {

		var base = this;

		base.shortcode_parse = function (serialize, key) {

			var shortcode = '';

			$.each(serialize, function (shortcode_key, shortcode_values) {

				key = (key) ? key : shortcode_key;

				shortcode += '[' + key;

				$.each(shortcode_values, function (shortcode_tag, shortcode_value) {

					if (shortcode_tag === 'content') {

						shortcode += ']';
						shortcode += shortcode_value;
						shortcode += '[/' + key + '';

					} else {

						shortcode += base.shortcode_tags(shortcode_tag, shortcode_value);

					}

				});

				shortcode += ']';

			});

			return shortcode;

		};

		base.shortcode_tags = function (shortcode_tag, shortcode_value) {

			var shortcode = '';

			if (shortcode_value !== '') {

				if (typeof shortcode_value === 'object' && !$.isArray(shortcode_value)) {

					$.each(shortcode_value, function (sub_shortcode_tag, sub_shortcode_value) {

						// sanitize spesific key/value
						switch (sub_shortcode_tag) {

							case 'background-image':
								sub_shortcode_value = (sub_shortcode_value.url) ? sub_shortcode_value.url : '';
								break;

						}

						if (sub_shortcode_value !== '') {
							shortcode += ' ' + sub_shortcode_tag.replace('-', '_') + '="' + sub_shortcode_value.toString() + '"';
						}

					});

				} else {

					shortcode += ' ' + shortcode_tag.replace('-', '_') + '="' + shortcode_value.toString() + '"';

				}

			}

			return shortcode;

		};

		base.insertAtChars = function (_this, currentValue) {

			var obj = (typeof _this[0].name !== 'undefined') ? _this[0] : _this;

			if (obj.value.length && typeof obj.selectionStart !== 'undefined') {
				obj.focus();
				return obj.value.substring(0, obj.selectionStart) + currentValue + obj.value.substring(obj.selectionEnd, obj.value.length);
			} else {
				obj.focus();
				return currentValue;
			}

		};

		base.send_to_editor = function (html, editor_id) {

			var tinymce_editor;

			if (typeof tinymce !== 'undefined') {
				tinymce_editor = tinymce.get(editor_id);
			}

			if (tinymce_editor && !tinymce_editor.isHidden()) {
				tinymce_editor.execCommand('mceInsertContent', false, html);
			} else {
				var $editor = $('#' + editor_id);
				$editor.val(base.insertAtChars($editor, html)).trigger('change');
			}

		};

		return this.each(function () {

			var $modal = $(this),
				$load = $modal.find('.splogocarousel-modal-load'),
				$content = $modal.find('.splogocarousel-modal-content'),
				$insert = $modal.find('.splogocarousel-modal-insert'),
				$loading = $modal.find('.splogocarousel-modal-loading'),
				$select = $modal.find('select'),
				modal_id = $modal.data('modal-id'),
				nonce = $modal.data('nonce'),
				editor_id,
				target_id,
				sc_key,
				sc_name,
				sc_view,
				sc_group,
				$cloned,
				$button;

			$(document).on('click', '.splogocarousel-shortcode-button[data-modal-id="' + modal_id + '"]', function (e) {

				e.preventDefault();

				$button = $(this);
				editor_id = $button.data('editor-id') || false;
				target_id = $button.data('target-id') || false;

				$modal.removeClass('hidden');

				// single usage trigger first shortcode
				if ($modal.hasClass('splogocarousel-shortcode-single') && sc_name === undefined) {
					$select.trigger('change');
				}

			});

			$select.on('change', function () {

				var $option = $(this);
				var $selected = $option.find(':selected');

				sc_key = $option.val();
				sc_name = $selected.data('shortcode');
				sc_view = $selected.data('view') || 'normal';
				sc_group = $selected.data('group') || sc_name;

				$load.empty();

				if (sc_key) {

					$loading.show();

					window.wp.ajax.post('splogocarousel-get-shortcode-' + modal_id, {
						shortcode_key: sc_key,
						nonce: nonce
					})
						.done(function (response) {

							$loading.hide();

							var $appended = $(response.content).appendTo($load);

							$insert.parent().removeClass('hidden');

							$cloned = $appended.find('.splogocarousel--repeat-shortcode').splogocarousel_clone();

							$appended.splogocarousel_reload_script();
							$appended.find('.splogocarousel-fields').splogocarousel_reload_script();

						});

				} else {

					$insert.parent().addClass('hidden');

				}

			});

			$insert.on('click', function (e) {

				e.preventDefault();

				if ($insert.prop('disabled') || $insert.attr('disabled')) { return; }

				var shortcode = '';
				var serialize = $modal.find('.splogocarousel-field:not(.splogocarousel-depend-on)').find(':input:not(.ignore)').serializeObjectSPLC();

				switch (sc_view) {

					case 'contents':
						var contentsObj = (sc_name) ? serialize[sc_name] : serialize;
						$.each(contentsObj, function (sc_key, sc_value) {
							var sc_tag = (sc_name) ? sc_name : sc_key;
							shortcode += '[' + sc_tag + ']' + sc_value + '[/' + sc_tag + ']';
						});
						break;

					case 'group':

						shortcode += '[' + sc_name;
						$.each(serialize[sc_name], function (sc_key, sc_value) {
							shortcode += base.shortcode_tags(sc_key, sc_value);
						});
						shortcode += ']';
						shortcode += base.shortcode_parse(serialize[sc_group], sc_group);
						shortcode += '[/' + sc_name + ']';

						break;

					case 'repeater':
						shortcode += base.shortcode_parse(serialize[sc_group], sc_group);
						break;

					default:
						shortcode += base.shortcode_parse(serialize);
						break;

				}

				shortcode = (shortcode === '') ? '[' + sc_name + ']' : shortcode;

				if (editor_id) {

					base.send_to_editor(shortcode, editor_id);

				} else {

					var $textarea = (target_id) ? $(target_id) : $button.parent().find('textarea');
					$textarea.val(base.insertAtChars($textarea, shortcode)).trigger('change');

				}

				$modal.addClass('hidden');

			});

			$modal.on('click', '.splogocarousel--repeat-button', function (e) {

				e.preventDefault();

				var $repeatable = $modal.find('.splogocarousel--repeatable');
				var $new_clone = $cloned.splogocarousel_clone();
				var $remove_btn = $new_clone.find('.splogocarousel-repeat-remove');

				var $appended = $new_clone.appendTo($repeatable);

				$new_clone.find('.splogocarousel-fields').splogocarousel_reload_script();

				SPLC.helper.name_nested_replace($modal.find('.splogocarousel--repeat-shortcode'), sc_group);

				$remove_btn.on('click', function () {

					$new_clone.remove();

					SPLC.helper.name_nested_replace($modal.find('.splogocarousel--repeat-shortcode'), sc_group);

				});

			});

			$modal.on('click', '.splogocarousel-modal-close, .splogocarousel-modal-overlay', function () {
				$modal.addClass('hidden');
			});

		});
	};

	//
	// WP Color Picker
	//
	if (typeof Color === 'function') {

		Color.prototype.toString = function () {

			if (this._alpha < 1) {
				return this.toCSS('rgba', this._alpha).replace(/\s+/g, '');
			}

			var hex = parseInt(this._color, 10).toString(16);

			if (this.error) { return ''; }

			if (hex.length < 6) {
				for (var i = 6 - hex.length - 1; i >= 0; i--) {
					hex = '0' + hex;
				}
			}

			return '#' + hex;

		};

	}

	SPLC.funcs.parse_color = function (color) {

		var value = color.replace(/\s+/g, ''),
			trans = (value.indexOf('rgba') !== -1) ? parseFloat(value.replace(/^.*,(.+)\)/, '$1') * 100) : 100,
			rgba = (trans < 100) ? true : false;

		return { value: value, transparent: trans, rgba: rgba };

	};

	$.fn.splogocarousel_color = function () {
		return this.each(function () {

			var $input = $(this),
				picker_color = SPLC.funcs.parse_color($input.val()),
				palette_color = window.splogocarousel_vars.color_palette.length ? window.splogocarousel_vars.color_palette : true,
				$container;

			// Destroy and Reinit
			if ($input.hasClass('wp-color-picker')) {
				$input.closest('.wp-picker-container').after($input).remove();
			}

			$input.wpColorPicker({
				palettes: palette_color,
				change: function (event, ui) {

					var ui_color_value = ui.color.toString();

					$container.removeClass('splogocarousel--transparent-active');
					$container.find('.splogocarousel--transparent-offset').css('background-color', ui_color_value);
					$input.val(ui_color_value).trigger('change');

				},
				create: function () {

					$container = $input.closest('.wp-picker-container');

					var a8cIris = $input.data('a8cIris'),
						$transparent_wrap = $('<div class="splogocarousel--transparent-wrap">' +
							'<div class="splogocarousel--transparent-slider"></div>' +
							'<div class="splogocarousel--transparent-offset"></div>' +
							'<div class="splogocarousel--transparent-text"></div>' +
							'<div class="splogocarousel--transparent-button">transparent <i class="fa fa-toggle-off"></i></div>' +
							'</div>').appendTo($container.find('.wp-picker-holder')),
						$transparent_slider = $transparent_wrap.find('.splogocarousel--transparent-slider'),
						$transparent_text = $transparent_wrap.find('.splogocarousel--transparent-text'),
						$transparent_offset = $transparent_wrap.find('.splogocarousel--transparent-offset'),
						$transparent_button = $transparent_wrap.find('.splogocarousel--transparent-button');

					if ($input.val() === 'transparent') {
						$container.addClass('splogocarousel--transparent-active');
					}

					$transparent_button.on('click', function () {
						if ($input.val() !== 'transparent') {
							$input.val('transparent').trigger('change').removeClass('iris-error');
							$container.addClass('splogocarousel--transparent-active');
						} else {
							$input.val(a8cIris._color.toString()).trigger('change');
							$container.removeClass('splogocarousel--transparent-active');
						}
					});

					$transparent_slider.slider({
						value: picker_color.transparent,
						step: 1,
						min: 0,
						max: 100,
						slide: function (event, ui) {

							var slide_value = parseFloat(ui.value / 100);
							a8cIris._color._alpha = slide_value;
							$input.wpColorPicker('color', a8cIris._color.toString());
							$transparent_text.text((slide_value === 1 || slide_value === 0 ? '' : slide_value));

						},
						create: function () {

							var slide_value = parseFloat(picker_color.transparent / 100),
								text_value = slide_value < 1 ? slide_value : '';

							$transparent_text.text(text_value);
							$transparent_offset.css('background-color', picker_color.value);

							$container.on('click', '.wp-picker-clear', function () {

								a8cIris._color._alpha = 1;
								$transparent_text.text('');
								$transparent_slider.slider('option', 'value', 100);
								$container.removeClass('splogocarousel--transparent-active');
								$input.trigger('change');

							});

							$container.on('click', '.wp-picker-default', function () {

								var default_color = SPLC.funcs.parse_color($input.data('default-color')),
									default_value = parseFloat(default_color.transparent / 100),
									default_text = default_value < 1 ? default_value : '';

								a8cIris._color._alpha = default_value;
								$transparent_text.text(default_text);
								$transparent_slider.slider('option', 'value', default_color.transparent);

								if (default_color.value === 'transparent') {
									$input.removeClass('iris-error');
									$container.addClass('splogocarousel--transparent-active');
								}

							});

						}
					});
				}
			});

		});
	};

	//
	// ChosenJS
	//
	$.fn.splogocarousel_chosen = function () {
		return this.each(function () {

			var $this = $(this),
				$inited = $this.parent().find('.chosen-container'),
				is_sortable = $this.hasClass('splogocarousel-chosen-sortable') || false,
				is_ajax = $this.hasClass('splogocarousel-chosen-ajax') || false,
				is_multiple = $this.attr('multiple') || false,
				set_width = is_multiple ? '100%' : 'auto',
				set_options = $.extend({
					allow_single_deselect: true,
					disable_search_threshold: 10,
					width: set_width,
					no_results_text: window.splogocarousel_vars.i18n.no_results_text,
				}, $this.data('chosen-settings'));

			if ($inited.length) {
				$inited.remove();
			}

			// Chosen ajax
			if (is_ajax) {

				var set_ajax_options = $.extend({
					data: {
						type: 'post',
						nonce: '',
					},
					allow_single_deselect: true,
					disable_search_threshold: -1,
					width: '100%',
					min_length: 3,
					type_delay: 500,
					typing_text: window.splogocarousel_vars.i18n.typing_text,
					searching_text: window.splogocarousel_vars.i18n.searching_text,
					no_results_text: window.splogocarousel_vars.i18n.no_results_text,
				}, $this.data('chosen-settings'));

				$this.SPLCAjaxChosen(set_ajax_options);

			} else {

				$this.chosen(set_options);

			}

			// Chosen keep options order
			if (is_multiple) {

				var $hidden_select = $this.parent().find('.splogocarousel-hide-select');
				var $hidden_value = $hidden_select.val() || [];

				$this.on('change', function (obj, result) {

					if (result && result.selected) {
						$hidden_select.append('<option value="' + result.selected + '" selected="selected">' + result.selected + '</option>');
					} else if (result && result.deselected) {
						$hidden_select.find('option[value="' + result.deselected + '"]').remove();
					}

					// Force customize refresh
					if (window.wp.customize !== undefined && $hidden_select.children().length === 0 && $hidden_select.data('customize-setting-link')) {
						window.wp.customize.control($hidden_select.data('customize-setting-link')).setting.set('');
					}

					$hidden_select.trigger('change');

				});

				// Chosen order abstract
				$this.SPLCChosenOrder($hidden_value, true);

			}

			// Chosen sortable
			if (is_sortable) {

				var $chosen_container = $this.parent().find('.chosen-container');
				var $chosen_choices = $chosen_container.find('.chosen-choices');

				$chosen_choices.bind('mousedown', function (event) {
					if ($(event.target).is('span')) {
						event.stopPropagation();
					}
				});

				$chosen_choices.sortable({
					items: 'li:not(.search-field)',
					helper: 'orginal',
					cursor: 'move',
					placeholder: 'search-choice-placeholder',
					start: function (e, ui) {
						ui.placeholder.width(ui.item.innerWidth());
						ui.placeholder.height(ui.item.innerHeight());
					},
					update: function (e, ui) {

						var select_options = '';
						var chosen_object = $this.data('chosen');
						var $prev_select = $this.parent().find('.splogocarousel-hide-select');

						$chosen_choices.find('.search-choice-close').each(function () {
							var option_array_index = $(this).data('option-array-index');
							$.each(chosen_object.results_data, function (index, data) {
								if (data.array_index === option_array_index) {
									select_options += '<option value="' + data.value + '" selected>' + data.value + '</option>';
								}
							});
						});

						$prev_select.children().remove();
						$prev_select.append(select_options);
						$prev_select.trigger('change');

					}
				});

			}

		});
	};

	//
	// Helper Checkbox Checker
	//
	$.fn.splogocarousel_checkbox = function () {
		return this.each(function () {

			var $this = $(this),
				$input = $this.find('.splogocarousel--input'),
				$checkbox = $this.find('.splogocarousel--checkbox');

			$checkbox.on('click', function () {
				$input.val(Number($checkbox.prop('checked'))).trigger('change');
			});

		});
	};

	//
	// Siblings
	//
	$.fn.splogocarousel_siblings = function () {
		return this.each(function () {

			var $this = $(this),
				$siblings = $this.find('.splogocarousel--sibling:not(.sp-lc-pro-only)'),
				multiple = $this.data('multiple') || false;

			$siblings.on('click', function () {

				var $sibling = $(this);

				if (multiple) {

					if ($sibling.hasClass('splogocarousel--active')) {
						$sibling.removeClass('splogocarousel--active');
						$sibling.find('input').prop('checked', false).trigger('change');
					} else {
						$sibling.addClass('splogocarousel--active');
						$sibling.find('input').prop('checked', true).trigger('change');
					}

				} else {

					$this.find('input').prop('checked', false);
					$sibling.find('input').prop('checked', true).trigger('change');
					$sibling.addClass('splogocarousel--active').siblings().removeClass('splogocarousel--active');

				}

			});

		});
	};

	//
	// Help Tooltip
	//
	$.fn.splogocarousel_help = function () {
		return this.each(function () {
			var $this = $(this);
			var $tooltip;
			var $class = '';
			$this.on({
				mouseenter: function () {
					// this class add with the support tooltip.
					if ($this.find('.splogocarousel-support').length > 0) {
						$class = 'support-tooltip';
					}
					$tooltip = $('<div class="splogocarousel-tooltip ' + $class + '"></div>')
						.html($this.find('.splogocarousel-help-text').html())
						.appendTo('body');

					var offset_left = SPLC.vars.is_rtl
						? $this.offset().left - $tooltip.outerWidth()
						: $this.offset().left + 24;
					var $top = $this.offset().top - ($tooltip.outerHeight() / 2 - 14);
					// this block used for support tooltip.
					if ($this.find('.splogocarousel-support').length > 0) {
						$top = $this.offset().top + 52;
						offset_left = $this.offset().left - 230;
					}
					$tooltip.css({
						top: $top,
						left: offset_left,
					});
				},
				mouseleave: function () {
					if ($tooltip !== undefined) {
						// Check if the cursor is still over the tooltip
						if (!$tooltip.is(':hover')) {
							$tooltip.remove();
						}
					}
				},
			});

			// Event delegation to handle tooltip removal when the cursor leaves the tooltip itself.
			$('body').on('mouseleave', '.splogocarousel-tooltip', function () {
				if ($tooltip !== undefined) {
					$tooltip.remove();
				}
			});
		});
	}

	//
	// Customize Refresh
	//
	$.fn.splogocarousel_customizer_refresh = function () {
		return this.each(function () {

			var $this = $(this),
				$complex = $this.closest('.splogocarousel-customize-complex');

			if ($complex.length) {

				var unique_id = $complex.data('unique-id');

				if (unique_id === undefined) {
					return;
				}

				var $input = $complex.find(':input'),
					option_id = $complex.data('option-id'),
					obj = $input.serializeObjectSPLC(),
					data = (!$.isEmptyObject(obj) && obj[unique_id] && obj[unique_id][option_id]) ? obj[unique_id][option_id] : '',
					control = window.wp.customize.control(unique_id + '[' + option_id + ']');

				// clear the value to force refresh.
				control.setting._value = null;

				control.setting.set(data);

			} else {

				$this.find(':input').first().trigger('change');

			}

			$(document).trigger('splogocarousel-customizer-refresh', $this);

		});
	};

	//
	// Customize Listen Form Elements
	//
	$.fn.splogocarousel_customizer_listen = function (options) {

		var settings = $.extend({
			closest: false,
		}, options);

		return this.each(function () {

			if (window.wp.customize === undefined) { return; }

			var $this = (settings.closest) ? $(this).closest('.splogocarousel-customize-complex') : $(this),
				$input = $this.find(':input'),
				unique_id = $this.data('unique-id'),
				option_id = $this.data('option-id');

			if (unique_id === undefined) {
				return;
			}

			$input.on('change keyup', function () {

				var obj = $this.find(':input').serializeObjectSPLC();
				var val = (!$.isEmptyObject(obj) && obj[unique_id] && obj[unique_id][option_id]) ? obj[unique_id][option_id] : '';

				window.wp.customize.control(unique_id + '[' + option_id + ']').setting.set(val);

			});

		});
	};

	//
	// Customizer Listener for Reload JS
	//
	$(document).on('expanded', '.control-section', function () {

		var $this = $(this);

		if ($this.hasClass('open') && !$this.data('inited')) {

			var $fields = $this.find('.splogocarousel-customize-field');
			var $complex = $this.find('.splogocarousel-customize-complex');

			if ($fields.length) {
				$this.splogocarousel_dependency();
				$fields.splogocarousel_reload_script({ dependency: false });
				$complex.splogocarousel_customizer_listen();
			}

			$this.data('inited', true);

		}

	});

	//
	// Window on resize
	//
	SPLC.vars.$window.on('resize splogocarousel.resize', SPLC.helper.debounce(function (event) {

		var window_width = navigator.userAgent.indexOf('AppleWebKit/') > -1 ? SPLC.vars.$window.width() : window.innerWidth;

		if (window_width <= 782 && !SPLC.vars.onloaded) {
			$('.splogocarousel-section').splogocarousel_reload_script();
			SPLC.vars.onloaded = true;
		}

	}, 200)).trigger('splogocarousel.resize');

	//
	// Widgets Framework
	//
	$.fn.splogocarousel_widgets = function () {
		if (this.length) {

			$(document).on('widget-added widget-updated', function (event, $widget) {
				$widget.find('.splogocarousel-fields').splogocarousel_reload_script();
			});

			$('.widgets-sortables, .control-section-sidebar').on('sortstop', function (event, ui) {
				ui.item.find('.splogocarousel-fields').splogocarousel_reload_script_retry();
			});

			$(document).on('click', '.widget-top', function (event) {
				$(this).parent().find('.splogocarousel-fields').splogocarousel_reload_script();
			});

		}
	};

	//
	// Nav Menu Options Framework
	//
	$.fn.splogocarousel_nav_menu = function () {
		return this.each(function () {

			var $navmenu = $(this);

			$navmenu.on('click', 'a.item-edit', function () {
				$(this).closest('li.menu-item').find('.splogocarousel-fields').splogocarousel_reload_script();
			});

			$navmenu.on('sortstop', function (event, ui) {
				ui.item.find('.splogocarousel-fields').splogocarousel_reload_script_retry();
			});

		});
	};

	//
	// Retry Plugins
	//
	$.fn.splogocarousel_reload_script_retry = function () {
		return this.each(function () {

			var $this = $(this);

		});
	};

	//
	// Reload Plugins
	//
	$.fn.splogocarousel_reload_script = function (options) {

		var settings = $.extend({
			dependency: true,
		}, options);

		return this.each(function () {

			var $this = $(this);

			// Avoid for conflicts
			if (!$this.data('inited')) {

				// Field plugins
				$this.children('.splogocarousel-field-code_editor').splogocarousel_field_code_editor();
				$this.children('.splogocarousel-field-fieldset').splogocarousel_field_fieldset();
				$this.children('.splogocarousel-field-slider').splogocarousel_field_slider();
				$this.children('.splogocarousel-field-spinner').splogocarousel_field_spinner();
				$this.children('.splogocarousel-field-switcher').splogocarousel_field_switcher();
				$this.children('.splogocarousel-field-tabbed').splogocarousel_field_tabbed();
				// $this.children('.splogocarousel-field-typography').splogocarousel_field_typography();

				// Field colors
				$this.children('.splogocarousel-field-border').find('.splogocarousel-color').splogocarousel_color();
				$this.children('.splogocarousel-field-color').find('.splogocarousel-color').splogocarousel_color();
				$this.children('.splogocarousel-field-color_group').find('.splogocarousel-color').splogocarousel_color();
				$this.children('.splogocarousel-field-typography').find('.splogocarousel-color').splogocarousel_color();

				// Field chosenjs
				$this.children('.splogocarousel-field-select').find('.splogocarousel-chosen').splogocarousel_chosen();

				// Field Checkbox
				$this.children('.splogocarousel-field-checkbox').find('.splogocarousel-checkbox').splogocarousel_checkbox();

				// Field Siblings
				$this.children('.splogocarousel-field-button_set').find('.splogocarousel-siblings').splogocarousel_siblings();
				$this.children('.splogocarousel-field-layout_preset').find('.splogocarousel-siblings').splogocarousel_siblings();

				// Help Tooptip
				$this.children('.splogocarousel-field').find('.splogocarousel-help').splogocarousel_help();

				if (settings.dependency) {
					$this.splogocarousel_dependency();
				}

				$this.data('inited', true);

				$(document).trigger('splogocarousel-reload-script', $this);

			}

		});
	};

	//
	// Document ready and run scripts
	//
	$(document).ready(function () {

		$('.splogocarousel-save').splogocarousel_save();
		$('.splogocarousel-options').splogocarousel_options();
		$('.splogocarousel-sticky-header').splogocarousel_sticky();
		$('.splogocarousel-nav-options').splogocarousel_nav_options();
		$('.splogocarousel-nav-metabox').splogocarousel_nav_metabox();
		$('.splogocarousel-taxonomy').splogocarousel_taxonomy();
		$('.splogocarousel-page-templates').splogocarousel_page_templates();
		$('.splogocarousel-post-formats').splogocarousel_post_formats();
		$('.splogocarousel-shortcode').splogocarousel_shortcode();
		$('.splogocarousel-search').splogocarousel_search();
		$('.splogocarousel-confirm').splogocarousel_confirm();
		$('.splogocarousel-expand-all').splogocarousel_expand_all();
		$('.splogocarousel-onload').splogocarousel_reload_script();
		$('.widget').splogocarousel_widgets();
		$('#menu-to-edit').splogocarousel_nav_menu();
		$('.sp_lcp_shortcode_header_support').splogocarousel_help();

	});

	/**
	 * Get the selected layout value.
	 */
	var selectedLcpLayout = $('.lcp_layout').find('.splogocarousel--image.splogocarousel--active input').val();
	var $shortcodeOptions = $('#sp_lcp_shortcode_options');
	var $navItem = $shortcodeOptions.find('.splogocarousel-nav ul li:nth-child(4)');
	var $logoMarginSpace = $shortcodeOptions.find('.lcp_logo_margin_between .splogocarousel--space:nth-child(2)');

	// Function to toggle visibility of elements based on layout value
	function toggleLayoutVisibility(layoutValue) {
		if (layoutValue !== 'carousel') {
			$navItem.hide();
			$logoMarginSpace.show();
		} else {
			$navItem.show();
			$logoMarginSpace.hide();
		}
	}

	// Toggle layout visibility based on selected layout
	toggleLayoutVisibility(selectedLcpLayout);

	/**
	 * Show/Hide tabs on changing of layout.
	 */
	var $lcpLayout = $('.splogocarousel-field-layout_preset.lcp_layout .splogocarousel--sibling.splogocarousel--image');
	var $gridInlineVaSelector = $('.lcp-grid-inline-vertical-alignment');

	// Function to toggle visibility of elements based on layout value
	function layoutToggleElements(layoutValue) {
		if (layoutValue !== 'carousel') {
			$navItem.hide();
			$logoMarginSpace.show();
		} else {
			$navItem.show();
			$logoMarginSpace.hide();
		}
	}

	// Function to trigger grid inline vertical alignment based on layout
	function setGridAlignment(layoutValue) {
		if (layoutValue === 'grid') {
			$gridInlineVaSelector.find('.splogocarousel--image:last-child').trigger('click');
		} else if (layoutValue === 'carousel') {
			$gridInlineVaSelector.find('.splogocarousel--image:first-child').trigger('click');
		}
	}

	// Event listener for layout change or click
	$lcpLayout.on('change click', 'input', function () {
		let layoutValue = $(this).val();

		// Toggle elements visibility
		layoutToggleElements(layoutValue);

		// Set grid vertical alignment
		setGridAlignment(layoutValue);
	});

	/**
	 * Copy selected text to clipboard.
	 */
	$('.lc-sc-code.selectable').on('click', function (e) {
		e.preventDefault();
		lc_copyToClipboard($(this));
		lc_SelectText($(this));
		$(this).trigger('focus').trigger('select');
		jQuery(".lc-after-copy-text").animate({
			opacity: 1,
			bottom: 25
		}, 300);
		setTimeout(function () {
			jQuery(".lc-after-copy-text").animate({
				opacity: 0,
			}, 200);
			jQuery(".lc-after-copy-text").animate({
				bottom: 0
			}, 0);
		}, 2000);
	});

	$('.lc_input_shortcode').on('click', function (e) {
		e.preventDefault();
		/* Get the text field */
		var copyText = $(this);
		/* Select the text field */
		copyText.trigger("select");
		document.execCommand("copy");
		$('.lc-after-copy-text').animate({
			opacity: 1,
			bottom: 25
		}, 300);
		setTimeout(function () {
			jQuery(".lc-after-copy-text").animate({
				opacity: 0,
			}, 200);
			jQuery(".lc-after-copy-text").animate({
				bottom: 0
			}, 0);
		}, 2000);
	});
	function lc_copyToClipboard(element) {
		var $temp = $("<input>");
		$("body").append($temp);
		$temp.val($(element).text()).trigger('select');
		document.execCommand("copy");
		$temp.remove();
	}
	function lc_SelectText(element) {
		var r = document.createRange();
		var w = element.get(0);
		r.selectNodeContents(w);
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(r);
	}

	function isValidJSONString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}

	// Logo Carousel export.
	var $export_type = $('.lcp_what_export').find('input:checked').val();
	$('.lcp_what_export').on('change', function () {
		$export_type = $(this).find('input:checked').val();
	});
	$('.lcp_export .splogocarousel--button').on('click', function (event) {
		event.preventDefault();
		var $this = $(this),
			button_label = $(this).text();
		var $shortcode_ids = $('.lcp_post_ids select').val();
		var $ex_nonce = $('#splogocarousel_options_noncesp_lcp_tools').val();
		// console.log($shortcode_ids);
		if ($shortcode_ids.length && $export_type === 'selected_shortcodes') {
			var data = {
				action: 'lcp_export_shortcodes',
				lcp_ids: $shortcode_ids,
				nonce: $ex_nonce,
			}
		} else if ($export_type === 'all_shortcodes') {
			var data = {
				action: 'lcp_export_shortcodes',
				lcp_ids: 'all_shortcodes',
				nonce: $ex_nonce,
			}
		} else if ($export_type === 'all_logos') {
			var data = {
				action: 'lcp_export_shortcodes',
				lcp_ids: 'all_logos',
				nonce: $ex_nonce,
			}
		} else {
			$('.splogocarousel-form-result.splogocarousel-form-success').text('No shortcode selected.').show();
			setTimeout(function () {
				$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('');
			}, 3000);
			return;
		}
		$this.append('<span class="splogocarousel-loading-spinner"><i class="fa fa-spinner" aria-hidden="true"></i></span>');
		$this.css('opacity', '0.7');

		$.post(ajaxurl, data, function (resp) {
			if (resp) {
				// Convert JSON Array to string.
				if (isValidJSONString(resp)) {
					var json = JSON.stringify(JSON.parse(resp));
				} else {
					var json = JSON.stringify(resp);
				}
				// Convert JSON string to BLOB.
				var blob = new Blob([json], { type: 'application/json' });
				var link = document.createElement('a');
				var lcp_time = $.now();
				link.href = window.URL.createObjectURL(blob);
				link.download = "logo-carousel-export-" + lcp_time + ".json";
				link.click();
				$('.splogocarousel-form-result.splogocarousel-form-success').text('Exported successfully!').show();
				$this.html(button_label).css('opacity', '1');
				setTimeout(function () {
					$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('');
					$('.lcp_post_ids select').val('').trigger('chosen:updated');
				}, 3000);
			}
		})
			.fail(function () {
				$this.html(button_label).css('opacity', '1');
				$('.splogocarousel-form-result.splogocarousel-form-success').addClass('splogocarousel-import-warning')
					.text('Something went wrong, please try again!').show();
				setTimeout(function () {
					$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('').removeClass('splogocarousel-import-warning');
				}, 2000);
			});
	});
	// Logo Carousel import.
	$('.lcp_import button.import').on('click', function (event) {
		event.preventDefault();
		var lcp_shortcodes = $('#import').prop('files')[0];
		if ($('#import').val() != '') {
			var $this = $(this),
				button_label = $(this).text();
			$this.append('<span class="splogocarousel-loading-spinner"><i class="fa fa-spinner" aria-hidden="true"></i></span>');
			$this.css('opacity', '0.7');
			var $im_nonce = $('#splogocarousel_options_noncesp_lcp_tools').val();
			var reader = new FileReader();
			reader.readAsText(lcp_shortcodes);
			reader.onload = function (event) {
				var jsonObj = JSON.stringify(event.target.result);
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						shortcode: jsonObj,
						action: 'lcp_import_shortcodes',
						nonce: $im_nonce,
					},
					success: function (resp) {
						$this.html(button_label).css('opacity', '1');
						$('.splogocarousel-form-result.splogocarousel-form-success').text('Imported successfully!').show();
						setTimeout(function () {
							$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('');
							$('#import').val('');
							if (resp.data === 'sp_logo_carousel') {
								window.location.replace($('#lcp_logo_link_redirect').attr('href'));
							} else {
								window.location.replace($('#lcp_shortcode_link_redirect').attr('href'));
							}
						}, 2000);
					},
					error: function (error) {
						$('#import').val('');
						$this.html(button_label).css('opacity', '1');
						$('.splogocarousel-form-result.splogocarousel-form-success').addClass('splogocarousel-import-warning')
							.text('Something went wrong, please try again!').show();
						setTimeout(function () {
							$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('').removeClass('splogocarousel-import-warning');
						}, 2000);
					}
				});
			}
		} else {
			$('.splogocarousel-form-result.splogocarousel-form-success').text('No exported json file chosen.').show();
			setTimeout(function () {
				$('.splogocarousel-form-result.splogocarousel-form-success').hide().text('');
			}, 3000);
		}
	});

	$(`.order_by_pro option`).each(function (i, item) {
		const regex = new RegExp('Pro');
		if (regex.test(item.innerText)) {
			$(item).attr('disabled', 'disabled');
		}
	})

	// Live Preview script for Logo Carousel.
	var preview_box = $('#splcp-preview-box');
	var preview_display = $('#splcp_live_preview').hide();
	$(document).on('click', '#splcp-show-preview:contains(Hide)', function (e) {
		e.preventDefault();
		var _this = $(this);
		_this.html('<i class="fa fa-eye" aria-hidden="true"></i> Show Preview');
		preview_box.html('');
		preview_display.hide();
	});

	$(document).on('click', '#splcp-show-preview:not(:contains(Hide))', function (e) {
		e.preventDefault();
		var previewJS = window.splogocarousel_vars.previewJS;
		var _data = $('form#post').serialize();
		var _this = $(this);
		var data = {
			action: 'splcp_preview_meta_box',
			data: _data,
			ajax_nonce: $('#splogocarousel_metabox_noncesp_lcp_shortcode_options').val()
		};
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			error: function (response) {
				console.log(response)
			},
			success: function (response) {
				preview_display.show();
				preview_box.html(response);
				$.getScript(previewJS, function () {
					_this.html('<i class="fa fa-eye-slash" aria-hidden="true"></i> Hide Preview');
					$(document).on('keyup change', '.post-type-sp_lc_shortcodes', function (e) {
						e.preventDefault();
						_this.html('<i class="fa fa-refresh" aria-hidden="true"></i> Update Preview');
					});
					$("html, body").animate({ scrollTop: preview_display.offset().top - 50 }, "slow");
				});
			}
		})
	});

	// Function to update icon type
	function updateIconType(selector, regex, type) {
		var str = "";
		$(selector + ' option:selected').each(function () {
			str = $(this).val();
		});
		var src = $(selector + ' .splogocarousel-fieldset img').attr('src');
		var result = src.match(regex);
		if (result && result[1]) {
			src = src.replace(result[1], str);
			$(selector + ' .splogocarousel-fieldset img').attr('src', src);
		}
		if (type.includes(str)) {
			$(selector + ' .lcp-pro-notice').hide();
		} else {
			var noticeText = "This is a <a href='https://logocarousel.com/pricing/' target='_blank'>Pro Feature!</a>";
			$(selector + ' .lcp-pro-notice').html(noticeText).show();
		}
	}
	$('.lcp-carousel-nav-position').on('change', function () {
		updateIconType(".lcp-carousel-nav-position", /navigation-icon\/(.+)\.svg/, 'top_right');
	});
	if ($('.lcp-carousel-nav-position').length > 0) {
		updateIconType(".lcp-carousel-nav-position", /navigation-icon\/(.+)\.svg/, 'top_right');
	}
	$(document).on('keyup change', '.lcpro_setting_options #splogocarousel-form', function (e) {
		e.preventDefault();
		var $button = $(this).find('.splogocarousel-save');
		$button.css({ "background-color": "#00C263", "pointer-events": "initial" }).val('Save Settings');
	});
	$('.lcpro_setting_options .splogocarousel-save').on('click', function (e) {
		e.preventDefault();
		$(this).css({ "background-color": "#C5C5C6", "pointer-events": "none" }).val('Changes Saved');
	});

	// Add logo image section title.
	$('.post-type-sp_logo_carousel #postimagediv').prepend('<div class="splogocarousel-field splogocarousel-field-subheading">Logo Image<div class="clear"></div></div>');

})(jQuery, window, document);
