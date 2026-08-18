@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    <div class="card">
        <h1>Appointment Queue - Pending Review</h1>
        <p>All pending appointment requests. Confirm which ones are viable for scheduling.</p>
    </div>

    <div class="card">
        <div style="margin-bottom: 2rem; overflow-x: auto; overflow-y: auto; max-height: 600px; border-radius: 0.375rem; border: 2px solid #f59e0b;">
            <h3 style="color: #d97706; margin-top: 0; padding: 0.75rem 1.25rem; position: sticky; top: 0; background: #fffbeb; z-index: 10; border-bottom: 2px solid #f59e0b; font-size: 1.1rem;">All Pending Appointments ({{ $pendingAppointments->count() }})</h3>
            
            <!-- Filter Buttons -->
            <div style="padding: 1rem 1.25rem; background: #fffbeb; display: flex; gap: 0.5rem; flex-wrap: wrap; border-bottom: 1px solid #fde68a;">
                <button onclick="filterQueueTable('all')" class="queue-filter-btn" data-filter="all" style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">All</button>
                <button onclick="filterQueueTable('regular')" class="queue-filter-btn" data-filter="regular" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">Regular Check-up</button>
                <button onclick="filterQueueTable('vaccination')" class="queue-filter-btn" data-filter="vaccination" style="padding: 0.5rem 1rem; background: #8b5cf6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">Vaccination</button>
                <button onclick="filterQueueTable('emergency')" class="queue-filter-btn" data-filter="emergency" style="padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500; font-size: 0.85rem;">🚨 Emergency</button>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; table-layout: auto;">
                <thead style="position: sticky; top: 3.5rem; z-index: 11;">
                    <tr style="background-color: #fef3c7; border-bottom: 2px solid #fde68a;">
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Pet Name</th>
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Species</th>
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Owner</th>
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Phone</th>
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Reason</th>
                        <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Date & Time</th>
                        <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Status</th>
                        <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #92400e; font-size: 0.95rem; white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody id="queueTableBody">
                    @php
                        $allAppointments = collect();
                        if ($emergencyQueue->count() > 0) {
                            $allAppointments = $allAppointments->concat($emergencyQueue);
                        }
                        if ($highPriorityQueue->count() > 0) {
                            $allAppointments = $allAppointments->concat($highPriorityQueue);
                        }
                        if ($routineQueue->count() > 0) {
                            $allAppointments = $allAppointments->concat($routineQueue);
                        }
                    @endphp
                    
                    @forelse($allAppointments as $appointment)
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
                        <tr class="queue-row" data-type="{{ $appointmentType }}" style="border-bottom: 1px solid #fde68a; background-color: {{ $appointmentType === 'emergency' ? '#fef2f2' : '#fffbeb' }}; {{ $appointmentType === 'emergency' ? 'border-left: 4px solid #dc2626;' : '' }}">
                            <td style="padding: 1.25rem; color: #1f2937; font-weight: 500;">{{ $appointment->pet->name }}</td>
                            <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->species }}</td>
                            <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->name }}</td>
                            <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                            <td style="padding: 1.25rem; color: #4b5563; font-weight: {{ $appointmentType === 'emergency' ? '600' : '500' }};">{{ $reason }}</td>
                            <td style="padding: 1.25rem; color: #4b5563; white-space: nowrap;">{{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y • h:i A') : 'TBD' }}</td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <span style="display: inline-block; background-color: {{ $appointmentType === 'emergency' ? '#fee2e2' : '#fed7aa' }}; color: {{ $appointmentType === 'emergency' ? '#991b1b' : '#92400e' }}; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.85rem; font-weight: 500;">{{ $appointmentType === 'emergency' ? 'URGENT' : 'PENDING' }}</span>
                            </td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                    <a href="{{ route('staff.appointments.confirm', $appointment->appointment_id) }}" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; text-decoration: none; display: inline-block; background-color: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">Confirm</a>
                                    <form method="POST" action="{{ route('staff.appointments.reject', $appointment->appointment_id) }}" style="margin: 0; display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Reject this appointment?')" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; background: #dc2626; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 1.5rem; text-align: center; color: #6b7280;">No pending appointments</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

  

    <script>
    function filterQueueTable(filterType) {
        const rows = document.querySelectorAll('.queue-row');
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
        document.querySelectorAll('.queue-filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === filterType) {
                btn.style.opacity = '1';
                btn.style.boxShadow = '0 0 0 2px rgba(0,0,0,0.2)';
            } else {
                btn.style.opacity = '0.6';
                btn.style.boxShadow = 'none';
            }
        });
    }
    
    // Initialize with emergency appointments on top
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('queueTableBody');
        const rows = Array.from(document.querySelectorAll('.queue-row'));
        
        rows.sort((a, b) => {
            const typeA = a.getAttribute('data-type');
            const typeB = b.getAttribute('data-type');
            if (typeA === 'emergency' && typeB !== 'emergency') return -1;
            if (typeA !== 'emergency' && typeB === 'emergency') return 1;
            return 0;
        });
        
        rows.forEach(row => tbody.appendChild(row));
        
        // Highlight all button initially
        document.querySelectorAll('.queue-filter-btn').forEach(btn => {
            btn.style.opacity = btn.getAttribute('data-filter') === 'all' ? '1' : '0.6';
        });
    });
    </script>
@endsection
