INSERT INTO leave_types
  (name, gender_eligibility, default_entitlement, requires_balance, requires_attachment, attachment_after_days, is_paid, is_active)
VALUES
  ('Sport Leave', 'any', 0.00, 0, 0, NULL, 1, 1),
  ('Terminal Leave', 'any', 30.00, 1, 0, NULL, 1, 1),
  ('Unpaid Leave', 'any', 0.00, 0, 0, NULL, 0, 1)
ON DUPLICATE KEY UPDATE
  gender_eligibility = VALUES(gender_eligibility),
  default_entitlement = VALUES(default_entitlement),
  requires_balance = VALUES(requires_balance),
  requires_attachment = VALUES(requires_attachment),
  attachment_after_days = VALUES(attachment_after_days),
  is_paid = VALUES(is_paid),
  is_active = VALUES(is_active);
