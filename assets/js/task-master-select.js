/**
 * Reusable searchable task master Select2 with inline create (no nested sidelayout).
 */
var TaskMasterSelect = (function() {
    function dropdownParent($wrap) {
        var $offcanvas = $wrap.closest('#offcanvasRight');
        if ($offcanvas.length) {
            return $offcanvas;
        }
        return $wrap.closest('.dept-tasks-section, .formCard, .card, body');
    }

    function initWrap($wrap) {
        if (!$wrap.length || $wrap.data('taskMasterBound')) {
            return;
        }
        $wrap.data('taskMasterBound', 1);

        var $select = $wrap.find('.task-master-select');
        if (!$select.length || !$.fn.select2) {
            return;
        }

        var searchUrl = $wrap.data('search-url') || '';
        var selectedId = parseInt($select.data('selected-id'), 10) || 0;
        var selectedText = String($select.data('selected-text') || '').trim();

        if (selectedId > 0 && selectedText && !$select.find('option[value="' + selectedId + '"]').length) {
            $select.append(new Option(selectedText, selectedId, true, true));
        }

        $select.select2({
            placeholder: 'Search tasks...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            dropdownParent: dropdownParent($wrap),
            ajax: {
                url: searchUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        include_id: $select.val() || selectedId || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: (data && data.results) ? data.results : []
                    };
                },
                cache: true
            },
            language: {
                noResults: function() {
                    return 'No tasks found';
                },
                searching: function() {
                    return 'Searching...';
                }
            }
        });

        $wrap.find('.task-master-toggle-create').off('click.taskMaster').on('click.taskMaster', function(e) {
            e.preventDefault();
            $wrap.find('.task-master-inline-create').slideDown(150);
            $wrap.find('.task-master-new-name').val('').focus();
        });

        $wrap.find('.task-master-cancel-create').off('click.taskMaster').on('click.taskMaster', function(e) {
            e.preventDefault();
            $wrap.find('.task-master-inline-create').slideUp(150);
            $wrap.find('.task-master-new-name').val('');
        });

        $wrap.find('.task-master-save-catalog').off('click.taskMaster').on('click.taskMaster', function(e) {
            e.preventDefault();
            saveInlineCreate($wrap, $(this));
        });
    }

    function saveInlineCreate($wrap, $btn) {
        var quickCreateUrl = $wrap.data('quick-create-url') || '';
        var taskName = ($wrap.find('.task-master-new-name').val() || '').trim();
        if (!taskName) {
            if (typeof parseFormErrors === 'function') {
                parseFormErrors({ error: 1, msg: ['Please enter task name'] }, 'error');
            }
            $wrap.find('.task-master-new-name').focus();
            return;
        }

        ajaxRequestWithPromise(quickCreateUrl, { task_name: taskName }, 'quick_create_master_task', 0, '', $btn)
            .then(function(res) {
                if (res.error != 0 && res.error != '0') {
                    return;
                }
                var task = res.task || {};
                var id = parseInt(task.id, 10) || 0;
                var text = task.task_name || taskName;
                if (id <= 0) {
                    return;
                }
                setValue($wrap, id, text);
                $wrap.find('.task-master-inline-create').slideUp(150);
                $wrap.find('.task-master-new-name').val('');
            });
    }

    function setValue($wrap, taskId, taskName) {
        var $select = $wrap.find('.task-master-select');
        if (!$select.length) {
            return;
        }
        taskId = String(taskId || '');
        taskName = String(taskName || '').trim();
        if (taskId === '') {
            $select.val(null).trigger('change');
            return;
        }
        if (!$select.find('option[value="' + taskId + '"]').length) {
            $select.append(new Option(taskName, taskId, true, true));
        }
        $select.val(taskId).trigger('change');
    }

    function clearValue($wrap) {
        var $select = $wrap.find('.task-master-select');
        if ($select.length && $select.hasClass('select2-hidden-accessible')) {
            $select.val(null).trigger('change');
        } else if ($select.length) {
            $select.val('');
        }
        $wrap.find('.task-master-inline-create').hide();
        $wrap.find('.task-master-new-name').val('');
    }

    function bind($root) {
        var $scope = ($root && $root.length) ? $root : $(document);
        $scope.find('.task-master-select-wrap').each(function() {
            initWrap($(this));
        });
    }

    return {
        bind: bind,
        setValue: setValue,
        clearValue: clearValue
    };
})();
