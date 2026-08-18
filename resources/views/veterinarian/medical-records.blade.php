@extends('layouts.app')

@section('title', 'Medical Records')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item active">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')
    @php
        $patientCount = $patients->count();
        $totalPrescriptionCount = $allMedicalRecords->sum(fn ($record) => $record->prescriptions->count());
        $scheduledFollowUps = $allMedicalRecords->filter(fn ($record) => $record->follow_up_date && $record->follow_up_date->isFuture())->count();
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Medical Records</h1>
                <p class="hero-copy">Select a pet owner, retrieve their pets, then open that pet's medical records.</p>
            </div>
            <div class="metric-grid" style="min-width: min(100%, 460px);">
                <div class="metric-card"><span class="metric-label">Owners</span><strong class="metric-value">{{ $owners->count() }}</strong></div>
                <div class="metric-card"><span class="metric-label">Pets</span><strong class="metric-value">{{ $patientCount }}</strong></div>
                <div class="metric-card"><span class="metric-label">Records</span><strong class="metric-value">{{ $allMedicalRecords->count() }}</strong></div>
                <div class="metric-card"><span class="metric-label">Prescriptions</span><strong class="metric-value">{{ $totalPrescriptionCount }}</strong></div>
            </div>
        </div>
    </section>

    <section class="card">
        <form method="GET" action="{{ route('vet.medical-records') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="search">Search records</label>
                <input id="search" name="search" value="{{ $search }}" class="field-control" placeholder="Owner, pet, diagnosis, treatment">
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if($search !== '')
                    <a href="{{ route('vet.medical-records') }}" class="btn btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Pet Owners</h2>
                <p class="section-copy">Press a pet owner name to retrieve their pets.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr><th>Owner</th><th>Email</th><th>Phone</th><th>Pets With Records</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($owners->take($seeAll ? $owners->count() : 10) as $owner)
                        <tr>
                            <td><strong>{{ $owner->name }}</strong></td>
                            <td>{{ $owner->email }}</td>
                            <td>{{ $owner->phone ?? 'N/A' }}</td>
                            <td>{{ $owner->pets->count() }}</td>
                            <td><a href="{{ route('vet.medical-records', ['owner' => $owner->user_id, 'search' => $search]) }}" class="btn btn-secondary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Retrieve Pets</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: #6b7280;">No pet owners found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(!$seeAll && $owners->count() > 10)
            <a href="{{ route('vet.medical-records', ['search' => $search, 'owner' => $selectedOwner?->user_id, 'pet' => $selectedPet?->pet_id, 'see_all' => 1]) }}" class="btn btn-secondary" style="margin-top: 1rem;">See All</a>
        @endif
    </section>

    @if(!$selectedOwner)
        <section class="card">
            <div class="empty-state">
                Select a pet owner to retrieve pets. Medical records stay hidden until a pet is selected.
            </div>
        </section>
    @endif

    @if($selectedOwner)
        <section class="card">
            <div class="surface-header">
                <div>
                    <h2>{{ $selectedOwner->name }} Pets</h2>
                    <p class="section-copy">Press a pet to retrieve all medical records for that pet.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="app-table">
                    <thead>
                        <tr><th>Pet</th><th>Species</th><th>Breed</th><th>Records</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse($ownerPets->take($seeAll ? $ownerPets->count() : 10) as $pet)
                            <tr>
                                <td><strong>{{ $pet->name }}</strong></td>
                                <td>{{ $pet->species }}</td>
                                <td>{{ $pet->breed }}</td>
                                <td>{{ $allMedicalRecords->where('pet_id', $pet->pet_id)->count() }}</td>
                                <td><a href="{{ route('vet.medical-records', ['owner' => $selectedOwner->user_id, 'pet' => $pet->pet_id, 'search' => $search]) }}" class="btn btn-primary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Retrieve Records</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: #6b7280;">No pets with records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if(!$selectedPet)
            <section class="card">
                <div class="empty-state">
                    Select a pet to retrieve medical records.
                </div>
            </section>
        @endif
    @endif

    @if($selectedPet)
        <section class="card">
            <div class="surface-header">
                <div>
                    <h2>{{ $selectedPet->name }} Medical Records</h2>
                    <p class="section-copy">Showing {{ $selectedPatientRecordsPreview->count() }} of {{ $selectedPatientRecords->count() }} records. {{ $scheduledFollowUps }} follow-up record(s) are scheduled across all results.</p>
                </div>
                @if(!$seeAll && $selectedPatientRecords->count() > 10)
                    <a href="{{ route('vet.medical-records', ['owner' => $selectedOwner?->user_id, 'pet' => $selectedPet->pet_id, 'search' => $search, 'see_all' => 1]) }}" class="btn btn-secondary">See All</a>
                @endif
            </div>
            <div class="table-wrap">
                <table class="app-table">
                    <thead>
                        <tr><th>Created</th><th>Diagnosis</th><th>Treatment Plan</th><th>Follow-Up</th><th>Prescriptions</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse($selectedPatientRecordsPreview as $record)
                            <tr>
                                <td>{{ $record->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $record->diagnosis ?: 'No diagnosis recorded' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($record->treatment_plan ?: 'No treatment plan recorded', 120) }}</td>
                                <td>{{ $record->follow_up_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
                                <td>{{ $record->prescriptions->count() }}</td>
                                <td><a href="{{ route('vet.prescriptions', ['record' => $record->record_id]) }}" class="btn btn-secondary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Open Rx</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align: center; color: #6b7280;">No medical records found for this pet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
