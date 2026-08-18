@extends('layouts.app')

@section('sidebar')
    @include('super-admin.sidebar')
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-info">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert" style="margin-bottom: 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <h1 style="margin: 0;">Roles & Access</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Review the permission model and adjust account-level access without leaving the Super Admin console.
        </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        @foreach ($roles as $role => $meta)
            <div class="widget">
                <div class="widget-title">{{ $meta['label'] }}</div>
                <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    @foreach ($meta['permissions'] as $permission)
                        <span style="padding: 0.25rem 0.55rem; border-radius: 999px; background: #f1f5f9; color: #334155; font-size: 0.85rem;">
                            {{ $permission }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">User Access Overrides</h2>
        <p style="margin: 0.5rem 0 1rem 0; color: #6b7280;">
            Promote or demote specific accounts, or revoke access when an account should be paused.
        </p>

        <div style="display: grid; gap: 1rem;">
            @foreach ($users as $user)
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.9rem; background: #fff;">
                    <form action="{{ route('super-admin.access-control.update', $user) }}" method="POST" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); align-items: end;">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="form-label">Account</label>
                            <div style="font-weight: 700;">{{ $user->full_name }}</div>
                            <div style="color: #6b7280; font-size: 0.95rem;">{{ $user->email }}</div>
                        </div>
                        <div>
                            <label class="form-label" for="role-{{ $user->user_id }}">Role</label>
                            <select class="form-control" id="role-{{ $user->user_id }}" name="role">
                                @foreach (array_keys(config('super-admin.roles')) as $role)
                                    @if ($role !== 'Super Admin')
                                        <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="status-{{ $user->user_id }}">Status</label>
                            <select class="form-control" id="status-{{ $user->user_id }}" name="status">
                                <option value="Active" @selected($user->status === 'Active')>Active</option>
                                <option value="Inactive" @selected($user->status === 'Inactive')>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                <input type="checkbox" name="is_super_admin" value="1" @checked($user->isSuperAdmin()) {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>
                                <span>Super admin access</span>
                            </label>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>Save Access</button>
                        </div>
                    </form>
                    @if ($user->user_id === auth()->id())
                        <p style="margin: 0.75rem 0 0 0; color: #6b7280;">Your own access level is protected.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
