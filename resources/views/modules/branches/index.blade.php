@extends('layouts.dashboard-modern')

@section('title', 'Branches')
@section('page_heading', 'Branches')

@section('content')
    <section id="branches-page-root" data-payload='@json($pagePayload)'></section>
@endsection
