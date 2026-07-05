ALTER TABLE leave_requests
  ADD COLUMN passport_photo_path VARCHAR(255) NULL AFTER attachment_path;
