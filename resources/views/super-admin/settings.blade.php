@extends('layouts.app')

@section('sidebar')
    @include('super-admin.sidebar')
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-info">{{ session('success') }}</div>
    @endif

    <div class="card">
        <h1 style="margin: 0;">System Settings</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Update the core clinic settings that shape the experience across the platform.
        </p>
    </div>

    <div class="card">
        <form action="{{ route('super-admin.settings.update') }}" method="POST" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label" for="clinic_name">Clinic Name</label>
                <input class="form-control" id="clinic_name" name="clinic_name" type="text" value="{{ old('clinic_name', $settings['clinic_name']) }}" required>
            </div>
            <div>
                <label class="form-label" for="clinic_email">Clinic Email</label>
                <input class="form-control" id="clinic_email" name="clinic_email" type="email" value="{{ old('clinic_email', $settings['clinic_email']) }}" required>
            </div>
            <div>
                <label class="form-label" for="timezone">Timezone</label>
                <input class="form-control" id="timezone" name="timezone" type="text" value="{{ old('timezone', $settings['timezone']) }}" required>
            </div>
            <div>
                <label class="form-label" for="appointment_lead_time_hours">Appointment Lead Time (Hours)</label>
                <input class="form-control" id="appointment_lead_time_hours" name="appointment_lead_time_hours" type="number" min="0" max="168" value="{{ old('appointment_lead_time_hours', $settings['appointment_lead_time_hours']) }}" required>
            </div>
            <div>
                <label class="form-label" for="emergency_contact">Emergency Contact</label>
                <input class="form-control" id="emergency_contact" name="emergency_contact" type="text" value="{{ old('emergency_contact', $settings['emergency_contact']) }}">
            </div>
            <div>
                <label style="display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 1.8rem;">
                    <input type="checkbox" name="online_booking_enabled" value="1" @checked(old('online_booking_enabled', $settings['online_booking_enabled']))>
                    <span>Online booking enabled</span>
                </label>
            </div>
            <div style="align-self: end;">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Current Values</h2>
        <div style="display: grid; gap: 0.75rem;">
            @foreach ($settings as $key => $value)
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                    <span>{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                    <strong>{{ is_bool($value) ? ($value ? 'Enabled' : 'Disabled') : $value }}</strong>
                </div>
            @endforeach
        </div>
    </div>
@endsection
