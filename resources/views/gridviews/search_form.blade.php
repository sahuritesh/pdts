@if(isset($grid_data['filters']) && !empty($grid_data['filters']))
<div class="searchmain">
    <form class="custom-validations" id="searchform" action="#" method="POST" autocomplete="off">
        @csrf
        <div class="row">
            @foreach($grid_data['filters'] as $filter)
            <div class="{{ isset($filter['col_class']) ? $filter['col_class'] : 'col-md-2' }} mb-2">
                @if(isset($filter['label']) && $filter['label'] != '')
                <label for="{{ $filter['name'] }}">{{ $filter['label'] }}</label>
                @endif
                
                @if($filter['type'] == 'text')
                <input type="text" 
                       name="{{ $filter['name'] }}" 
                       id="{{ $filter['name'] }}" 
                       placeholder="{{ isset($filter['placeholder']) ? $filter['placeholder'] : 'Search' }}" 
                       class="form-control {{ isset($filter['class']) ? $filter['class'] : '' }}" />
                
                @elseif($filter['type'] == 'select')
                <select name="{{ $filter['name'] }}" 
                        id="{{ $filter['name'] }}" 
                        class="form-select {{ isset($filter['class']) ? $filter['class'] : '' }} {{ isset($filter['select2']) && $filter['select2'] !== false ? 'select2' : '' }}">
                    <option value="">{{ isset($filter['placeholder']) ? $filter['placeholder'] : 'Select' }}</option>
                    @if(isset($filter['show_all_option']) && $filter['show_all_option'] === true)
                    <option value="All">All</option>
                    @endif
                    @if(isset($filter['options']) && is_array($filter['options']))
                        @foreach($filter['options'] as $option)
                        <option value="{{ isset($option['value']) ? $option['value'] : (isset($option['id']) ? $option['id'] : '') }}" 
                                @if(isset($filter['selected_value']) && isset($option['value']) && $filter['selected_value'] == $option['value']) selected @endif>
                            {{ isset($option['label']) ? $option['label'] : (isset($option['name']) ? $option['name'] : '') }}
                        </option>
                        @endforeach
                    @endif
                </select>
                
                @elseif($filter['type'] == 'daterange')
                <input type="text" 
                       class="form-control {{ isset($filter['flatpickr']) ? 'default' : '' }}" 
                       name="{{ $filter['name'] }}" 
                       value="" 
                       id="{{ $filter['name'] }}"
                       placeholder="{{ isset($filter['placeholder']) ? $filter['placeholder'] : 'Select Date Range' }}">
                
                @elseif($filter['type'] == 'date')
                <input type="text" 
                       class="form-control {{ isset($filter['datepicker']) ? 'default' : '' }}" 
                       name="{{ $filter['name'] }}" 
                       value="" 
                       id="{{ $filter['name'] }}"
                       placeholder="{{ isset($filter['placeholder']) ? $filter['placeholder'] : 'Select Date' }}">
                
                @elseif($filter['type'] == 'checkbox')
                <div class="form-check mt-2">
                    <input type="checkbox" 
                           class="form-check-input" 
                           name="{{ $filter['name'] }}" 
                           id="{{ $filter['name'] }}" 
                           value="1"
                           @if(isset($filter['checked']) && $filter['checked']) checked @endif>
                    <label class="form-check-label" for="{{ $filter['name'] }}">
                        {{ isset($filter['label']) ? $filter['label'] : '' }}
                    </label>
                </div>
                @endif
            </div>
            @endforeach
            
            <div class="col-md-2 mb-2">
                <label style="visibility:hidden">Actions</label>
                <div>
                    <button type="button" 
                            id="Search" 
                            class="btn btn-primary waves-effect waves-light searchBtn me-1" 
                            onclick="saveFiltersAndFetch()" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Search">
                        <i class="fa fa-search"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-primary waves-effect waves-light searchBtn me-1 reset_cls" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Reset">
                        <i class="mdi mdi-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('child-scripts')
<script>
$(document).ready(function() {
    // Reset button functionality
    $('.reset_cls').click(function() {
        $('#searchform')[0].reset();
        // Reset select2 if used
        $('#searchform select.select2').val(null).trigger('change');
        // Reset date pickers
        $('#searchform input.default').each(function() {
            if ($(this)[0]._flatpickr) {
                $(this)[0]._flatpickr.clear();
            }
            // Reset date range pickers
            if ($(this).data('daterangepicker')) {
                $(this).data('daterangepicker').setStartDate(null);
                $(this).data('daterangepicker').setEndDate(null);
                $(this).val('');
            }
        });
        saveFiltersAndFetch();
    });
});
</script>
@endpush
@endif
