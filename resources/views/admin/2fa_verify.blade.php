@extends('layouts.custom-app')

    @section('styles')

    @endsection

    @section('class')

	    <div class="bg-primary">

    @endsection

    @section('content')

    <div class="page-single">
        <div class="container">
            <div class="row">
                <div class="col-xl-5 col-lg-6 col-md-8 col-sm-8 col-xs-10 card-sigin-main mx-auto my-auto py-45 justify-content-center">
                    <div class="card-sigin mt-5 mt-md-0">
                        <!-- Demo content-->
                        <div class="main-card-signin d-md-flex">
                            <div class="wd-100p"><div class="d-flex mb-4"><img src="{{asset('assets/img/brand').'/'.env('APP_ICON')}}" class="sign-favicon ht-40" alt="logo"></div>
                                <div class="">
                                    <div class="main-signup-header">
                                        {{-- <h2>Welcome back!</h2>
                                        <h6 class="font-weight-semibold mb-4">Please sign in to continue.</h6> --}}
                                        <h2>Two-Factor Authentication</h2>
                                        <p>Please enter the code from your authenticator app to verify your login.</p>

                                        <div class="panel panel-primary">
                                        <div class="panel-body tabs-menu-body border-0 p-3">
                                            <div class="tab-content">
                                                <div class="tab-pane active" id="tab5">
                                                    <form method="POST" action="{{ route('admin.2fa') }}">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="otp" class="form-label">Authentication Code</label>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                id="otp"
                                                                name="otp"
                                                                placeholder="Enter code"
                                                                required>
                                                        </div>
                                                        @if ($errors->has('otp'))
                                                            <div class="text-danger">
                                                                {{ $errors->first('otp') }}
                                                            </div>
                                                        @endif
                                                        <button type="submit" class="btn btn-primary">Verify</button>

                                                        <a class="dropdown-item" href="{{url('logout')}}"><i class="far fa-arrow-alt-circle-left"></i> {{ __('locale.Sign Out') }}</a>
                                                    </form>
                                                    {{-- <form action="{{url('/login')}}" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label>Username</label> <input class="form-control" placeholder="Enter your username" name="username" type="text">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Password</label> <input class="form-control" placeholder="Enter your password" name="password" type="password">
                                                        </div>
                                                        <div class="mt-3">
                                                            {!! NoCaptcha::display() !!}
                                                        </div>
                                                        @if ($errors->has('g-recaptcha-response'))
                                                            <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
                                                        @endif
                                                        @if(isset($error))
                                                            <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                                                                <span class="alert-inner--icon"><i class="fe fe-slash"></i></span>
                                                                <span class="alert-inner--text"><strong>{{$error}}</strong></span>
                                                                <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                        @endif
                                                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                                                        </form>
                                                        <script>
                                                            grecaptcha.ready(function () {
                                                                grecaptcha.execute('{{ env('NOCAPTCHA_SITEKEY') }}', { action: 'login' }).then(function (token) {
                                                                    document.getElementById('g-recaptcha-response').value = token;
                                                                });
                                                            });
                                                        </script> --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endsection

    @section('scripts')

		<!-- generate-otp js -->
		<script src="{{asset('assets/js/generate-otp.js')}}"></script>

    @endsection
