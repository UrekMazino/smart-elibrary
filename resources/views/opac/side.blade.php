<div class="col-3">
<div class="widget">
                    <form method="get" action="{{ url('/results') }}" id="frm">
                        @csrf
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="series_statement_id" value="0">
                        <input type="hidden" name="layout" value="grid">
                        <input type="hidden" value="all-material" id="checkAll" name="seachtype[]">
							<div class="form-group">
								<input type="text" name="query" id="query" class="form-control" placeholder="Search...">
							</div>
							<button type="submit" id="submit" class="btn_1"> Search</button>
						</form>
					</div>
					<!-- /widget -->

					<div class="widget">
						<div class="widget-title">
							<h4>Recent News</h4>
						</div>
						<ul class="comments-list">
						@foreach(getLatestNews() AS $news)
						<li>
							<div class="alignleft">
								@if($news->news_cover)
									<a href="#0"><img src="{{ url('/'.$news->news_cover) }}" alt=""></a>
								@else
									<a href="#0"><img src="http://via.placeholder.com/160x160.jpg" alt=""></a>
								@endif
							</div>
							<small>{{ date('Y-m-d',strtotime($news->created_at)) }}</small>
							<h3><a href="#" title="" style="font-size:14px">{{ $news->news_title }}</a></h3>
						</li>
						@endforeach
						</ul>
					</div>
</div>