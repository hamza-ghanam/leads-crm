@extends('layouts.app')

@section('title')
    Broker Details
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brokers.index') }}">Brokers</a></li>
    <li class="breadcrumb-item active">Broker Details</li>
@endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Please fix the following:</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $nationalityLabel = '-';
        if (!empty($broker->nationality)) {
            if (is_numeric($broker->nationality) && isset($countriesById)) {
                $nationalityLabel = $countriesById[(int)$broker->nationality] ?? $broker->nationality;
            } else {
                $nationalityLabel = $broker->nationality;
            }
        }

        $typeBadge   = $broker->type === 'Company' ? 'info' : 'secondary';
        $idTypeBadge = $broker->id_type === 'Passport' ? 'dark' : 'primary';

        $docs       = $broker->docs ?? collect();
        $docsByType = $docs->groupBy('doc_type');

        $docUrl = function ($path) {
            if (!$path) return null;
            return asset('storage/' . ltrim($path, '/'));
        };
    @endphp

    {{-- Action bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-tie mr-1 text-muted"></i>
            {{ $broker->full_name ?? 'Broker #' . $broker->id }}
        </h4>
        <div>
            <a href="{{ route('brokers.index') }}" class="btn btn-dark mr-2">
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
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #2d3955;">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-id-card mr-1"></i> Personal Information
                    </h3>
                    <div class="card-tools">
                        <small class="text-white-50">
                            Created: {{ $broker->created_at ? $broker->created_at->format('d/m/Y') : '-' }}
                            &nbsp;|&nbsp;
                            Updated: {{ $broker->updated_at ? $broker->updated_at->format('d/m/Y') : '-' }}
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge badge-{{ $typeBadge }} mr-1">{{ $broker->type ?? '-' }}</span>
                        <span class="badge badge-{{ $idTypeBadge }}">{{ $broker->id_type ?? '-' }}</span>
                    </div>

                    <table class="table table-bordered table-sm mb-0">
                        <tr>
                            <th style="width:30%;">Email</th>
                            <td>
                                @if($broker->email)
                                    <a href="mailto:{{ $broker->email }}">{{ $broker->email }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>
                                @if($broker->phone)
                                    <a href="tel:{{ $broker->phone }}">{{ $broker->phone }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td style="white-space: pre-wrap;">{{ $broker->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Nationality</th>
                            <td>{{ $nationalityLabel }}</td>
                        </tr>
                        <tr>
                            <th>ID Type</th>
                            <td>{{ $broker->id_type ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>ID Number</th>
                            <td>{{ $broker->id_number ?? '—' }}</td>
                        </tr>
                        @if(($broker->type ?? null) === 'Company')
                            <tr>
                                <th>Company Name</th>
                                <td>{{ $broker->company_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>License Number</th>
                                <td>{{ $broker->license_number ?? '—' }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: Documents --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #2d3955;">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-file-alt mr-1"></i> Documents
                    </h3>
                </div>
                <div class="card-body">
                    @if($docs->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            No documents uploaded for this broker.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Uploaded</th>
                                    <th style="width:80px;" class="text-center">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($docs as $doc)
                                    @php
                                        $url = $docUrl($doc->file_path);
                                        $docTypeLabel = match($doc->doc_type) {
                                            'trade_license' => 'Trade License',
                                            'id_document'   => 'ID / Passport',
                                            default         => ucfirst(str_replace('_', ' ', (string)$doc->doc_type)),
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light border">{{ $docTypeLabel }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $doc->created_at ? $doc->created_at->format('d/m/Y h:i A') : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($url)
                                                <a href="{{ route('brokers.docs.download', $doc->id) }}"
                                                   class="btn btn-sm btn-success" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
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
