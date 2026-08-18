@extends('layouts.app')

@section('sidebar')
    @include('super-admin.sidebar')
@endsection

@section('content')
    <div class="card">
        <h1 style="margin: 0;">Super Admin Analytics</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            High-level usage counts pulled directly from the current database.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Users By Role</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($users_by_role as $row)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <span>{{ $row->role }}</span>
                        <strong>{{ $row->count }}</strong>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No user records available.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Appointments By Status</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($appointments_by_status as $row)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <span>{{ $row->status }}</span>
                        <strong>{{ $row->count }}</strong>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No appointments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Recent Users</h2>
        <div style="display: grid; gap: 0.75rem;">
            @forelse ($recent_users as $user)
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.9rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $user->email }}</p>
                    </div>
                    <div style="text-align: right;">
                        <strong>{{ $user->role }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $user->created_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                    </div>
                </div>
            @empty
                <p style="color: #6b7280; margin: 0;">No recent users found.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Recent Activity</h2>
        <div style="display: grid; gap: 0.75rem;">
            @forelse ($recent_activities as $activity)
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.9rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                    <div>
                        <strong>{{ $activity->action }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $activity->description ?: 'No description provided.' }}</p>
                    </div>
                    <div style="text-align: right;">
                        <strong>{{ $activity->actor?->full_name ?? 'System' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $activity->created_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
                    </div>
                </div>
            @empty
                <p style="color: #6b7280; margin: 0;">No system activity captured yet.</p>
            @endforelse
        </div>
    </div>
@endsection
