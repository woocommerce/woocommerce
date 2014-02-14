/*!
 * ApiGen 2.8.0 - API documentation generator for PHP 5.3+
 *
 * Copyright (c) 2010-2011 David Grudl (http://davidgrudl.com)
 * Copyright (c) 2011-2012 Jaroslav Hanslík (https://github.com/kukulich)
 * Copyright (c) 2011-2012 Ondřej Nešpor (https://github.com/Andrewsville)
 * Copyright (c) 2012 Olivier Laviale (https://github.com/olvlvl)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

$(function() {
	var $document = $(document);
	var $navigation = $('#navigation');
	var navigationHeight = $('#navigation').height();
	var $left = $('#left');
	var $right = $('#right');
	var $rightInner = $('#rightInner');
	var $splitter = $('#splitter');
	var $groups = $('#groups');
	var $content = $('#content');

	// Menu

	// Hide deep packages and namespaces
	$('ul span', $groups).click(function(event) {
		event.preventDefault();
		event.stopPropagation();
		$(this)
			.toggleClass('collapsed')
			.parent()
				.next('ul')
					.toggleClass('collapsed');
	}).click();

	$active = $('ul li.active', $groups);
	if ($active.length > 0) {
		// Open active
		$('> a > span', $active).click();
	} else {
		$main = $('> ul > li.main', $groups);
		if ($main.length > 0) {
			// Open first level of the main project
			$('> a > span', $main).click();
		} else {
			// Open first level of all
			$('> ul > li > a > span', $groups).click();
		}
	}

	// Content

	// Search autocompletion
	var autocompleteFound = false;
	var autocompleteFiles = {'c': 'class', 'co': 'constant', 'f': 'function', 'm': 'class', 'mm': 'class', 'p': 'class', 'mp': 'class', 'cc': 'class'};
	var $search = $('#search input[name=q]');
	$search
		.autocomplete(ApiGen.elements, {
			matchContains: true,
			scrollHeight: 200,
			max: 20,
			formatItem: function(data) {
				return data[1].replace(/^(.+\\)(.+)$/, '<span><small>$1</small>$2</span>');
			},
			formatMatch: function(data) {
				return data[1];
			},
			formatResult: function(data) {
				return data[1];
			},
			show: function($list) {
				var $items = $('li span', $list);
				var maxWidth = Math.max.apply(null, $items.map(function() {
					return $(this).width();
				}));
				// 10px padding
				$list.width(Math.max(maxWidth + 10, $search.innerWidth()));
			}
		}).result(function(event, data) {
			autocompleteFound = true;
			var location = window.location.href.split('/');
			location.pop();
			var parts = data[1].split(/::|$/);
			var file = $.sprintf(ApiGen.config.templates.main[autocompleteFiles[data[0]]].filename, parts[0].replace(/[^\w]/g, '.'));
			if (parts[1]) {
				file += '#' + ('mm' === data[0] || 'mp' === data[0] ? 'm' : '') + parts[1].replace(/([\w]+)\(\)/, '_$1');
			}
			location.push(file);
			window.location = location.join('/');

			// Workaround for Opera bug
			$(this).closest('form').attr('action', location.join('/'));
		}).closest('form')
			.submit(function() {
				var query = $search.val();
				if ('' === query) {
					return false;
				}

				var label = $('#search input[name=more]').val();
				if (!autocompleteFound && label && -1 === query.indexOf('more:')) {
					$search.val(query + ' more:' + label);
				}

				return !autocompleteFound && '' !== $('#search input[name=cx]').val();
			});

	// Save natural order
	$('table.summary tr[data-order]', $content).each(function(index) {
		do {
			index = '0' + index;
		} while (index.length < 3);
		$(this).attr('data-order-natural', index);
	});

	// Switch between natural and alphabetical order
	var $caption = $('table.summary', $content)
		.filter(':has(tr[data-order])')
			.prev('h2');
	$caption
		.click(function() {
			var $this = $(this);
			var order = $this.data('order') || 'natural';
			order = 'natural' === order ? 'alphabetical' : 'natural';
			$this.data('order', order);
			$.cookie('order', order, {expires: 365});
			var attr = 'alphabetical' === order ? 'data-order' : 'data-order-natural';
			$this
				.next('table')
					.find('tr').sortElements(function(a, b) {
						return $(a).attr(attr) > $(b).attr(attr) ? 1 : -1;
					});
			return false;
		})
		.addClass('switchable')
		.attr('title', 'Switch between natural and alphabetical order');
	if ((null === $.cookie('order') && 'alphabetical' === ApiGen.config.options.elementsOrder) || 'alphabetical' === $.cookie('order')) {
		$caption.click();
	}

	// Open details
	if (ApiGen.config.options.elementDetailsCollapsed) {
		$('tr', $content).filter(':has(.detailed)')
			.click(function() {
				var $this = $(this);
				$('.short', $this).hide();
				$('.detailed', $this).show();
			});
	}

	// Splitter
	var splitterWidth = $splitter.width();
	function setSplitterPosition(position)
	{
		$left.width(position);
		$right.css('margin-left', position + splitterWidth);
		$splitter.css('left', position);
	}
	function setNavigationPosition()
	{
		var height = $(window).height() - navigationHeight;
		$left.height(height);
		$splitter.height(height);
		$right.height(height);
	}
	function setContentWidth()
	{
		var width = $rightInner.width();
		$rightInner
			.toggleClass('medium', width <= 960)
			.toggleClass('small', width <= 650);
	}
	$splitter.mousedown(function() {
			$splitter.addClass('active');

			$document.mousemove(function(event) {
				if (event.pageX >= 230 && $document.width() - event.pageX >= 600 + splitterWidth) {
					setSplitterPosition(event.pageX);
					setContentWidth();
				}
			});

			$()
				.add($splitter)
				.add($document)
					.mouseup(function() {
						$splitter
							.removeClass('active')
							.unbind('mouseup');
						$document
							.unbind('mousemove')
							.unbind('mouseup');

						$.cookie('splitter', parseInt($splitter.css('left')), {expires: 365});
					});

			return false;
		});
	var splitterPosition = $.cookie('splitter');
	if (null !== splitterPosition) {
		setSplitterPosition(parseInt(splitterPosition));
	}
	setNavigationPosition();
	setContentWidth();
	$(window)
		.resize(setNavigationPosition)
		.resize(setContentWidth);

	// Select selected lines
	var matches = window.location.hash.substr(1).match(/^\d+(?:-\d+)?(?:,\d+(?:-\d+)?)*$/);
	if (null !== matches) {
		var lists = matches[0].split(',');
		for (var i = 0; i < lists.length; i++) {
			var lines = lists[i].split('-');
			lines[1] = lines[1] || lines[0];
			for (var j = lines[0]; j <= lines[1]; j++) {
				$('#' + j).addClass('selected');
			}
		}

		var $firstLine = $('#' + parseInt(matches[0]));
		if ($firstLine.length > 0) {
			$right.scrollTop($firstLine.offset().top);
		}
	}

	// Save selected lines
	var lastLine;
	$('a.l').click(function(event) {
		event.preventDefault();

		var $selectedLine = $(this).parent();
		var selectedLine = parseInt($selectedLine.attr('id'));

		if (event.shiftKey) {
			if (lastLine) {
				for (var i = Math.min(selectedLine, lastLine); i <= Math.max(selectedLine, lastLine); i++) {
					$('#' + i).addClass('selected');
				}
			} else {
				$selectedLine.addClass('selected');
			}
		} else if (event.ctrlKey) {
			$selectedLine.toggleClass('selected');
		} else {
			var $selected = $('.l.selected')
				.not($selectedLine)
				.removeClass('selected');
			if ($selected.length > 0) {
				$selectedLine.addClass('selected');
			} else {
				$selectedLine.toggleClass('selected');
			}
		}

		lastLine = $selectedLine.hasClass('selected') ? selectedLine : null;

		// Update hash
		var lines = $('.l.selected')
			.map(function() {
				return parseInt($(this).attr('id'));
			})
			.get()
			.sort(function(a, b) {
				return a - b;
			});

		var hash = [];
		var list = [];
		for (var j = 0; j < lines.length; j++) {
			if (0 === j && j + 1 === lines.length) {
				hash.push(lines[j]);
			} else if (0 === j) {
				list[0] = lines[j];
			} else if (lines[j - 1] + 1 !== lines[j] && j + 1 === lines.length) {
				hash.push(list.join('-'));
				hash.push(lines[j]);
			} else if (lines[j - 1] + 1 !== lines[j]) {
				hash.push(list.join('-'));
				list = [lines[j]];
			} else if (j + 1 === lines.length) {
				list[1] = lines[j];
				hash.push(list.join('-'));
			} else {
				list[1] = lines[j];
			}
		}

		window.location.hash = hash.join(',');
	});
});
