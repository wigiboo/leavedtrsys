-- Apply once to an existing Leavewise database to enable Civil Service Form No. 6.
USE leave_monitor;
ALTER TABLE users MODIFY role ENUM('platform_admin','school_admin','personnel_officer','approving_authority','employee','teacher','student') NOT NULL;
ALTER TABLE leave_requests
  ADD leave_type VARCHAR(120) NULL,
  ADD office_department VARCHAR(190) NULL,
  ADD applicant_last VARCHAR(120) NULL,
  ADD applicant_first VARCHAR(120) NULL,
  ADD applicant_middle VARCHAR(120) NULL,
  ADD filing_date DATE NULL,
  ADD position_title VARCHAR(160) NULL,
  ADD salary DECIMAL(12,2) NULL,
  ADD details_json JSON NULL,
  ADD working_days DECIMAL(6,2) NULL,
  ADD commutation ENUM('not_requested','requested') DEFAULT 'not_requested',
  ADD vl_earned DECIMAL(8,2) NULL,
  ADD vl_less DECIMAL(8,2) NULL,
  ADD vl_balance DECIMAL(8,2) NULL,
  ADD sl_earned DECIMAL(8,2) NULL,
  ADD sl_less DECIMAL(8,2) NULL,
  ADD sl_balance DECIMAL(8,2) NULL,
  ADD personnel_remarks TEXT NULL,
  ADD recommendation ENUM('approval','disapproval') NULL,
  ADD approval_days_with_pay DECIMAL(6,2) NULL,
  ADD approval_days_without_pay DECIMAL(6,2) NULL,
  ADD authority_remarks TEXT NULL;
