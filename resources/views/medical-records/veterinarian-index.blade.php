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
        $totalPrescriptionCount = $medicalRecords->sum(fn ($record) => $record->prescriptions->count());
        $scheduledFollowUps = $medicalRecords->filter(fn ($record) => $record->follow_up_date && $record->follow_up_date->isFuture())->count();
    @endphp

    <div class="records-layout">
        <section class="card">
            <div class="surface-header">
                <div>
                    <h2>Record List</h2>
                    <p class="section-copy">Search or filter records, then press View to open the report page.</p>
                </div>
            </div>

            <div class="record-filter-tools">
                <label class="field">
                    <span class="field-label">Search</span>
                    <input type="search" id="recordSearch" class="field-control" placeholder="Search patient, owner, vet, diagnosis">
                </label>

                <label class="field">
                    <span class="field-label">Species</span>
                    <select id="recordSpeciesFilter" class="field-control">
                        <option value="">All species</option>
                        @foreach ($medicalRecords->pluck('pet.species')->filter()->unique()->sort()->values() as $species)
                            <option value="{{ strtolower($species) }}">{{ $species }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="table-responsive">
                @if ($medicalRecords->isNotEmpty())
                    <table class="data-table" id="medicalRecordsTable">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Owner</th>
                                <th>Veterinarian</th>
                                <th>Diagnosis</th>
                                <th>Consultation Date</th>
                                <th>Follow-Up</th>
                                <th>Prescriptions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($medicalRecords as $record)
                                @php
                                    $pet = $record->pet;
                                    $owner = $pet?->owner;
                                    $veterinarian = $record->consultation?->veterinarian;
                                    $searchText = strtolower(implode(' ', [
                                        $pet?->name,
                                        $pet?->species,
                                        $pet?->breed,
                                        $owner?->name,
                                        $owner?->email,
                                        $veterinarian?->name,
                                        $record->diagnosis,
                                    ]));
                                @endphp

                                <tr
                                    data-search="{{ $searchText }}"
                                    data-species="{{ strtolower((string) $pet?->species) }}">
                                    <td class="col-patient">
                                        <strong>{{ $pet?->name ?? 'Unknown Pet' }}</strong>
                                        <span>{{ $pet?->species ?? 'Unknown species' }}{{ $pet?->breed ? ' / '.$pet->breed : '' }}</span>
                                    </td>
                                    <td class="col-owner">
                                        <strong>{{ $owner?->name ?? 'Unknown Owner' }}</strong>
                                        <span>{{ $owner?->contact_no ?? $owner?->phone ?? 'No contact recorded' }}</span>
                                    </td>
                                    <td class="col-vet">
                                        {{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Not assigned' }}
                                    </td>
                                    <td class="col-diagnosis">
                                        <small>{{ $record->diagnosis ?: 'No diagnosis recorded.' }}</small>
                                    </td>
                                    <td class="col-created">
                                        {{ $record->consultation?->consultation_date?->format('M j, Y') ?? $record->created_at?->format('M j, Y') ?? 'Unknown' }}
                                    </td>
                                    <td class="col-followup">
                                        {{ $record->follow_up_date?->format('M j, Y') ?? 'Optional' }}
                                    </td>
                                    <td class="col-prescriptions">
                                        <span class="pill {{ $record->prescriptions->count() > 0 ? 'pill-success' : 'pill-neutral' }}">
                                            {{ $record->prescriptions->count() }}
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <a href="{{ route('vet.medical-records.details', $record) }}" class="btn btn-sm btn-primary" title="Open Report">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="recordTableEmpty" class="empty-state" hidden>
                        No medical records match the current search or filter.
                    </div>
                @else
                    <div class="empty-state">
                        No medical records are available yet.
                    </div>
                @endif
            </div>
        </section>

    </div>
@endsection

@section('styles')
    <style>
        .records-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.4rem;
            align-items: stretch;
        }

        .record-filter-tools {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(150px, 0.65fr);
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .table-responsive,
        .mini-table-wrap {
            overflow: auto;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.92);
        }

        .table-responsive {
            max-height: 520px;
        }

        .data-table,
        .mini-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table {
            min-width: 980px;
        }

        .data-table th,
        .data-table td,
        .mini-table th,
        .mini-table td {
            padding: 1rem 1.05rem;
            border-bottom: 1px solid rgba(23, 49, 61, 0.08);
            text-align: left;
            vertical-align: top;
        }

        .data-table th,
        .mini-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
            color: var(--shell-muted);
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .data-table tr {
            transition: background 0.18s ease;
        }

        .data-table tbody tr:hover,
        .data-table tbody tr.selected-row {
            background: rgba(15, 139, 141, 0.08);
        }

        .data-table strong,
        .mini-table strong {
            display: block;
            color: var(--shell-ink);
        }

        .data-table span,
        .mini-table span {
            display: block;
            color: var(--shell-muted);
            font-size: 0.86rem;
            margin-top: 0.2rem;
        }

        .data-table .col-actions {
            text-align: center;
            min-width: 110px;
        }

        .report-card,
        .medical-report {
            background: rgba(255, 255, 255, 0.92);
        }

        .report-card {
            border-left: 5px solid rgba(15, 139, 141, 0.55);
        }

        .report-header,
        .report-section-head {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .report-section-head.compact {
            align-items: center;
        }

        .report-section-title {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--shell-line);
        }

        .report-section-title h3 {
            margin: 0.15rem 0 0;
            font-size: 1.35rem;
        }

        .report-stamp {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(15, 139, 141, 0.1);
            color: var(--shell-accent-deep);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .report-summary-grid,
        .report-form-grid,
        .adherence-report-grid {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .report-summary-grid {
            grid-template-columns: repeat(3, minmax(220px, 1fr));
        }

        .report-form-grid {
            grid-template-columns: repeat(2, minmax(260px, 1fr));
        }

        .adherence-report-grid {
            grid-template-columns: repeat(3, minmax(180px, 1fr));
        }

        .report-field {
            padding: 1.05rem 1.1rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(248, 250, 252, 0.86);
        }

        .report-field.wide {
            grid-column: 1 / -1;
        }

        .report-field p {
            margin: 0.35rem 0 0;
            color: #374151;
            line-height: 1.55;
            word-break: break-word;
        }

        .report-field strong {
            display: block;
            color: var(--shell-ink);
            font-size: 1.2rem;
        }

        .report-list {
            display: grid;
            gap: 1rem;
        }

        .report-subsection {
            margin-top: 1.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--shell-line);
        }

        .mini-table {
            min-width: 720px;
            font-size: 0.9rem;
        }

        .mini-table td:first-child {
            font-weight: 700;
            color: var(--shell-ink);
        }

        .pill-warning {
            background: rgba(183, 121, 31, 0.14);
            color: #8d5e12;
        }

        @media (max-width: 1100px) {
            .records-layout {
                grid-template-columns: 1fr;
            }

            .report-summary-grid,
            .report-form-grid,
            .adherence-report-grid,
            .record-filter-tools {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        const recordSearch = document.getElementById('recordSearch');
        const recordSpeciesFilter = document.getElementById('recordSpeciesFilter');
        const recordRows = Array.from(document.querySelectorAll('#medicalRecordsTable tbody tr'));
        const emptyState = document.getElementById('recordTableEmpty');

        function filterRecordTable() {
            const searchValue = (recordSearch?.value || '').trim().toLowerCase();
            const speciesValue = (recordSpeciesFilter?.value || '').trim().toLowerCase();
            let visibleRows = 0;

            recordRows.forEach((row) => {
                const matchesSearch = !searchValue || row.dataset.search.includes(searchValue);
                const matchesSpecies = !speciesValue || row.dataset.species === speciesValue;
                const isVisible = matchesSearch && matchesSpecies;

                row.hidden = !isVisible;
                if (isVisible) {
                    visibleRows += 1;
                }
            });

            if (emptyState) {
                emptyState.hidden = visibleRows !== 0;
            }
        }

        recordSearch?.addEventListener('input', filterRecordTable);
        recordSpeciesFilter?.addEventListener('change', filterRecordTable);
    </script>
@endsection
