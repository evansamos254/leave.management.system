ALTER TABLE leave_requests
  MODIFY status ENUM(
    'pending_supervisor',
    'approved',
    'rejected',
    'cancelled',
    'forfeited'
  ) NOT NULL DEFAULT 'pending_supervisor';

CREATE TABLE IF NOT EXISTS leave_forfeitures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leave_request_id INT UNSIGNED NOT NULL,
  days_forfeited DECIMAL(6,2) NOT NULL,
  payout_amount DECIMAL(10,2) NOT NULL,
  notes TEXT NULL,
  recorded_by_user_id INT UNSIGNED NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_leave_forfeitures_request (leave_request_id),
  CONSTRAINT fk_leave_forfeitures_request
    FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_forfeitures_user
    FOREIGN KEY (recorded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
