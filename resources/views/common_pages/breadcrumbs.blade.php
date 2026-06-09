<!-- start page title -->
<div class="row d-none" >
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div  class="mb-sm-0">
                <h4>
                    @isset($confereneHeading){{$confereneHeading->name}}
                </h4>
                <h6>{{date("F d",strtotime($confereneHeading->start_date))}} -
                    {{date("F d, Y",strtotime($confereneHeading->end_date))}}</h6> @endif
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Home</a></li>
                    @if(!empty(Request::segment(2)))
                    <li class="breadcrumb-item active"><a href="javascript:history.back()">Back</a></li>
                    @else
                    <li class="breadcrumb-item active">{{$pageTitle??''}}</li>
                    @endif
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->