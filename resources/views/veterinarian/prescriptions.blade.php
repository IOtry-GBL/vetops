@extends('layouts.app')

@section('title', 'E-Prescriptions')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item active">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')

@php
    $selectedRecordId = old('record_id', optional($selectedRecord)->record_id);

    $breeds = $medicalRecords
        ->pluck('pet.breed')
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

@if(session('success'))
    <div class="alert alert-info">
        {{ session('success') }}
    </div>
@endif

<div class="prescription-layout">

    {{-- LEFT SIDE --}}
    <section class="card">

        <div class="surface-header">
            <div>
                <h2>Medical Records</h2>

                <p class="section-copy">
                    Select a patient to review and update prescriptions.
                </p>
            </div>
        </div>

        {{-- SEARCH + FILTER --}}
        <div class="datatable-toolbar">

            <div class="search-box">
                <input
                    type="text"
                    id="recordSearch"
                    class="field-control"
                    placeholder="Search pet or owner name...">
            </div>

            <div class="filter-box">
                <select id="breedFilter" class="field-control">

                    <option value="">
                        All Breeds
                    </option>

                    @foreach($breeds as $breed)

                        <option value="{{ strtolower($breed) }}">
                            {{ $breed }}
                        </option>

                    @endforeach

                </select>
            </div>

        </div>

        {{-- TABLE --}}
        <div class="datatable-wrapper">

            <table class="records-table">

                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Owner</th>
                        <th>Breed</th>
                        <th>Current Rx</th>
                    </tr>
                </thead>

                <tbody id="recordsTableBody">

                    @forelse($medicalRecords as $record)

                        @php
                            $pet = $record->pet;
                            $owner = $pet?->owner;

                            $latestPrescription =
                                $record->prescriptions
                                    ->sortByDesc('created_at')
                                    ->first();

                            $isSelected =
                                optional($selectedRecord)->record_id === $record->record_id;
                        @endphp

                        <tr
                            onclick="window.location='{{ route('vet.prescriptions', ['record' => $record->record_id]) }}'"
                            class="clickable-row {{ $isSelected ? 'selected-row' : '' }}"
                            data-pet="{{ strtolower($pet?->name ?? '') }}"
                            data-owner="{{ strtolower($owner?->name ?? '') }}"
                            data-breed="{{ strtolower($pet?->breed ?? '') }}">

                            <td>

                                <div class="pet-cell">

                                    <strong>
                                        {{ $pet?->name ?? 'Unknown' }}
                                    </strong>

                                    <span>
                                        {{ $pet?->species ?? 'N/A' }}
                                    </span>

                                </div>

                            </td>

                            <td>
                                {{ $owner?->name ?? 'Unknown' }}
                            </td>

                            <td>
                                {{ $pet?->breed ?? 'N/A' }}
                            </td>

                            <td>

                                @if($latestPrescription)

                                    <div class="rx-mini">

                                        <strong>
                                            {{ $latestPrescription->medication_name }}
                                        </strong>

                                        <span>
                                            {{ $latestPrescription->dosage }}
                                        </span>

                                    </div>

                                @else

                                    <span class="pill pill-neutral">
                                        No Rx
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4">

                                <div class="empty-state">
                                    No medical records available.
                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

    {{-- RIGHT SIDE --}}
    <section class="stack">

        <div class="card">

            <div class="surface-header">

                <div>
                    <h2>Prescription Details</h2>

                    <p class="section-copy">
                        Review all prescriptions starting from the current/latest prescription.
                    </p>
                </div>

            </div>

            @if($selectedRecord)

                @php
                    $selectedPet = $selectedRecord->pet;
                    $selectedOwner = $selectedPet?->owner;
                @endphp

                {{-- SUMMARY --}}
                <div class="patient-summary">

                    <div class="summary-block">

                        <label>Patient</label>

                        <strong>
                            {{ $selectedPet?->name ?? 'Unknown Pet' }}
                        </strong>

                        <span>
                            {{ $selectedPet?->species ?? 'Unknown Species' }}
                            {{ $selectedPet?->breed ? ' - '.$selectedPet->breed : '' }}
                        </span>

                    </div>

                    <div class="summary-block">

                        <label>Owner</label>

                        <strong>
                            {{ $selectedOwner?->name ?? 'Unknown Owner' }}
                        </strong>

                        <span>
                            Record #{{ $selectedRecord->record_id }}
                        </span>

                    </div>

                    <div class="summary-block">

                        <label>Diagnosis</label>

                        <strong>
                            {{ $selectedRecord->diagnosis ?: 'No diagnosis recorded' }}
                        </strong>

                        <span>
                            {{ $selectedRecord->created_at?->format('M d, Y') }}
                        </span>

                    </div>

                </div>

                {{-- PRESCRIPTION HISTORY --}}
                <div class="current-prescription">

                    <div class="current-header">

                        <h3>Prescription History</h3>

                        <span class="pill pill-success">
                            {{ $selectedRecord->prescriptions->count() }} Total
                        </span>

                    </div>

                    @if($selectedRecord->prescriptions->count() > 0)

                        <div class="prescription-history-scroll">

                            @foreach($selectedRecord->prescriptions->sortByDesc('created_at') as $prescription)

                                <div class="history-prescription-card">

                                    <div class="history-top">

                                        <div class="history-medication">

                                            <strong>
                                                {{ $prescription->medication_name }}
                                            </strong>

                                            <span>
                                                {{ $prescription->issued_at?->format('M d, Y') ?? 'Issued' }}
                                            </span>

                                        </div>

                                        @if($loop->first)

                                            <span class="pill pill-success">
                                                Current
                                            </span>

                                        @endif

                                    </div>

                                    <div class="history-grid">

                                        <div class="history-item">

                                            <label>Dosage</label>

                                            <strong>
                                                {{ $prescription->dosage }}
                                            </strong>

                                        </div>

                                        <div class="history-item">

                                            <label>Frequency</label>

                                            <strong>
                                                {{ $prescription->frequency }}
                                            </strong>

                                        </div>

                                        <div class="history-item">

                                            <label>Duration</label>

                                            <strong>
                                                {{ $prescription->duration }}
                                            </strong>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="empty-state">
                            No prescription available for this record.
                        </div>

                    @endif

                </div>

                {{-- ADD PRESCRIPTION --}}
                <form
                    action="{{ route('vet.prescriptions.store') }}"
                    method="POST"
                    class="stack"
                    style="margin-top:1.5rem;">

                    @csrf

                    <input
                        type="hidden"
                        name="record_id"
                        value="{{ $selectedRecord->record_id }}">

                    <div class="form-grid">

                        <div class="field">

                            <label class="field-label">
                                Medication Name
                            </label>

                            <input
                                type="text"
                                name="medication_name"
                                class="field-control"
                                placeholder="Amoxicillin"
                                value="{{ old('medication_name') }}">

                            @error('medication_name')
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                        </div>

                        <div class="field">

                            <label class="field-label">
                                Dosage
                            </label>

                            <input
                                type="text"
                                name="dosage"
                                class="field-control"
                                placeholder="250mg"
                                value="{{ old('dosage') }}">

                            @error('dosage')
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                        </div>

                        <div class="field">

                            <label class="field-label">
                                Frequency
                            </label>

                            <input
                                type="text"
                                name="frequency"
                                class="field-control"
                                placeholder="2x daily"
                                value="{{ old('frequency') }}">

                            @error('frequency')
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                        </div>

                        <div class="field">

                            <label class="field-label">
                                Duration
                            </label>

                            <input
                                type="text"
                                name="duration"
                                class="field-control"
                                placeholder="7 days"
                                value="{{ old('duration') }}">

                            @error('duration')
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                        </div>

                    </div>

                    <div class="action-row">

                        <button type="submit" class="btn btn-primary">
                            Add Prescription
                        </button>

                    </div>

                </form>

            @else

                <div class="empty-state">
                    Select a medical record to review prescriptions.
                </div>

            @endif

        </div>

    </section>

