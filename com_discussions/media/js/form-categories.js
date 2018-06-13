/*
 * @package    Discussions Component
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		var field = $('[data-input-discussionscategories]'),
			items = field.find('.categories .item'),
			tags = field.find('input[type="checkbox"]'),
			currents = field.find('.actives'),
			titleField = field.closest('form').find('[name*="title"]');

		setActives();

		// On click item
		$(items).on('click', function () {
			$(tags).prop('checked', false);

			var values = $(this).data('tags');
			$(values).each(function (key, val) {
				field.find('input[value="' + val + '"]').prop('checked', true);
			});

			setActives();
		});

		$(tags).on('change', function () {
			setTimeout(setActives, 50);
		});

		function setActives() {
			items.removeClass('active');
			$(currents).html('');

			var values = [];
			$(tags).each(function (i, input) {
				if ($(input).prop('checked')) {
					values.push($(input).val() * 1);
				}
			});
			$(items).each(function (i, item) {
				var active = true;
				var itemTags = $(item).data('tags');
				$(itemTags).each(function (ik, tag) {
					if ($.inArray(tag, values) == -1) {
						active = false;
					}
				});

				if (active) {
					$(item).addClass('active');
					var itemHTML = '<li class="item">' + $(item).data('title') + '</li>';
					$(itemHTML).appendTo($(currents));
					if ($(titleField).val() == '') {
						$(titleField).val($(item).data('title'));
					}
				}
			});
		}

	});
})(jQuery);