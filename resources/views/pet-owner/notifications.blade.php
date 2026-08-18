@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item">
        Dashboard
    </a>

    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item">
        My Pets
    </a>

    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item">
        Appointments
    </a>

    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item">
        Medical Records
    </a>

    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item">
        Prescriptions
    </a>

    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item">
        Medication Reminders
    </a>

    <a href="{{ route('pet-owner.notifications') }}"
       class="sidebar-item active">
        Notifications
    </a>

    <a href="#"
       class="sidebar-item"
       onclick="openPetCareAI(event)">
        Ask Pet Care AI
    </a>
@endsection

@section('content')

<style>

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.notification-table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.notification-table {
    width: 100%;
    border-collapse: collapse;
}

.notification-table thead {
    background: #f9fafb;
}

.notification-table th,
.notification-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    vertical-align: top;
}

.notification-table tbody tr:hover {
    background: #f9fafb;
}

.notification-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.type-adherence {
    background: #dcfce7;
    color: #166534;
}

.type-appointment {
    background: #dbeafe;
    color: #1d4ed8;
}

.action-btn {
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.75rem;
}

.action-btn.delete {
    background: #fee2e2;
    color: #b91c1c;
}

.action-btn.delete:hover {
    background: #fecaca;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.form-select {
    width: 250px;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: white;
}

.scrollable-body {
    max-height: 600px;
    overflow-y: auto;
}

</style>

<!-- FILTER -->

<div class="card" style="margin-top:1rem;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3>Filter Notifications</h3>
        <button 
            class="action-btn delete" 
            onclick="clearAllNotifications()"
            style="padding: 0.5rem 1rem; font-size: 0.9rem;"
        >
            Clear All
        </button>
    </div>

    <select id="notificationFilter" class="form-select">

        <option value="all">
            All Notifications ({{ $totalCount }})
        </option>

        <option value="adherence">
            Adherence ({{ $adherenceCount }})
        </option>

        <option value="appointment">
            Appointments
        </option>

    </select>

</div>

<!-- TABLE -->

<div class="card" style="margin-top:1rem;">

    <div class="notification-table-container">

        @if(count($notifications) > 0)

            <div class="scrollable-body">

                <table class="notification-table"
                       id="notificationTable">

                    <thead>

                        <tr>
                            <th width="15%">Type</th>
                            <th width="50%">Message</th>
                            <th width="15%">Date</th>
                            <th width="5%">Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($notifications as $notification)

                            @php

                                $typeName = match($notification->type) {
                                    'adherence' => 'Adherence',
                                    'appointment' => 'Appointment',
                                    default => 'Notification'
                                };

                                $typeClass = match($notification->type) {
                                    'adherence' => 'type-adherence',
                                    'appointment' => 'type-appointment',
                                    default => ''
                                };

                                $filterData = match($notification->type) {
                                    'adherence' => 'adherence',
                                    'appointment' => 'appointment',
                                    default => 'all'
                                };

                            @endphp

                            <tr data-filter="{{ $filterData }}" data-notification-id="{{ $notification->id }}">

                                <!-- TYPE -->

                                <td>

                                    <span class="notification-type-badge {{ $typeClass }}">
                                        {{ $typeName }}
                                    </span>

                                </td>

                                <!-- MESSAGE -->

                                <td style="word-break: break-word;">

                                    @if($notification->type === 'adherence')

                                        <div style="line-height: 1.6;">
                                            <strong>{{ $notification->pet_name }}</strong> - Medication Reminder
                                            <br>
                                            <span style="color: #374151; font-size: 0.9rem;">
                                                <strong>Medication:</strong> {{ $notification->medication_name }} ({{ $notification->dosage }})
                                            </span>
                                            <br>
                                            <span style="color: #374151; font-size: 0.9rem;">
                                                <strong>Scheduled Time:</strong> {{ $notification->scheduled_at->format('h:i A') }}
                                            </span>
                                            <br>
                                            <span style="color: #6b7280; font-size: 0.85rem;">
                                                <strong>Compliance Window:</strong> 
                                                {{ $notification->scheduled_at->format('h:i A') }} - {{ $notification->confirmation_deadline->format('h:i A') }}
                                            </span>
                                            <br>
                                            <span style="color: #6b7280; font-size: 0.85rem;">
                                                Please administer the medication within this time window.
                                            </span>
                                        </div>

                                    @else

                                        {{ $notification->message }}

                                    @endif

                                </td>

                                <!-- DATE -->

                                <td style="font-size:0.8rem; color:#6b7280;">

                                    @if($notification->type === 'adherence')
                                        {{ $notification->scheduled_at->format('M d, Y h:i A') }}
                                    @else
                                        {{ $notification->created_at->format('M d, Y h:i A') }}
                                    @endif

                                </td>

                                <!-- ACTION -->

                                <td>

                                    <button
                                        class="action-btn delete"
                                        onclick="deleteNotification('{{ $notification->id }}', '{{ $notification->type }}')"
                                    >
                                        Delete
                                    </button>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-state">

                <p>
                    📭 No notifications found.
                </p>

            </div>

        @endif

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const filter = document.getElementById('notificationFilter');

    filter.addEventListener('change', function () {

        const selected = this.value;

        const rows = document.querySelectorAll(
            '#notificationTable tbody tr'
        );

        rows.forEach(row => {

            const type = row.dataset.filter;

            if (selected === 'all' || type === selected) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });

    });

});

function deleteNotification(notificationId, type) {

    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        alert('Error: CSRF token not found');
        console.error('CSRF token not found');
        return;
    }

    fetch(
        `{{ route('pet-owner.notifications.delete') }}?type=${type}&id=${notificationId}`,
        {
            method: 'DELETE',

            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        }
    )

    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })

    .then(data => {
        console.log('Delete response:', data);
        
        const row = document.querySelector(
            `tr[data-notification-id="${notificationId}"]`
        );

        if (row) {
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                row.remove();
            }, 300);
        } else {
            console.warn(`Row not found for id: ${notificationId}`);
        }
    })

    .catch(error => {
        console.error('Delete Error:', error);
        alert('Error deleting notification: ' + error.message);
    });

}

function clearAllNotifications() {
    if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        alert('Error: CSRF token not found');
        console.error('CSRF token not found');
        return;
    }

    fetch(
        `{{ route('pet-owner.notifications.clear-all') }}`,
        {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        }
    )
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Clear all response:', data);
        
        // Remove all notification rows with fade effect
        const rows = document.querySelectorAll('#notificationTable tbody tr');
        rows.forEach(row => {
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s';
        });
        
        setTimeout(() => {
            // Reload the page or show empty state
            window.location.reload();
        }, 300);
    })
    .catch(error => {
        console.error('Clear all error:', error);
        alert('Error clearing notifications: ' + error.message);
    });
}

</script>

@endsection
