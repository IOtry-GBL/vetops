@extends('layouts.app')

@section('title', 'Register New Client')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')

<section class="hero-card">
    <div class="hero-row">
        <div>
            <h1 class="hero-title">Register New Client</h1>
            <p class="hero-copy">Add a new pet owner to the system.</p>
        </div>
    </div>
</section>

<section class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="surface-header">
        <h2>Client Registration</h2>
    </div>

    @if($errors->any())
        <div style="padding: 1rem; background: #fee2e2; border-radius: 0.375rem; margin-bottom: 1rem; border-left: 4px solid #ef4444;">
            <p style="color: #991b1b; margin: 0; font-weight: 600;">Please fix the errors below:</p>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.store-client') }}" method="POST">
        @csrf

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Full Name *</label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Contact Number</label>
            <input type="text" name="contact_no" value="{{ old('contact_no') }}"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password *</label>
            <input type="password" name="password" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
            <small style="color: #6b7280;">Minimum 6 characters</small>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Confirm Password *</label>
            <input type="password" name="password_confirmation" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Register Client</button>
            <a href="{{ route('staff.clients') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

@endsection
