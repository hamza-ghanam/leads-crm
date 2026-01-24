@extends('layouts.app')

@section('title')
    Broker Details
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brokers.index') }}">Brokers list</a></li>
    <li class="breadcrumb-item">Broker details</li>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="list-style: none;" class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // nationality may be stored as string OR as country_id (numeric)
        $nationalityLabel = '-';

        if (!empty($broker->nationality)) {
            if (is_numeric($broker->nationality ?? null) && isset($countriesById)) {
                $nationalityLabel = $countriesById[(int)$broker->nationality] ?? $broker->nationality;
            } else {
                $nationalityLabel = $broker->nationality;
            }
        }

        $typeBadge = $broker->type === 'Company' ? 'info' : 'secondary';
        $idTypeBadge = $broker->id_type === 'Passport' ? 'dark' : 'primary';

        // docs grouping
        $docs = $broker->docs ?? collect();
        $docsByType = $docs->groupBy('doc_type');

        // helper for file name
        $fileName = function ($path) {
            return $path ? basename($path) : '-';
        };

        // helper to build a public URL (works if stored on "public" disk, or you have a route)
        $docUrl = function ($path) {
            if (!$path) return null;

            // If you store files in storage/app/public, and you ran "php artisan storage:link"
            // paths like "docs/xyz.pdf" => /storage/docs/xyz.pdf
            return asset('storage/' . ltrim($path, '/'));
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"></h3>

        <div class="d-flex gap-2">
            <a href="{{ route('brokers.index') }}" class="btn btn-dark">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            @can('update', $broker)
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- LEFT: Basic details --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h4 class="mb-1">{{ $broker->full_name ?? '-' }}</h4>
                            <div class="text-muted">
                                <span class="badge badge-{{ $typeBadge }}">{{ $broker->type ?? '-' }}</span>
                                <span class="badge badge-{{ $idTypeBadge }}">{{ $broker->id_type ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="text-right text-muted" style="font-size: 0.9rem;">
                            <div><b>Created:</b> {{ $broker->created_at ? $broker->created_at->format('d/m/Y h:i A') : '-' }}</div>
                            <div><b>Updated:</b> {{ $broker->updated_at ? $broker->updated_at->format('d/m/Y h:i A') : '-' }}</div>
                        </div>
                    </div>

                    <table class="table table-bordered table-sm mb-0">
                        <tr>
                            <th style="width: 30%;">Email</th>
                            <td>{{ $broker->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $broker->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td style="white-space: pre-wrap;">{{ $broker->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Nationality</th>
                            <td>{{ $nationalityLabel }}</td>
                        </tr>
                        <tr>
                            <th>ID Type</th>
                            <td>{{ $broker->id_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>ID Number</th>
                            <td>{{ $broker->id_number ?? '-' }}</td>
                        </tr>

                        @if(($broker->type ?? null) === 'Company')
                            <tr>
                                <th>Company Name</th>
                                <td>{{ $broker->company_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>License Number</th>
                                <td>{{ $broker->license_number ?? '-' }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: Documents --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Broker Documents</h4>

                    @if($docs->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No documents uploaded for this broker.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-sm mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 18%;">Type</th>
                                    <th style="width: 22%;">Uploaded</th>
                                    <th style="width: 18%;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($docs as $doc)
                                    @php
                                        $url = $docUrl($doc->file_path);
                                        $docTypeLabel = match($doc->doc_type) {
                                            'trade_license' => 'Trade License',
                                            'id_document' => 'ID / Passport',
                                            default => ucfirst(str_replace('_', ' ', (string)$doc->doc_type)),
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light" style="font-size: 16px;">{{ $docTypeLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $doc->created_at ? $doc->created_at->format('d/m/Y h:i A') : '-' }}
                                        </td>
                                        <td>
                                            <div class="d-flex" style="gap:8px;">
                                                @if($url)
                                                    <a href="{{ route('brokers.docs.download', $doc->id) }}"
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @else
                                                    {{-- If you have a download route, use it instead:
                                                         route('brokers.docs.download', $doc->id)
                                                     --}}
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>


        </div>
    </div>
@endsection
