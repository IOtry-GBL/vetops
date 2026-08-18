@extends('layouts.app')

@section('title', 'Appointment Report')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Appointment Report</h1>
                <p class="hero-copy">
                    Review appointments by date and status, then export the filtered list when you need an offline copy.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('staff.reports') }}" class="btn btn-secondary">Back To Reports</a>
                <a href="{{ route('staff.reports.appointments.export', request()->only('from_date', 'to_date', 'status')) }}" class="btn btn-primary">Export CSV</a>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Filters</h2>
                <p class="section-copy">Adjust the date window or status to narrow the appointment list.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('staff.reports.appointments') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="from_date">From</label>
                <input id="from_date" name="from_date" type="date" class="field-control" value="{{ $fromDate }}">
            </div>

            <div class="field">
                <label class="field-label" for="to_date">To</label>
                <input id="to_date" name="to_date" type="date" class="field-control" value="{{ $toDate }}">
            </div>

            <div class="field">
                <label class="field-label" for="status">Status</label>
                <select id="status" name="status" class="field-control">
                    <option value="">All statuses</option>
                    @foreach (['Pending', 'Approved', 'Completed', 'Cancelled', 'Missed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="action-row" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('staff.reports.appointments') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="metric-grid">
            <div class="metric-card">
                <span class="metric-label">Total</span>
                <strong class="metric-value">{{ $stats['total'] }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Approved</span>
                <strong class="metric-value">{{ $stats['approved'] }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Completed</span>
                <strong class="metric-value">{{ $stats['completed'] }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Cancelled</span>
                <strong class="metric-value">{{ $stats['cancelled'] }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Missed</span>
                <strong class="metric-value">{{ $stats['missed'] }}</strong>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Appointments</h2>
                <p class="section-copy">Live appointment data for the current report window.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Owner</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Veterinarian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'Unknown date' }}</td>
                            <td>{{ $appointment->pet?->name ?? 'Unknown Pet' }}</td>
                            <td>{{ $appointment->pet?->owner?->name ?? 'Unknown Owner' }}</td>
                            <td>{{ $appointment->consultation_mode ?? 'N/A' }}</td>
                            <td>{{ $appointment->status }}</td>
                            <td>{{ $appointment->consultation?->veterinarian?->name ? 'Dr. '.$appointment->consultation->veterinarian->name : 'Not assigned' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted-copy" style="text-align: center;">No appointments matched the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($appointments->hasPages())
        <section class="card">
            {{ $appointments->withQueryString()->links() }}
        </section>
    @endif
@endsection
