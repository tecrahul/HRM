@extends('layouts.dashboard-modern')

@section('title', 'Communication')
@section('page_heading', 'Communication')

@push('head')
    <style>
        .comm-filter-shell {
            margin-top: 16px;
            border: 1px solid var(--hr-line);
            border-radius: 16px;
            padding: 12px;
            background:
                linear-gradient(165deg, var(--hr-accent-soft), transparent 56%),
                var(--hr-surface-strong);
        }

        .comm-filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .comm-filter-row + .comm-filter-row {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed var(--hr-line);
        }

        .comm-filter-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--hr-text-muted);
            min-width: 68px;
        }

        .comm-filter-group {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .comm-filter-pill {
            border-radius: 999px;
            border: 1px solid var(--hr-line);
            padding: 7px 12px;
            font-size: 12px;
            line-height: 1;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--hr-text-main);
            background: var(--hr-surface);
            transition: all 160ms ease;
        }

        .comm-filter-pill:hover {
            transform: translateY(-1px);
            border-color: var(--hr-accent-border);
            background: var(--hr-accent-soft);
        }

        .comm-filter-pill.is-active {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(120deg, #7c3aed, #ec4899);
            box-shadow: 0 16px 28px -22px rgb(124 58 237 / 0.9);
        }

        .comm-filter-count {
            border-radius: 999px;
            border: 1px solid var(--hr-line);
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            background: var(--hr-surface-strong);
            color: var(--hr-text-main);
        }

        .comm-filter-pill.is-active .comm-filter-count {
            color: #fff;
            border-color: rgb(255 255 255 / 0.35);
            background: rgb(255 255 255 / 0.2);
        }

        @media (max-width: 768px) {
            .comm-filter-label {
                min-width: auto;
                width: 100%;
            }
        }

        /* Inline attachment preview */
        .comm-attachment-preview {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 260ms ease, opacity 220ms ease;
        }

        .comm-attachment-preview.is-open {
            opacity: 1;
        }

        .comm-attachment-surface {
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            border-radius: 12px;
            padding: 10px;
        }

        .comm-attachment-image {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 10px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface);
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $inboxCount = (int) ($stats['inbox'] ?? 0);
        $sentCount = (int) ($stats['sent'] ?? 0);
        $unreadCount = (int) ($stats['unread'] ?? 0);
        $readCount = max($inboxCount - $unreadCount, 0);
        $unreadRatio = $inboxCount > 0 ? min((int) round(($unreadCount / $inboxCount) * 100), 100) : 0;
    @endphp

    <section class="ui-kpi-grid is-3">
        <article class="ui-kpi-card" style="background: linear-gradient(145deg, var(--hr-surface-strong), var(--hr-surface));">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Inbox</p>
                    <p class="ui-kpi-value">{{ number_format($inboxCount) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-6l-2 3h-4l-2-3H2"></path>
                        <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 rounded-xl border px-3 py-2.5" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="text-xs font-semibold">Unread pending: {{ number_format($unreadCount) }}</p>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Direct and broadcast messages in your inbox.</p>
            </div>
        </article>
        <article class="ui-kpi-card" style="background: linear-gradient(145deg, var(--hr-surface-strong), var(--hr-surface));">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Sent</p>
                    <p class="ui-kpi-value">{{ number_format($sentCount) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m22 2-7 20-4-9-9-4Z"></path>
                        <path d="M22 2 11 13"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 rounded-xl border px-3 py-2.5" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="text-xs font-semibold">Read in inbox: {{ number_format($readCount) }}</p>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Messages in your inbox already marked as read.</p>
            </div>
        </article>
        <article class="ui-kpi-card" style="background: linear-gradient(145deg, var(--hr-surface-strong), var(--hr-surface));">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Unread</p>
                    <p class="ui-kpi-value">{{ number_format($unreadCount) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </span>
            </div>
            <div class="mt-3 rounded-xl border px-3 py-2.5" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="h-1.5 rounded-full" style="background: var(--hr-line);">
                    <span class="block h-full rounded-full" style="width: {{ $unreadRatio }}%; background: #f59e0b;"></span>
                </div>
                <p class="text-xs mt-2 font-semibold">
                    Unread ratio: {{ $unreadRatio }}%
                </p>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                    {{ $unreadCount > 0 ? 'Pending responses need attention.' : 'All caught up.' }}
                </p>
            </div>
        </article>
    </section>

    @php
        $hasBroadcastTab = $canBroadcastAll || $canBroadcastTeam;
        $actionTab = (string) old('communication_action_tab', 'compose');
        if (! in_array($actionTab, ['compose', 'broadcast'], true)) {
            $actionTab = 'compose';
        }
        if (! $hasBroadcastTab) {
            $actionTab = 'compose';
        }
        $composeHasErrors = $errors->has('receiver_id')
            || $errors->has('message')
            || $errors->has('attachments')
            || $errors->has('attachments.*');
        $showMessageActions = old('communication_action_tab') !== null || $composeHasErrors;
    @endphp

    <section id="communicationActionTabs" class="ui-section {{ $showMessageActions ? '' : 'hidden' }}">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Message Actions</h3>
                <p class="ui-section-subtitle">Compose direct messages or send role-based broadcasts.</p>
            </div>
        </div>

        <div class="mt-4 inline-flex rounded-xl border p-1 gap-1" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
            <button
                type="button"
                data-comm-tab-trigger="compose"
                class="ui-btn {{ $actionTab === 'compose' ? 'ui-btn-primary' : 'ui-btn-ghost' }}"
                aria-selected="{{ $actionTab === 'compose' ? 'true' : 'false' }}"
            >
                Compose Message
            </button>
            @if ($hasBroadcastTab)
                <button
                    type="button"
                    data-comm-tab-trigger="broadcast"
                    class="ui-btn {{ $actionTab === 'broadcast' ? 'ui-btn-primary' : 'ui-btn-ghost' }}"
                    aria-selected="{{ $actionTab === 'broadcast' ? 'true' : 'false' }}"
                >
                    Broadcast
                </button>
            @endif
        </div>

        <div class="mt-4">
            <div data-comm-tab-panel="compose" class="{{ $actionTab === 'compose' ? '' : 'hidden' }}">
                @if ($directRecipients->isEmpty())
                    <p class="ui-empty">No allowed recipients available for your role.</p>
                @else
                    <p class="text-xs font-semibold" style="color: var(--hr-text-muted);">Quickly send direct messages when needed.</p>
                    <div class="rounded-xl border p-4 mt-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                        <form method="POST" action="{{ route('modules.communication.direct.send') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @csrf
                            <input type="hidden" name="communication_action_tab" value="compose">
                            <div>
                                <label class="ui-kpi-label block mb-2" for="direct_receiver_id">Recipient</label>
                                <select id="direct_receiver_id" name="receiver_id" class="ui-select">
                                    <option value="">Select recipient</option>
                                    @foreach($directRecipients as $recipient)
                                        <option value="{{ $recipient->id }}" @selected((string) old('receiver_id') === (string) $recipient->id)>
                                            {{ $recipient->name }} ({{ $recipient->role instanceof \App\Enums\UserRole ? $recipient->role->label() : ucfirst((string) $recipient->role) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('receiver_id')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="ui-kpi-label block mb-2" for="direct_attachments">Attachments</label>
                                <input id="direct_attachments" name="attachments[]" type="file" multiple class="ui-input">
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Up to 5 files, max 5MB each.</p>
                                @error('attachments')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                @error('attachments.*')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="ui-kpi-label block mb-2" for="direct_message">Message</label>
                                <textarea id="direct_message" name="message" rows="4" class="ui-textarea" placeholder="Write your message...">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <button type="submit" class="ui-btn ui-btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            @if ($hasBroadcastTab)
                <div data-comm-tab-panel="broadcast" class="{{ $actionTab === 'broadcast' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-4">
                        @if ($canBroadcastAll)
                            @php
                                $selectedBroadcastEmployees = collect((array) old('employee_ids', []))
                                    ->map(static fn (mixed $id): string => (string) $id)
                                    ->values();
                                $selectedBranch = (string) old('target_branch', 'all');
                                $selectedTeam = (string) old('target_team', 'all');
                            @endphp
                            <article class="rounded-xl border p-4 xl:col-span-2 2xl:col-span-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <p class="font-semibold">Unified Broadcast</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                                    One form to broadcast to all, by branch, by team, or selected employees.
                                </p>
                                <form method="POST" action="{{ route('modules.communication.broadcast.send') }}" enctype="multipart/form-data" class="mt-4 space-y-4" data-broadcast-audience-root>
                                    @csrf
                                    <input type="hidden" name="communication_action_tab" value="broadcast">
                                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                        <div class="rounded-xl border p-4 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                            <div>
                                                <label class="ui-kpi-label block mb-2" for="target_branch">Target Branch</label>
                                                <select id="target_branch" name="target_branch" class="ui-select">
                                                    <option value="all" @selected($selectedBranch === 'all')>All</option>
                                                    @foreach($broadcastTargetBranchOptions as $branchOption)
                                                        <option value="{{ $branchOption }}" @selected($selectedBranch === (string) $branchOption)>{{ $branchOption }}</option>
                                                    @endforeach
                                                </select>
                                                @error('target_branch')
                                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="ui-kpi-label block mb-2" for="target_team">Target Team (Department)</label>
                                                <select id="target_team" name="target_team" class="ui-select" data-team-select>
                                                    <option value="all" @selected($selectedTeam === 'all')>All</option>
                                                    @foreach($broadcastTargetTeamOptions as $teamOption)
                                                        <option value="{{ $teamOption }}" @selected($selectedTeam === (string) $teamOption)>{{ $teamOption }}</option>
                                                    @endforeach
                                                </select>
                                                @error('target_team')
                                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="pt-1">
                                                <p class="text-xs font-semibold" style="color: var(--hr-text-muted);">
                                                    Filter employees by branch/team, then select audience.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                            <div class="mb-2 flex items-center justify-between gap-3 flex-wrap">
                                                <label class="ui-kpi-label block" for="target_employee_ids">Target Employees</label>
                                                <label class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-semibold" style="border-color: var(--hr-accent-border); background: var(--hr-accent-soft); color: var(--hr-text-main);">
                                                    <input type="checkbox" id="broadcast_select_all_visible" class="rounded border-gray-300">
                                                    Select All Visible Employees
                                                </label>
                                            </div>
                                            <select id="target_employee_ids" name="employee_ids[]" multiple class="ui-select" size="12" data-employee-select>
                                                @foreach($broadcastTargetEmployees as $employeeRecipient)
                                                    @php
                                                        $employeeIdValue = (string) $employeeRecipient->id;
                                                        $employeeRoleLabel = $employeeRecipient->role instanceof \App\Enums\UserRole
                                                            ? $employeeRecipient->role->label()
                                                            : ucfirst((string) $employeeRecipient->role);
                                                        $employeeBranch = trim((string) ($employeeRecipient->profile?->branch ?? ''));
                                                        $employeeTeam = trim((string) ($employeeRecipient->profile?->department ?? ''));
                                                    @endphp
                                                    <option
                                                        value="{{ $employeeIdValue }}"
                                                        data-branch="{{ $employeeBranch }}"
                                                        data-team="{{ $employeeTeam }}"
                                                        @selected($selectedBroadcastEmployees->contains($employeeIdValue))
                                                    >
                                                        {{ $employeeRecipient->name }} ({{ $employeeRoleLabel }}) - {{ $employeeBranch !== '' ? $employeeBranch : 'No Branch' }} / {{ $employeeTeam !== '' ? $employeeTeam : 'No Team' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p id="broadcast_visible_meta" class="text-xs mt-2 font-semibold" style="color: var(--hr-text-main);">
                                                0/0 selected in current filter.
                                            </p>
                                            <p class="text-xs mt-2" style="color: var(--hr-text-muted);">
                                                Click any employee to toggle selection. Optional: leave unselected to send to all employees from current filters.
                                            </p>
                                            @error('employee_ids')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                            @error('employee_ids.*')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="ui-kpi-label block mb-2" for="broadcast_subject">Subject</label>
                                            <input id="broadcast_subject" name="subject" type="text" class="ui-input" value="{{ old('subject') }}" placeholder="Company update">
                                        </div>
                                        <div>
                                            <label class="ui-kpi-label block mb-2" for="broadcast_attachments">Attachments</label>
                                            <input id="broadcast_attachments" name="attachments[]" type="file" multiple class="ui-input">
                                            @error('attachments')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                            @error('attachments.*')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="ui-kpi-label block mb-2" for="broadcast_message">Message</label>
                                            <textarea id="broadcast_message" name="message" rows="5" class="ui-textarea" placeholder="Broadcast message...">{{ old('message') }}</textarea>
                                            @error('message')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <button type="submit" class="ui-btn ui-btn-primary">Send Broadcast</button>
                                </form>
                            </article>
                        @endif

                        @if ($canBroadcastTeam)
                            <article class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <p class="font-semibold">Broadcast to My Team</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                                    Available for supervisors. Team size: {{ $teamRecipients->count() }}
                                </p>
                                <form method="POST" action="{{ route('modules.communication.broadcast.team') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                    @csrf
                                    <input type="hidden" name="communication_action_tab" value="broadcast">
                                    <div>
                                        <label class="ui-kpi-label block mb-2" for="broadcast_team_subject">Subject</label>
                                        <input id="broadcast_team_subject" name="subject" type="text" class="ui-input" value="{{ old('subject') }}" placeholder="Team update">
                                    </div>
                                    <div>
                                        <label class="ui-kpi-label block mb-2" for="broadcast_team_message">Message</label>
                                        <textarea id="broadcast_team_message" name="message" rows="4" class="ui-textarea" placeholder="Team broadcast message...">{{ old('message') }}</textarea>
                                    </div>
                                    <div>
                                        <label class="ui-kpi-label block mb-2" for="broadcast_team_attachments">Attachments</label>
                                        <input id="broadcast_team_attachments" name="attachments[]" type="file" multiple class="ui-input">
                                    </div>
                                    <button type="submit" class="ui-btn ui-btn-primary">Send Team Broadcast</button>
                                </form>
                            </article>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="ui-section">
        @php
            $statusFilterValue = (string) ($statusFilter ?? 'all');
            $activeStatusFilter = in_array($statusFilterValue, ['all', 'read', 'unread'], true)
                ? $statusFilterValue
                : 'all';
            $messageCounts = is_array($messageFilterCounts ?? null) ? $messageFilterCounts : [];
            $inboxFilterCounts = is_array($messageCounts['inbox'] ?? null) ? $messageCounts['inbox'] : [];
            $sentFilterCounts = is_array($messageCounts['sent'] ?? null) ? $messageCounts['sent'] : [];
            $activeTabCounts = $tab === 'sent' ? $sentFilterCounts : $inboxFilterCounts;
        @endphp
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Message Box</h3>
                <p class="ui-section-subtitle">Track incoming and sent communication records.</p>
            </div>
            <button type="button" class="ui-btn ui-btn-primary" data-message-box-compose-trigger>
                Compose Message
            </button>
        </div>

        <div class="comm-filter-shell">
            <div class="comm-filter-row">
                <p class="comm-filter-label">Mailbox</p>
                <div class="comm-filter-group">
                    <a
                        href="{{ route('modules.communication.index', ['tab' => 'inbox', 'status' => $activeStatusFilter]) }}"
                        class="comm-filter-pill {{ $tab === 'inbox' ? 'is-active' : '' }}"
                        @if ($tab === 'inbox') aria-current="page" @endif
                    >
                        Inbox
                        <span class="comm-filter-count">{{ number_format((int) ($inboxFilterCounts['all'] ?? 0)) }}</span>
                    </a>
                    <a
                        href="{{ route('modules.communication.index', ['tab' => 'sent', 'status' => $activeStatusFilter]) }}"
                        class="comm-filter-pill {{ $tab === 'sent' ? 'is-active' : '' }}"
                        @if ($tab === 'sent') aria-current="page" @endif
                    >
                        Sent
                        <span class="comm-filter-count">{{ number_format((int) ($sentFilterCounts['all'] ?? 0)) }}</span>
                    </a>
                </div>
            </div>

            <div class="comm-filter-row">
                <p class="comm-filter-label">Status</p>
                <div class="comm-filter-group">
                    <a
                        href="{{ route('modules.communication.index', ['tab' => $tab, 'status' => 'all']) }}"
                        class="comm-filter-pill {{ $activeStatusFilter === 'all' ? 'is-active' : '' }}"
                        @if ($activeStatusFilter === 'all') aria-current="page" @endif
                    >
                        All
                        <span class="comm-filter-count">{{ number_format((int) ($activeTabCounts['all'] ?? 0)) }}</span>
                    </a>
                    <a
                        href="{{ route('modules.communication.index', ['tab' => $tab, 'status' => 'read']) }}"
                        class="comm-filter-pill {{ $activeStatusFilter === 'read' ? 'is-active' : '' }}"
                        @if ($activeStatusFilter === 'read') aria-current="page" @endif
                    >
                        Read
                        <span class="comm-filter-count">{{ number_format((int) ($activeTabCounts['read'] ?? 0)) }}</span>
                    </a>
                    <a
                        href="{{ route('modules.communication.index', ['tab' => $tab, 'status' => 'unread']) }}"
                        class="comm-filter-pill {{ $activeStatusFilter === 'unread' ? 'is-active' : '' }}"
                        @if ($activeStatusFilter === 'unread') aria-current="page" @endif
                    >
                        Unread
                        <span class="comm-filter-count">{{ number_format((int) ($activeTabCounts['unread'] ?? 0)) }}</span>
                    </a>
                </div>
            </div>
        </div>

        @if ($tab === 'inbox')
            <div class="mt-4 space-y-3">
                @forelse($inboxMessages as $messageRow)
                    <article class="rounded-xl border p-4 {{ ! $messageRow->read_status ? 'border-amber-300' : '' }}" style="background: var(--hr-surface-strong); border-color: var(--hr-line);">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $messageRow->sender?->name ?? 'Unknown Sender' }}</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                                    {{ str($messageRow->conversation?->type ?? 'direct')->replace('_', ' ')->title() }}
                                    • {{ $messageRow->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                </p>
                            </div>
                            <span class="ui-status-chip {{ $messageRow->read_status ? 'ui-status-slate' : 'ui-status-amber' }}">
                                {{ $messageRow->read_status ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <p class="text-sm mt-2" style="white-space: pre-line;">{{ $messageRow->message }}</p>
                        @if (is_array($messageRow->attachments) && $messageRow->attachments !== [])
                            <div class="mt-2 flex flex-col gap-2">
                                @foreach($messageRow->attachments as $attachmentPath)
                                    @php
                                        $attachmentUrl = str_starts_with((string) $attachmentPath, 'http') ? (string) $attachmentPath : asset((string) $attachmentPath);
                                    @endphp
                                    <div>
                                        <button type="button" class="ui-btn ui-btn-ghost comm-attachment-trigger" data-attachment-url="{{ $attachmentUrl }}">
                                            Attachment
                                        </button>
                                        <div class="comm-attachment-preview mt-2" aria-hidden="true"></div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (! $messageRow->read_status)
                            <form method="POST" action="{{ route('modules.communication.messages.read', $messageRow) }}" class="mt-3">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="ui-btn ui-btn-primary">Mark Read</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="ui-empty">
                        {{ $activeStatusFilter === 'all' ? 'No inbox messages found.' : 'No '.str($activeStatusFilter)->lower().' inbox messages found.' }}
                    </p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $inboxMessages->links() }}
            </div>
        @else
            <div class="mt-4 space-y-3">
                @forelse($sentMessages as $messageRow)
                    <article class="rounded-xl border p-4" style="background: var(--hr-surface-strong); border-color: var(--hr-line);">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">To: {{ $messageRow->receiver?->name ?? 'Unknown Recipient' }}</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                                    {{ str($messageRow->conversation?->type ?? 'direct')->replace('_', ' ')->title() }}
                                    • {{ $messageRow->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                </p>
                            </div>
                            <span class="ui-status-chip {{ $messageRow->read_status ? 'ui-status-green' : 'ui-status-amber' }}">
                                {{ $messageRow->read_status ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <p class="text-sm mt-2" style="white-space: pre-line;">{{ $messageRow->message }}</p>
                        @if (is_array($messageRow->attachments) && $messageRow->attachments !== [])
                            <div class="mt-2 flex flex-col gap-2">
                                @foreach($messageRow->attachments as $attachmentPath)
                                    @php
                                        $attachmentUrl = str_starts_with((string) $attachmentPath, 'http') ? (string) $attachmentPath : asset((string) $attachmentPath);
                                    @endphp
                                    <div>
                                        <button type="button" class="ui-btn ui-btn-ghost comm-attachment-trigger" data-attachment-url="{{ $attachmentUrl }}">
                                            Attachment
                                        </button>
                                        <div class="comm-attachment-preview mt-2" aria-hidden="true"></div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="ui-empty">
                        {{ $activeStatusFilter === 'all' ? 'No sent messages found.' : 'No '.str($activeStatusFilter)->lower().' sent messages found.' }}
                    </p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $sentMessages->links() }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.getElementById("communicationActionTabs");
            if (!root) {
                return;
            }

            const triggers = Array.from(root.querySelectorAll("[data-comm-tab-trigger]"));
            const panels = Array.from(root.querySelectorAll("[data-comm-tab-panel]"));
            const composeReceiverSelect = root.querySelector("#direct_receiver_id");
            const messageBoxComposeTrigger = document.querySelector("[data-message-box-compose-trigger]");

            const setTab = (tabName) => {
                triggers.forEach((trigger) => {
                    const isActive = trigger.getAttribute("data-comm-tab-trigger") === tabName;
                    trigger.classList.toggle("ui-btn-primary", isActive);
                    trigger.classList.toggle("ui-btn-ghost", !isActive);
                    trigger.setAttribute("aria-selected", isActive ? "true" : "false");
                });

                panels.forEach((panel) => {
                    const isActive = panel.getAttribute("data-comm-tab-panel") === tabName;
                    panel.classList.toggle("hidden", !isActive);
                });
            };

            triggers.forEach((trigger) => {
                trigger.addEventListener("click", () => {
                    const tabName = trigger.getAttribute("data-comm-tab-trigger") ?? "compose";
                    setTab(tabName);
                });
            });

            if (messageBoxComposeTrigger instanceof HTMLButtonElement) {
                messageBoxComposeTrigger.addEventListener("click", () => {
                    root.classList.remove("hidden");
                    setTab("compose");
                    if (composeReceiverSelect instanceof HTMLSelectElement) {
                        composeReceiverSelect.focus();
                    }
                    root.scrollIntoView({ behavior: "smooth", block: "start" });
                });
            }

            const audienceRoot = root.querySelector("[data-broadcast-audience-root]");
            const branchSelect = audienceRoot?.querySelector("#target_branch");
            const teamSelect = audienceRoot?.querySelector("[data-team-select]");
            const employeeSelect = audienceRoot?.querySelector("[data-employee-select]");
            const selectAllVisibleCheckbox = audienceRoot?.querySelector("#broadcast_select_all_visible");
            const visibleMeta = audienceRoot?.querySelector("#broadcast_visible_meta");

            if (
                !(branchSelect instanceof HTMLSelectElement)
                || !(teamSelect instanceof HTMLSelectElement)
                || !(employeeSelect instanceof HTMLSelectElement)
                || !(selectAllVisibleCheckbox instanceof HTMLInputElement)
            ) {
                return;
            }

            const allRows = Array.from(employeeSelect.options).map((option) => ({
                id: option.value,
                label: option.textContent?.trim() ?? "",
                branch: option.dataset.branch ?? "",
                team: option.dataset.team ?? "",
            }));

            const getSelectedEmployeeIds = () => (
                new Set(Array.from(employeeSelect.selectedOptions).map((option) => option.value))
            );

            const selectedTeamValue = () => teamSelect.value || "all";
            const selectedBranchValue = () => branchSelect.value || "all";

            const updateVisibleMeta = () => {
                const visibleCount = employeeSelect.options.length;
                const selectedCount = employeeSelect.selectedOptions.length;
                if (visibleMeta instanceof HTMLElement) {
                    visibleMeta.textContent = `${selectedCount}/${visibleCount} selected in current filter.`;
                }
            };

            const updateSelectAllState = () => {
                const visibleCount = employeeSelect.options.length;
                if (visibleCount === 0) {
                    selectAllVisibleCheckbox.checked = false;
                    selectAllVisibleCheckbox.disabled = true;
                    updateVisibleMeta();
                    return;
                }

                selectAllVisibleCheckbox.disabled = false;
                const selectedCount = employeeSelect.selectedOptions.length;
                selectAllVisibleCheckbox.checked = selectedCount === visibleCount;
                updateVisibleMeta();
            };

            const syncEmployeeOptionSelectionStyles = () => {
                Array.from(employeeSelect.options).forEach((option) => {
                    const baseLabel = option.dataset.baseLabel ?? option.textContent?.trim() ?? "";
                    option.dataset.baseLabel = baseLabel;

                    if (option.selected) {
                        option.textContent = `✓ ${baseLabel}`;
                        option.style.backgroundColor = "var(--hr-accent-soft)";
                        option.style.color = "var(--hr-text-main)";
                        option.style.fontWeight = "700";
                    } else {
                        option.textContent = baseLabel;
                        option.style.backgroundColor = "";
                        option.style.color = "";
                        option.style.fontWeight = "";
                    }
                });
            };

            const renderTeamOptions = () => {
                const previousTeam = selectedTeamValue();
                const currentBranch = selectedBranchValue();

                const teams = Array.from(new Set(
                    allRows
                        .filter((row) => currentBranch === "all" || row.branch === currentBranch)
                        .map((row) => row.team)
                        .filter((team) => team !== "")
                )).sort((a, b) => a.localeCompare(b));

                teamSelect.innerHTML = "";
                const allOption = new Option("All", "all", false, false);
                teamSelect.add(allOption);
                teams.forEach((team) => {
                    teamSelect.add(new Option(team, team, false, false));
                });

                if (previousTeam !== "all" && teams.includes(previousTeam)) {
                    teamSelect.value = previousTeam;
                } else {
                    teamSelect.value = "all";
                }
            };

            const renderEmployeeOptions = () => {
                const selectedIds = getSelectedEmployeeIds();
                const currentBranch = selectedBranchValue();
                const currentTeam = selectedTeamValue();
                const shouldSelectAllVisible = selectAllVisibleCheckbox.checked;

                const filteredRows = allRows.filter((row) => {
                    const branchMatches = currentBranch === "all" || row.branch === currentBranch;
                    const teamMatches = currentTeam === "all" || row.team === currentTeam;
                    return branchMatches && teamMatches;
                });

                employeeSelect.innerHTML = "";
                filteredRows.forEach((row) => {
                    const option = new Option(row.label, row.id, false, shouldSelectAllVisible || selectedIds.has(row.id));
                    option.dataset.baseLabel = row.label;
                    employeeSelect.add(option);
                });

                syncEmployeeOptionSelectionStyles();
                updateSelectAllState();
            };

            branchSelect.addEventListener("change", () => {
                renderTeamOptions();
                renderEmployeeOptions();
            });

            teamSelect.addEventListener("change", () => {
                renderEmployeeOptions();
            });

            employeeSelect.addEventListener("change", () => {
                syncEmployeeOptionSelectionStyles();
                updateSelectAllState();
            });

            employeeSelect.addEventListener("mousedown", (event) => {
                const target = event.target;
                if (!(target instanceof HTMLOptionElement)) {
                    return;
                }

                event.preventDefault();
                target.selected = !target.selected;
                employeeSelect.focus();
                syncEmployeeOptionSelectionStyles();
                updateSelectAllState();
            });

            selectAllVisibleCheckbox.addEventListener("change", () => {
                const checked = selectAllVisibleCheckbox.checked;
                Array.from(employeeSelect.options).forEach((option) => {
                    option.selected = checked;
                });
                syncEmployeeOptionSelectionStyles();
                updateSelectAllState();
            });

            renderTeamOptions();
            renderEmployeeOptions();
        })();

        // Inline attachment preview toggles (no popups/new tabs)
        (() => {
            const isImage = (url) => /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(url);
            const isPdf = (url) => /\.(pdf)(\?.*)?$/i.test(url);

            const renderPreviewContent = (container, url) => {
                container.innerHTML = "";
                const surface = document.createElement("div");
                surface.className = "comm-attachment-surface";

                if (isImage(url)) {
                    const img = document.createElement("img");
                    img.src = url;
                    img.alt = "Attachment preview";
                    img.className = "comm-attachment-image";
                    img.addEventListener("load", () => {
                        // Adjust height smoothly after image loads
                        container.style.maxHeight = container.scrollHeight + "px";
                    }, { once: true });
                    surface.appendChild(img);
                } else if (isPdf(url)) {
                    const frame = document.createElement("iframe");
                    frame.src = url;
                    frame.width = "100%";
                    frame.height = "420";
                    frame.style.border = "1px solid var(--hr-line)";
                    frame.style.borderRadius = "10px";
                    frame.loading = "lazy";
                    frame.addEventListener("load", () => {
                        container.style.maxHeight = container.scrollHeight + "px";
                    }, { once: true });
                    surface.appendChild(frame);
                } else {
                    const p = document.createElement("p");
                    p.className = "text-sm";
                    p.style.color = "var(--hr-text-muted)";
                    p.textContent = "Preview not available. You can download the file:";
                    const link = document.createElement("a");
                    link.href = url;
                    link.textContent = " Download";
                    link.className = "ml-1 underline";
                    link.setAttribute("download", "");
                    link.rel = "noopener";
                    p.appendChild(link);
                    surface.appendChild(p);
                }

                container.appendChild(surface);
            };

            const togglePreview = (container, url) => {
                const isOpen = container.classList.contains("is-open");
                if (!isOpen) {
                    renderPreviewContent(container, url);
                    // Measure and open smoothly
                    container.classList.add("is-open");
                    container.style.maxHeight = container.scrollHeight + "px";
                    container.setAttribute("aria-hidden", "false");
                } else {
                    // Close smoothly
                    container.style.maxHeight = container.scrollHeight + "px";
                    // Force reflow to ensure transition
                    void container.offsetHeight;
                    container.style.maxHeight = "0px";
                    container.setAttribute("aria-hidden", "true");
                    container.addEventListener("transitionend", () => {
                        container.classList.remove("is-open");
                        container.innerHTML = "";
                    }, { once: true });
                }
            };

            document.addEventListener("click", (event) => {
                const button = event.target instanceof Element ? event.target.closest(".comm-attachment-trigger") : null;
                if (!button) return;
                event.preventDefault();
                const url = button.getAttribute("data-attachment-url") ?? "";
                const preview = button.parentElement?.querySelector(".comm-attachment-preview");
                if (!preview) return;
                togglePreview(preview, url);
            });
        })();
    </script>
@endpush
