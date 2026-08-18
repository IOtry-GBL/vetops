@extends('layouts.app')

@section('title', 'Pet Management')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('admin.users') }}" class="sidebar-item">Users</a>
    <a href="{{ route('admin.pets') }}" class="sidebar-item active">Pets</a>
    <a href="{{ route('admin.analytics') }}" class="sidebar-item">Analytics</a>
@endsection

@section('content')

<section class="hero-card">
    <div class="hero-row">
        <div>
            <h1 class="hero-title">Pet Management</h1>
            <p class="hero-copy">View and manage all pets in the system. Filter by owner to see their pets.</p>
        </div>
    </div>
</section>

<!-- FILTER SECTION -->
<section class="card">
    <div class="surface-header">
        <h2>Filter Pets</h2>
    </div>

    <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <select name="owner_id" style="padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px solid #d1d5db;">
            <option value="">All Owners</option>
            @foreach($owners as $owner)
                <option value="{{ $owner->user_id }}" {{ request('owner_id') == $owner->user_id ? 'selected' : '' }}>
                    {{ $owner->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request('owner_id'))
            <a href="{{ route('admin.pets') }}" class="btn btn-secondary">Clear</a>
        @endif
    </form>
</section>

<!-- STATS -->
<section class="stat-grid">
    <div class="widget">
        <span class="widget-title">Total Pets</span>
        <strong class="widget-value">{{ $pets->total() }}</strong>
        <p class="muted-copy">In the system</p>
    </div>

    <div class="widget">
        <span class="widget-title">Pet Owners</span>
        <strong class="widget-value">{{ $owners->count() }}</strong>
        <p class="muted-copy">Active pet owners</p>
    </div>
</section>

<!-- PETS TABLE -->
<section class="card">
    <div class="surface-header">
        <h2>Pets</h2>
        <p class="section-copy">
            @if(request('owner_id'))
                Showing pets for selected owner
            @else
                All pets in system
            @endif
        </p>
    </div>

    @if($pets->count() > 0)
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Pet Name</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Species</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Breed</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Owner</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Sex</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Weight</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pets as $pet)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <strong>{{ $pet->name }}</strong>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $pet->species ?? 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $pet->breed ?? 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                <a href="{{ route('admin.pets', ['owner_id' => $pet->owner->user_id]) }}" style="color: #3b82f6; text-decoration: none;">
                                    {{ $pet->owner->name ?? 'Unknown' }}
                                </a>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $pet->sex ?? 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $pet->weight ? $pet->weight . ' kg' : 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $pet->date_of_birth ? $pet->date_of_birth->format('M d, Y') : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div style="margin-top: 1rem;">
            {{ $pets->links() }}
        </div>
    @else
        <div style="padding: 2rem; text-align: center; background: #f9fafb; border-radius: 0.375rem;">
            <p style="color: #6b7280; margin: 0;">No pets found.</p>
        </div>
    @endif
</section>

@endsection
