/**
 * Reusable project department task manager (wizard setup, execution, linked drill-down).
 * One task per row; optional department link on the same row.
 */
var ProjectDepartmentTasks = (function() {
    var QUICK_STATUSES = [
        { key: 'in_progress', label: 'In Progress' },
        { key: 'on_hold', label: 'On Hold' },
        { key: 'completed', label: 'Completed' }
    ];

    function cfg($section) {
        return {
            $section: $section,
            pdId: parseInt($section.data('project-department-id'), 10) || 0,
            readOnly: String($section.data('read-only')) === '1',
            mode: String($section.data('mode') || ''),
            saveUrl: $section.data('save-url'),
            deleteUrl: $section.data('delete-url'),
            listUrl: $section.data('list-url'),
            statusUrl: $section.data('status-url'),
            linkedPanelUrl: $section.data('linked-panel-url'),
            projectMinStart: ($section.data('project-min-start') || '').trim()
        };
    }

    function quickStatusHtml(currentStatus) {
        currentStatus = currentStatus || 'not_started';
        var html = '<div class="dept-task-status-quick btn-group btn-group-sm flex-wrap" role="group">';
        QUICK_STATUSES.forEach(function(item) {
            var isActive = currentStatus === item.key;
            html += '<button type="button" class="btn btn-dept-task-set-status ' +
                (isActive ? 'btn-primary' : 'btn-outline-secondary') + '"' +
                ' data-status="' + item.key + '"' +
                (isActive ? ' disabled' : '') + '>' + escapeHtml(item.label) + '</button>';
        });
        html += '</div>';
        return html;
    }

    function taskItemHtml(task, c) {
        var readOnly = c.readOnly;
        var showQuickStatus = c.mode === 'execution' && !readOnly;
        var name = task.task_name || task.display_name || 'Task';
        var linkedDept = (task.linked_department_name || '').trim();
        var start = task.planned_start_date || '—';
        var end = task.planned_end_date || '—';
        var badge = task.status_badge_html || '';
        var deptBadge = linkedDept
            ? '<span class="badge bg-info-subtle text-info">' + escapeHtml(linkedDept) + '</span>'
            : '';
        var actions = '';
        var linkedToken = task.linked_project_department_token || '';
        var currentStatus = task.task_status || 'not_started';

        if (linkedDept && linkedToken && c.mode !== 'execution') {
            actions += '<button type="button" class="btn btn-sm btn-outline-primary btn-open-linked-dept-tasks" title="Open department workflow"><i class="ri-external-link-line"></i></button>';
        }
        if (!readOnly) {
            actions += '<button type="button" class="btn btn-sm btn-outline-secondary btn-edit-dept-task" title="Edit task"><i class="ri-edit-line"></i></button>';
            actions += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-dept-task" title="Remove task"><i class="ri-delete-bin-line"></i></button>';
        }

        return '<div class="dept-task-item d-flex align-items-center gap-2 border rounded bg-white p-2 mb-2"' +
            ' data-task-id="' + (task.id || 0) + '"' +
            ' data-task-status="' + escapeHtml(currentStatus) + '"' +
            ' data-linked-pd-id="' + (linkedToken || '') + '">' +
            '<div class="dept-task-item-main flex-grow-1 min-w-0">' +
            '<div class="d-flex align-items-center gap-2 flex-wrap"><strong>' + escapeHtml(name) + '</strong>' + deptBadge + badge + '</div>' +
            '<div class="text-muted small mt-1"><i class="ri-calendar-line"></i> ' + escapeHtml(start) + ' → ' + escapeHtml(end) + '</div>' +
            '</div>' +
            (showQuickStatus ? quickStatusHtml(currentStatus) : '') +
            '<div class="dept-task-item-actions d-flex gap-1 flex-shrink-0">' + actions + '</div></div>';
    }

    function taskIdFrom($item) {
        $item = $item instanceof jQuery ? $item : $($item);
        return parseInt($item.attr('data-task-id') || $item.data('taskId') || 0, 10) || 0;
    }

    function linkedPdTokenFrom($item) {
        $item = $item instanceof jQuery ? $item : $($item);
        return String($item.attr('data-linked-pd-id') || $item.data('linkedPdId') || '').trim();
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function updateTaskCountBadge(projectDepartmentId, count) {
        var $badge = $('.dept-task-count-badge[data-pd-id="' + projectDepartmentId + '"]');
        if (!$badge.length) {
            return;
        }
        count = parseInt(count, 10) || 0;
        $badge.find('.dept-task-count-label').text(count + ' ' + (count === 1 ? 'task' : 'tasks'));
        $badge.toggleClass('bg-primary-subtle text-primary', count > 0);
        $badge.toggleClass('bg-light text-muted', count === 0);
    }

    function taskCountBadgeHtml(projectDepartmentId, taskCount, cssClass) {
        projectDepartmentId = parseInt(projectDepartmentId, 10) || 0;
        taskCount = parseInt(taskCount, 10) || 0;
        cssClass = (cssClass || '').trim();
        var tone = taskCount > 0 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted';
        return '<span class="badge rounded-pill dept-task-count-badge ' + tone +
            (cssClass ? ' ' + cssClass : '') + '" data-pd-id="' + projectDepartmentId + '" title="Tasks configured">' +
            '<i class="ri-list-check-2"></i><span class="dept-task-count-label">' + taskCount + ' ' +
            (taskCount === 1 ? 'task' : 'tasks') + '</span></span>';
    }

    function renderList($section, tasks) {
        var c = cfg($section);
        var $list = $section.find('.dept-task-list');
        var taskList = tasks || [];
        if (!taskList.length) {
            $list.html('<p class="text-muted small mb-0 dept-task-empty">No tasks yet.</p>');
        } else {
            var html = '';
            taskList.forEach(function(task) {
                html += taskItemHtml(task, c);
            });
            $list.html(html);
        }
        if (c.pdId > 0) {
            updateTaskCountBadge(c.pdId, taskList.length);
        }
    }

    function buildTaskPayload($section) {
        var $wrap = $section.find('.dept-task-form');
        var payload = new FormData();
        $wrap.find('input, select, textarea').each(function() {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name || $el.is(':disabled')) {
                return;
            }
            if (($el.attr('type') || '').toLowerCase() === 'file') {
                return;
            }
            payload.append(name, $el.val() || '');
        });
        var token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            payload.append('_token', token);
        }
        return payload;
    }

    function resetForm($section) {
        var $wrap = $section.find('.dept-task-form');
        $wrap.find('[name="id"]').val('');
        if (typeof TaskMasterSelect !== 'undefined') {
            TaskMasterSelect.clearValue($wrap.find('.task-master-select-wrap'));
        }
        $wrap.find('[name="linked_department_id"]').val('');
        $wrap.find('[name="planned_start_date"], [name="planned_end_date"]').val('');
        $wrap.find('[name="task_status"]').each(function() {
            this.selectedIndex = 0;
        });
        $section.find('.dept-task-form-wrap').hide();
    }

    function showForm($section) {
        var $wrap = $section.find('.dept-task-form');
        var isEdit = String($wrap.find('[name="id"]').val() || '').trim() !== '';

        if (!isEdit) {
            $wrap.find('[name="id"]').val('');
            if (typeof TaskMasterSelect !== 'undefined') {
                TaskMasterSelect.clearValue($wrap.find('.task-master-select-wrap'));
            }
            $wrap.find('[name="linked_department_id"]').val('');
            $wrap.find('[name="planned_start_date"], [name="planned_end_date"]').val('');
            $wrap.find('[name="task_status"]').each(function() {
                this.selectedIndex = 0;
            });
        }

        $section.find('.dept-task-form-wrap').show();
        if (typeof TaskMasterSelect !== 'undefined') {
            TaskMasterSelect.bind($wrap);
        }
        if (typeof bindPlannedDateRangeInputs === 'function') {
            bindPlannedDateRangeInputs($section.find('.dept-task-form'));
        }
    }

    function fillForm($section, $item) {
        var taskId = taskIdFrom($item);
        var c = cfg($section);
        ajaxRequestWithPromise(c.listUrl, { project_department_id: c.pdId }, 'get_project_department_tasks', 0, '', null, 'GET')
            .then(function(res) {
                if (res.error != 0 && res.error != '0') {
                    return;
                }
                var task = (res.tasks || []).find(function(row) {
                    return String(row.id) === String(taskId);
                });
                if (!task) {
                    return;
                }
                var $form = $section.find('.dept-task-form');
                $form.find('[name="id"]').val(task.id || '');
                if (typeof TaskMasterSelect !== 'undefined') {
                    TaskMasterSelect.setValue(
                        $form.find('.task-master-select-wrap'),
                        task.task_id || 0,
                        task.task_name || task.display_name || ''
                    );
                }
                $form.find('[name="linked_department_id"]').val(task.linked_department_id || '');
                $form.find('[name="planned_start_date"]').val(task.planned_start_date || '');
                $form.find('[name="planned_end_date"]').val(task.planned_end_date || '');
                $form.find('[name="task_status"]').val(task.task_status || 'not_started');
                showForm($section);
            });
    }

    function saveTask($section, $btn) {
        var $form = $section.find('.dept-task-form');
        var taskId = parseInt($form.find('[name="task_id"]').val(), 10) || 0;
        if (!taskId) {
            if (typeof parseFormErrors === 'function') {
                parseFormErrors({ error: 1, msg: ['Please select a task'] }, 'error');
            }
            $form.find('.task-master-select').focus();
            return;
        }
        if (typeof validatePlannedDateRangesInScope === 'function' && !validatePlannedDateRangesInScope($form)) {
            return;
        }
        var c = cfg($section);
        var payload = buildTaskPayload($section);
        ajaxRequestWithPromise(c.saveUrl, payload, 'save_project_department_task', 1, '', $btn)
            .then(function(res) {
                if (res.error == 0 || res.error == '0') {
                    renderList($section, res.tasks || []);
                    resetForm($section);
                }
            });
    }

    function deleteTask($section, taskId, $btn) {
        var c = cfg($section);
        ajaxRequestWithPromise(c.deleteUrl, {
            id: taskId,
            project_department_id: c.pdId
        }, 'delete_project_department_task', 0, '', $btn)
            .then(function(res) {
                if (res.error == 0 || res.error == '0') {
                    renderList($section, res.tasks || []);
                }
            });
    }

    function setTaskStatus($section, taskId, status, $btn) {
        var c = cfg($section);
        if (!c.statusUrl) {
            return;
        }
        ajaxRequestWithPromise(c.statusUrl, {
            id: taskId,
            project_department_id: c.pdId,
            task_status: status
        }, 'update_project_department_task_status', 0, '', $btn)
            .then(function(res) {
                if (res.error == 0 || res.error == '0') {
                    renderList($section, res.tasks || []);
                }
            });
    }

    function openLinkedPanel($section, linkedPdId, title) {
        var c = cfg($section);
        if (!linkedPdId || typeof openSideLayout !== 'function') {
            return;
        }
        var url = c.linkedPanelUrl + '/' + encodeURIComponent(linkedPdId);
        openSideLayout({}, url, title || 'Department tasks');
    }

    var documentEventsBound = false;

    function bindDocumentEvents() {
        if (documentEventsBound) {
            return;
        }
        documentEventsBound = true;

        $(document).off('click.pdtsDeptTasks', '.btn-add-dept-task').on('click.pdtsDeptTasks', '.btn-add-dept-task', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $section = $(this).closest('.dept-tasks-section');
            if (cfg($section).mode === 'execution') {
                return;
            }
            showForm($section);
        });

        $(document).off('click.pdtsDeptTasks', '.btn-cancel-dept-task').on('click.pdtsDeptTasks', '.btn-cancel-dept-task', function(e) {
            e.preventDefault();
            e.stopPropagation();
            resetForm($(this).closest('.dept-tasks-section'));
        });

        $(document).off('click.pdtsDeptTasks', '.btn-save-dept-task').on('click.pdtsDeptTasks', '.btn-save-dept-task', function(e) {
            e.preventDefault();
            e.stopPropagation();
            saveTask($(this).closest('.dept-tasks-section'), $(this));
        });

        $(document).off('click.pdtsDeptTasks', '.btn-edit-dept-task').on('click.pdtsDeptTasks', '.btn-edit-dept-task', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fillForm($(this).closest('.dept-tasks-section'), $(this).closest('.dept-task-item'));
        });

        $(document).off('click.pdtsDeptTasks', '.btn-delete-dept-task').on('click.pdtsDeptTasks', '.btn-delete-dept-task', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $item = $(this).closest('.dept-task-item');
            var $section = $(this).closest('.dept-tasks-section');
            deleteTask($section, taskIdFrom($item), $(this));
        });

        $(document).off('click.pdtsDeptTasks', '.btn-dept-task-set-status').on('click.pdtsDeptTasks', '.btn-dept-task-set-status', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($(this).is(':disabled')) {
                return;
            }
            var $item = $(this).closest('.dept-task-item');
            var $section = $(this).closest('.dept-tasks-section');
            setTaskStatus($section, taskIdFrom($item), $(this).data('status'), $(this));
        });

        $(document).off('click.pdtsDeptTasks', '.btn-open-linked-dept-tasks').on('click.pdtsDeptTasks', '.btn-open-linked-dept-tasks', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $item = $(this).closest('.dept-task-item');
            var $section = $(this).closest('.dept-tasks-section');
            var title = $item.find('strong').first().text().trim() + ' — Tasks';
            openLinkedPanel($section, linkedPdTokenFrom($item), title);
        });
    }

    function bind($root) {
        bindDocumentEvents();

        var $scope = ($root && $root.length) ? $root : $(document);

        $scope.find('.dept-tasks-section').each(function() {
            var $section = $(this);
            if ($section.data('deptTasksBound')) {
                return;
            }
            $section.data('deptTasksBound', 1);
            if (typeof TaskMasterSelect !== 'undefined') {
                TaskMasterSelect.bind($section);
            }
            if (typeof bindPlannedDateRangeInputs === 'function') {
                bindPlannedDateRangeInputs($section);
            }
        });
    }

    return {
        bind: bind,
        renderList: renderList,
        updateTaskCountBadge: updateTaskCountBadge,
        taskCountBadgeHtml: taskCountBadgeHtml
    };
})();
