@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item active"> My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item"> Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item"> Medical Records</a>
    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item"> Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')

<div class="card" style="padding: 0; overflow: hidden; height: calc(94vh - 120px);">

    <!-- HEADER / FILTER SECTION -->
    <div style="
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    ">

        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            align-items: end;
        ">

            <!-- Search -->
            <div>
                <label
                    for="searchPets"
                    style="
                        display: block;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                        font-size: 0.875rem;
                        color: #374151;
                    "
                >
                    Search Pet Name
                </label>

                <input
                    type="text"
                    id="searchPets"
                    placeholder="Search by pet name..."
                    style="
                        width: 100%;
                        padding: 0.75rem;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                    "
                />
            </div>

            <!-- Filter -->
            <div>
                <label
                    for="filterSpecies"
                    style="
                        display: block;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                        font-size: 0.875rem;
                        color: #374151;
                    "
                >
                    Filter by Species
                </label>

                <select
                    id="filterSpecies"
                    style="
                        width: 100%;
                        padding: 0.75rem;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                    "
                >
                    <option value="">-- All Species --</option>

                    @php
                        $species = $pets->pluck('species')->unique()->sort();
                    @endphp

                    @foreach($species as $specie)
                        <option value="{{ $specie }}">
                            {{ $specie }}
                        </option>
                    @endforeach

                </select>
            </div>

            <!-- Reset Button -->
            <div style="display: flex; align-items: flex-end;">
                <button
                    type="button"
                    id="resetFilters"
                    style="
                        width: 100%;
                        height: 48px;
                        background: #e5e7eb;
                        border: none;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 0.875rem;
                        transition: background 0.2s ease;
                    "
                >
                    Reset Filters
                </button>
            </div>

            <!-- Add Pet -->
            <div style="display: flex; align-items: flex-end;">
                <a
                    href="{{ route('pet-owner.pets.create') }}"
                    class="btn btn-primary"
                    style="
                        width: 100%;
                        height: 48px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    "
                >
                    + Add New Pet
                </a>
            </div>

        </div>

    </div>

    <!-- TABLE -->
    <div style="overflow-x: auto; overflow-y: auto; max-height: 600px;">

        <table
            id="petsTable"
            style="
                width: 100%;
                border-collapse: collapse;
                min-width: 1000px;
            "
        >

            <thead
                style="
                    position: sticky;
                    top: 0;
                    z-index: 10;
                    background: #f3f4f6;
                "
            >

                <tr style="border-bottom: 2px solid #d1d5db;">

                    <th class="table-head">Pet Name</th>
                    <th class="table-head">Species</th>
                    <th class="table-head">Breed</th>
                    <th class="table-head center">Age (Years)</th>
                    <th class="table-head center">Weight (kg)</th>
                    <th class="table-head center">Sex</th>
                    <th class="table-head center">Total Visits</th>
                    <th class="table-head center">Status</th>
                    <th class="table-head center">Action</th>

                </tr>

            </thead>

            <tbody>

                @forelse($pets as $pet)

                    <tr
                        class="pet-row"
                        data-pet-name="{{ strtolower($pet->name) }}"
                        data-pet-species="{{ $pet->species }}"
                        style="
                            border-bottom: 1px solid #e5e7eb;
                            transition: background-color 0.2s;
                        "
                    >

                        <td class="table-cell strong">
                            {{ $pet->name }}
                        </td>

                        <td class="table-cell">
                            {{ $pet->species }}
                        </td>

                        <td class="table-cell">
                            {{ $pet->breed }}
                        </td>

                        <td class="table-cell center">
                            {{ $pet->age }}
                        </td>

                        <td class="table-cell center">
                            {{ $pet->weight }}
                        </td>

                        <td class="table-cell center">
                            {{ ucfirst($pet->sex) }}
                        </td>

                        <td class="table-cell center">
                            <span
                                style="
                                    background-color: #dbeafe;
                                    color: #1e40af;
                                    padding: 0.5rem 1rem;
                                    border-radius: 999px;
                                    font-weight: 600;
                                    font-size: 0.875rem;
                                "
                            >
                                {{ $pet->appointments->whereIn('status', ['Approved', 'Completed'])->count() }}
                            </span>
                        </td>

                        <td class="table-cell center">
                            <span
                                class="badge badge-monitor"
                                style="
                                    padding: 0.5rem 1rem;
                                    font-size: 0.875rem;
                                "
                            >
                                Active
                            </span>
                        </td>

                        <td class="table-cell center">
                            <a
                                href="{{ route('pet-owner.pets.edit', $pet->id) }}"
                                class="btn btn-secondary"
                                style="
                                    font-size: 0.875rem;
                                    padding: 0.625rem 1.25rem;
                                    white-space: nowrap;
                                "
                            >
                                Edit
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr id="emptyState">

                        <td
                            colspan="9"
                            style="
                                padding: 3rem;
                                text-align: center;
                                background-color: #f9fafb;
                                color: #6b7280;
                                font-size: 1rem;
                            "
                        >
                            No pets found.

                            <a
                                href="{{ route('pet-owner.pets.create') }}"
                                style="
                                    color: #667eea;
                                    text-decoration: none;
                                    font-weight: 600;
                                "
                            >
                                Add your first pet
                            </a>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


