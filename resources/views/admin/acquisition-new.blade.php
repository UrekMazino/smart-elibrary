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
                <div class="card-header">
                    {{-- <button class="btn p-2 btn-warning" onclick="uploadMARCFile()">
                        <i class="icon-lg pb-3px text-white" data-feather="upload"> </i> UPLOAD MARC FILE &nbsp&nbsp
                      </button> --}}
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0">NEW MATERIAL</h6>

                        <button type="submit" class="btn p-2 btn-primary" onclick="saveMaterial()">
                            <i class="icon-lg pb-3px text-white" data-feather="save"> </i> SAVE &nbsp&nbsp
                          </button>
                      </div>

                    <form method="post" action="{{ url('/admin/acquisition/create') }}" id="frm_acquisition" enctype="multipart/form-data">
                    @csrf
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
                        {{-- <li class="nav-item">
                            <a class="nav-link" id="copies-tab" data-bs-toggle="tab" href="#copies" role="tab" aria-controls="copies" aria-selected="false">Copies</a>
                          </li> --}}

                        <li class="nav-item">
                            <a class="nav-link" id="files-tab" data-bs-toggle="tab" href="#files" role="tab" aria-controls="files" aria-selected="false">Files</a>
                        </li>
                      </ul>
                      <div class="tab-content border border-top-0 p-3" id="myTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="material_type" class="form-label"><span class="badge bg-danger">006</span> Material Type <span class="text-danger"><b>*</b></span></label> --}}
                                        <label for="material_type" class="form-label">Material Type <span class="text-danger"><b>*</b></span></label>
                                        <select class="form-control" name="material_id" id="materialTypeSelect" required>
                                            <option value="" selected disabled>--- Select ---</option>
                                            @foreach(getMaterials() AS $list)
                                                 <option value="{{ $list->id }}">{{ $list->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12 invisible" id="issn-div">
                                    <div class="mb-3">
                                        {{-- <label for="issn" class="form-label"><span class="badge bg-primary">020</span>  ISSN</label> --}}
                                        <label for="issn" class="form-label">ISSN</label>
                                        <input type="text" class="form-control" name="issn" placeholder="Enter text here...">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 invisible" id="isbn-div">
                                    <div class="mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" placeholder="Enter text here...">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="classifications_system" class="form-label">Classification System</label>
                                        <select class="form-control" name="classifications_system">
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
                                        {{-- <label for="classification_number" class="form-label"><span class="badge bg-primary">050a</span>  Classification Number</label> --}}
                                        <label for="classification_number" class="form-label">Classification Number</label>
                                        <input type="text" class="form-control" name="classification_number" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="author_num" class="form-label"><span class="badge bg-primary">050b</span> Author Number</label> --}}
                                        <label for="author_num" class="form-label">Author Number</label>
                                        <input type="text" class="form-control" name="author_number" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="copyright_date" class="form-label"><span class="badge bg-primary">050c</span> Publication Date/Copyright Date</label> --}}
                                        <label for="copyright_date" class="form-label">Publication Date/Copyright Date</label>
                                        <input data-provide="datepicker" name="copyright_date" id="copyright_date" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        {{-- <label for="title" class="form-label"><span class="badge bg-danger">245a</span> Title <span class="text-danger"><b>*</b></span></label> --}}
                                        <label for="title" class="form-label">Title <span class="text-danger"><b>*</b></span></label>
                                        <textarea class="form-control" name="title_statement" placeholder="Enter text here..." required></textarea>
                                    </div>
                                </div>
                            
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="title_remainder" class="form-label"><span class="badge bg-primary">245b</span> Title Remainder</label> --}}
                                        <label for="title_remainder" class="form-label">Title Remainder</label>
                                        <input type="text" class="form-control" name="title_remainder" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="statement_responsibility" class="form-label"><span class="badge bg-primary">245c</span> Statement of Responsibility</label> --}}
                                        <label for="statement_responsibility" class="form-label">Statement of Responsibility</label>
                                        <input type="text" class="form-control" name="statement_responsibility" placeholder="Enter text here...">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="title" class="form-label"><span class="badge bg-danger"></span> URL</span></label>
                                    <textarea class="form-control" name="pub_url" placeholder="URL link..." required></textarea>
                                </div>
                            </div>
                            
                        <br>
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    {{-- <label for="edition_statement" class="form-label"><span class="badge bg-primary">250a</span> Edition Statement</label> --}}
                                    <label for="edition_statement" class="form-label">Edition Statement</label>
                                    <input type="text" class="form-control" name="edition_statement" placeholder="Enter text here..">
                                </div>
                            </div>
                            
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    {{-- <label for="edition_statement_remainder" class="form-label"><span class="badge bg-primary">250b</span>  Edition Statement Remainder</label> --}}
                                    <label for="edition_statement_remainder" class="form-label">Edition Statement Remainder</label>
                                     <input type="text" class="form-control" name="edition_statement_remainder" placeholder="Enter text here...">
                                </div>
                            </div>
                        </div>

                        </div>

                        <div class="tab-pane fade" id="pub" role="tabpanel" aria-labelledby="pub-tab">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="publication_place" class="form-label"><span class="badge bg-primary">264a</span> Publcation Place</label> --}}
                                        <label for="publication_place" class="form-label">Publication Place</label>
                                        <input type="text" class="form-control" name="publication_place" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="publisher" class="form-label"><span class="badge bg-primary">264b</span> Publisher</label> --}}
                                        <label for="publisher" class="form-label">Publisher</label>
                                        <input type="text" class="form-control" name="publisher" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="publication_date" class="form-label"><span class="badge bg-primary">264c</span> Publication Date</label> --}}
                                        <label for="publication_date" class="form-label">Publication Date</label>
                                        <input type="text" class="form-control" name="publication_date" id="publication_date" placeholder="Enter text here..." value="">
                                    </div>
                                </div>
                            </div>

                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="extent" class="form-label"><span class="badge bg-primary">300a</span> Extent</label> --}}
                                            <label for="extent" class="form-label">Extent</label>
                                            <input type="text" class="form-control" name="extent" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="other_physical" class="form-label"><span class="badge bg-primary">300b</span> Other Physical Detail</label> --}}
                                            <label for="other_physical" class="form-label">Other Physical Detail</label>
                                            <input type="text" class="form-control" name="other_physical" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="dimension" class="form-label"><span class="badge bg-primary">300c</span> Dimension</label> --}}
                                            <label for="dimension" class="form-label">Dimension</label>
                                            <input type="text" class="form-control" name="dimension" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="frequency" class="form-label"><span class="badge bg-primary">310</span> Frequency</label> --}}
                                            <label for="frequency" class="form-label">Frequency</label>
                                            <select class="form-control" name="frequency">
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
                                            <input type="text" class="form-control" name="volume" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="issue_number" class="form-label">Issue Number</label>
                                            <input type="text" class="form-control" name="issue_number" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="series_statement_id" class="form-label"><span class="badge bg-primary">490</span> Series Statement</label> --}}
                                            <label for="series_statement_id" class="form-label">Series Statement</label>
                                            <select class="slct2 form-select" data-width="100%" name="series_statement_id">
                                                <option value="" selected disabled>--- Select ---</option>
                                                @foreach(getSeries() AS $list)
                                                    <option value="{{ $list->id }}">{{ $list->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="bibliography" class="form-label"><span class="badge bg-primary">504</span> Bibliography</label> --}}
                                            <label for="bibliography" class="form-label">Bibliography</label>
                                            <input type="text" class="form-control" name="bibliography" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    {{-- <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="accession_number" class="form-label"><span class="badge bg-danger">876a</span> Accession Number <span class="text-danger"><b>*</b></span></label>
                                            <label for="accession_number" class="form-label"><span class="badge bg-danger">876a</span> Accession Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="accession_number" placeholder="Enter text here..." required>
                                        </div>
                                    </div> --}}
                                </div>

                                <br>
                                <div class="row">
                                    {{-- <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cir_num" class="form-label"><span class="badge bg-danger">876b</span> Circulation Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="circulation_number" placeholder="Enter text here..." required>
                                        </div>
                                    </div> --}}
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="cost_price" class="form-label"><span class="badge bg-danger">876c</span> Cost/Price <span class="text-danger"><b>*</b></span></label> --}}
                                            <label for="cost_price" class="form-label">Cost/Price <span class="text-danger"><b>*</b></span></label>
                                            <input type="number" class="form-control" name="cost_price" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="date_acquired" class="form-label"><span class="badge bg-primary">876d</span> Date Acquired</label> --}}
                                            <label for="date_acquired" class="form-label">Date Acquired</label>
                                            <input data-provide="datepicker" name="date_acquired" id="date_acquired" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="frequency" class="form-label"><span class="badge bg-danger">876e</span> Acquisition Modes <span class="text-danger"><b>*</b></span></label> --}}
                                            <label for="frequency" class="form-label">Acquisition Modes <span class="text-danger"><b>*</b></span></label>
                                            <select class="form-control" name="acquisition_modes" required>
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="Subscription">Subscription</option>
                                                <option value="Purchase">Purchase</option>
                                                <option value="Complimentary">Complimentary</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="user_restriction" class="form-label"><span class="badge bg-primary">876h</span> User Restriction</label> --}}
                                            <label for="user_restriction" class="form-label">User Restriction</label>
                                            <input type="text" class="form-control" name="user_restriction" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    {{-- <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="temporary_location" class="form-label"><span class="badge bg-primary">876l</span> Temporary Location</label>
                                            <input type="text" class="form-control" name="temporary_location" placeholder="Enter text here...">
                                        </div>
                                    </div> --}}
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="copy_number" class="form-label"><span class="badge bg-danger">876t</span> Copy Number <span class="text-danger"><b>*</b></span></label> --}}
                                            <label for="copy_number" class="form-label">Copy Number <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="copy_number" placeholder="Enter text here..." required>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="nonpublic_note" class="form-label"><span class="badge bg-primary">876x</span> Nonpublic note</label> --}}
                                            <label for="nonpublic_note" class="form-label">Nonpublic note</label>
                                            <input type="text" class="form-control" name="nonpublic_note" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                {{-- <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="no_of_copy" class="form-label"><span class="badge bg-primary">Inventory</span> No. of Copies</label>
                                            <input type="number" class="form-control" name="no_of_copy" placeholder="">
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            {{-- <label for="no_of_copy" class="form-label"><span class="badge bg-primary">520$a</span> Summary</label> --}}
                                            <label for="summary" class="form-label">Summary</label>
                                            <textarea class="ckeditor form-control" name="summary" id="summary"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            {{-- <label for="no_of_copy" class="form-label"><span class="badge bg-primary">500$a</span> General Note</label> --}}
                                            <label for="no_of_copy" class="form-label">General Note</label>
                                            <textarea class="ckeditor form-control" name="general_note"></textarea>
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

                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="author_type" class="form-label"><span class="badge bg-danger">100</span> Type <span class="text-danger"><b>*</b></span></label> --}}
                                            <label for="author_type" class="form-label">Type <span class="text-danger"><b>*</b></span></label>
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
                                            {{-- <label for="author_name" class="form-label"><span class="badge bg-danger">100a</span> Personal Name <span class="text-danger"><b>*</b></span></label> --}}
                                            <label for="author_name" class="form-label">Personal Name <span class="text-danger"><b>*</b></span></label>
                                            <input type="text" class="form-control" name="author_name" placeholder="Enter text here..." required>
                                        </div>
                                    </div>
                                </div>
                            
                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="numeration" class="form-label"><span class="badge bg-primary">100b</span> Numeration</label> --}}
                                            <label for="numeration" class="form-label">Numeration</label>
                                            <input type="text" class="form-control" name="numeration" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="title_and_words" class="form-label"><span class="badge bg-primary">100c</span> Tiles and words associated with a name</label> --}}
                                            <label for="title_and_words" class="form-label">Tiles and words associated with a name</label>
                                            <input type="text" class="form-control" name="title_and_words" placeholder="Enter text here..." >
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="relator_term" class="form-label"><span class="badge bg-primary">100e</span> Relator Term</label> --}}
                                            <label for="relator_term" class="form-label">Term</label>
                                            <input type="text" class="form-control" name="relator_term" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>
                            <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="date_associated" class="form-label"><span class="badge bg-primary">100d</span> Date associated with a name</label> --}}
                                            <label for="date_associated" class="form-label">Date associated with a name</label>
                                            <input data-provide="datepicker" name="date_associated" id="date_associated" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="fuller_form" class="form-label"><span class="badge bg-primary">100q</span> Fuller form of name</label> --}}
                                            <label for="fuller_form" class="form-label"> Fuller form of name</label>
                                            <input type="text" class="form-control" name="fuller_form" placeholder="Enter text here..." >
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            {{-- <label for="affiliation" class="form-label"><span class="badge bg-primary">100u</span> Affiliation</label> --}}
                                            <label for="affiliation" class="form-label">Affiliation</label>
                                            <input type="text" class="form-control" name="affiliation" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="add_author_check" name="add_author_check" value="1">
                                                <label class="form-check-label" for="add_author_check">
                                                    <h5>Added Entry (Personal Author)</h5>
                                                </label>
                                    </div>
                                <br>
                                <div id="add_author_check_div">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="author_type2" class="form-label"><span class="badge bg-primary">700</span> Type</label> --}}
                                                <label for="author_type2" class="form-label">Type</label>
                                                <select class="form-control" name="author_type2">
                                                    <option value="" selected disabled>--- Select ---</option>
                                                    <option value="700">Forename</option>
                                                    <option value="701">Surname</option>
                                                    <option value="703">Family Name</option>
                                                </select>
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="author_name2" class="form-label"><span class="badge bg-primary">700a</span> Personal Name</label> --}}
                                                <label for="author_name2" class="form-label">Personal Name</label>
                                                <input type="text" class="form-control" name="author_name2" placeholder="Enter text here...">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="numeration2" class="form-label"><span class="badge bg-primary">700b</span> Numeration</label> --}}
                                                <label for="numeration2" class="form-label">Numeration</label>
                                                <input type="text" class="form-control" name="numeration2" placeholder="Enter text here...">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="title_and_words2" class="form-label"><span class="badge bg-primary">700c</span> Tiles and words associated with a name</label> --}}
                                                <label for="title_and_words2" class="form-label">Tiles and words associated with a name</label>
                                                <input type="text" class="form-control" name="title_and_words2" placeholder="Enter text here..." >
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="relator_term2" class="form-label"><span class="badge bg-primary">700e</span> Relator Term</label> --}}
                                                <label for="relator_term2" class="form-label">Relator Term</label>
                                                <input type="text" class="form-control" name="relator_term2" placeholder="Enter text here...">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="date_associated2" class="form-label"><span class="badge bg-primary">700d</span> Date associated with a name</label> --}}
                                                <label for="date_associated2" class="form-label">Date associated with a name</label>
                                                <input data-provide="datepicker" name="date_associated2" id="date_associated2" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="fuller_form2" class="form-label"><span class="badge bg-primary">700q</span> Fuller form of name</label> --}}
                                                <label for="fuller_form2" class="form-label">Fuller form of name</label>
                                                <input type="text" class="form-control" name="fuller_form2" placeholder="Enter text here..." >
                                            </div>
                                        </div>
                                    
                                        <div class="col-lg-4 col-md-4 col-sm-12">
                                            <div class="mb-3">
                                                {{-- <label for="affiliation2" class="form-label"><span class="badge bg-primary">700u</span> Affiliation</label> --}}
                                                <label for="affiliation2" class="form-label">Affiliation</label>
                                                <input type="text" class="form-control" name="affiliation2" placeholder="Enter text here...">
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="corpo_author" role="tabpanel" aria-labelledby="corpo_author-tab">
                            <br>
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="cauthor_type" class="form-label"><span class="badge bg-primary">110</span> Type</label> --}}
                                        <label for="cauthor_type" class="form-label">Type</label>
                                        <select class="form-control" name="cauthor_type">
                                            <option value="" selected disabled>--- Select ---</option>
                                            <option value="1100">Invented name</option>
                                            <option value="1101">Jurisdiction name</option>
                                            <option value="1102">Name in direct order</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="cauthor_name" class="form-label"><span class="badge bg-primary">110a</span> <small>Corporate name or jurisdiction name as entry element</small></label> --}}
                                        <label for="cauthor_name" class="form-label"><small>Corporate name or jurisdiction name as entry element</small></label>
                                        <input type="text" class="form-control" name="cauthor_name" placeholder="Enter text here..." >
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="subordinate_unit" class="form-label"><span class="badge bg-primary">110b</span> Subordinate Unit</label> --}}
                                        <label for="subordinate_unit" class="form-label">Subordinate Unit</label>
                                        <input type="text" class="form-control" name="subordinate_unit" placeholder="Enter text here...">
                                    </div>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        {{-- <label for="location_meeting" class="form-label"><span class="badge bg-primary">110c</span> Location Meeting</label> --}}
                                        <label for="location_meeting" class="form-label">Location Meeting</label>
                                        <input type="text" class="form-control" name="location_meeting" placeholder="Enter text here...">
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="date_of_meeting" class="form-label"><span class="badge bg-primary">110d</span> Date of meeting or treaty signing</label>
                                        <input type="text" class="form-control" name="date_of_meeting" placeholder="Enter text here..." >
                                    </div>
                                </div>
                            
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="number_of_part" class="form-label"><span class="badge bg-primary">110n</span> Number of part/session/meeting</label>
                                        <input type="text" class="form-control" name="number_of_part" placeholder="Enter text here...">
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
                            <div id="add_corpo_check_div">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cauthor_type2" class="form-label"><span class="badge bg-primary">710</span> Type</label>
                                            <select class="form-control" name="cauthor_type2">
                                                <option value="" selected disabled>--- Select ---</option>
                                                <option value="Invented name">Invented name</option>
                                                <option value="Jurisdiction name">Jurisdiction name</option>
                                                <option value="Family Name">Name in direct order</option>
                                            </select>
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="cauthor_name2" class="form-label"><span class="badge bg-primary">710a</span> <small>Corporate name or jurisdiction name as entry element</small></label>
                                            <input type="text" class="form-control" name="cauthor_name2" placeholder="Enter text here..." >
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="subordinate_unit2" class="form-label"><span class="badge bg-primary">710b</span> Subordinate Unit</label>
                                            <input type="text" class="form-control" name="subordinate_unit2" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="location_meeting2" class="form-label"><span class="badge bg-primary">710c</span> Location Meeting</label>
                                            <input type="text" class="form-control" name="location_meeting2" placeholder="Enter text here...">
                                        </div>
                                    </div>
                                
                                    <div class="col-lg-4 col-md-4 col-sm-12">
                                        <div class="mb-3">
                                            <label for="date_of_meeting2" class="form-label"><span class="badge bg-primary">710d</span> Date of meeting or treaty signing</label>
                                            <input type="text" class="form-control" name="date_of_meeting2" placeholder="Enter text here..." >
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
                                    <th>Status</th>
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
                                        <label for="formCover" class="form-label">Cover Photo</label>
                                        <input class="form-control" type="file" id="formCover" name="formCover">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12">
                                    <div class="mb-3">
                                        <label for="formPDF" class="form-label">PDF</label>
                                        <input class="form-control" type="file" id="formPDF" name="formPDF" accept="application/pdf">
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
{{-- Modal for upload MARC File - Anna --}}
<div class="modal fade" id="uploadMarcFile" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="title" id="smallModalLabel">Upload</h4>
        </div>
        <form method="post" action="{{ url('/marcfile/upload') }}" id="frm_marc_file_upload" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          
          <label for="formPDF" class="form-label">MARC File</label>
          <input class="form-control" type="file" id="marc_file_for_upload" name="marc_file_for_upload">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-info">Submit</button>
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">CLOSE</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  
@endsection

@section('js')
<script src="//cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
       $('.ckeditor').ckeditor;

       $("#materialTypeSelect").change(function() {
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
    let getStatus = @json(getStatus());
</script>
<script type="text/javascript">
    $(".slct2").select2();

    $("#add_author_check_div,#add_corpo_check_div").hide();
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
        appBusy.show(); 
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

function uploadMARCFile()
{
    alert();
    $("#uploadMarcFile").modal("toggle");
}

</script>
<script src="{{ asset('js/custom.js') }}"></script>
@endsection