@extends('layouts.dashboard-modern')

@section('title', 'Edit Attendance')
@section('page_heading', 'Edit Attendance')

@section('content')
    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Update Attendance Record</h3>
                <p class="ui-section-subtitle">Correct employee attendance details and mark status.</p>
            </div>
            <a href="{{ route('modules.attendance.overview') }}" class="ui-btn ui-btn-ghost">Back</a>
        </div>

        <form method="POST" action="{{ route('modules.attendance.update', $attendance) }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div>
                <label for="attendance_edit_user_id" class="ui-kpi-label block mb-2">Employee</label>
                <div
                    data-employee-autocomplete-root
                    data-api-url="{{ route('api.employees.search') }}"
                    data-name="user_id"
                    data-input-id="attendance_edit_user_id"
                    data-required="true"
                    data-selected='@json($selectedEmployee)'
                ></div>
                @error('user_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="attendance_date" class="ui-kpi-label block mb-2">Attendance Date</label>
                <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', $attendance->attendance_date?->format('Y-m-d')) }}" class="ui-input">
                @error('attendance_date')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="ui-kpi-label block mb-2">Status</label>
                <select id="status" name="status" class="ui-select">
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ old('status', $attendance->status) === $statusOption ? 'selected' : '' }}>
                            {{ str($statusOption)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="check_in_time" class="ui-kpi-label block mb-2">Check In</label>
                    <input id="check_in_time" name="check_in_time" type="time" value="{{ old('check_in_time', $attendance->check_in_at?->format('H:i')) }}" class="ui-input">
                    @error('check_in_time')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="check_out_time" class="ui-kpi-label block mb-2">Check Out</label>
                    <input id="check_out_time" name="check_out_time" type="time" value="{{ old('check_out_time', $attendance->check_out_at?->format('H:i')) }}" class="ui-input">
                    @error('check_out_time')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="ui-kpi-label block mb-2">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="ui-textarea resize-y">{{ old('notes', $attendance->notes) }}</textarea>
                @error('notes')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Update Attendance</button>
                <a href="{{ route('modules.attendance.overview') }}" class="ui-btn ui-btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
@endsection
