@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="d-flex align-items-center justify-content-center px-3" style="min-height: 100vh; background: radial-gradient(circle at 15% 12%, rgba(30,58,95,.06), transparent 45%), radial-gradient(circle at 85% 88%, rgba(5,150,105,.07), transparent 45%), var(--bg);">
    <div class="w-100" style="max-width: 400px;">
        <div class="card border-0" style="border-radius: 1.1rem; box-shadow: var(--shadow-lg); overflow: hidden;">
            <div style="height: 5px; background: linear-gradient(90deg, var(--brand), var(--accent));"></div>
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 84px; height: 84px; border-radius: 50%; background: var(--surface-alt); box-shadow: inset 0 0 0 1px var(--border);">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="width: 60px; height: 60px; object-fit: contain;">
                    </div>
                    <h4 class="mb-1 fw-bold">{{ config('app.name') }}</h4>
                    <p class="text-muted small mb-0">Sign in to the payroll module</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-circle mt-1"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Login ID</label>
                        <input type="text" name="login_id" class="form-control" value="{{ old('login_id') }}" required autofocus autocapitalize="none" placeholder="e.g. HR, Finance, Superadmin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Sign in</button>
                </form>
            </div>
        </div>
        <p class="text-center text-muted mt-4 mb-0" style="font-size: .78rem;">Central Education Department &middot; Payroll Management System</p>
    </div>
</div>
@endsection
