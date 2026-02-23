@extends('layouts.dashboard-modern')

@section('title', 'Attendance')
@section('page_heading', 'Attendance')

@section('content')
    <div
        id="attendance-page-root"
        data-payload='@json(array_merge($payload, ['csrfToken' => csrf_token()]))'
    ></div>
@endsection
