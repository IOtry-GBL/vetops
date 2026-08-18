@extends('layouts.app')

@section('title', 'Staff Medical Records')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    @php
        $pageRecords = $medicalRecords->getCollection();
        $patientCount = $pageRecords->pluck('pet.pet_id')->filter()->unique()->count();
        $linkedPrescriptionCount = $pageRecords->sum(fn ($record) => $record->prescriptions->count());
        $scheduledFollowUps = $pageRecords->filter(fn ($record) => $record->follow_up_date && $record->follow_up_date->isFuture())->count();
    @endphp

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Filter Medical Records</h2>
                <p class="section-copy">Search by patient or species and narrow the record timeline when needed.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('staff.medical-records') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="search">Patient Or Species</label>
                <input id="search" name="search" type="text" class="field-control" value="{{ request('search') }}" placeholder="Peanut or Dog">
            </div>

            <div class="field">
                <label class="field-label" for="from_date">Created From</label>
                <input id="from_date" name="from_date" type="date" class="field-control" value="{{ request('from_date') }}">
            </div>

            <div class="field">
                <label class="field-label" for="to_date">Created To</label>
                <input id="to_date" name="to_date" type="date" class="field-control" value="{{ request('to_date') }}">
            </div>

            <div class="action-row" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('staff.medical-records') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Record List</h2>
                <p class="section-copy">Open a record to view consultation notes, follow-up dates, and linked prescriptions.</p>
            </div>
        </div>

        <div class="table-responsive">
            @forelse ($medicalRecords as $medicalRecord)
                @php
                    $pet = $medicalRecord->pet;
                    $owner = $pet?->owner;
                    $veterinarian = $medicalRecord->consultation?->veterinarian;
                    $latestPrescription = $medicalRecord->prescriptions->sortByDesc('issued_at')->first();
                @endphp
                @if ($loop->first)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Owner</th>
                                <th>Veterinarian</th>
                                <th>Diagnosis</th>
                                <th>Created</th>
                                <th>Follow-Up</th>
                                <th>Prescriptions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                @if ($loop->iteration <= 5)
                            <tr>
                                <td class="col-patient">
                                    <div><strong>{{ $pet?->name ?? 'Unknown Pet' }}</strong></div>
                                    <div class="text-secondary" style="font-size: 0.875rem;">
                                        {{ $pet?->species ?? 'Unknown species' }}{{ $pet?->breed ? ' - '.$pet->breed : '' }}
                                    </div>
                                </td>
                                <td class="col-owner">
                                    {{ $owner?->name ?? 'Unknown Owner' }}
                                </td>
                                <td class="col-vet">
                                    {{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Not assigned' }}
                                </td>
                                <td class="col-diagnosis">
                                    <small>{{ $medicalRecord->diagnosis ?: 'No diagnosis recorded.' }}</small>
                                </td>
                                <td class="col-created">
                                    {{ $medicalRecord->created_at?->format('M j, Y') ?? 'Unknown' }}
                                </td>
                                <td class="col-followup">
                                    {{ $medicalRecord->follow_up_date?->format('M j, Y') ?? 'Not scheduled' }}
                                </td>
                                <td class="col-prescriptions">
                                    <span class="pill {{ $medicalRecord->prescriptions->count() > 0 ? 'pill-success' : 'pill-neutral' }}">
                                        {{ $medicalRecord->prescriptions->count() }}
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <a href="{{ route('staff.medical-records.details', $medicalRecord->record_id) }}" class="btn btn-sm btn-primary" title="Open Record">View</a>
                                        @if ($latestPrescription)
                                            <a href="{{ route('staff.prescriptions.details', $latestPrescription->prescription_id) }}" class="btn btn-sm btn-secondary" title="Latest Prescription">Rx</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                @endif
                @if ($loop->last)
                        </tbody>
                    </table>
                @endif
            @empty
                <div class="empty-state">
                    No medical records matched the current filters.
                </div>
            @endforelse
        </div>
    </section>

    @if ($medicalRecords->hasPages())
        <section class="card">
            {{ $medicalRecords->withQueryString()->links() }}
        </section>
    @endif
@endsection

@section('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--shell-line, #e0e0e0);
            border-radius: var(--shell-radius-lg, 0.5rem);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9375rem;
        }

        .data-table thead {
            background: var(--shell-surface-secondary, #f5f5f5);
            border-bottom: 2px solid var(--shell-line, #e0e0e0);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th {
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary, #333);
            white-space: nowrap;
        }

        .data-table th:last-child {
            text-align: center;
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--shell-line, #e0e0e0);
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .data-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .data-table .col-patient {
            font-weight: 500;
            min-width: 140px;
        }

        .data-table .col-patient .text-secondary {
            color: var(--text-secondary, #666);
            margin-top: 0.25rem;
        }

        .data-table .col-owner,
        .data-table .col-vet {
            min-width: 130px;
        }

        .data-table .col-diagnosis {
            min-width: 150px;
            color: var(--text-secondary, #666);
        }

        .data-table .col-created,
        .data-table .col-followup {
            min-width: 100px;
        }

        .data-table .col-prescriptions {
            text-align: center;
            min-width: 90px;
        }

        .data-table .col-actions {
            text-align: center;
            min-width: 140px;
            padding: 1rem 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
        }

        .pill {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .pill-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .pill-neutral {
            background-color: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        @media (max-width: 1024px) {
            .data-table {
                font-size: 0.875rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.625rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-sm {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .data-table {
                font-size: 0.8125rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.625rem;
            }

            .data-table .col-diagnosis,
            .data-table .col-followup {
                display: none;
            }

            .action-buttons {
                flex-direction: row;
                gap: 0.25rem;
            }

            .btn-sm {
                width: auto;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
@endsection
