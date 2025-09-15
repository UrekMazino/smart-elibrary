@extends("admin.layouts.master")

@section('css')
<link rel="stylesheet" href="{{ asset('admin/nobleui/template/assets/vendors/select2/select2.min.css') }}">
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">REPORT</h6>
                    <div class="mb-3">
                        <form method="POST" id="frm" enctype="multipart/form-data" target="_blank" action="{{ url('/admin/print-report') }}">  
                            {{ csrf_field() }}
                        <label for="typeReport" class="form-label">Type of Report</label>
                        <select class="form-select" name="typeReport" id="typeReport">
                            <option selected disabled>---Select---</option>
                            <option value="1">Acquisition</option>
                            <option value="2">Patron</option>
                            <option value="3">Borrowed Materials</option>
                            <option value="4">Inquiries</option>
                            <option value="5">CSF</option>
                            <option value="6">Publication Downloads</option>
                            <option value="7">Publication Uploads</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 filter-row">
            <div class="card">
                <div class="card-body">
                    
                    <h6 class="card-title">Filter</h6>

                    <div class="mb-3 filter-div" id="filter-acquisition">
                        <div class="row">
                            <div class="col-12">
                                <label for="typeReport" class="form-label">Keyword/Phrase</label>
                                <input type="text" class="form-control" name="keyword" placeholder="Enter keyword/phrase..">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-12">
                                <label for="typeReport" class="form-label">Material Type</label>
                                <select class="js-example-basic-multiple form-select" id="selectMaterial" name="selectMaterial[]"  multiple="multiple" data-width="100%">
                                    <option value="0" selected>All</option>
                                    @foreach(getMaterials() AS $list)
                                        <option value="{{ $list->id }}">{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <br>
                        <div class="row">
                            <div class="col-12">
                                <label for="typeReport" class="form-label">Source</label>
                                <select class="js-example-basic-multiple form-select" id="selectSource" name="selectSource[]"  multiple="multiple" data-width="100%">
                                    <option value="0" selected>All</option>
                                    @foreach(getConsortia('all') AS $list)
                                        <option value="{{ $list->name }}">{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <br/>
                    </div>

                    <div class="mb-3 filter-div" id="filter-patron">
                        <div class="row">
                            <div class="col-12">
                                <label id="date_title" class="form-label">Date Registered</label>
                                <div class="row">
                                    <div class="col-6">
                                        <span>Filter Type:</span>
                                        <select class="form-control" name="select_type" id="select_type">
                                                    <option value="1">Date</option>
                                                    <option value="2">Monthly</option>
                                                    <option value="3">Yearly</option>
                                                    <option value="4">Duration</option>
                                        </select>
                                    </div>
                                </div>
                
                                <div class="row filter_group" id="filter_date" style="margin-top: 2.5%;">
                                    <div class="col-6">
                                        <span>Select Date:</span>
                                        <input data-provide="datepicker" name="filter_date_dt" id="filter_date_dt" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
                                    </div>
                                </div>
                
                                <div class="row filter_group" id="filter_month" style="margin-top: 2.5%;">
                                    <div class="col-6">
                                        <span>Select Month:</span>
                                        <select class="form-control" name="filter_month_mon" id="filter_month_mon">
                                            <option value="1">January</option>
                                            <option value="2">February</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <span>Select Year:</span>
                                        <select class="form-control" name="filter_month_year" id="filter_month_year">
                                            <?php
                                                $date_now = date('Y');
                                                for($i = $date_now;$i >= ($date_now - 5);$i--)
                                                {
                                                    echo '<option value="'.$i.'">'.$i.'</option>';
                                                }
                                            ?>
                                        </select>
                                    </div>
                
                                </div>
                
                                <div class="row filter_group" id="filter_year" style="margin-top: 2.5%;">
                                    <div class="col-6">
                                        <span>Select Year:</span>
                                        <select class="form-control" name="filter_year_yr" id="filter_year_yr">
                                            <?php
                                                $date_now = date('Y');
                                                for($i = $date_now;$i >= ($date_now - 5);$i--)
                                                {
                                                    echo '<option value="'.$i.'">'.$i.'</option>';
                                                }
                                            ?>
                                        </select>
                                    </div>
                
                                </div>
                
                                <div class="row filter_group" id="filter_duration" style="margin-top: 2.5%;">
                                    <div class="input-daterange input-group" data-provide="datepicker">
                                    <div class="col-6">
                                        <span>Select Date From:</span>
                                        <input type="text" class="input-sm form-control" name="start"  value="{{ date('m/d/Y') }}">
                                    </div>
                                    <div class="col-6">
                                        <span>Select Date To:</span>
                                        <input type="text" class="input-sm form-control" name="end"  value="{{ date('m/d/Y') }}">
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                    </div>



                    <p align='right'><button type="submit" class="btn btn-success" id="btnSubmit">Submit</button></p>
                    </form>
                </div>



            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('admin/nobleui/template/assets/vendors/select2/select2.min.js') }}"></script>

<script>
$(".filter-div,#btnSubmit,.filter_group").hide();

$(function() {
  'use strict'
  $(".js-example-basic-multiple").select2();
});

$("#typeReport").change(function(){
    $(".filter-div,#btnSubmit").hide();
    switch(this.value)
    {
        case "1":
            $("#filter-acquisition,#btnSubmit").show();
        break;
        case "2":
            $("#date_title").text('Date Registered');
            $("#filter_date").show();
            $("#filter-patron,#btnSubmit").show();
        break;
        case "4":
            $("#date_title").text('Date Inquiry');
            $("#filter_date").show();
            $("#filter-patron,#btnSubmit").show();
        break;
        case "5":
            $("#date_title").text('Date of CSF');
            $("#filter_date").show();
            $("#filter-patron,#btnSubmit").show();
        break;
        case "6":
            $("#date_title").text('Publication Downloads');
            $("#filter_date").show();
            $("#filter-patron,#btnSubmit").show();
        break;
        case "7":
            $("#date_title").text('Publication Uploads');
            $("#filter_date").show();
            $("#filter-patron,#btnSubmit").show();
        break;
    }
})

$('.js-example-basic-multiple').change(function () {
    SelectOption(this.id);
});

function SelectOption(slect)
{
    $('#'+slect).on('select2:select', function (e) {
    var data = e.params.data;
    console.log(data.id);
    if(data.id == 0)
    {
        $('#'+slect).val('').change();
        $('#'+slect).val(0).change();
    }
    else
    {
        var wanted_option = $('#'+slect+' option[value="0"]');
        wanted_option.prop('selected', false);

        $('#'+slect).trigger('change.select2');

       // $('#selectColumn').val(0).change();
    }
    });
}

$("#select_type").change(function(){
        $(".filter_group").hide();

        switch(this.value)
        {
            case "1":
                $("#filter_date").show();
            break;
            case "2":
                $("#filter_month").show();
            break;
            case "3":
                $("#filter_year").show();
            break;
            case "4":
                $("#filter_duration").show();
            break;
        }
    });
</script>
@endsection