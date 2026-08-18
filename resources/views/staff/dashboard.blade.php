@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    

    <div class="grid">
        <div class="widget">
            <div class="widget-title">Today's Approved Appointments</div>
            <div class="widget-value">{{ $todayAppointments }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Approved and scheduled for today</p>
        </div>

        <div class="widget">
            <div class="widget-title">Pending Confirmations</div>
            <div class="widget-value" style="color: var(--color-visit);">{{ $pending }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Awaiting action</p>
        </div>

        <div class="widget">
            <div class="widget-title">Total Appointments</div>
            <div class="widget-value">{{ $allAppointments }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">In the system</p>
        </div>
    </div>

    <div class="card">
        <h2>Quick Tasks</h2>
        
        <!-- Pending Appointments (Not Confirmed) - PRIORITY SECTION -->
        @if($pendingRequests->count() > 0)
            <div style="margin-bottom: 1rem; overflow-x: auto; overflow-y: auto; max-height: 450px; border-radius: 0.375rem; border: 2px solid #f59e0b;">
                <h3 style="color: #d97706; margin-top: 0; padding: 0.75rem 1.25rem; position: sticky; top: 0; background: #fffbeb; z-index: 12; border-bottom: 2px solid #f59e0b; font-size: 1.1rem;"> Pending Confirmations ({{ $pendingRequests->count() }}) - Awaiting Review</h3>
                
                <!-- Filter Buttons -->
                <div style="padding: 1rem 1.25rem; background: #fffbeb; display: flex; gap: 0.5rem; flex-wrap: wrap; border-bottom: 1px solid #fde68a; position: sticky; top: 3.5rem; z-index: 11;">
                    <button onclick="filterPendingAppointments('all')" class="filter-btn" data-filter="all" style="padding: 0.5rem 1rem; background: #d97706; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">All</button>
                    <button onclick="filterPendingAppointments('regular')" class="filter-btn" data-filter="regular" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">Regular Check-up</button>
                    <button onclick="filterPendingAppointments('vaccination')" class="filter-btn" data-filter="vaccination" style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">Vaccination</button>
                    <button onclick="filterPendingAppointments('emergency')" class="filter-btn" data-filter="emergency" style="padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">🚨 Emergency</button>
                </div>
                
                <table style="width: 100%; border-collapse: collapse; table-layout: auto;">
                    <thead style="position: sticky; top: 7.5rem; z-index: 10;">
                        <tr style="background-color: #fef3c7; border-bottom: 2px solid #fde68a;">
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Pet Name</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Species</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Owner</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Phone</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Reason</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Requested Date & Time</th>
                            <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Status</th>
                            <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="pendingAppointmentsBody">
                        @forelse($pendingRequests as $appointment)
                            @php
                                $reason = $appointment->reason_for_visit ?? $appointment->reason ?? 'Regular Check-up';
                                $reasonLower = strtolower($reason);
                                $appointmentType = 'regular';
                                if (strpos($reasonLower, 'emergency') !== false) {
                                    $appointmentType = 'emergency';
                                } elseif (strpos($reasonLower, 'vaccination') !== false) {
                                    $appointmentType = 'vaccination';
                                }
                            @endphp
                            <tr class="pending-appt-row" data-type="{{ $appointmentType }}" style="border-bottom: 1px solid #fde68a; background-color: {{ $appointmentType === 'emergency' ? '#fef2f2' : '#fffbeb' }}; {{ $appointmentType === 'emergency' ? 'border-left: 4px solid #dc2626;' : '' }}">
                                <td style="padding: 1.25rem; color: #1f2937; font-weight: 500;">{{ $appointment->pet->name }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->species }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->name }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563; font-weight: {{ $appointmentType === 'emergency' ? '600' : '500' }};">{{ $reason }}</td>
                                <td style="padding: 1.25rem; color: #4b5563; white-space: nowrap;">{{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}</td>
                                <td style="padding: 1.25rem; text-align: center;">
                                    <span style="display: inline-block; background-color: #fed7aa; color: #92400e; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 500;">{{ $appointment->status }}</span>
                                </td>
                                <td style="padding: 1.25rem; text-align: center;">
                                    <a href="{{ route('staff.appointments.confirm', $appointment->id) }}" class="btn btn-primary" style="padding: 0.625rem 1rem; font-size: 0.875rem; text-decoration: none; display: inline-block; white-space: nowrap; background-color: #d97706; border: none; color: white; border-radius: 0.375rem;">Confirm Now</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 1.5rem; text-align: center; color: #6b7280;">No pending appointments awaiting confirmation</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
        
        
        
      
        <!-- Upcoming Approved Appointments Table -->
        @if($upcomingApprovedAppointments->count() > 0)
            <div style="margin-bottom: 2rem; overflow-x: auto; overflow-y: auto; max-height: 500px; border-radius: 0.375rem;">
                <h3 style="color: #667eea; margin-top: 0; padding: 0 1.25rem; position: sticky; top: 0; background: white; z-index: 10;">Upcoming Appointments ({{ $upcomingApprovedAppointments->count() }})</h3>
                <table style="width: 100%; border-collapse: collapse; table-layout: auto;">
                    <thead style="position: sticky; top: 0; z-index: 11;">
                        <tr style="background-color: #dbeafe; border-bottom: 2px solid #bfdbfe;">
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Pet Name</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Species</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Owner</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Phone</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Reason</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Date & Time</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #1e40af; font-size: 0.95rem; white-space: nowrap;">Veterinarian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingApprovedAppointments as $appointment)
                            <tr style="border-bottom: 1px solid #bfdbfe;">
                                <td style="padding: 1.25rem; color: #1f2937; font-weight: 500;">{{ $appointment->pet->name }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->species }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->name }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->reason_for_visit ?? $appointment->reason ?? 'Regular Check-up' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563; white-space: nowrap;">{{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">
                                    @if($appointment->consultation && $appointment->consultation->veterinarian)
                                        Dr. {{ $appointment->consultation->veterinarian->name }}
                                    @else
                                        <span style="color: #9ca3af; font-style: italic;">Not assigned</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 1.5rem; text-align: center; color: #6b7280;">No upcoming appointments</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
        
    </div>
<script>
        function filterPendingAppointments(filterType) {
            const rows = document.querySelectorAll('.pending-appt-row');
            const emergencyRows = [];
            const otherRows = [];
            
            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                const matches = filterType === 'all' || type === filterType;
                
                if (matches) {
                    if (type === 'emergency') {
                        emergencyRows.push(row);
                    } else {
                        otherRows.push(row);
                    }
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update button styles
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if (btn.getAttribute('data-filter') === filterType) {
                    btn.style.opacity = '1';
                    btn.style.boxShadow = '0 0 0 2px rgba(0,0,0,0.2)';
                } else {
                    btn.style.opacity = '0.6';
                    btn.style.boxShadow = 'none';
                }
            });
        }
        
        // Initialize with emergency and regular-checkup on top
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('pendingAppointmentsBody');
            const rows = Array.from(document.querySelectorAll('.pending-appt-row'));
            
            rows.sort((a, b) => {
                const typeA = a.getAttribute('data-type');
                const typeB = b.getAttribute('data-type');
                if (typeA === 'emergency' && typeB !== 'emergency') return -1;
                if (typeA !== 'emergency' && typeB === 'emergency') return 1;
                return 0;
            });
            
            rows.forEach(row => tbody.appendChild(row));
            
            // Highlight all buttons initially
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.style.opacity = btn.getAttribute('data-filter') === 'all' ? '1' : '0.6';
            });
        });
        </script>
   @endsection
