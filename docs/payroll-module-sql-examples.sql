-- Payroll module indexing and audit examples (MySQL)

-- Branch/department filters used by dashboard and salary structure APIs
CREATE INDEX user_profiles_branch_department_idx
    ON user_profiles (branch, department);

-- Processing/history table filters (month + status + employee)
CREATE INDEX payrolls_month_status_user_idx
    ON payrolls (payroll_month, status, user_id);

-- Audit lookup for payroll detail views
CREATE INDEX audit_logs_entity_time_idx
    ON audit_logs (entity_type, entity_id, performed_at);

-- Salary structure change history lookup
CREATE INDEX payroll_structure_histories_user_time_idx
    ON payroll_structure_histories (user_id, changed_at);
