@extends('layouts.dashboard-modern')

@section('title', 'Create User')
@section('page_heading', 'Create User')

@php
    // Normalize server-provided options for dropdowns
    $roleOptions = $roleOptions ?? \App\Enums\UserRole::cases();
    $statusOptions = $statusOptions ?? ['active', 'inactive', 'suspended'];
    $employmentTypes = $employmentTypes ?? ['full_time', 'part_time', 'contract'];
    // Ensure 'intern' option is available as per wizard spec
    if (!in_array('intern', $employmentTypes, true)) {
        $employmentTypes[] = 'intern';
    }
    $departmentOptions = $departmentOptions ?? [];
    $branchOptions = $branchOptions ?? [];
    $supervisorOptions = $supervisorOptions ?? collect();
    $designationOptions = $designationOptions ?? [];
@endphp

@push('head')
<style>
  .wizard-shell { display: grid; gap: 12px; }
  .wizard-stepper { background: var(--hr-surface); border: 1px solid var(--hr-line); box-shadow: var(--hr-shadow-soft); }
  .wizard-stepper ul { display: flex; gap: 8px; align-items: stretch; }
  .wizard-step { flex: 1 1 0; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-bottom: 2px solid transparent; transition: border-color 160ms ease, color 160ms ease, background 160ms ease; }
  .wizard-step .index { height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid var(--hr-line); background: var(--hr-surface-strong); font-weight: 800; font-size: 12px; }
  .wizard-step .label { font-weight: 800; font-size: 13px; }
  .wizard-step.is-active { border-color: var(--hr-accent); }
  .wizard-step.is-complete .index { color: #10b981; border-color: rgba(16,185,129,0.5); }
  .wizard-stepper .meta { margin-left: auto; font-size: 12px; color: var(--hr-text-muted); }

  .wizard-panels { position: relative; }
  .wizard-panel { background: var(--hr-surface); border: 1px solid var(--hr-line); box-shadow: var(--hr-shadow-soft); border-radius: 16px; overflow: clip; transition: max-height 260ms ease, opacity 200ms ease, transform 220ms ease; }
  .wizard-panel[aria-hidden="true"] { max-height: 0; opacity: 0; transform: translateY(6px); }
  .wizard-panel[aria-hidden="false"] { opacity: 1; transform: translateY(0); }

  .wizard-actions { display: flex; gap: 8px; align-items: center; }
  /* Use global .ui-btn and .ui-btn-primary from layout for themed buttons */
  .ui-btn { border-radius: 12px; font-weight: 700; font-size: 14px; padding: 8px 14px; border: 1px solid var(--hr-line); }
  .ui-btn[disabled] { opacity: 0.6; cursor: not-allowed; }

  .ui-card { padding: 16px; border-radius: 16px; }
  .ui-title { font-weight: 900; font-size: 18px; }
  .ui-sub { color: var(--hr-text-muted); font-size: 13px; margin-top: 4px; }
  .ui-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 14px; }
  @media (min-width: 768px) { .ui-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
  .ui-field label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--hr-text-muted); margin-bottom: 6px; }
  .ui-field input, .ui-field select, .ui-field textarea { width: 100%; border: 1px solid var(--hr-line); background: transparent; border-radius: 12px; padding: 10px 12px; }
  .ui-hint { font-size: 12px; color: var(--hr-text-muted); margin-top: 4px; }
  .ui-error { font-size: 12px; color: #dc2626; margin-top: 4px; }

  .toast-stack { position: fixed; right: 16px; bottom: 16px; z-index: 50; display: grid; gap: 8px; }
  .toast { border: 1px solid rgba(16,185,129,0.45); background: rgba(16,185,129,0.08); color: var(--hr-text-main); padding: 10px 12px; border-radius: 12px; font-size: 14px; font-weight: 700; box-shadow: var(--hr-shadow-soft); }
  .toast.is-danger { border-color: rgba(248,113,113,0.45); background: rgba(248,113,113,0.10); }
</style>
@endpush

@section('content')
  <div class="wizard-shell">
    <section class="wizard-stepper rounded-2xl p-3">
      <div class="flex items-center gap-3">
        <ul class="flex-1">
          <li>
            <div class="wizard-step" data-stepper-item data-step="1">
              <span class="index" data-step-index>1</span>
              <span class="label">Account</span>
            </div>
          </li>
          <li>
            <div class="wizard-step" data-stepper-item data-step="2">
              <span class="index" data-step-index>2</span>
              <span class="label">Employment Details</span>
            </div>
          </li>
          <li>
            <div class="wizard-step" data-stepper-item data-step="3">
              <span class="index" data-step-index>3</span>
              <span class="label">Personal Details</span>
            </div>
          </li>
        </ul>
        <span class="meta" data-progress-indicator>Step 1 of 3</span>
      </div>
    </section>

    <section class="wizard-panels">
      <!-- Step 1: Account -->
      <article class="wizard-panel ui-card" data-panel="1" aria-hidden="false">
        <div class="flex items-center gap-2">
          <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg>
          </span>
          <div>
            <h3 class="ui-title">Account Setup</h3>
            <p class="ui-sub">Primary access details and role assignment.</p>
          </div>
        </div>
        <div class="ui-grid">
          <div class="ui-field">
            <label for="acc_first_name">First Name</label>
            <input id="acc_first_name" maxlength="120" required />
            <p class="ui-error" data-error-for="acc_first_name"></p>
          </div>
          <div class="ui-field">
            <label for="acc_middle_name">Middle Name (optional)</label>
            <input id="acc_middle_name" maxlength="120" />
            <p class="ui-error" data-error-for="acc_middle_name"></p>
          </div>
          <div class="ui-field">
            <label for="acc_last_name">Last Name</label>
            <input id="acc_last_name" maxlength="120" required />
            <p class="ui-error" data-error-for="acc_last_name"></p>
          </div>
          <div class="ui-field">
            <label for="acc_email">Email</label>
            <input id="acc_email" type="email" maxlength="255" required />
            <p class="ui-error" data-error-for="acc_email"></p>
          </div>
          <div class="ui-field">
            <label for="acc_role">Role</label>
            <select id="acc_role" required>
              @foreach($roleOptions as $roleOption)
                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="acc_role"></p>
          </div>
          <div class="ui-field">
            <label for="acc_status">Status</label>
            <select id="acc_status" required>
              @foreach($statusOptions as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="acc_status"></p>
          </div>
          <div class="ui-field">
            <label for="acc_password">Password</label>
            <input id="acc_password" type="password" required />
            <p class="ui-error" data-error-for="acc_password"></p>
          </div>
          <div class="ui-field">
            <label for="acc_password_confirmation">Confirm Password</label>
            <input id="acc_password_confirmation" type="password" required />
            <p class="ui-error" data-error-for="acc_password_confirmation"></p>
          </div>
        </div>
        <div class="wizard-actions mt-4">
          <button class="ui-btn ui-btn-primary" id="btn_step1_submit">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
            Create Account and Continue
          </button>
        </div>
      </article>

      <!-- Step 2: Employment -->
      <article class="wizard-panel ui-card" data-panel="2" aria-hidden="true">
        <div class="flex items-center gap-2">
          <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18" /></svg>
          </span>
          <div>
            <h3 class="ui-title">Employment Information</h3>
            <p class="ui-sub">Organization details for reporting and HR workflows.</p>
          </div>
        </div>
        <div class="ui-grid">
          <div class="ui-field">
            <label for="emp_designation">Designation</label>
            <select id="emp_designation">
              <option value="">Select designation</option>
              @foreach($designationOptions as $d)
                <option value="{{ (int) $d['id'] }}">{{ $d['name'] }}{{ $d['code'] ? (' (' . $d['code'] . ')') : '' }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="emp_designation"></p>
          </div>
          <div class="ui-field">
            <label for="emp_job_title">Job Title</label>
            <input id="emp_job_title" maxlength="100" />
            <p class="ui-error" data-error-for="emp_job_title"></p>
          </div>
          <div class="ui-field">
            <label for="emp_department">Department</label>
            <select id="emp_department">
              <option value="">Select department</option>
              @foreach($departmentOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="emp_department"></p>
          </div>
          <div class="ui-field">
            <label for="emp_branch">Branch</label>
            <select id="emp_branch">
              <option value="">Select branch</option>
              @foreach($branchOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="emp_branch"></p>
          </div>
          <div class="ui-field">
            <label for="emp_supervisor">Supervisor</label>
            <select id="emp_supervisor">
              <option value="">Select supervisor</option>
              @foreach($supervisorOptions as $sup)
                @php
                  $supRole = $sup->role instanceof \App\Enums\UserRole ? $sup->role->label() : ucfirst((string) $sup->role);
                @endphp
                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $supRole }})</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="emp_supervisor"></p>
          </div>
          <div class="ui-field">
            <label for="emp_manager_name">Manager Name</label>
            <input id="emp_manager_name" maxlength="120" />
            <p class="ui-hint">Auto-fills from supervisor if left blank.</p>
            <p class="ui-error" data-error-for="emp_manager_name"></p>
          </div>
          <div class="ui-field">
            <label for="emp_type">Employee Type</label>
            <select id="emp_type" required>
              @foreach($employmentTypes as $type)
                <option value="{{ $type }}">{{ str($type)->replace('_', ' ')->title() }}</option>
              @endforeach
            </select>
            <p class="ui-error" data-error-for="emp_type"></p>
          </div>
          <div class="ui-field">
            <label for="emp_joining">Joining Date</label>
            <input id="emp_joining" type="date" />
            <p class="ui-error" data-error-for="emp_joining"></p>
          </div>
        </div>
        <div class="wizard-actions mt-4">
          <button class="ui-btn" data-back>Back</button>
          <button class="ui-btn ui-btn-primary" id="btn_step2_submit" disabled>Save and Continue</button>
        </div>
      </article>

      <!-- Step 3: Personal -->
      <article class="wizard-panel ui-card" data-panel="3" aria-hidden="true">
        <div class="flex items-center gap-2">
          <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
          </span>
          <div>
            <h3 class="ui-title">Personal Information</h3>
            <p class="ui-sub">Contact and personal details.</p>
          </div>
        </div>
        <div class="ui-grid">
          <div class="ui-field">
            <label for="per_dob">Date of Birth</label>
            <input id="per_dob" type="date" />
            <p class="ui-error" data-error-for="per_dob"></p>
          </div>
          <div class="ui-field">
            <label for="per_gender">Gender</label>
            <select id="per_gender">
              <option value="">Select gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
              <option value="prefer_not_to_say">Prefer not to say</option>
            </select>
            <p class="ui-error" data-error-for="per_gender"></p>
          </div>
          <div class="ui-field">
            <label for="per_phone">Phone Number</label>
            <input id="per_phone" maxlength="40" />
            <p class="ui-error" data-error-for="per_phone"></p>
          </div>
          <div class="ui-field">
            <label for="per_alt_phone">Alternate Phone</label>
            <input id="per_alt_phone" maxlength="40" />
            <p class="ui-error" data-error-for="per_alt_phone"></p>
          </div>
          <div class="ui-field md:col-span-2">
            <label for="per_address">Address</label>
            <textarea id="per_address" rows="3" maxlength="1000"></textarea>
            <p class="ui-error" data-error-for="per_address"></p>
          </div>
          <div class="ui-field">
            <label for="per_city">City</label>
            <input id="per_city" maxlength="120" />
            <p class="ui-hint">Optional field; stored in address if schema not available.</p>
            <p class="ui-error" data-error-for="per_city"></p>
          </div>
          <div class="ui-field">
            <label for="per_state">State</label>
            <input id="per_state" maxlength="120" />
            <p class="ui-error" data-error-for="per_state"></p>
          </div>
          <div class="ui-field">
            <label for="per_country">Country</label>
            <input id="per_country" maxlength="120" />
            <p class="ui-error" data-error-for="per_country"></p>
          </div>
          <div class="ui-field">
            <label for="per_emg_name">Emergency Contact Name</label>
            <input id="per_emg_name" maxlength="120" />
            <p class="ui-error" data-error-for="per_emg_name"></p>
          </div>
          <div class="ui-field">
            <label for="per_emg_phone">Emergency Contact Number</label>
            <input id="per_emg_phone" maxlength="40" />
            <p class="ui-error" data-error-for="per_emg_phone"></p>
          </div>
        </div>
        <div class="wizard-actions mt-4">
          <button class="ui-btn" data-back>Back</button>
          <button class="ui-btn ui-btn-primary" id="btn_step3_submit" disabled>Save</button>
        </div>
      </article>
    </section>
  </div>
  <div id="wizardResumeBanner" class="rounded-2xl p-3 border mb-3" style="display:none; border-color: var(--hr-line); background: var(--hr-surface);">
    <div class="flex items-center justify-between gap-3">
      <div>
        <p class="font-semibold">Unfinished setup detected</p>
        <p class="text-sm" style="color: var(--hr-text-muted);">Resume the employee setup from where you left off.</p>
      </div>
      <div class="flex items-center gap-2">
        <button id="resumeWizardBtn" class="ui-btn ui-btn-primary">Resume</button>
        <button id="discardWizardBtn" class="ui-btn">Discard</button>
      </div>
    </div>
  </div>

  <div class="toast-stack" id="toastStack" aria-live="polite"></div>

  <template id="redirectTemplate" data-url="{{ url('/employees/0/overview') }}"></template>
@endsection

@push('scripts')
<script>
  (function() {
    const csrf = @json(csrf_token());
    const accountUrl = @json(route('api.admin.users.wizard.account'));
    const employmentUrlTmpl = @json(route('api.admin.users.wizard.employment', ['user' => 0]));
    const personalUrlTmpl = @json(route('api.admin.users.wizard.personal', ['user' => 0]));

    const preferReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const panels = Array.from(document.querySelectorAll('[data-panel]'));
    const stepperItems = Array.from(document.querySelectorAll('[data-stepper-item]'));
    const progress = document.querySelector('[data-progress-indicator]');
    const toastStack = document.getElementById('toastStack');

    const state = {
      current: 1,
      completed: new Set(),
      userId: null,
      step1: {},
      step2: {},
      step3: {},
    };

    const $ = (id) => document.getElementById(id);
    const setDisabled = (el, disabled) => { if (el) { el.disabled = !!disabled; el.setAttribute('aria-disabled', disabled ? 'true' : 'false'); }};
    const showToast = (message, tone = 'success') => {
      const node = document.createElement('div');
      node.className = 'toast' + (tone === 'danger' ? ' is-danger' : '');
      node.textContent = message;
      toastStack.appendChild(node);
      window.setTimeout(() => node.remove(), 3600);
    };

    const updateStepper = () => {
      stepperItems.forEach((item) => {
        const step = Number(item.getAttribute('data-step'));
        item.classList.toggle('is-active', step === state.current);
        item.classList.toggle('is-complete', state.completed.has(step));
        const indexEl = item.querySelector('[data-step-index]');
        if (indexEl) {
          indexEl.textContent = state.completed.has(step) ? '✓' : String(step);
        }
      });
      if (progress) progress.textContent = `Step ${state.current} of 3`;
    };

    const setPanelOpen = (step) => {
      panels.forEach((p) => {
        const isTarget = Number(p.getAttribute('data-panel')) === step;
        p.setAttribute('aria-hidden', isTarget ? 'false' : 'true');
        if (!preferReducedMotion) {
          p.style.transition = 'opacity 200ms ease, transform 220ms ease, max-height 260ms ease';
        }
      });
    };

    const gotoStep = (step) => {
      state.current = step;
      setPanelOpen(step);
      updateStepper();
      validateStepButtons();
    };

    // Persist progress so the wizard can be resumed if the page is left
    const STORAGE_KEY = 'hrm_user_wizard_progress';
    const saveProgress = () => {
      try {
        if (!state.userId) return;
        const payload = { userId: state.userId, step: state.current, completed: Array.from(state.completed) };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
      } catch (_) {}
    };
    const clearProgress = () => {
      try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
      const banner = document.getElementById('wizardResumeBanner');
      if (banner) banner.style.display = 'none';
    };
    const tryRestoreFromQueryOrStorage = () => {
      const params = new URLSearchParams(window.location.search);
      const qpUser = Number(params.get('user') || params.get('user_id') || 0) || null;
      const qpStep = Number(params.get('step') || 0) || 0;
      if (qpUser) {
        state.userId = qpUser;
        state.completed.add(1);
        gotoStep(qpStep >= 2 && qpStep <= 3 ? qpStep : 2);
        saveProgress();
        return;
      }
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const parsed = JSON.parse(raw);
        if (parsed && Number(parsed.userId)) {
          const banner = document.getElementById('wizardResumeBanner');
          if (banner) banner.style.display = '';
          const resumeBtn = document.getElementById('resumeWizardBtn');
          resumeBtn?.addEventListener('click', () => {
            state.userId = Number(parsed.userId);
            (Array.isArray(parsed.completed) ? parsed.completed : []).forEach((s) => state.completed.add(Number(s)));
            const target = Number(parsed.step) >= 2 ? Number(parsed.step) : 2;
            gotoStep(target);
          });
          const discardBtn = document.getElementById('discardWizardBtn');
          discardBtn?.addEventListener('click', () => { clearProgress(); showToast('Wizard progress cleared'); });
        }
      } catch (_) {}
    };

    const fieldError = (id, message) => {
      const errorEl = document.querySelector(`[data-error-for="${id}"]`);
      if (errorEl) errorEl.textContent = message || '';
    };
    const clearErrors = () => {
      document.querySelectorAll('[data-error-for]').forEach((el) => { el.textContent = ''; });
    };

    const mapServerErrors = (errors = {}) => {
      // Step 1 mapping
      if (errors.first_name) fieldError('acc_first_name', String(errors.first_name[0] || ''));
      if (errors.middle_name) fieldError('acc_middle_name', String(errors.middle_name[0] || ''));
      if (errors.last_name) fieldError('acc_last_name', String(errors.last_name[0] || ''));
      if (errors.email) fieldError('acc_email', String(errors.email[0] || ''));
      if (errors.role) fieldError('acc_role', String(errors.role[0] || ''));
      if (errors.status) fieldError('acc_status', String(errors.status[0] || ''));
      if (errors.password) fieldError('acc_password', String(errors.password[0] || ''));
      if (errors.password_confirmation) fieldError('acc_password_confirmation', String(errors.password_confirmation[0] || ''));
      // Step 2 mapping
      if (errors.designation_id) fieldError('emp_designation', String(errors.designation_id[0] || ''));
      if (errors.job_title) fieldError('emp_job_title', String(errors.job_title[0] || ''));
      if (errors.department) fieldError('emp_department', String(errors.department[0] || ''));
      if (errors.branch) fieldError('emp_branch', String(errors.branch[0] || ''));
      if (errors.supervisor_user_id) fieldError('emp_supervisor', String(errors.supervisor_user_id[0] || ''));
      if (errors.manager_name) fieldError('emp_manager_name', String(errors.manager_name[0] || ''));
      if (errors.employment_type) fieldError('emp_type', String(errors.employment_type[0] || ''));
      if (errors.joined_on) fieldError('emp_joining', String(errors.joined_on[0] || ''));
      // Step 3 mapping
      if (errors.date_of_birth) fieldError('per_dob', String(errors.date_of_birth[0] || ''));
      if (errors.gender) fieldError('per_gender', String(errors.gender[0] || ''));
      if (errors.phone) fieldError('per_phone', String(errors.phone[0] || ''));
      if (errors.alternate_phone) fieldError('per_alt_phone', String(errors.alternate_phone[0] || ''));
      if (errors.address) fieldError('per_address', String(errors.address[0] || ''));
      if (errors.emergency_contact_name) fieldError('per_emg_name', String(errors.emergency_contact_name[0] || ''));
      if (errors.emergency_contact_phone) fieldError('per_emg_phone', String(errors.emergency_contact_phone[0] || ''));
      if (errors.city) fieldError('per_city', String(errors.city[0] || ''));
      if (errors.state) fieldError('per_state', String(errors.state[0] || ''));
      if (errors.country) fieldError('per_country', String(errors.country[0] || ''));
    };

    const serializeStep1 = () => ({
      first_name: $('acc_first_name').value.trim(),
      middle_name: $('acc_middle_name').value.trim() || null,
      last_name: $('acc_last_name').value.trim(),
      email: $('acc_email').value.trim(),
      role: $('acc_role').value,
      status: $('acc_status').value,
      password: $('acc_password').value,
      password_confirmation: $('acc_password_confirmation').value,
    });

    const serializeStep2 = () => ({
      designation_id: $('emp_designation').value ? Number($('emp_designation').value) : null,
      job_title: $('emp_job_title').value.trim() || null,
      department: $('emp_department').value || null,
      branch: $('emp_branch').value || null,
      supervisor_user_id: $('emp_supervisor').value ? Number($('emp_supervisor').value) : null,
      manager_name: $('emp_manager_name').value.trim() || null,
      employment_type: $('emp_type').value,
      joined_on: $('emp_joining').value || null,
    });

    const serializeStep3 = () => ({
      date_of_birth: $('per_dob').value || null,
      gender: $('per_gender').value || null,
      phone: $('per_phone').value.trim() || null,
      alternate_phone: $('per_alt_phone').value.trim() || null,
      address: $('per_address').value.trim() || null,
      emergency_contact_name: $('per_emg_name').value.trim() || null,
      emergency_contact_phone: $('per_emg_phone').value.trim() || null,
      city: $('per_city').value.trim() || null,
      state: $('per_state').value.trim() || null,
      country: $('per_country').value.trim() || null,
    });

    const canProceedStep1 = () => {
      const s1 = serializeStep1();
      if (!s1.first_name || !s1.last_name || !s1.email || !s1.role || !s1.status || !s1.password || !s1.password_confirmation) return false;
      return s1.password === s1.password_confirmation;
    };
    const canProceedStep2 = () => {
      const s2 = serializeStep2();
      return Boolean(s2.employment_type);
    };
    const canProceedStep3 = () => {
      // Optional-only step; allow save always
      return true;
    };

    const validateStepButtons = () => {
      setDisabled(document.getElementById('btn_step1_submit'), !canProceedStep1());
      setDisabled(document.getElementById('btn_step2_submit'), !canProceedStep2());
      setDisabled(document.getElementById('btn_step3_submit'), !canProceedStep3());
    };

    // Wire Back buttons
    document.querySelectorAll('[data-back]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const prev = Math.max(1, state.current - 1);
        gotoStep(prev);
      });
    });

    // Step 1 submit
    const btn1 = document.getElementById('btn_step1_submit');
    btn1?.addEventListener('click', async (e) => {
      e.preventDefault();
      clearErrors();
      if (!canProceedStep1()) { showToast('Please complete required fields.', 'danger'); return; }
      setDisabled(btn1, true);
      btn1.dataset.loading = 'true';
      try {
        const payload = serializeStep1();
        const res = await fetch(accountUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          mapServerErrors(data?.errors || {});
          throw new Error(data?.message || 'Unable to create account.');
        }
        state.userId = Number(data?.data?.id || 0) || null;
        state.completed.add(1);
        showToast('Account created successfully.');
        gotoStep(2);
        saveProgress();
      } catch (err) {
        showToast(err.message || 'Unable to create account.', 'danger');
      } finally {
        setDisabled(btn1, false);
        delete btn1.dataset.loading;
      }
    });

    // Step 2 submit
    const btn2 = document.getElementById('btn_step2_submit');
    btn2?.addEventListener('click', async (e) => {
      e.preventDefault();
      clearErrors();
      if (!state.userId) { showToast('Please create account first.', 'danger'); return; }
      if (!canProceedStep2()) { showToast('Please select employment type.', 'danger'); return; }
      setDisabled(btn2, true);
      btn2.dataset.loading = 'true';
      try {
        const payload = serializeStep2();
        const url = employmentUrlTmpl.replace('/0/', `/${state.userId}/`);
        const res = await fetch(url, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          mapServerErrors(data?.errors || {});
          throw new Error(data?.message || 'Unable to save employment details.');
        }
        state.completed.add(2);
        showToast('Employment details saved.');
        gotoStep(3);
        saveProgress();
      } catch (err) {
        showToast(err.message || 'Unable to save employment details.', 'danger');
      } finally {
        setDisabled(btn2, false);
        delete btn2.dataset.loading;
      }
    });

    // Step 3 submit
    const btn3 = document.getElementById('btn_step3_submit');
    btn3?.addEventListener('click', async (e) => {
      e.preventDefault();
      clearErrors();
      if (!state.userId) { showToast('Please create account first.', 'danger'); return; }
      if (!canProceedStep3()) { showToast('Please complete required fields.', 'danger'); return; }
      setDisabled(btn3, true);
      btn3.dataset.loading = 'true';
      try {
        const payload = serializeStep3();
        const url = personalUrlTmpl.replace('/0/', `/${state.userId}/`);
        const res = await fetch(url, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          mapServerErrors(data?.errors || {});
          throw new Error(data?.message || 'Unable to save personal details.');
        }
        state.completed.add(3);
        showToast('Employee created successfully. Redirecting...');
        clearProgress();
        const redirectTmpl = document.getElementById('redirectTemplate');
        const urlTemplate = redirectTmpl?.dataset?.url || '{{ url('/employees/0/overview') }}';
        const finalUrl = urlTemplate.replace('/0/', `/${state.userId}/`);
        window.setTimeout(() => { window.location.assign(finalUrl); }, 800);
      } catch (err) {
        showToast(err.message || 'Unable to save personal details.', 'danger');
      } finally {
        setDisabled(btn3, false);
        delete btn3.dataset.loading;
      }
    });

    // Live validation hooks to toggle buttons
    document.addEventListener('input', (e) => {
      const target = e.target;
      if (!(target instanceof HTMLElement)) return;
      validateStepButtons();
    });

    // Initialize
    updateStepper();
    setPanelOpen(1);
    tryRestoreFromQueryOrStorage();
    validateStepButtons();
  })();
</script>
@endpush
