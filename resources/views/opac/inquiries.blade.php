@extends('opac.layout.master')
@section('css')
<style>
	.container-msg-reply {
	border: 2px solid #dedede;
	background-color: #f1f1f1;
	border-radius: 5px;
	padding: 20px;
	margin: 10px 0;
	}

	.darker {
	border-color: rgb(0, 107, 164);
	background-color: rgb(1, 152, 212);
	color: #FFF;
	}

	.time-right {
	float: right;
	color: #FFF;
	}

	/* Style time text */
	.time-left {
	float: left;
	color: #999;
	}
</style>
@endsection
@section('content')

		<div class="container margin_60">
			<div class="main_title">
				<h1>Inquiries</h1>
				{{-- <p>Usu habeo equidem sanctus no. Suas summo id sed, erat erant oporteat cu pri. In eum omnes molestie. Sed ad debet scaevola, ne mel.</p> --}}
			</div>
			<div class="row">
				<div class="col-9">
				@if($flag)
				@foreach($list AS $lists)
								<div class="strip_list wow fadeIn">
									<figure>
											{!! getFrontpage($lists->catalog_id) !!}
									</figure>
									<h3>{!! $lists->message !!}</h3>
									<h6>{!! getTitle($lists->catalog_id) !!}</h6>
									<div class="row">
										<div class="col-6">
											<p align='left'>
												<i class="icon_comment_alt"></i> {{ $lists->total_msg }}&nbsp&nbsp&nbsp
												{!! getReplies($lists->new_reply) !!}
											</p>
										</div>
										<div class="col-6">
											<p align='right'><button class="btn_1" onclick="showThread({{ $lists->id }},{{ Auth::user()->id }})">Show</button></p>
										</div>
									</div>
								</div>
				@endforeach
				@else
				<div class="strip_list wow fadeIn">
					<div class="row">
						<div class="col-12">
							<p align='center'>
								No inquiries..
							</p>
						</div>
					</div>
				</div>
				@endif
				</div>
				@include('opac.side')
			</div>
			{{ $list->links('vendor.pagination.default') }}
				<!-- /col -->
		</div>

@endsection

@section('js')
<script type="text/javascript">


function showThread(id,userid)
{
	$("#catalog_id_inquiry_reply").val(id);
	$.getJSON( "{{ url('inquiry/json/') }}/"+id, function( datajson ) {

	}).done(function(datajson){
		$("#inquiry_message").text('');
		jQuery.each(datajson,function(i,obj){
			
			if(userid == obj.replied_by){
					$("#inquiry_message").append('<div class="container-msg-reply darker"><p>'+obj.replied_msg+'</p><span class="time-right"><small>'+obj.thread_date+'</small></span></div>');
				}
				else{
					$("#inquiry_message").append('<div class="container-msg-reply"><p>'+obj.replied_msg+'</p><span class="time-left"><small>'+obj.replied_by_name+" | "+obj.thread_date+'</small></span></div>');
				}
        });
	}).fail(function(datajson){
		
	});


	$.getJSON( "{{ url('inquiry/seen/') }}/"+id, function( datajson ) {

	}).done(function(datajson){
		//SEEN
	}).fail(function(datajson){
		
	});

	$("#inquireReplyModal").modal('toggle');
}
</script>

@endsection