@extends('layouts.dashboard-modern')

@section('title', 'Departments')
@section('page_heading', 'Departments')

@section('content')
    <section id="departments-page-root" data-payload='@json($pagePayload)'></section>
@endsection
