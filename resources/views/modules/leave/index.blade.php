@extends('layouts.dashboard-modern')

@section('title', 'Leave Management')
@section('page_heading', 'Leave Management')

@section('content')
    <section class="sr-only">Leave Management</section>
    <section id="leave-management-root" data-payload='@json($pagePayload)'></section>
@endsection
