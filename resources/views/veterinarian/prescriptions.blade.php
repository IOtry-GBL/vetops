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
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">E-Prescriptions</h1>
                <p class="hero-copy">Create and edit prescriptions from clinic medical records.</p>
            </div>
            <div class="metric-grid" style="min-width: min(100%, 460px);">
                <div class="metric-card"><span class="metric-label">Medical Records</span><strong class="metric-value">{{ $medicalRecords->count() }}</strong></div>
                <div class="metric-card"><span class="metric-label">Patients</span><strong class="metric-value">{{ $patientCount }}</strong></div>
                <div class="metric-card"><span class="metric-label">Issued Today</span><strong class="metric-value">{{ $issuedTodayCount }}</strong></div>
                <div class="metric-card"><span class="metric-label">Total Prescriptions</span><strong class="metric-value">{{ $totalPrescriptionRows }}</strong></div>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-info">{{ session('success') }}</div>
    @endif

    <section class="card">
        <form method="GET" action="{{ route('vet.prescriptions') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="search">Search prescriptions</label>
                <input id="search" name="search" value="{{ $search }}" class="field-control" placeholder="Medication, pet, owner, dosage">
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if($search !== '')
                    <a href="{{ route('vet.prescriptions') }}" class="btn btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Medical Records</h2>
                <p class="section-copy">First 10 matching records. Choose one to create a prescription.</p>
            </div>
            @if(!$seeAll && $medicalRecords->count() > 10)
                <a href="{{ route('vet.prescriptions', ['search' => $search, 'record' => $selectedRecord?->record_id, 'see_all' => 1]) }}" class="btn btn-secondary">See All</a>
            @endif
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr><th>Record</th><th>Pet</th><th>Owner</th><th>Diagnosis</th><th>Rx Count</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($medicalRecords->take($seeAll ? $medicalRecords->count() : 10) as $record)
                        <tr>
                            <td>Record {{ $record->record_id }}</td>
                            <td>{{ $record->pet?->name ?? 'Unknown Pet' }}</td>
                            <td>{{ $record->pet?->owner?->name ?? 'Unknown Owner' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($record->diagnosis ?: 'No diagnosis recorded', 90) }}</td>
                            <td>{{ $record->prescriptions->count() }}</td>
                            <td><a href="{{ route('vet.prescriptions', ['record' => $record->record_id, 'search' => $search]) }}" class="btn btn-secondary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Select</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align: center; color: #6b7280;">No medical records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Create Prescription</h2>
                <p class="section-copy">Selected record: {{ $selectedRecord ? 'Record '.$selectedRecord->record_id.' / '.$selectedRecord->pet?->name : 'None' }}</p>
            </div>
        </div>

        @if ($selectedRecord)
            <form action="{{ route('vet.prescriptions.store') }}" method="POST" class="stack">
                @csrf
                <div class="field">
                    <label class="field-label" for="record_id">Medical Record</label>
                    <select id="record_id" name="record_id" class="field-control">
                        @foreach ($medicalRecords as $record)
                            <option value="{{ $record->record_id }}" @selected((string) $selectedRecordId === (string) $record->record_id)>
                                {{ $record->pet?->name ?? 'Unknown Pet' }} - Record {{ $record->record_id }}
                            </option>
                        @endforeach
                    </select>
                    @error('record_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label class="field-label" for="medication_name">Medication Name</label>
                        <input id="medication_name" name="medication_name" type="text" class="field-control" value="{{ old('medication_name') }}">
                        @error('medication_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="field-label" for="dosage">Dosage</label>
                        <input id="dosage" name="dosage" type="text" class="field-control" value="{{ old('dosage') }}">
                        @error('dosage')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="field-label" for="frequency">Frequency</label>
                        <input id="frequency" name="frequency" type="text" list="prescription-frequency-options" class="field-control" placeholder="1 to 5" value="{{ old('frequency') }}">
                        @error('frequency')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="field-label" for="duration">Duration</label>
                        <input id="duration" name="duration" type="text" list="prescription-duration-options" class="field-control" placeholder="7" value="{{ old('duration') }}">
                        @error('duration')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <datalist id="prescription-frequency-options">
                    <option value="1" label="Once daily"></option>
                    <option value="2" label="Twice daily"></option>
                    <option value="3" label="3 times daily"></option>
                    <option value="4" label="4 times daily"></option>
                    <option value="5" label="5 times daily"></option>
                </datalist>
                <datalist id="prescription-duration-options">
                    <option value="1"></option><option value="3"></option><option value="5"></option><option value="7"></option><option value="10"></option><option value="14"></option><option value="30"></option>
                </datalist>

                <button type="submit" class="btn btn-primary">Create Prescription</button>
            </form>
        @else
            <div class="empty-state">Select a medical record before creating a prescription.</div>
        @endif
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Prescription Data</h2>
                <p class="section-copy">Showing {{ $prescriptions->count() }} of {{ $totalPrescriptionRows }} prescription records. Edit existing prescriptions directly in the table.</p>
            </div>
            @if(!$seeAll && $totalPrescriptionRows > 10)
                <a href="{{ route('vet.prescriptions', ['search' => $search, 'record' => $selectedRecord?->record_id, 'see_all' => 1]) }}" class="btn btn-secondary">See All</a>
            @endif
        </div>

        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Record</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Issued</th>
                        <th>Save</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prescriptions as $prescription)
                        @php
                            $record = $prescription->medicalRecord;
                            $pet = $record?->pet;
                            $owner = $pet?->owner;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $pet?->name ?? 'Unknown Pet' }}</strong><br>
                                <span class="muted-copy">{{ $owner?->name ?? 'Unknown Owner' }}</span>
                            </td>
                            <td>Record {{ $record?->record_id ?? 'N/A' }}</td>
                            <td><input form="prescription-update-{{ $prescription->prescription_id }}" name="medication_name" value="{{ old('medication_name', $prescription->medication_name) }}" class="field-control" style="min-width: 160px;"></td>
                            <td><input form="prescription-update-{{ $prescription->prescription_id }}" name="dosage" value="{{ old('dosage', $prescription->dosage) }}" class="field-control" style="min-width: 110px;"></td>
                            <td><input form="prescription-update-{{ $prescription->prescription_id }}" name="frequency" value="{{ old('frequency', $prescription->frequency) }}" class="field-control" style="min-width: 130px;"></td>
                            <td><input form="prescription-update-{{ $prescription->prescription_id }}" name="duration" value="{{ old('duration', $prescription->duration) }}" class="field-control" style="min-width: 110px;"></td>
                            <td>{{ $prescription->issued_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>
                                <form id="prescription-update-{{ $prescription->prescription_id }}" method="POST" action="{{ route('vet.prescriptions.update', $prescription) }}" style="margin: 0;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-primary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align: center; color: #6b7280;">No e-prescriptions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
