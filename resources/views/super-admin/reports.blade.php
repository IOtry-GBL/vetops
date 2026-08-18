@extends('layouts.app')

@section('sidebar')
    @include('super-admin.sidebar')
@endsection

@section('content')
    <div class="card">
        <h1 style="margin: 0;">Clinic Operation Reports</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Generate a date-bounded snapshot of clinic operations and export it for review.
        </p>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('super-admin.reports') }}" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div>
                <label class="form-label" for="from">From</label>
                <input class="form-control" id="from" name="from" type="date" value="{{ $period['from'] }}">
            </div>
            <div>
                <label class="form-label" for="to">To</label>
                <input class="form-control" id="to" name="to" type="date" value="{{ $period['to'] }}">
            </div>
            <button type="submit" class="btn btn-primary">Refresh Report</button>
            <a class="btn btn-secondary" href="{{ route('super-admin.reports.export', ['from' => $period['from'], 'to' => $period['to']]) }}">Export CSV</a>
        </form>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
        @foreach ($summary as $label => $value)
            <div class="widget">
                <div class="widget-title">{{ $label }}</div>
                <div class="widget-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        <div class="card">
            <h2 style="margin-top: 0;">Recent Appointments</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($recentAppointments as $appointment)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $appointment->pet?->pet_name ?? 'Unknown pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $appointment->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No appointments in this period.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Recent Consultations</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($recentConsultations as $consultation)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $consultation->appointment?->pet?->pet_name ?? 'Unknown pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $consultation->consultation_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $consultation->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No consultations in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
