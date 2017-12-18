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
	// Manage ticket tasks
	var tasks_json = $('#ticket_tasks_data input[name="tasks"]').val();
	var tasks = tasks_json ? JSON.parse(tasks_json) : new Array();
	var tasks_btn = $('#manage_ticket_tasks');
	var next_id = tasks.length;

	tasks_btn.attr('data-orig-label', tasks_btn.text());
	tasks_btn.text(tasks_btn.attr('data-orig-label') + ' (' + tasks.length + ')');

	tasks_btn.click(function(){
		// Do not reload the overlay if it exists because it could contain unsaved changes
		if ($('#ticket_tasks_manager').length == 0) {
			$('#overlay').load($(this).attr('data-url') + '?overlay=true', function(){
				$('#overlay').overlay();
			});
		} else {
			$('#overlay').overlay();
		}
	});

	// Add ticket task
	$(document).on('click', "#ticket_tasks_manager #add_task", function(){
		var el = $('#ticket_task_new').clone();
		$('#ticket_tasks_manager .tasks').append(el);
		el.attr('data-task-id', next_id++).slideDown('fast').find('input:last').focus();
	});

	// Process ticket tasks form data
	$(document).on('click', "#overlay #set_ticket_tasks", function(){
		close_overlay(function(){
			var task, completed;
			tasks = new Array();
			$('#ticket_tasks_manager .task').each(function(){
				if (task = $(this).find('[type=text]').val()) {
					completed = !!$(this).find('[type=checkbox]:checked').length;
					tasks.push({task, completed});
				}
			});
			tasks_btn.text(tasks_btn.attr('data-orig-label') + ' (' + tasks.length + ')');
			$("#ticket_tasks_data input[name='tasks']").val(JSON.stringify(tasks));
		});
	});

	// Delete ticket task
	$(document).on('click', '#overlay button.delete_ticket_task', function(){
		$(this).parent().slideUp('fast', function(){
			$(this).remove();
		});
	});

	// Toggle task state
	$(document).on('click', '#ticket_info #tasks .task input[type="checkbox"]', function(){
		$('#tasks input[type="checkbox"]').attr('disabled','disabled');
		// Update task
		$.ajax({
			url: $(this).attr('data-url'),
			data: { completed: this.checked },
		}).done(function(){
			// Enable tasks
			$('#tasks input[type="checkbox"]').removeAttr('disabled');
		});
	});
});
