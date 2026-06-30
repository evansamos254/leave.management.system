CREATE DATABASE IF NOT EXISTS leave_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE leave_system;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS approval_steps;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS leave_balances;
DROP TABLE IF EXISTS holidays;
DROP TABLE IF EXISTS leave_types;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS directorates;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  national_id VARCHAR(50) NULL UNIQUE,
  gender ENUM('male', 'female') NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employee', 'supervisor', 'hr', 'director') NOT NULL DEFAULT 'employee',
  phone VARCHAR(30) NULL,
  profile_photo_path VARCHAR(255) NULL,
  employment_document_path VARCHAR(255) NULL,
  status ENUM('pending', 'active', 'inactive', 'rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  last_login_at DATETIME NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE directorates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  directorate_id INT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_departments_directorate
    FOREIGN KEY (directorate_id) REFERENCES directorates(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE employees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  staff_id VARCHAR(50) NOT NULL UNIQUE,
  department_id INT UNSIGNED NULL,
  designation VARCHAR(120) NULL,
  supervisor_id INT UNSIGNED NULL,
  employment_date DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_employees_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_employees_department
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_employees_supervisor
    FOREIGN KEY (supervisor_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE leave_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  gender_eligibility ENUM('any', 'male', 'female') NOT NULL DEFAULT 'any',
  default_entitlement DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  requires_balance TINYINT(1) NOT NULL DEFAULT 1,
  requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
  attachment_after_days DECIMAL(6,2) NULL,
  is_paid TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE holidays (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  holiday_date DATE NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE leave_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL, -- Financial year start year, e.g. 2025 for 2025/2026
  entitlement DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  carried_forward DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  used_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_leave_balance_employee_type_year (employee_id, leave_type_id, year),
  CONSTRAINT fk_leave_balances_employee
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_balances_type
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NOT NULL,
  contact_number VARCHAR(30) NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days_requested DECIMAL(6,2) NOT NULL,
  reason TEXT NULL,
  handover_notes TEXT NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM(
    'pending_supervisor',
    'approved',
    'rejected',
    'cancelled'
  ) NOT NULL DEFAULT 'pending_supervisor',
  rejection_reason TEXT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finalized_at DATETIME NULL,
  resumed_at DATETIME NULL,
  end_reminder_sent_at DATETIME NULL,
  resumed_by_user_id INT UNSIGNED NULL,
  resumption_notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_leave_requests_status (status),
  KEY idx_leave_requests_dates (start_date, end_date),
  CONSTRAINT fk_leave_requests_employee
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_requests_type
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
  CONSTRAINT fk_leave_requests_resumed_by
    FOREIGN KEY (resumed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE approval_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leave_request_id INT UNSIGNED NOT NULL,
  step_order TINYINT UNSIGNED NOT NULL,
  role ENUM('supervisor') NOT NULL,
  approver_user_id INT UNSIGNED NULL,
  action ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  comments TEXT NULL,
  acted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_approval_step_request_role (leave_request_id, role),
  CONSTRAINT fk_approval_steps_request
    FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_approval_steps_user
    FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user_read (user_id, is_read),
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id INT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_logs_user (user_id),
  KEY idx_audit_logs_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (full_name, email, password_hash, role, status)
VALUES (
  'System Administrator',
  'admin@leavesystem.local',
  'pbkdf2_sha256$120000$7c3f1e9a2b4d6f8091a3c5e7d9b0f246$a8e6bfd6ba64705dd60789355788371b1b74b568ec55ce0aea9325f1dd1b507d',
  'admin',
  'active'
);

INSERT INTO directorates (name)
VALUES
  ('Education And Industrial Skills Development'),
  ('Health & Sanitation'),
  ('Lands, Housing And Urban Development'),
  ('Public Service Management & Governance'),
  ('Smart Agriculture, Livestock, Fisheries, Blue Economy'),
  ('Strategic Partnerships, ICT And Digital Economy'),
  ('The County Treasury And Economic Planning'),
  ('Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'),
  ('Transport, Roads And Public Works'),
  ('Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'),
  ('Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts');

INSERT INTO departments (directorate_id, name)
SELECT id, 'Education' FROM directorates WHERE name = 'Education And Industrial Skills Development'
UNION ALL SELECT id, 'Industrial Skills Development' FROM directorates WHERE name = 'Education And Industrial Skills Development'
UNION ALL SELECT id, 'Health' FROM directorates WHERE name = 'Health & Sanitation'
UNION ALL SELECT id, 'Sanitation' FROM directorates WHERE name = 'Health & Sanitation'
UNION ALL SELECT id, 'Lands' FROM directorates WHERE name = 'Lands, Housing And Urban Development'
UNION ALL SELECT id, 'Housing' FROM directorates WHERE name = 'Lands, Housing And Urban Development'
UNION ALL SELECT id, 'Urban Development' FROM directorates WHERE name = 'Lands, Housing And Urban Development'
UNION ALL SELECT id, 'Public Service Management' FROM directorates WHERE name = 'Public Service Management & Governance'
UNION ALL SELECT id, 'Governance' FROM directorates WHERE name = 'Public Service Management & Governance'
UNION ALL SELECT id, 'Smart Agriculture' FROM directorates WHERE name = 'Smart Agriculture, Livestock, Fisheries, Blue Economy'
UNION ALL SELECT id, 'Livestock' FROM directorates WHERE name = 'Smart Agriculture, Livestock, Fisheries, Blue Economy'
UNION ALL SELECT id, 'Fisheries' FROM directorates WHERE name = 'Smart Agriculture, Livestock, Fisheries, Blue Economy'
UNION ALL SELECT id, 'Blue Economy' FROM directorates WHERE name = 'Smart Agriculture, Livestock, Fisheries, Blue Economy'
UNION ALL SELECT id, 'ICT and Digital Economy' FROM directorates WHERE name = 'Strategic Partnerships, ICT And Digital Economy'
UNION ALL SELECT id, 'Strategic Partnership and SDG' FROM directorates WHERE name = 'Strategic Partnerships, ICT And Digital Economy'
UNION ALL SELECT id, 'Resource Mobilisation' FROM directorates WHERE name = 'Strategic Partnerships, ICT And Digital Economy'
UNION ALL SELECT id, 'County Treasury' FROM directorates WHERE name = 'The County Treasury And Economic Planning'
UNION ALL SELECT id, 'Economic Planning' FROM directorates WHERE name = 'The County Treasury And Economic Planning'
UNION ALL SELECT id, 'Trade' FROM directorates WHERE name = 'Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'
UNION ALL SELECT id, 'Investment' FROM directorates WHERE name = 'Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'
UNION ALL SELECT id, 'Industrialization' FROM directorates WHERE name = 'Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'
UNION ALL SELECT id, 'Cooperatives' FROM directorates WHERE name = 'Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'
UNION ALL SELECT id, 'Small Micro Enterprises (SME)' FROM directorates WHERE name = 'Trade, Investment, Industrialization, Cooperatives And Small Micro Enterprises (SME)'
UNION ALL SELECT id, 'Transport' FROM directorates WHERE name = 'Transport, Roads And Public Works'
UNION ALL SELECT id, 'Roads' FROM directorates WHERE name = 'Transport, Roads And Public Works'
UNION ALL SELECT id, 'Public Works' FROM directorates WHERE name = 'Transport, Roads And Public Works'
UNION ALL SELECT id, 'Water' FROM directorates WHERE name = 'Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'
UNION ALL SELECT id, 'Irrigation' FROM directorates WHERE name = 'Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'
UNION ALL SELECT id, 'Environment' FROM directorates WHERE name = 'Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'
UNION ALL SELECT id, 'Natural Resources' FROM directorates WHERE name = 'Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'
UNION ALL SELECT id, 'Climate Change And Energy' FROM directorates WHERE name = 'Water, Irrigation, Environment, Natural Resources, Climate Change And Energy'
UNION ALL SELECT id, 'Youth' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Sports' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Tourism' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Culture' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Social Protection' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Gender Affairs' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts'
UNION ALL SELECT id, 'Creative Arts' FROM directorates WHERE name = 'Youth, Sports, Tourism, Culture, Social Protection, Gender Affairs And Creative Arts';

INSERT INTO leave_types
  (name, gender_eligibility, default_entitlement, requires_balance, requires_attachment, attachment_after_days, is_paid, is_active)
VALUES
  ('Annual Leave', 'any', 24.00, 1, 0, NULL, 1, 1),
  ('Sick Leave', 'any', 12.00, 0, 0, 3.00, 1, 1),
  ('Maternity Leave', 'female', 90.00, 0, 0, NULL, 1, 1),
  ('Paternity Leave', 'male', 14.00, 1, 0, NULL, 1, 1),
  ('Compassionate Leave', 'any', 5.00, 1, 1, NULL, 1, 1),
  ('Study Leave', 'any', 0.00, 0, 0, NULL, 1, 1),
  ('Unpaid Leave', 'any', 0.00, 0, 0, NULL, 0, 1);
