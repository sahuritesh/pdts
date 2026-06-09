<?php

namespace App\Http\Traits;

use App\Models\Common_model;

trait GridConfigTrait
{
    /**
     * Build a text search filter
     * 
     * @param string $name Field name
     * @param string $placeholder Placeholder text
     * @param string $label Label text
     * @param string $colClass Bootstrap column class (default: 'col-md-3')
     * @return array
     */
    protected function buildTextFilter($name, $placeholder = 'Search', $label = 'Search', $colClass = 'col-md-3')
    {
        return [
            'type' => 'text',
            'name' => $name,
            'placeholder' => $placeholder,
            'label' => $label,
            'col_class' => $colClass
        ];
    }

    /**
     * Build a select dropdown filter
     * 
     * @param string $name Field name
     * @param array $options Array of ['value' => '', 'label' => ''] options
     * @param string $label Label text
     * @param string $placeholder Placeholder text
     * @param bool $showAllOption Show "All" option
     * @param bool $useSelect2 Use Select2 plugin
     * @param string $colClass Bootstrap column class (default: 'col-md-2')
     * @return array
     */
    protected function buildSelectFilter($name, $options = [], $label = '', $placeholder = 'Select', $showAllOption = true, $useSelect2 = true, $colClass = 'col-md-2')
    {
        return [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'show_all_option' => $showAllOption,
            'options' => $options,
            'select2' => $useSelect2,
            'col_class' => $colClass
        ];
    }

    /**
     * Build a date filter (single date)
     * 
     * @param string $name Field name
     * @param string $label Label text
     * @param string $colClass Bootstrap column class (default: 'col-md-2')
     * @return array
     */
    protected function buildDateFilter($name, $label = '', $colClass = 'col-md-2')
    {
        return [
            'type' => 'date',
            'name' => $name,
            'placeholder' => 'Select Date',
            'label' => $label,
            'col_class' => $colClass,
            'datepicker' => true
        ];
    }

    /**
     * Build a date range filter
     * 
     * @param string $name Field name
     * @param string $placeholder Placeholder text
     * @param string $label Label text
     * @param string $colClass Bootstrap column class (default: 'col-md-3')
     * @return array
     */
    protected function buildDateRangeFilter($name, $placeholder = 'Select Date Range', $label = '', $colClass = 'col-md-3')
    {
        return [
            'type' => 'daterange',
            'name' => $name,
            'placeholder' => $placeholder,
            'label' => $label,
            'col_class' => $colClass,
            'flatpickr' => true
        ];
    }

    /**
     * Build a checkbox filter
     * 
     * @param string $name Field name
     * @param string $label Label text
     * @param string $colClass Bootstrap column class (default: 'col-md-2')
     * @param bool $checked Whether checkbox is checked by default
     * @return array
     */
    protected function buildCheckboxFilter($name, $label = '', $colClass = 'col-md-2', $checked = false)
    {
        return [
            'type' => 'checkbox',
            'name' => $name,
            'label' => $label,
            'col_class' => $colClass,
            'checked' => $checked
        ];
    }

    /**
     * Get status options from database
     * 
     * @param string $type Status type (default: 'Default')
     * @return array Array of ['value' => id, 'label' => status_name]
     */
    protected function getStatusOptions($type = 'Default')
    {
        // Return hardcoded status options (tbl_status table doesn't exist)
        return [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive']
        ];
    }

    /**
     * Build complete grid configuration
     * 
     * @param array $config Configuration array with keys:
     *   - columns: array of column names
     *   - table: database table name
     *   - dataurl: URL endpoint for data fetching
     *   - addurl: URL for add button (optional)
     *   - addurllabel: Label for add button (optional)
     *   - filters: array of filter configurations
     *   - page_length: default page length (optional)
     *   - length_menu: custom length menu (optional)
     *   - export_columns: export column selector (optional)
     *   - no_sort_columns: array of columns that should not be sortable (optional)
     *   - server_export: optional array with keys url (full URL), limit_options (array of value/label), max_rows (int cap when exporting "all")
     *   - reload_on_filter_change: when true (default), changing filter fields reloads the grid without clicking Search
     * @return array
     */
    protected function buildGridConfig($config)
    {
        $defaultConfig = [
            'columns' => [],
            'table' => '',
            'dataurl' => '',
            'addurl' => '',
            'filters' => [],
            'page_length' => 0,
            'length_menu' => null,
            'export_columns' => ':not(:last-child)',
            'no_sort_columns' => [],
            'server_export' => null,
            'reload_on_filter_change' => true,
        ];

        return array_merge($defaultConfig, $config);
    }
}

