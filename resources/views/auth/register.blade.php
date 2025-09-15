@extends('opac.layout.master')

@section('content')
        <div class="bg_color_2">
            <div class="container margin_60_35">
                <div id="login">
                    <center><h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="200"class="img-fluid"></h3></center>
                    <div class="box_form">
                        <x-auth-validation-errors class="mb-4 text-danger" :errors="$errors"/>
                        <form method="POST" action="{{ route('register') }}">
                        @csrf
                            <div class="form-group">
                                <input type="text" class="form-control" name="name" placeholder="Fullname" :value="old('name')" required autofocus >
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" placeholder="Email Address" :value="old('email')" required >
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="contactnum" placeholder="Contact number" :value="old('contactnum')">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Your password" name="password" id="password">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Confirm password" name="password_confirmation" id="password">
                            </div>

                            <div class="form-group text-center add_top_20">
                                <input class="btn_1 medium" type="submit" value="Register">
                            </div>
                        </form>
                    </div>
                    
                </div>
                <!-- /login -->
            </div>
        </div>
@endsection

@section('js')

@endsection