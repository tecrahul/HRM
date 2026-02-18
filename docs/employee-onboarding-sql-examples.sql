-- Employee onboarding setup status snapshot for one employee
SELECT
    u.id,
    u.name,
    u.email,
    CASE WHEN ps.id IS NULL THEN 0 ELSE 1 END AS salaryConfigured,
    CASE WHEN p_month.id IS NULL THEN 0 ELSE 1 END AS payrollAssigned,
    CASE WHEN up.employment_type IS NOT NULL AND up.employment_type <> ''
              AND up.department IS NOT NULL AND up.department <> ''
         THEN 1 ELSE 0 END AS leavePolicyAssigned,
    CASE WHEN (up.branch IS NOT NULL AND up.branch <> '')
           OR (up.work_location IS NOT NULL AND up.work_location <> '')
         THEN 1 ELSE 0 END AS attendancePolicyAssigned,
    CASE WHEN up.bank_account_name IS NOT NULL AND up.bank_account_name <> ''
              AND up.bank_account_number IS NOT NULL AND up.bank_account_number <> ''
              AND up.bank_ifsc IS NOT NULL AND up.bank_ifsc <> ''
         THEN 1 ELSE 0 END AS bankDetailsAdded,
    0 AS documentsUploaded,
    CASE WHEN login_audit.id IS NULL THEN 0 ELSE 1 END AS loginSent
FROM users u
LEFT JOIN user_profiles up ON up.user_id = u.id
LEFT JOIN payroll_structures ps ON ps.user_id = u.id
LEFT JOIN payrolls p_month
    ON p_month.user_id = u.id
    AND YEAR(p_month.payroll_month) = YEAR(CURDATE())
    AND MONTH(p_month.payroll_month) = MONTH(CURDATE())
LEFT JOIN audit_logs login_audit
    ON login_audit.entity_type = 'employee'
    AND login_audit.entity_id = u.id
    AND login_audit.action = 'employee.login_credentials.sent'
WHERE u.id = :employee_id
  AND u.role = 'employee'
LIMIT 1;

-- Last credential send action details
SELECT
    al.id,
    al.entity_id AS employee_id,
    al.performed_by_user_id,
    al.action,
    al.performed_at,
    al.metadata
FROM audit_logs al
WHERE al.entity_type = 'employee'
  AND al.entity_id = :employee_id
  AND al.action = 'employee.login_credentials.sent'
ORDER BY al.performed_at DESC
LIMIT 10;
