@extends('opac.layout.master')

@section('content')
        <div class="bg_color_2">
            <div class="container margin_60_35">
                <div id="login">
                    <center><h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="200"class="img-fluid"></h3></center>
                    <div class="box_form">
                        <form method="POST" action="{{ route('login') }}">
                        @csrf
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" placeholder="Your email address">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Your password" name="password" id="password">
                            </div>
                            <a href="#0"><small>Forgot password?</small></a>
                            <div class="form-group text-center add_top_20">
                                <input class="btn_1 medium" type="submit" value="Login">
                            </div>
                        </form>

                        <p class="text-center">Do not have an account yet? <a href="{{ url('register') }}"><strong>Register now!</strong></a></p>
                    </div>
                    
                </div>
                <!-- /login -->
            </div>
        </div>
@endsection

@section('js')

@endsection