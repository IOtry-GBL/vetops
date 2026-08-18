@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection


@section('content')

<div class="card">
    <h1>Welcome, {{ auth()->user()->name }}!</h1>
    <p>Here's your daily clinic overview.</p>
</div>


{{-- ================= WIDGETS ================= --}}
<div class="grid">
    <div class="widget">
        <div class="widget-title">Today's Appointments</div>
        <div class="widget-value">{{ $todayAppointments }}</div>
    </div>

    <div class="widget">
        <div class="widget-title">Pending Confirmations</div>
        <div class="widget-value" style="color:#f59e0b;">{{ $pending }}</div>
    </div>

    <div class="widget">
        <div class="widget-title">Emergency Alerts</div>
        <div class="widget-value" style="color:#ef4444;">{{ $emergencies }}</div>
    </div>

    <div class="widget">
        <div class="widget-title">Total Appointments</div>
        <div class="widget-value">{{ $allAppointments }}</div>
    </div>
</div>


{{-- ================= EMERGENCY DATATABLE ================= --}}
@if($emergencyAppointments->count() > 0)
<div class="card" style="margin-top:2rem;">
    <h2 style="color:#ef4444;">Emergency Appointments</h2>

    <table id="emergencyTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Owner</th>
                <th style="text-align:left;">Phone</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Schedule</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($emergencyAppointments as $appointment)
                <tr>
                    <td>{{ $appointment->pet->name }} ({{ $appointment->pet->species }})</td>
                    <td>{{ $appointment->pet->owner->name }}</td>
                    <td>{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                    <td style="color:#ef4444; font-weight:500;">
                        {{ $appointment->reason }}
                    </td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('staff.appointments.confirm', $appointment->id) }}"
                           class="btn btn-danger"
                           style="padding:6px 10px; font-size:12px;">
                            Review
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No emergencies</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif


{{-- ================= REGULAR DATATABLE ================= --}}
@if($regularAppointments->count() > 0)
<div class="card" style="margin-top:2rem;">
    <h2 style="color:#10b981;">Regular Check-ups</h2>

    <table id="regularTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Owner</th>
                <th style="text-align:left;">Phone</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Schedule</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($regularAppointments as $appointment)
                <tr>
                    <td>{{ $appointment->pet->name }} ({{ $appointment->pet->species }})</td>
                    <td>{{ $appointment->pet->owner->name }}</td>
                    <td>{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                    <td>{{ $appointment->reason }}</td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('staff.appointments.confirm', $appointment->id) }}"
                           class="btn btn-primary"
                           style="padding:6px 10px; font-size:12px;">
                            Review
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No regular appointments</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif


{{-- ================= UPCOMING APPROVED ================= --}}
<div class="card" style="margin-top:2rem;">
    <h2 style="color:#667eea;">Upcoming Approved Appointments</h2>

    <table id="upcomingTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Owner</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:center;">Time</th>
                <th style="text-align:left;">Vet</th>
            </tr>
        </thead>

        <tbody>
            @forelse($upcomingApprovedAppointments as $appointment)
                <tr>
                    <td>{{ $appointment->pet->name }}</td>
                    <td>{{ $appointment->pet->owner->name }}</td>
                    <td>{{ $appointment->reason }}</td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y') : 'TBD' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date ? $appointment->appointment_date->format('h:i A') : '' }}
                    </td>
                    <td>{{ $appointment->veterinarian->name ?? 'Unassigned' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No upcoming appointments</td></tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- ================= MISSED ================= --}}
<div class="card" style="margin-top:2rem;">
    <h2 style="color:#ef4444;">Missed Appointments</h2>

    <table id="missedTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Owner</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:left;">Vet</th>
            </tr>
        </thead>

        <tbody>
            @forelse($missedApprovedAppointments as $appointment)
                <tr>
                    <td>{{ $appointment->pet->name }}</td>
                    <td>{{ $appointment->pet->owner->name }}</td>
                    <td>{{ $appointment->reason }}</td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}
                    </td>
                    <td>{{ $appointment->veterinarian->name ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">No missed appointments</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection


{{-- ================= STYLES ================= --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
.align-table th,
.align-table td {
    padding: 12px 10px;
    vertical-align: middle;
}

.align-table {
    width: 100%;
}

.btn {
    text-decoration: none;
    display: inline-block;
}
</style>
@endpush


{{-- ================= SCRIPTS ================= --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    $('#emergencyTable').DataTable({ pageLength: 5 });
    $('#regularTable').DataTable({ pageLength: 5 });
    $('#upcomingTable').DataTable({ pageLength: 10 });
    $('#missedTable').DataTable({ pageLength: 10 });
});
</script>
@endpush