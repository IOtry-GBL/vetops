@extends('layouts.app')

@section('title', 'Staff Notifications')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    

    @if (session('success'))
        <div class="alert alert-info">
            {{ session('success') }}
        </div>
    @endif

    <section class="card" style="margin-top: 1.2rem;">
        <div class="surface-header">
            <div>
                <h2>Notifications Feed</h2>
                <p class="section-copy">View all notifications with ability to delete individual items.</p>
            </div>
            <button 
                class="btn btn-sm btn-danger" 
                onclick="clearAllStaffNotifications()"
                title="Clear all notifications"
            >
              Clear All
            </button>
        </div>

        <div class="table-responsive">
            @if (count($staffNotifications) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Patient</th>
                            <th>Owner</th>
                            <th>Message</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffNotifications as $notification)
                            <tr class="{{ $notification['class'] }}">
                                <td class="col-type">
                                    <span class="notification-badge notification-badge-{{ strtolower(str_replace(' ', '-', $notification['type'])) }}">
                                        {{ $notification['type'] }}
                                    </span>
                                </td>
                                <td class="col-patient">
                                    <strong>{{ $notification['pet'] }}</strong>
                                </td>
                                <td class="col-owner">
                                    {{ $notification['owner'] }}
                                </td>
                                <td class="col-message">
                                    {{ $notification['message'] }}
                                </td>
                                <td class="col-time">
                                    {{ $notification['time']?->diffForHumans() ?? 'Recently' }}
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        @if ($notification['can_view'] && $notification['route'])
                                            <a href="{{ $notification['route'] }}" class="btn btn-sm btn-primary" title="View">View</a>
                                        @endif
                                        <form method="POST" action="{{ route('staff.notifications.delete', [$notification['route_type'], $notification['id']]) }}" class="delete-notification-form">
                                            @csrf
                                            @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    No notifications at this time.
                </div>
            @endif
        </div>
    </section>
@endsection

@section('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid var(--shell-line, #e0e0e0);
            border-radius: var(--shell-radius-lg, 0.5rem);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9375rem;
        }

        .data-table thead {
            background: var(--shell-surface-secondary, #f5f5f5);
            border-bottom: 2px solid var(--shell-line, #e0e0e0);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th {
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary, #333);
            white-space: nowrap;
        }

        .data-table th:last-child {
            text-align: center;
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--shell-line, #e0e0e0);
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .data-table tbody tr.type-new {
            border-left: 4px solid #0f8b8d;
        }

        .data-table tbody tr.type-cancelled {
            border-left: 4px solid #f59e0b;
        }

        .data-table tbody tr.type-missed {
            border-left: 4px solid #ef4444;
        }

        .data-table tbody tr.type-adherence {
            border-left: 4px solid #198754;
        }

        .data-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .data-table .col-type {
            min-width: 140px;
        }

        .data-table .col-patient {
            min-width: 120px;
            font-weight: 500;
        }

        .data-table .col-owner {
            min-width: 130px;
        }

        .data-table .col-message {
            min-width: 150px;
            color: var(--text-secondary, #666);
        }

        .data-table .col-time {
            min-width: 100px;
            font-size: 0.875rem;
            color: var(--text-secondary, #666);
        }

        .data-table .col-actions {
            text-align: center;
            min-width: 140px;
            padding: 1rem 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .notification-badge-new-appointment {
            background-color: rgba(15, 139, 141, 0.1);
            color: #0f8b8d;
        }

        .notification-badge-appointment-cancelled {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .notification-badge-did-not-arrive {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .notification-badge-adherence {
            background-color: rgba(25, 135, 84, 0.12);
            color: #116d44;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }

        .delete-notification-form {
            display: inline-flex;
            margin: 0;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .btn-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ffffff;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }

        .btn-danger svg {
            display: inline;
        }

        @media (max-width: 1024px) {
            .data-table {
                font-size: 0.875rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.625rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-sm {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .table-responsive {
                max-height: 400px;
            }

            .data-table {
                font-size: 0.8125rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.625rem;
            }

            .data-table .col-message {
                display: none;
            }

            .action-buttons {
                flex-direction: row;
                gap: 0.25rem;
            }

            .btn-sm {
                width: auto;
                padding: 0.25rem 0.5rem;
            }
        }
  
            
        

        .notification-empty {
            padding: 1rem;
            border-radius: 1rem;
            border: 1px dashed var(--shell-line);
            background: rgba(248, 250, 252, 0.85);
            color: var(--shell-muted);
            text-align: center;
        }

        @media (max-width: 1100px) {
            .notification-summary-grid,
            .notification-feed-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        function clearAllStaffNotifications() {
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
                `{{ route('staff.notifications.clear-all') }}`,
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
                const rows = document.querySelectorAll('table.data-table tbody tr');
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
