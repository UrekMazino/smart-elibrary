@extends("admin.layouts.master")

@section('css')

@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0">EDIT MATERIAL</h6>
                    
                        <button type="submit" class="btn p-2 btn-primary" onclick="saveMaterial()">
                          <i class="icon-lg pb-3px text-white" data-feather="save"> </i> SAVE &nbsp&nbsp
                        </button>
                      </div>

                    <form method="post" action="{{ url('/admin/update-acquisition') }}" id="frm_acquisition" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="catalog_id" id="catalog_id" value="{{$data['holdings']['id']}}">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                          <a class="nav-link active" id="info-tab" data-bs-toggle="tab" href="#info" role="tab" aria-controls="home" aria-selected="true">Information</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="pub-tab" data-bs-toggle="tab" href="#pub" role="tab" aria-controls="pub" aria-selected="false">Publication</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="author-tab" data-bs-toggle="tab" href="#author" role="tab" aria-controls="author" aria-selected="false">Author</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" id="corpo_author-tab" data-bs-toggle="tab" href="#corpo_author" role="tab" aria-controls="corpo_author" aria-selected="false">Corporate Author</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" id="copy-tab" data-bs-toggle="tab" href="#copy" role="tab" aria-controls="copy" aria-selected="false">Copy/ies</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" id="files-tab" data-bs-toggle="tab" href="#files" role="tab" aria-controls="files" aria-selected="false">Files</a>
                        </li>
                      </ul>
                      <div class="tab-content border border-top-0 p-3" id="myTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="material_type" class="form-label"><span class="badge bg-danger">006</span> Material Type <span class="text-danger"><b>*</b></span></label>
                                        <select class="form-control" name="material_id"  id="material_id" required>
                                            <option value="" selected disabled>--- Select ---</option>
                                            @foreach(getMaterials() AS $list)
                                                 <option value="{{ $list->id }}">{{ $list->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 {{ $data['holdings']['ISSN'] ? 'visible' : 'invisible' }}" id="issn-div">
                                    <div class="mb-3">
                                        <label for="issn" class="form-label">ISSN</label>
                                        <input type="text" class="form-control" name="issn" value="{{$data['holdings']['ISSN']}}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 {{ $data['holdings']['ISBN'] ? 'visible' : 'invisible' }}" id="isbn-div">
                                    <div class="mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" value="{{$data['holdings']['ISBN']}}">
                                    </div>
                                </div>                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="classifications_system" class="form-label">Classification System</label>
                                        <select class="form-control" name="classifications_system" id="classifications_system"> 
                                            <option value="" selected disabled>--- Select ---</option>
                                            <option value="Library of Congress">Library of Congress</option>
                                            <option value="Dewey Decimal Classification">Dewey Decimal Classification</option>
                                        </select>
                                    </div>
                                </div>
                            
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="classification_number" class="form-label"><span class="badge bg-primary">050a</span>  Classification Number</label>
                                        <input type="text" class="form-control" name="classification_number" placeholder="Enter text here..." value="{{$data['holdings']['classification_number']}}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="author_num" class="form-label"><span class="badge bg-primary">050b</span> Author Number</label>
                                        <input type="text" class="form-control" name="author_number" placeholder="Enter text here..."  value="{{$data['holdings']['author_number']}}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="copyright_date" class="form-label"><span class="badge bg-primary">050c</span> Publication Date/Copyright Date</label>
                                        <input data-provide="datepicker" name="copyright_date" id="copyright_date" data-date-autoclose="true" class="form-control" value="{{$data['holdings']['copyright_date']}}">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="title" class="form-label"><span class="badge bg-danger">245a</span> Title <span class="text-danger"><b>*</b></span></label>
                                        <textarea class="form-control" name="title_statement" placeholder="Enter text here..." required>{{ $data['holdings']['title_statement'] }}</textarea>
                                    </div>
                                </div>
                            
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="title_remainder" class="form-label"><span class="badge bg-primary">245b</span> Title Remainder</label>
                                        <input type="text" class="form-control" name="title_remainder" placeholder="Enter text here..." value="{{ $data['holdings']['title_remainder'] }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="statement_responsibility" class="form-label"><span class="badge bg-primary">245c</span> Statement of Responsibility</label>
                                        <input type="text" class="form-control" name="statement_responsibility" placeholder="Enter text here..." value="{{ $data['holdings']['statement_responsibility'] }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="title" class="form-label"><span class="badge bg-danger"></span> URL</span></label>
                                        <textarea class="form-control" name="pub_url" placeholder="URL link..." required>{{ $data['holdings']['pub_url'] }}</textarea>
                                    </div>
                                </div>
                            </div>

                            
                        <br>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="edition_statement" class="form-label"><span class="badge bg-primary">250a</span> Edition Statement</label>
                                    <input type="text" class="form-control" name="edition_statement" placeholder="Enter text here.." value="{{ $data['holdings']['edition_statement'] }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="edition_statement_remainder" class="form-label"><span class="badge bg-primary">250b</span>  Edition Statement Remainder</label>
                                     <input type="text" class="form-control" name="edition_statement_remainder" placeholder="Enter text here..."  value="{{ $data['holdings']['edition_statement_remainder'] }}">
                                </div>
                            </div>
                        </div>


                        </div>

                        <div class="tab-pane fade" id="pub" role="tabpanel" aria-labelledby="pub-tab">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="publication_place" class="form-label"><span class="badge bg-primary">264a</span> Publication Place</label>
                                        <input type="text" class="form-control" name="publication_place"  placeholder="Enter text here..." value="{{ $data['holdings']['publication_place'] }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="publisher" class="form-label"><span class="badge bg-primary">264b</span> Publisher</label>
                                        <input type="text" class="form-control" name="publisher" placeholder="Enter text here..." value="{{ $data['holdings']['publisher'] }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="publication_date" class="form-label"><span class="badge bg-primary">264c</span> Publication Date</label>
                                        <input type="text" class="form-control" name="publication_date" id="publication_date" placeholder="Enter text here..." value="{{ $data['holdings']['publication_date'] }}">
                                    </div>
                                </div>
                            </div>

                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="extent" class="form-label"><span class="badge bg-primary">300a</span> Extent</label>
                                            <input type="text" class="form-control" name="extent" placeholder="Enter text here..." value="{{ $data['holdings']['extent'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="other_physical" class="form-label"><span class="badge bg-primary">300b</span> Other Physical Detail</label>
                                            <input type="text" class="form-control" name="other_physical" placeholder="Enter text here..." value="{{ $data['holdings']['other_physical'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="dimension" class="form-label"><span class="badge bg-primary">300c</span> Dimension</label>
                                            <input type="text" class="form-control" name="dimension" placeholder="Enter text here..." value="{{ $data['holdings']['dimension'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="frequency" class="form-label"><span class="badge bg-primary">310</span> Frequency</label>
                                            <select class="form-control" name="frequency" id="frequency">
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="Daily">Daily</option>
                                                <option value="Weekly">Weekly</option>
                                                <option value="Monthly">Monthly</option>
                                                <option value="Quarterly">Quarterly</option>
                                                <option value="Yearly">Yearly</option>
                                                <option value="Irregular">Irregular</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="volume" class="form-label">Volume</label>
                                            <input type="text" class="form-control" name="volume" placeholder="Enter text here..." value="{{ $data['holdings']['volume'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="issue_number" class="form-label">Issue Number</label>
                                            <input type="text" class="form-control" name="issue_number" placeholder="Enter text here..." value="{{ $data['holdings']['issue_number'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="series_statement_id" class="form-label"><span class="badge bg-primary">490</span> Series Statement</label>
                                            <select class="slct2 form-select" data-width="100%" name="series_statement_id" id="series_statement_id">
                                                <option value="" selected disabled>--- Select ---</option>
                                                @foreach(getSeries() AS $list)
                                                    <option value="{{ $list->id }}">{{ $list->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="bibliography" class="form-label"><span class="badge bg-primary">504</span> Bibliography</label>
                                            <input type="text" class="form-control" name="bibliography" placeholder="Enter text here..." value="{{ $data['holdings']['bibliography'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="accession_number" class="form-label"><span class="badge bg-danger">876a</span> Accession Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="accession_number" placeholder="Enter text here..." required value="{{ $data['holdings']['accession_number'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cir_num" class="form-label"><span class="badge bg-danger">876b</span> Circulation Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="circulation_number" placeholder="Enter text here..." required value="{{ $data['holdings']['circulation_number'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cost_price" class="form-label"><span class="badge bg-danger">876c</span> Cost/Price <span class="text-danger"><b>*</b></span></label>
                                            <input type="number" class="form-control" name="cost_price" placeholder="Enter text here..." value="{{ $data['holdings']['cost_price'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="date_acquired" class="form-label"><span class="badge bg-primary">876d</span> Date Acquired</label>
                                            <input data-provide="datepicker" name="date_acquired" id="date_acquired" data-date-autoclose="true" class="form-control" value="{{ date("m/d/Y",strtotime($data['holdings']['date_acquired'])) }}">
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="frequency" class="form-label"><span class="badge bg-danger">876e</span> Acquisition Modes <span class="text-danger"><b>*</b></span></label>
                                            <select class="form-control" name="acquisition_modes" id="acquisition_modes" required>
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="Subscription">Subscription</option>
                                                <option value="Purchase">Purchase</option>
                                                <option value="Complimentary">Complimentary</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="user_restriction" class="form-label"><span class="badge bg-primary">876h</span> User Restriction</label>
                                            <input type="text" class="form-control" name="user_restriction" placeholder="Enter text here..." value="{{ $data['holdings']['user_restriction'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="temporary_location" class="form-label"><span class="badge bg-primary">876l</span> Temporary Location</label>
                                            <input type="text" class="form-control" name="temporary_location" placeholder="Enter text here..." value="{{ $data['holdings']['temporary_location'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="copy_number" class="form-label"><span class="badge bg-danger">876t</span> Copy Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="copy_number" placeholder="Enter text here..." required value="{{$data['holdings']['copy_number']}}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="nonpublic_note" class="form-label"><span class="badge bg-primary">876x</span> Nonpublic note</label>
                                            <input type="text" class="form-control" name="nonpublic_note" placeholder="Enter text here..." value="{{ $data['holdings']['nonpublic_note'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="no_of_copy" class="form-label"><span class="badge bg-primary">Inventory</span> No. of Copies</label>
                                            <input type="number" class="form-control" name="no_of_copy" value="{{ $data['holdings']['copies'] }}" placeholder="">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="no_of_copy" class="form-label"><span class="badge bg-primary">520$a</span> Summary</label>
                                            <textarea class="form-control" name="pub_summary">{{ $data['holdings']['Summary'] }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="no_of_copy" class="form-label"><span class="badge bg-primary">500$a</span> General Note</label>
                                            <textarea class="form-control" name="general_note">{{ $data['holdings']['general_note'] }}</textarea>
                                        </div>
                                    </div>

                                    {{-- <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="no_of_copy" class="form-label"><span class="badge bg-primary"></span> Subjects</label>
                                            <textarea class="form-control" name="subject"></textarea>
                                        </div>
                                    </div> --}}

                                </div>

                        </div>
                        
                        
                        <div class="tab-pane fade" id="author" role="tabpanel" aria-labelledby="author-tab">
                            <input type="hidden" name="author_id" value="{{ $data['authors']['id'] }}">
                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="author_type" class="form-label"><span class="badge bg-danger">100</span> Type <span class="text-danger"><b>*</b></span></label>
                                            <select class="form-control" name="author_type" id="author_type" required>
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="100">Forename</option>
                                                <option value="101">Surname</option>
                                                <option value="103">Family Name</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="author_name" class="form-label"><span class="badge bg-danger">100a</span> Personal Name <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="author_name" placeholder="Enter text here..." value="{{ $data['authors']['author_name2'] }}" required>
                                        </div>
                                    </div>
                                </div>
                            
                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="numeration" class="form-label"><span class="badge bg-primary">100b</span> Numeration</label>
                                            <input type="text" class="form-control" name="numeration" placeholder="Enter text here..." value="{{ $data['authors']['numeration'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="title_and_words" class="form-label"><span class="badge bg-primary">100c</span> Tiles and words associated with a name</label>
                                            <input type="text" class="form-control" name="title_and_words" placeholder="Enter text here..." value="{{ $data['authors']['title_and_words'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="relator_term" class="form-label"><span class="badge bg-primary">100e</span> Relator Term</label>
                                            <input type="text" class="form-control" name="relator_term" placeholder="Enter text here..." value="{{ $data['authors']['relator_term'] }}">
                                        </div>
                                    </div>
                                </div>
                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="date_associated" class="form-label"><span class="badge bg-primary">100d</span> Date associated with a name</label>
                                            <input data-provide="datepicker" name="date_associated" id="date_associated" data-date-autoclose="true" class="form-control" value="{{ date("m/d/Y",strtotime($data['authors']['date_associated'])) }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="fuller_form" class="form-label"><span class="badge bg-primary">100q</span> Fuller form of name</label>
                                            <input type="text" class="form-control" name="fuller_form" placeholder="Enter text here..." value="{{ $data['authors']['fuller_form'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="affiliation" class="form-label"><span class="badge bg-primary">100u</span> Affiliation</label>
                                            <input type="text" class="form-control" name="affiliation" placeholder="Enter text here..." value="{{ $data['authors']['affiliation'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="form-check mb-3">
                                    @if(isset($data['authors2']['id']))
                                        <input type="checkbox" class="form-check-input" id="add_author_check" name="add_author_check" value="1" checked>
                                    @else
                                        <input type="checkbox" class="form-check-input" id="add_author_check" name="add_author_check" value="1">
                                    @endif
                                                <label class="form-check-label" for="add_author_check">
                                                    <h5>Added Entry (Personal Author)</h5>
                                                </label>
                                    </div>
                                    <input type="hidden" name="author_id2" value="{{ $data['authors2']['id'] }}">
                                <br>
                                    @if(isset($data['authors2']['id']))
                                        <div id="add_author_check_div">
                                    @else
                                        <div id="add_author_check_div" style="display:none">
                                    @endif
                                
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="author_type2" class="form-label"><span class="badge bg-primary">700</span> Type</label>
                                                <select class="form-control" name="author_type2" id="author_type2">
                                                    <option value="" selected disabled>--- Select ---</option>
                                                    <option value="700">Forename</option>
                                                    <option value="701">Surname</option>
                                                    <option value="703">Family Name</option>
                                                </select>
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="author_name2" class="form-label"><span class="badge bg-primary">700a</span> Personal Name</label>
                                                <input type="text" class="form-control" name="author_name2" placeholder="Enter text here..." value="{{ $data['authors2']['author_name2'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="numeration2" class="form-label"><span class="badge bg-primary">700b</span> Numeration</label>
                                                <input type="text" class="form-control" name="numeration2" placeholder="Enter text here..." value="{{ $data['authors2']['numeration'] }}">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="title_and_words2" class="form-label"><span class="badge bg-primary">700c</span> Tiles and words associated with a name</label>
                                                <input type="text" class="form-control" name="title_and_words2" placeholder="Enter text here..." value="{{ $data['authors2']['title_and_words'] }}">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="relator_term2" class="form-label"><span class="badge bg-primary">700e</span> Relator Term</label>
                                                <input type="text" class="form-control" name="relator_term2" placeholder="Enter text here..." value="{{ $data['authors2']['relator_term'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="date_associated2" class="form-label"><span class="badge bg-primary">700d</span> Date associated with a name</label>
                                                <input data-provide="datepicker" name="date_associated2" id="date_associated2" data-date-autoclose="true" class="form-control" value="{{ date("m/d/Y",strtotime($data['authors2']['date_associated'])) }}">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="fuller_form2" class="form-label"><span class="badge bg-primary">700q</span> Fuller form of name</label>
                                                <input type="text" class="form-control" name="fuller_form2" placeholder="Enter text here..." value="{{ $data['authors2']['fuller_form'] }}">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                <label for="affiliation2" class="form-label"><span class="badge bg-primary">700u</span> Affiliation</label>
                                                <input type="text" class="form-control" name="affiliation2" placeholder="Enter text here..." value="{{ $data['authors2']['affiliation'] }}">
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="corpo_author" role="tabpanel" aria-labelledby="corpo_author-tab">
                            <br>
                            <input type="hidden" name="cauthor_id" value="{{ $data['cauthors']['id'] }}">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="cauthor_type" class="form-label"><span class="badge bg-primary">110</span> Type</label>
                                        <select class="form-control" name="cauthor_type" id="cauthor_type">
                                            <option value="" selected disabled>--- Select ---</option>
                                            <option value="110">Invented name</option>
                                            <option value="111">Jurisdiction name</option>
                                            <option value="112">Name in direct order</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="cauthor_name" class="form-label"><span class="badge bg-primary">110a</span> <small>Corporate name or jurisdiction name as entry element</small></label>
                                        <input type="text" class="form-control" name="cauthor_name" placeholder="Enter text here..." value="{{ $data['cauthors']['author_name'] }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="subordinate_unit" class="form-label"><span class="badge bg-primary">110b</span> Subordinate Unit</label>
                                        <input type="text" class="form-control" name="subordinate_unit" placeholder="Enter text here..." value="{{ $data['cauthors']['subordinate_unit'] }}">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="location_meeting" class="form-label"><span class="badge bg-primary">110c</span> Location Meeting</label>
                                        <input type="text" class="form-control" name="location_meeting" placeholder="Enter text here..." value="{{ $data['cauthors']['location_meeting'] }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="date_of_meeting" class="form-label"><span class="badge bg-primary">110d</span> Date of meeting or treaty signing</label>
                                        <input type="text" class="form-control" name="date_of_meeting" placeholder="Enter text here..." value="{{ date("m/d/Y",strtotime($data['cauthors']['date_of_meeting'])) }}">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="number_of_part" class="form-label"><span class="badge bg-primary">110n</span> Number of part/session/meeting</label>
                                        <input type="text" class="form-control" name="number_of_part" placeholder="Enter text here..." value="{{ $data['cauthors']['number_of_part'] }}">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="add_corpo_check" name="add_corpo_check" value="1">
										<label class="form-check-label" for="add_corpo_check">
											<h5>Added Entry (Corporate Author)</h5>
										</label>
							</div>
                            <br>
                                <input type="hidden" name="cauthor_id2" value="{{ $data['cauthors2']['id'] }}">
                                    @if(isset($data['cauthors2']['id']))
                                        <div id="add_corpo_check_div">
                                    @else
                                        <div id="add_corpo_check_div" style="display:none">
                                    @endif
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cauthor_type2" class="form-label"><span class="badge bg-primary">710</span> Type</label>
                                            <select class="form-control" name="cauthor_type2" id="cauthor_type2">
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="710">Invented name</option>
                                                <option value="711">Jurisdiction name</option>
                                                <option value="712">Name in direct order</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cauthor_name2" class="form-label"><span class="badge bg-primary">710a</span> <small>Corporate name or jurisdiction name as entry element</small></label>
                                            <input type="text" class="form-control" name="cauthor_name2" placeholder="Enter text here..." value="{{ $data['cauthors2']['author_name'] }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="subordinate_unit2" class="form-label"><span class="badge bg-primary">710b</span> Subordinate Unit</label>
                                            <input type="text" class="form-control" name="subordinate_unit2" placeholder="Enter text here..." value="{{ $data['cauthors2']['subordinate_unit'] }}">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="location_meeting2" class="form-label"><span class="badge bg-primary">710c</span> Location Meeting</label>
                                            <input type="text" class="form-control" name="location_meeting2" placeholder="Enter text here..." value="{{ date("m/d/Y",strtotime($data['cauthors2']['date_of_meeting'])) }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="date_of_meeting2" class="form-label"><span class="badge bg-primary">710d</span> Date of meeting or treaty signing</label>
                                            <input type="text" class="form-control" name="date_of_meeting2" placeholder="Enter text here..." value="{{ $data['cauthors2']['location_meeting'] }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="copy" role="tabpanel" aria-labelledby="copy-tab">
                            <p align="right"><button type="button" class="btn btn-info" onclick="addCopy()">ADD</button></p>
                            <table class="table" id="tbl_copy">
                                <thead>
                                    <tr><th>Accession No.</th>
                                    <th>Circulation No.</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                    <th></th>
                                </tr></thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" class="form-control input-sm" name="accession_number[]" required="true"></td>
                                        <td><input type="text" class="form-control input-sm" name="circulation_number[]" required="true"></td>
                                        <td><input type="text" class="form-control input-sm" name="location[]" required="true"></td>
                                        <td><input type="text" class="form-control input-sm" name="source[]" required="true"></td>
                                        <td>
                                            <select class="form-control input-sm" name="status[]" required>
                                                <option value="" selected disabled>--- Select Status ---</option>
                                                @foreach(getStatus() as $status)
                                                    <option value="{{ $status->order_no }}">{{ $status->name }}</option>
                                                 @endforeach
                                                <!-- Add more options as needed -->
                                            </select>
                                        </td>
                                        <td></td>
                                    </tr>                                        
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="formCover" class="form-label">Update Cover Photo</label>
                                        <input class="form-control" type="file" id="formCover" name="formCover">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="formCoverPreview" class="form-label">&nbsp</label><br>
                                        <a href="{{getFrontpageLink($data['holdings']['catalog_id'])}}" target="_blank" class="btn btn-secondary" id="formCoverPreview">Click to view</a> 
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="formPDF" class="form-label">Update PDF</label>
                                        <input class="form-control" type="file" id="formPDF" name="formPDF" accept="application/pdf">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="formCoverPreview" class="form-label">&nbsp</label><br>
                                        <a href="{{getPDFLink($data['holdings']['catalog_id'])}}" target="_blank" class="btn btn-secondary" id="formCoverPreview">Click to view</a> 
                                    </div>
                                </div>
                            </div>
                        </div>
                      </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    let getStatus = @json(getStatus());
</script>
<script type="text/javascript">
    $(document).ready(function() {
       $('.ckeditor').ckeditor;

       $("#material_id").change(function() {
            let selectedValue = $(this).val();
            // Check if the selected Material Type matches the value for ISSN or ISBN
            if (selectedValue == 2) {
                $("#issn-div").removeClass("invisible").addClass("visible");
                $("#isbn-div").removeClass("visible").addClass("invisible");
            } else if (selectedValue == 1) {
                $("#issn-div").removeClass("visible").addClass("invisible");
                $("#isbn-div").removeClass("invisible").addClass("visible");
            } else {
                // If none of the above, hide both ISSN and ISBN fields
                $("#issn-div").removeClass("visible").addClass("invisible");
                $("#isbn-div").removeClass("visible").addClass("invisible");
            }
        });
    });
    
</script>

<script>
    $(document).ready(function(){
        $("#material_id").val("{{ $data['holdings']['material_type_id'] }}");
        $("#classifications_system").val("{{ $data['holdings']['classifications_system'] }}");
        $("#imprint").val("{{ $data['holdings']['imprint'] }}");
        $("#type_of_imprint").val("{{ $data['holdings']['type_of_imprint'] }}");
        $("#frequency").val("{{ $data['holdings']['frequency'] }}");
        $("#acquisition_modes").val("{{ $data['holdings']['acquisition_modes'] }}");

        $("#author_type").val("{{ $data['authors']['AuthorTag'] }}");
        $("#author_type2").val("{{ $data['authors2']['AuthorTag'] }}");

        $("#cauthor_type").val("{{ $data['cauthors']['author_type'] }}");
        $("#cauthor_type2").val("{{ $data['cauthors2']['author_type'] }}");
    });

    $(".slct2").select2();
    $("#series_statement_id").select2("val","{{ $data['holdings']['series_statement_id'] }}");

    //$("#add_author_check_div,#add_corpo_check_div").hide();
    
    $(".form-check-input").change(function(){
        if($('#' + this.id).is(":checked"))
            $("#"+this.id+"_div").show();
        else
            $("#"+this.id+"_div").hide();
    })

function saveMaterial()
{
    Swal.fire({
      title: 'Are you sure?',
      text: "",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle"); 
        $('#frm_acquisition').submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Your data is safe :)',
          'error'
        )
      }
    })
}
</script>
<script src="{{ asset('js/custom.js') }}"></script>
@endsection