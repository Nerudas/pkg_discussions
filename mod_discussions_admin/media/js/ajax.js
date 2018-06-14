/*
 * @package    Discussions - Administrator Module
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[data-mod-discussions-admin]').each(function () {
			var block = $(this),
				module_id = $(block).data('mod-discussions-admin'),
				tabPosts = $(block).find('[data-mod-discussions-admin-tab="posts"]'),
				tabTopics = $(block).find('[data-mod-discussions-admin-tab="topics"]'),
				reload = $(block).find('[data-mod-discussions-admin-reload]');

			getItems(module_id, tabPosts, 'posts');
			getItems(module_id, tabTopics, 'topics');

			$(reload).on('click', function () {
				getItems(module_id, tabPosts, 'posts');
				getItems(module_id, tabTopics, 'topics');
			});
		});

		function getItems(module_id, block, tab) {
			var loading = block.find('.loading'),
				items = block.find('.items'),
				result = block.find('.result');
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'index.php?option=com_ajax&module=discussions_admin&format=json',
				data: {module_id: module_id, tab: tab},
				beforeSend: function () {
					loading.slideDown(750);
					result.slideUp(750);
					items.html('');
				},
				success: function (response) {
					if (response.success) {
						items.html(response.data);
					}
					else {
						items.html(response.message);
					}
				},
				complete: function () {
					loading.slideUp(750);
					result.slideDown(750);
				}
			});
		}
	});
})(jQuery);