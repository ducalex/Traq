/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
 * Copyright (C) 2009-2013 J. Polgar
 * https://github.com/nirix
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
	// Get selected tickets.
	var selected_tickets = ($.cookie('selected_tickets') || "").split(',');

	// Selected users
	var selected_users = [];

	// If there are none, set empty array.
	$(selected_tickets).each(function(i, ticket_id) {
		$('#mass_action_ticket_' + ticket_id).prop('checked', true);
	});

	// Save selected tickets.
	var saveSelectedTickets = function() {
		selected_tickets = [];

		$('#tickets input[type="checkbox"][name^="tickets"]:checked').each(function() {
			selected_tickets.push(this.value);
		});

		// Show mass actions form
		if (selected_tickets.length > 0) {
			$('#mass_actions').slideDown('fast');
		} else {
			$('#mass_actions').slideUp('fast');
		}

		$.cookie('selected_tickets', selected_tickets.join(','));
		$('#mass_actions input[name="tickets"]').val(JSON.stringify(selected_tickets));
	};

	saveSelectedTickets();

	$('#tickets .mass_actions #select_all_tickets').on('click', function() {
		$('#tickets input[type="checkbox"][name^="tickets"]').prop('checked', this.checked);
		saveSelectedTickets();
	});

	// Loop over checkboxes
	$('#tickets .mass_actions input[type="checkbox"][name^="tickets"]').click(saveSelectedTickets);

	// I'm not particularly proud of the code below, but then again I'm not at all
	// proud of the 3.x codebase, so screw it, I'll make it better in 4.x.

	$('#select_all_users').on('click', function(){
		$('#users .mass_actions input').prop('checked', this.checked);
	});

	$('#users .mass_actions input').click(function(){
		if ($('#users .mass_actions input:checked').length > 0) {
			$('#mass_actions').slideDown('fast');
		} else {
			$('#mass_actions').slideUp('fast');
		}
	});
});
