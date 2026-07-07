ALTER TABLE leave_requests
  ADD COLUMN recalled_at DATETIME NULL AFTER resumed_at,
  ADD COLUMN recalled_by_user_id INT UNSIGNED NULL AFTER recalled_at,
  ADD COLUMN recall_reason TEXT NULL AFTER recalled_by_user_id,
  ADD COLUMN recall_attachment_path VARCHAR(255) NULL AFTER recall_reason;
