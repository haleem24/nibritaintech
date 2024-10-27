<!DOCTYPE html>
<html lang="en">
	<head>

		<meta charset="UTF-8">
		<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="Description" content="Nowa – Laravel Bootstrap 5 Admin & Dashboard Template">
		<meta name="Author" content="Spruko Technologies Private Limited">
        <meta name="csrf-token" content="{{ csrf_token() }}">

		{{-- <meta name="Keywords" content="admin dashboard, admin dashboard laravel, admin panel template, blade template, blade template laravel, bootstrap template, dashboard laravel, laravel admin, laravel admin dashboard, laravel admin panel, laravel admin template, laravel bootstrap admin template, laravel bootstrap template, laravel template"/> --}}

		<!-- Title -->
        @php
            $session_url = session('url');
        @endphp
		<title> {{ $title_page ?? null }} Britain Tech – Admin Panel </title>


        @include('layouts.components.styles')
        <style>

            .form-floating>.form-control,
            .form-floating>.form-control-plaintext {
            padding: 0rem 0.75rem;
            }

            .form-floating>.form-control,
            .form-floating>.form-control-plaintext,
            .form-floating>.form-select {
            height: calc(2.5rem + 2px);
            line-height: 1;
            }

            .form-floating>label {
            padding: 0.5rem 0.75rem;
            }
        </style>
        @livewireStyles
        <script src="//unpkg.com/alpinejs" defer></script>

        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
	</head>

	<body class="ltr main-body app sidebar-mini">

		<!-- Loader -->
		<div id="global-loader">
			<img src="{{asset('assets/img/loader.svg')}}" class="loader-img" alt="Loader">
		</div>
		<!-- /Loader -->

		<!-- Page -->
		<div class="page">
			<div>

                @include('layouts.components.app-header')

                @include('layouts.components.app-sidebar')

			</div>

			<!-- main-content -->
			<div class="main-content app-content">

				<!-- container -->
				<div class="main-container container-fluid">

                    @yield('content')

				</div>
				<!-- Container closed -->
			</div>
			<!-- main-content closed -->

            {{-- @include('layouts.components.sidebar-right') --}}

            {{-- @include('layouts.components.modal') --}}

            @yield('modal')

            @include('layouts.components.footer')

		</div>
		<!-- End Page -->

        @include('layouts.components.scripts')

        @livewireScripts
    </body>
</html>
