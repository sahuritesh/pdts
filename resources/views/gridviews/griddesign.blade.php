@extends('layouts.template_v1')
<!-- @section('title', 'Dashboard') -->
@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-title addFormBtn p-3">
                <h4 class="text-primary mb-0">{{$pageTitle}}</h4>
            </div>
            <div class="card-body pt-0">
                <ul class="customGridHeader">
                    <li>
                        <div class="row">
                            <div class="col-md-3">Name1</div>
                            <div class="col-md-3">Name2</div>
                            <div class="col-md-3">Name3</div>
                            <div class="col-md-3">Name4</div>
                        </div>
                    </li>
                </ul>

                <ul class="customGridListMain">
                    <li class="customSubList whiteBgShadow">
                        <div class="row innergridList">
                            <div class="col-md-3">
                               <ul>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization Subtypes</label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization</label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization</label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization Subtypes</label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales</label>
                                        <label> Subtypes</label>
                                    </li>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization </label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization </label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> <span class="bg-success badge me-2">Family</span></label>
                                    </li>
                                 </ul>
                            </div>
                        </div>
                        <div class="row p-0 m-0 lightBGColor pt-1 pb-1">
                            <div class="col-md-10">
                                <div class="innerlistBTN">
                                    <span class="bg-success badge me-2">Family</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                    <span class="bg-primary badge me-2">Social</span>
                                    <span class="bg-danger badge me-2">Friends</span>
                                    <span class="bg-info badge me-2">Support</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <div>
                                    <i class="readmoreBTN fas fa-ellipsis-h"></i>
                                </div>
                            </div>
                        </div>
                        <div class="row p-0 m-0 readmoretext">
                            <div class="col-md-12">
                                <div class="contentText p-15">
                                    We are holding large scale mineral leases in Coal, Graphite, Manganese and We are holding large scale mineral leases in Coal, Graphite, Manganese and Industrial Minerals
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="customSubList whiteBgShadow">
                        <div class="row innergridList">
                            <div class="col-md-3">
                               <ul>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization Subtypes</label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization</label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization</label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization Subtypes</label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales</label>
                                        <label> Subtypes</label>
                                    </li>
                                    <li>
                                        <label> Sales Product</label>
                                        <label> Organization </label>
                                    </li>
                                 </ul>
                            </div>
                            <div class="col-md-3">
                            <ul>
                                    <li>
                                        <label> Sales</label>
                                        <label> Organization </label>
                                    </li>
                                    <li>
                                        <label> Sales</label>
                                        <label> <span class="bg-success badge me-2">Family</span></label>
                                    </li>
                                 </ul>
                            </div>
                        </div>
                        <div class="row p-0 m-0 lightBGColor pt-1 pb-1">
                            <div class="col-md-10">
                                <div class="innerlistBTN">
                                    <span class="bg-success badge me-2">Family</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                    <span class="bg-primary badge me-2">Social</span>
                                    <span class="bg-danger badge me-2">Friends</span>
                                    <span class="bg-info badge me-2">Support</span>
                                    <span class="bg-warning badge me-2">Freelance</span>
                                </div>
                            </div>
                            <!-- <div class="col-md-2 text-end">
                                <div>
                                <i class="readmoreBTN fas fa-ellipsis-h"></i>
                                </div>
                            </div> -->
                        </div>
                        <!-- <div class="row p-0 m-0 readmoretext">
                            <div class="col-md-12">
                                <div class="contentText p-15">
                                    We are holding large scale mineral leases in Coal, Graphite, Manganese and We are holding large scale mineral leases in Coal, Graphite, Manganese and Industrial Minerals
                                </div>
                            </div>
                        </div> -->
                    </li>

                </ul>

                <ul class="list-style-none p-0 pagenation mb-0">
                    <li>
                        <div class="row">
                            <div class="col-md-4">
                            <div class="sectDropDownCustom">
                                <label>Show</label>
                                <select class="form-control form-select ">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                Showing 1 - 20 of 1,524
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group float-end">
                                    <button type="button" class="btn btn-sm btn-primary  waves-effect"><i class="fa fa-chevron-left"></i></button>
                                    <button type="button" class="btn btn-sm btn-primary  waves-effect"><i class="fa fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div> <!-- end row -->
@push('scripts')
<script>
$(document).ready(function(){
    $(".readmoretext").hide(); 
    $(".readmoreBTN").click(function(){
    $(".readmoretext").toggle();
  });
});
</script>
@endpush
<!-- @stack('scripts') -->
@endsection


