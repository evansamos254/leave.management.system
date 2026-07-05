ALTER TABLE users
  MODIFY role ENUM('admin', 'employee', 'supervisor', 'hr', 'director', 'chief_officer') NOT NULL DEFAULT 'employee';
