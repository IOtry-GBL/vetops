@extends('layouts.app')

@section('title', 'Edit Pet')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')

<section class="hero-card">
    <div class="hero-row">
        <div>
            <h1 class="hero-title">Edit Pet</h1>
            <p class="hero-copy">Update pet information in the system.</p>
        </div>
    </div>
</section>

<section class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="surface-header">
        <h2>Pet Details</h2>
    </div>

    @if($errors->any())
        <div style="padding: 1rem; background: #fee2e2; border-radius: 0.375rem; margin-bottom: 1rem; border-left: 4px solid #ef4444;">
            <p style="color: #991b1b; margin: 0; font-weight: 600;">Please fix the errors below:</p>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.pets.update', $pet->pet_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Pet Owner *</label>
            <select name="user_id" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                <option value="">-- Select Owner --</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->user_id }}" {{ old('user_id', $pet->user_id) == $owner->user_id ? 'selected' : '' }}>
                        {{ $owner->full_name }} ({{ $owner->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Pet Name *</label>
            <input type="text" name="pet_name" value="{{ old('pet_name', $pet->pet_name) }}" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Species *</label>
            <select name="species" required
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                <option value="">-- Select Species --</option>
                <option value="Dog" {{ old('species', $pet->species) == 'Dog' ? 'selected' : '' }}>Dog</option>
                <option value="Cat" {{ old('species', $pet->species) == 'Cat' ? 'selected' : '' }}>Cat</option>
                <option value="Bird" {{ old('species', $pet->species) == 'Bird' ? 'selected' : '' }}>Bird</option>
                <option value="Rabbit" {{ old('species', $pet->species) == 'Rabbit' ? 'selected' : '' }}>Rabbit</option>
                <option value="Hamster" {{ old('species', $pet->species) == 'Hamster' ? 'selected' : '' }}>Hamster</option>
                <option value="Guinea Pig" {{ old('species', $pet->species) == 'Guinea Pig' ? 'selected' : '' }}>Guinea Pig</option>
                <option value="Fish" {{ old('species', $pet->species) == 'Fish' ? 'selected' : '' }}>Fish</option>
                <option value="Reptile" {{ old('species', $pet->species) == 'Reptile' ? 'selected' : '' }}>Reptile</option>
                <option value="Other" {{ old('species', $pet->species) == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Breed</label>
            <input type="text" name="breed" value="{{ old('breed', $pet->breed) }}"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date of Birth</label>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $pet->date_of_birth?->format('Y-m-d')) }}"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Weight (kg)</label>
            <input type="number" name="weight" value="{{ old('weight', $pet->weight) }}" step="0.1"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Sex</label>
            <select name="sex"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                <option value="">-- Select --</option>
                <option value="Male" {{ old('sex', $pet->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('sex', $pet->sex) == 'Female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('staff.pets') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

@endsection