</div>

@endsection

@section('styles')

<style>

.prescription-layout{
    display:grid;
    grid-template-columns:420px 1fr;
    gap:1.5rem;
    align-items:start;
}

.datatable-toolbar{
    display:flex;
    gap:.8rem;
    margin-bottom:1rem;
}

.search-box{
    flex:1;
}

.filter-box{
    width:160px;
}

.datatable-wrapper{
    overflow:auto;
    max-height:720px;
    border:1px solid var(--shell-line);
    border-radius:16px;
}

.records-table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

.records-table thead{
    position:sticky;
    top:0;
    z-index:5;
    background:#f8fafc;
}

.records-table th{
    padding:.85rem;
    text-align:left;
    border-bottom:1px solid var(--shell-line);
    font-size:.82rem;
    font-weight:700;
    white-space:nowrap;
}

.records-table td{
    padding:.85rem;
    border-bottom:1px solid rgba(0,0,0,.06);
    font-size:.9rem;
    vertical-align:middle;
}

.clickable-row{
    cursor:pointer;
    transition:.18s ease;
}

.clickable-row:hover{
    background:rgba(15,139,141,.05);
}

.selected-row{
    background:rgba(15,139,141,.10);
}

.pet-cell{
    display:flex;
    flex-direction:column;
    gap:.15rem;
}

