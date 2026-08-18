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
        <h1 style="margin: 0;">Super Admin Users</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Create and manage veterinarian, clinic staff, client, and elevated access accounts.
        </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
        <div class="widget">
            <div class="widget-title">Pet Owners</div>
            <div class="widget-value">{{ $roleStats['owners'] }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Veterinarians</div>
            <div class="widget-value">{{ $roleStats['vets'] }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Staff</div>
            <div class="widget-value">{{ $roleStats['staff'] }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Super Admins</div>
            <div class="widget-value">{{ $roleStats['admins'] }}</div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Create Account</h2>
        <form action="{{ route('super-admin.users.store') }}" method="POST" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            @csrf
            <div>
                <label class="form-label" for="full_name">Full Name</label>
                <input class="form-control" id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required>
            </div>
            <div>
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="form-label" for="contact_no">Contact Number</label>
                <input class="form-control" id="contact_no" name="contact_no" type="text" value="{{ old('contact_no') }}">
            </div>
            <div>
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>
            <div>
                <label class="form-label" for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                    <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                </select>
            </div>
            <div style="align-self: end;">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">All Users</h2>
        <div style="display: grid; gap: 1rem;">
            @forelse ($users as $user)
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.9rem; background: #fff;">
                    <form action="{{ route('super-admin.users.update', $user) }}" method="POST" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); align-items: end;">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="form-label" for="full_name-{{ $user->user_id }}">Name</label>
                            <input class="form-control" id="full_name-{{ $user->user_id }}" type="text" name="full_name" value="{{ $user->full_name }}" {{ $user->user_id === auth()->id() ? 'readonly' : '' }}>
                        </div>
                        <div>
                            <label class="form-label" for="email-{{ $user->user_id }}">Email</label>
                            <input class="form-control" id="email-{{ $user->user_id }}" type="email" name="email" value="{{ $user->email }}" {{ $user->user_id === auth()->id() ? 'readonly' : '' }}>
                        </div>
                        <div>
                            <label class="form-label" for="role-{{ $user->user_id }}">Role</label>
                            <select class="form-control" id="role-{{ $user->user_id }}" name="role" {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>
                                @foreach ($roleOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="status-{{ $user->user_id }}">Status</label>
                            <select class="form-control" id="status-{{ $user->user_id }}" name="status" {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>
                                <option value="Active" @selected($user->status === 'Active')>Active</option>
                                <option value="Inactive" @selected($user->status === 'Inactive')>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 1.8rem;">
                                <input type="checkbox" name="is_super_admin" value="1" @checked($user->isSuperAdmin()) {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>
                                <span>Super admin</span>
                            </label>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" {{ $user->user_id === auth()->id() ? 'disabled' : '' }}>Save</button>
                        </div>
                    </form>

                    <div style="margin-top: 0.85rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div style="color: #6b7280;">
                            {{ $user->role }} | {{ $user->status }} | Joined {{ $user->created_at?->format('M j, Y') ?? 'N/A' }}
                            @if ($user->isSuperAdmin())
                                <span style="margin-left: 0.5rem; color: #5b21b6; font-weight: 700;">Protected super admin account</span>
                            @endif
                        </div>
                        <form action="{{ route('super-admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Delete this account?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" {{ $user->isSuperAdmin() || $user->user_id === auth()->id() ? 'disabled' : '' }}>
                                Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p style="margin: 0; color: #6b7280;">No users found.</p>
            @endforelse
        </div>

        @if ($users->hasPages())
            <div style="margin-top: 1rem;">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
