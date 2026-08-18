@extends('layouts.app')

@section('title', 'Pet Management')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')

<section class="hero-card">
    <div class="hero-row">
        <div>
            <h1 class="hero-title">Pet Management</h1>
            <p class="hero-copy">View and manage all pets in the system.</p>
        </div>

        <div class="action-row">
            <a href="{{ route('staff.add-pet') }}" class="btn btn-primary">Add New Pet</a>
        </div>
    </div>
</section>

<!-- PETS TABLE -->
<section class="card">
    <div class="surface-header">

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    ">
     <div style="
            display: flex;
            gap: 1rem;
        ">
            <div class="widget">
                <span class="widget-title">Total Pets</span>
                <strong class="widget-value">{{ $pets->total() }}</strong>
            </div>

            <div class="widget">
                <span class="widget-title">Pet Owners</span>
                <strong class="widget-value">{{ $owners->count() }}</strong>
            </div>
        </div>

    </div>



   
</div>

    <form method="GET" action="{{ route('staff.pets') }}" style="margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
            <div style="flex: 1;">
                <label for="owner_filter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Filter by Owner:</label>
                <select name="owner_id" id="owner_filter" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.9375rem;">
                    <option value="">-- All Owners --</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->user_id }}" {{ request('owner_id') == $owner->user_id ? 'selected' : '' }}>
                            {{ $owner->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
    

    @if($pets->count() > 0)
        <div style="overflow-x: auto; overflow-y: auto; max-height: 60vh; width: 100%; min-width: 0;">
            <table style="width: 100%; min-width: 900px; border-collapse: collapse; table-layout: auto;">
                <thead>
                    <tr style="background: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Pet Name</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Species</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Breed</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Owner</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Sex</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Weight</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Date of Birth</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Actions</th>
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
                                {{ $pet->owner->full_name ?? 'Unknown' }}
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
                            <td style="padding: 0.75rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('staff.pets.edit', $pet->pet_id) }}" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 0.375rem; border: none; cursor: pointer;">Edit</a>
                                    <a href="{{ route('staff.pets.confirm-delete', $pet->pet_id) }}" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background-color: #ef4444; color: white; text-decoration: none; border-radius: 0.375rem; border: none; cursor: pointer;">Delete</a>
                                </div>
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
