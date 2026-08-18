@extends('layouts.app')

@section('title', 'Client Management')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')

<section class="hero-card">
    <div class="hero-row">
        <div>
            <h1 class="hero-title">Client Management</h1>
            <p class="hero-copy">View all pet owners registered in the system.</p>
        </div>

        <div class="action-row">
            <a href="{{ route('staff.register-client') }}" class="btn btn-primary">Register New Client</a>
        </div>
    </div>
</section>

<!-- CLIENTS TABLE -->
<section class="card">

    <div class="surface-header">

        <div style="
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        ">

            <div class="widget">
                <span class="widget-title">Total Clients</span>
                <strong class="widget-value">{{ $clients->total() }}</strong>
            </div>

        </div>

    </div>

    @if($clients->count() > 0)
        <div style="
    overflow-x: auto;overflow-y: auto;max-height: 500px;">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Name</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Email</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Contact</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Status</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;">
                                <strong>{{ $client->name }}</strong>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $client->email }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $client->contact_no ?? 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="
                                    padding: 0.25rem 0.75rem;
                                    border-radius: 9999px;
                                    font-size: 0.875rem;
                                    font-weight: 600;
                                    background: {{ $client->status === 'Active' ? '#d1fae5' : '#fed7aa' }};
                                    color: {{ $client->status === 'Active' ? '#065f46' : '#92400e' }};
                                ">
                                    {{ $client->status }}
                                </span>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $client->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div style="margin-top: 1rem;">
            {{ $clients->links() }}
        </div>
    @else
        <div style="padding: 2rem; text-align: center; background: #f9fafb; border-radius: 0.375rem;">
            <p style="color: #6b7280; margin: 0;">No clients found.</p>
        </div>
    @endif
</section>

@endsection
