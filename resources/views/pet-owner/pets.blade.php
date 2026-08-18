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
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>My Pets</h1>
        <a href="{{ route('pet-owner.pets.create') }}" class="btn btn-primary">+ Add New Pet</a>
    </div>
</div>

<div class="card">
    <table id="petsTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th style="text-align:left;">Name</th>
                <th style="text-align:left;">Species</th>
                <th style="text-align:left;">Breed</th>
                <th style="text-align:center;">Age</th>
                <th style="text-align:center;">Weight</th>
                <th style="text-align:center;">Sex</th>
                <th style="text-align:center;">Visits</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse($pets as $pet)
                <tr>
                    <td style="text-align:left; font-weight:500;">
                        {{ $pet->name }}
                    </td>

                    <td style="text-align:left;">
                        {{ $pet->species }}
                    </td>

                    <td style="text-align:left;">
                        {{ $pet->breed }}
                    </td>

                    <td style="text-align:center;">
                        {{ $pet->age }} yrs
                    </td>

                    <td style="text-align:center;">
                        {{ $pet->weight }} kg
                    </td>

                    <td style="text-align:center;">
                        {{ ucfirst($pet->sex) }}
                    </td>

                    <td style="text-align:center;">
                        {{ $pet->appointments->whereIn('status', ['Approved', 'Completed'])->count() }}
                    </td>

                    <td style="text-align:right;">
                        <a href="{{ route('pet-owner.pets.edit', $pet->id) }}"
                           class="btn btn-secondary"
                           style="padding:0.35rem 0.75rem; font-size:0.8rem;">
                            Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:2rem;">
                        No pets found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection


@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
    /* Better spacing */
    #petsTable {
        width: 100%;
        border-collapse: collapse;
    }

    #petsTable th,
    #petsTable td {
        padding: 12px 10px;
        vertical-align: middle;
    }

    /* Alignment rules */
    #petsTable th:nth-child(-n+3),
    #petsTable td:nth-child(-n+3) {
        text-align: left;
    }

    #petsTable th:nth-child(n+4):nth-child(-n+7),
    #petsTable td:nth-child(n+4):nth-child(-n+7) {
        text-align: center;
    }

    #petsTable th:last-child,
    #petsTable td:last-child {
        text-align: right;
        white-space: nowrap;
    }

    /* Clean button spacing */
    .btn {
        display: inline-block;
        text-decoration: none;
    }
</style>
@endpush


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        $('#petsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']]
        });
    });
</script>
@endpush