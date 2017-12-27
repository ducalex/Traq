/*!
 * Traq
 * Copyright (C) 2009-2014 Jack Polgar
 * Copyright (C) 2012-2014 Traq.io
 * https://github.com/nirix
 * http://traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

$(document).ready(function(){
	var news_container = $("#traq_news ul");

	$.getJSON(traq.base + '_misc/traq_news.json').done(function(data){
		$.each(data, function(i, data){
			news_container.append($("<li>")
				.addClass('box')
				.append($("<h4>").append(data.title))
				.append($("<span>").append(data.created_at).attr('title', data.created_at))
				.append(data.content)
			);
		});
	});
});
