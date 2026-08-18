@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item active"> My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item"> Appointments</a>
    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    <div class="card">
        <h1>Edit Pet - {{ $pet->name }}</h1>
        <p>Update pet information</p>
    </div>

    <div class="card" style="max-width: 600px;">
        <form action="{{ route('pet-owner.pets.update', $pet->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1.5rem;">
                <label for="pet_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Pet Name *</label>
                <input 
                    type="text" 
                    id="pet_name" 
                    name="pet_name" 
                    placeholder="e.g., Max, Bella, Whiskers" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    value="{{ old('pet_name', $pet->name) }}"
                    required
                >
                @error('pet_name')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="species" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Species *</label>
                <select 
                    id="species" 
                    name="species" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    required
                >
                    <option value="">-- Select Species --</option>
                    <option value="Dog" {{ old('species', $pet->species) == 'Dog' ? 'selected' : '' }}>Dog</option>
                    <option value="Cat" {{ old('species', $pet->species) == 'Cat' ? 'selected' : '' }}>Cat</option>
                    <option value="Rabbit" {{ old('species', $pet->species) == 'Rabbit' ? 'selected' : '' }}>Rabbit</option>
                    <option value="Bird" {{ old('species', $pet->species) == 'Bird' ? 'selected' : '' }}>Bird</option>
                    <option value="Hamster" {{ old('species', $pet->species) == 'Hamster' ? 'selected' : '' }}>Hamster</option>
                    <option value="Guinea Pig" {{ old('species', $pet->species) == 'Guinea Pig' ? 'selected' : '' }}>Guinea Pig</option>
                </select>
                @error('species')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="breed" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Breed *</label>
                <input 
                    type="text" 
                    id="breed" 
                    name="breed" 
                    placeholder="e.g., Golden Retriever, Persian, Siamese" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    value="{{ old('breed', $pet->breed) }}"
                    required
                >
                @error('breed')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="age" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Age (years) *</label>
                    <input 
                        type="number" 
                        id="age" 
                        name="age" 
                        placeholder="0" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                        value="{{ old('age', $pet->age) }}"
                        min="0"
                        required
                    >
                    @error('age')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="weight" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Weight (kg) *</label>
                    <input 
                        type="number" 
                        id="weight" 
                        name="weight" 
                        placeholder="0.0" 
                        step="0.1"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                        value="{{ old('weight', $pet->weight) }}"
                        min="0.1"
                        required
                    >
                    @error('weight')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 500;">Sex *</label>
                <div style="display: flex; gap: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input 
                            type="radio" 
                            name="sex" 
                            value="male" 
                            {{ old('sex', $pet->sex) == 'male' ? 'checked' : '' }}
                            required
                            style="margin-right: 0.5rem;"
                        >
                        Male
                    </label>
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input 
                            type="radio" 
                            name="sex" 
                            value="female" 
                            {{ old('sex', $pet->sex) == 'female' ? 'checked' : '' }}
                            required
                            style="margin-right: 0.5rem;"
                        >
                        Female
                    </label>
                </div>
                @error('sex')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; gap: 1rem; align-items: center;">
                <button type="submit" class="btn btn-primary">Update Pet</button>
                <a href="{{ route('pet-owner.pets') }}" class="btn btn-secondary">Cancel</a>
                <a href="{{ route('pet-owner.pets.confirm-delete', $pet->id) }}" class="btn" style="background-color: #ef4444; color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem;">Delete Pet</a>
            </div>
        </form>
    </div>
@endsection
