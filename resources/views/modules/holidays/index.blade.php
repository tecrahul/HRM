@extends('layouts.dashboard-modern')

@section('title', 'Holidays')
@section('page_heading', 'Holidays')

@section('content')
    <section id="holidays-page-root" data-payload='@json($pagePayload)'></section>
@endsection
