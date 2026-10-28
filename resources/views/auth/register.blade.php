@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')


@section('content')
        <div class="bg_color_2">
            <div class="container margin_60_35">
                <div id="login">
                    <center><h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="200"class="img-fluid"></h3></center>
                    <div class="box_form">
                        <x-auth-validation-errors class="mb-4 text-danger" :errors="$errors"/>
                        @include('auth.partials.google-button', ['hint' => 'No password needed: Google confirms your email address.', 'or' => 'or sign up with your email'])
                        <form method="POST" action="{{ route('register') }}">
                        @csrf
                            <div class="form-group">
                                <input type="text" class="form-control" name="name" placeholder="Fullname" value="{{ old('name') }}" autocomplete="name" required autofocus >
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" placeholder="Email Address" value="{{ old('email') }}" autocomplete="email" required >
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="contactnum" placeholder="Contact number" value="{{ old('contactnum') }}" autocomplete="tel">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Your password" name="password" id="password" minlength="12" maxlength="128" autocomplete="new-password" aria-describedby="password-hint" required>
                                <small id="password-hint" class="form-text text-muted">At least 12 characters. A few unrelated words make a strong password that is easy to remember.</small>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Confirm password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" required>
                            </div>
                            {{-- The "I'm human" check (App\Rules\HumanCheck): Cloudflare's widget puts its token in a hidden
                                 cf-turnstile-response field. Most visitors pass without doing anything; some tick a box. --}}
                            @if (\App\Rules\HumanCheck::enabled())
                                <div class="form-group human-check">
                                    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-action="register"
                                         data-theme="light" data-size="flexible" data-language="en"></div>
                                </div>
                            @endif

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
@if (\App\Rules\HumanCheck::enabled())
<script src="{{ \App\Rules\HumanCheck::ORIGIN }}/turnstile/v0/api.js" nonce="{{ cspNonce() }}" async defer></script>
@endif
@endsection