.pet-cell span{
    font-size:.75rem;
    color:#64748b;
}

.rx-mini{
    display:flex;
    flex-direction:column;
    gap:.15rem;
}

.rx-mini strong{
    font-size:.82rem;
}

.rx-mini span{
    font-size:.72rem;
    color:#64748b;
}

.patient-summary{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:1rem;
    margin-bottom:1.5rem;
}

.summary-block{
    padding:1rem;
    border:1px solid var(--shell-line);
    border-radius:16px;
    background:#fff;
    display:flex;
    flex-direction:column;
    gap:.35rem;
}

.summary-block label{
    font-size:.72rem;
    text-transform:uppercase;
    color:#64748b;
    letter-spacing:.05em;
}

.summary-block span{
    color:#64748b;
    font-size:.85rem;
}

.current-prescription{
    border:1px solid var(--shell-line);
    border-radius:18px;
    padding:1.2rem;
    background:#fff;
}

.current-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:1rem;
}
.prescription-history-scroll{
    display:flex;
    flex-direction:column;
    gap:1rem;

    /* SHOW ONLY FIRST 2 CARDS */
    max-height:290px;

    overflow-y:auto;
    padding-right:.2rem;
}
.history-prescription-card{
    border:1px solid rgba(0,0,0,.08);
    border-radius:16px;
    padding:1rem;
    background:#fafafa;
}

.history-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:1rem;
}

.history-medication{
    display:flex;
    flex-direction:row;
    align-items:center;
    gap:.6rem;
    flex-wrap:wrap;
}

.history-medication strong{
    font-size:.95rem;
    margin:0;
}

.history-medication span{
    font-size:.78rem;
    color:#64748b;
    margin:0;
    white-space:nowrap;
}

.history-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:1rem;
}

.history-item{
    display:flex;
    flex-direction:column;
    gap:.3rem;
}

.history-item label{
    font-size:.72rem;
    text-transform:uppercase;
    color:#64748b;
    letter-spacing:.05em;
}

.history-item strong{
    font-size:.92rem;
}

@media(max-width:1200px){

    .prescription-layout{
        grid-template-columns:1fr;
    }

    .patient-summary{
        grid-template-columns:1fr;
    }

    .history-grid{
        grid-template-columns:1fr;
    }

}

</style>

@endsection

@section('scripts')

<script>

const searchInput = document.getElementById('recordSearch');
const breedFilter = document.getElementById('breedFilter');
const rows = document.querySelectorAll('#recordsTableBody tr');

function filterTable(){

    const search = searchInput.value.toLowerCase();
    const breed = breedFilter.value.toLowerCase();

    rows.forEach(row => {

        const pet = row.dataset.pet || '';
        const owner = row.dataset.owner || '';
        const rowBreed = row.dataset.breed || '';

        const matchesSearch =
            pet.includes(search) ||
            owner.includes(search);

        const matchesBreed =
            breed === '' ||
            rowBreed === breed;

        row.style.display =
            matchesSearch && matchesBreed
                ? ''
                : 'none';

    });

}

searchInput.addEventListener('keyup', filterTable);
breedFilter.addEventListener('change', filterTable);

</script>

@endsection