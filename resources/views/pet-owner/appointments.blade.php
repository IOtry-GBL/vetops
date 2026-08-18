@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item"> My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item active"> Appointments</a>
    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection


@section('content')

<div class="card">
    <div class="section-head">
        <div>
            <h1>Appointments</h1>
            <p class="muted-copy">Manage all your appointment records.</p>
        </div>
        <a href="{{ route('pet-owner.appointments.book') }}" class="btn btn-primary">Book Appointment</a>
    </div>
</div>


{{-- ================= UPCOMING ================= --}}
<div class="card" style="margin-top: 2rem;">
    <h2>Upcoming Appointments ({{ $upcomingAppointments->count() }})</h2>

    <table id="upcomingTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:center;">Time</th>
                <th style="text-align:left;">Veterinarian</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse($upcomingAppointments as $appointment)

                @php
                    $statusKey = strtolower($appointment->status);

                    $statusColor = match($statusKey) {
                        'pending' => '#f59e0b',
                        'approved' => '#10b981',
                        'completed' => '#10b981',
                        'missed' => '#ef4444',
                        'cancelled' => '#ef4444',
                        'rescheduled' => '#3b82f6',
                        default => '#3b82f6',
                    };

                    $statusText = match($statusKey) {
                        'pending' => 'Pending',
                        'approved' => 'Confirmed',
                        'completed' => 'Completed',
                        'missed' => 'Missed',
                        'cancelled' => 'Cancelled',
                        'rescheduled' => 'Rescheduled',
                        default => 'Unknown',
                    };
                @endphp

                <tr>
                    <td style="text-align:left; font-weight:500;">
                        {{ $appointment->pet->name }}
                    </td>

                    <td style="text-align:left;">
                        {{ $appointment->reason ?? 'N/A' }}
                    </td>

                    <td style="text-align:center;">
                        {{ $appointment->appointment_date->format('M d, Y') }}
                    </td>

                    <td style="text-align:center;">
                        {{ $appointment->appointment_date->format('h:i A') }}
                    </td>

                    <td style="text-align:left;">
                        {{ $appointment->veterinarian ? 'Dr. '.$appointment->veterinarian->name : 'Pending' }}
                    </td>

                    <td style="text-align:center;">
                        <span style="padding:4px 10px; border-radius:6px; background:{{ $statusColor }}; color:#fff; font-size:12px;">
                            {{ $statusText }}
                        </span>
                    </td>

                    <td style="text-align:right; white-space:nowrap;">
                        <button class="btn btn-primary btn-reschedule"
                                data-id="{{ $appointment->id }}"
                                style="font-size:12px; padding:5px 10px;">
                            Reschedule
                        </button>

                        <form method="POST"
                              action="{{ route('pet-owner.appointments.cancel', $appointment->id) }}"
                              style="display:inline;">
                            @csrf
                            @method('DELETE')

                            <button onclick="return confirm('Cancel this appointment?')"
                                    class="btn btn-danger"
                                    style="font-size:12px; padding:5px 10px;">
                                Cancel
                            </button>
                        </form>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem;">
                        No upcoming appointments
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- ================= MISSED ================= --}}
<div class="card" style="margin-top: 2rem;">
    <h2 style="color:#ef4444;">Missed Appointments ({{ $missedAppointments->count() }})</h2>

    <table id="missedTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:left;">Veterinarian</th>
                <th style="text-align:center;">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse($missedAppointments as $appointment)
                <tr>
                    <td style="text-align:left;">{{ $appointment->pet->name }}</td>
                    <td style="text-align:left;">{{ $appointment->reason }}</td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date->format('M d, Y h:i A') }}
                    </td>
                    <td style="text-align:left;">
                        {{ $appointment->veterinarian ? 'Dr. '.$appointment->veterinarian->name : 'Pending' }}
                    </td>
                    <td style="text-align:center;">
                        <span style="color:#ef4444; font-weight:600;">Missed</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No missed appointments</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- ================= COMPLETED ================= --}}
<div class="card" style="margin-top: 2rem;">
    <h2 style="color:#10b981;">Completed Appointments ({{ $completedAppointments->count() }})</h2>

    <table id="completedTable" class="display align-table" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Pet</th>
                <th style="text-align:left;">Reason</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:left;">Veterinarian</th>
                <th style="text-align:center;">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse($completedAppointments as $appointment)
                <tr>
                    <td style="text-align:left;">{{ $appointment->pet->name }}</td>
                    <td style="text-align:left;">{{ $appointment->reason }}</td>
                    <td style="text-align:center;">
                        {{ $appointment->appointment_date->format('M d, Y h:i A') }}
                    </td>
                    <td style="text-align:left;">
                        {{ $appointment->veterinarian ? 'Dr. '.$appointment->veterinarian->name : 'N/A' }}
                    </td>
                    <td style="text-align:center;">
                        <span style="color:#10b981; font-weight:600;">Completed</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No completed appointments</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection


{{-- ================= STYLES ================= --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
    .align-table td,
    .align-table th {
        padding: 12px 10px;
        vertical-align: middle;
    }

    .align-table {
        width: 100%;
    }

    .btn {
        display: inline-block;
        text-decoration: none;
    }
</style>
@endpush


{{-- ================= SCRIPTS ================= --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    $('#upcomingTable').DataTable({ pageLength: 10, order: [[2, 'asc']] });
    $('#missedTable').DataTable({ pageLength: 10 });
    $('#completedTable').DataTable({ pageLength: 10 });
});
</script>

<script>
document.querySelectorAll('.btn-reschedule').forEach(btn => {
    btn.addEventListener('click', function () {
        alert('Reschedule appointment ID: ' + this.dataset.id);
    });
});
</script>
@endpush