<style>

.table-head{
    padding: 1.25rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
    white-space: nowrap;
}

.table-head.center{
    text-align: center;
}

.table-cell{
    padding: 1.25rem;
    color: #4b5563;
}

.table-cell.center{
    text-align: center;
}

.table-cell.strong{
    color: #1f2937;
    font-weight: 500;
}

tbody tr:hover{
    background-color: #f9fafb;
}

</style>


<script>

    const searchInput = document.getElementById('searchPets');
    const filterSelect = document.getElementById('filterSpecies');
    const resetButton = document.getElementById('resetFilters');
    const petRows = document.querySelectorAll('.pet-row');
    const emptyState = document.getElementById('emptyState');
    const tbody = document.querySelector('#petsTable tbody');

    function filterPets() {

        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedSpecies = filterSelect.value;

        let visibleCount = 0;

        petRows.forEach(row => {

            const petName = row.getAttribute('data-pet-name');
            const petSpecies = row.getAttribute('data-pet-species');

            const matchesSearch = petName.includes(searchTerm);
            const matchesSpecies =
                selectedSpecies === '' ||
                petSpecies === selectedSpecies;

            if (matchesSearch && matchesSpecies) {

                row.style.display = '';
                visibleCount++;

            } else {

                row.style.display = 'none';

            }

        });

        // Show/hide empty state
        if (visibleCount === 0) {

            if (!document.getElementById('noResultsRow')) {

                const noResultsRow = document.createElement('tr');

                noResultsRow.id = 'noResultsRow';

                noResultsRow.innerHTML = `
                    <td colspan="9"
                        style="
                            padding: 3rem;
                            text-align: center;
                            background-color: #f9fafb;
                            color: #6b7280;
                            font-size: 1rem;
                        "
                    >
                        No pets match your search criteria.
                    </td>
                `;

                tbody.appendChild(noResultsRow);

            }

        } else {

            const noResultsRow = document.getElementById('noResultsRow');

            if (noResultsRow) {
                noResultsRow.remove();
            }

        }

    }

    function resetFilters() {

        searchInput.value = '';
        filterSelect.value = '';

        petRows.forEach(row => {
            row.style.display = '';
        });

        const noResultsRow = document.getElementById('noResultsRow');

        if (noResultsRow) {
            noResultsRow.remove();
        }

    }

    searchInput.addEventListener('input', filterPets);
    filterSelect.addEventListener('change', filterPets);
    resetButton.addEventListener('click', resetFilters);

</script>

@endsection
