jQuery(function($) {

	var c = '.ap_pager_content',
		b = '.ap_navi_button',
		n = '.ap_page_navigation',
		l = '.ap_pager_loading';

	if ( pager_swipe )
	{
		var upage = 1;
		if ( current_url.getQuery('upage') )
		{
			upage = Number(current_url.getQuery('upage'));
		}
		var total_page = Number($(n).find('li:last').text());
		var href = '';

		$(c).swipe({
			swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
				if ( direction === 'left' && upage !== total_page )
				{
					href = current_url.setQuery('upage', upage + 1);
					
				}
				else if ( direction === 'right' && upage > 1 )
				{
					href = current_url.setQuery('upage', upage - 1);
				}
				if ( href )
				{
					if ( pager_ajax )
					{
						ajaxDefault(href);
					}
					else
					{
						location.href = href;
					}
				}
				else
				{
					return false;
				}
			},
			threshold: 75,
			allowPageScroll: 'auto'
		});
	}

	if ( pager_ajax )
	{
		$('<div class="ap_pager_loading"></div>').appendTo(c);
	}

	$(b + ' a, ' + n + ' a').on('click', function(e) {
		$(this).removeAttr('target');
		if ( pager_ajax )
		{
			e.preventDefault();
			ajaxDefault($(this).attr('href'));
		}
		else
		{
			e.stopPropagation();
		}
	});

	function ajaxDefault(req_url) {
		$(c).css({
			height : $(c).height()
		});
		$(l).show();
		$.ajax ({
			global: false,
			url: req_url,
			dataType: 'html',
			success: function(data) {
				var content = $(data),
					selectedContent = content.find(c).html(),
					selectedPager = content.find(n).html();
				$(c).html(selectedContent);
				$(n).html(selectedPager);
				if ( req_url.indexOf(location.origin) === -1 )
				{
					current_url = location.origin + req_url;
				}
				else
				{
					current_url = req_url;
				}
			},
			complete: function() {
				if ( pager_ajax_offset && $(pager_ajax_offset).length > 0 )
				{
					if ( $(pager_ajax_offset).length > 1 )
					{
						pager_ajax_offset = pager_ajax_offset + ':eq(0)';
					}
					$('html, body').animate({
						scrollTop : $(pager_ajax_offset).offset().top
					}, 0);
				}
				var addons_scripts = [];
				if ( pager_minify_scripts )
				{
					$('body script[src^="/files/cache/assets/minified/"]').each(function(i) {
						addons_scripts[i] = $(this).attr('src');
					});
				}
				else
				{
					$('body script[src^="/addons/"]').each(function(i) {
						addons_scripts[i] = $(this).attr('src');
					});
				}
				$.each(addons_scripts, function(index, value) {
					$.getScript(value);
				});
				
				setTimeout(function() {
					$(c).css({
						height : 'auto'
					});
				}, 100);
			}
		});
	}

});