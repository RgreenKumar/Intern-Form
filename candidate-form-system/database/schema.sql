-- ============================================================
-- Dynamic Candidate & Form Management System
-- Database Schema (MySQL)
-- Built strictly from README.md — no extra tables/fields added
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. users
-- Core account table (Admin + Candidate share this)
-- ------------------------------------------------------------
CREATE TABLE users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(191) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NOT NULL,
    role                ENUM('ADMIN', 'CANDIDATE') NOT NULL,
    account_status      ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
    email_verified      TINYINT(1) NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. admin_profiles
-- ------------------------------------------------------------
CREATE TABLE admin_profiles (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    name                VARCHAR(150) NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. candidate_profiles
-- ------------------------------------------------------------
CREATE TABLE candidate_profiles (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    name                VARCHAR(150) NOT NULL,
    date_of_birth       DATE NOT NULL,
    phone               VARCHAR(20) NOT NULL,
    college             VARCHAR(200) NULL,
    course              VARCHAR(150) NULL,
    year                VARCHAR(20) NULL,
    profile_photo       VARCHAR(255) NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. email_verifications
-- Used for candidate registration OTP (6 digits) and email-change verification
-- ------------------------------------------------------------
CREATE TABLE email_verifications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    email               VARCHAR(191) NOT NULL,
    otp_code            CHAR(6) NOT NULL,
    is_verified         TINYINT(1) NOT NULL DEFAULT 0,
    expires_at          DATETIME NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_verifications_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email_verifications_user (user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. forms
-- Dynamic form definitions (Internship, Webinar, Workshop, etc.)
-- ------------------------------------------------------------
CREATE TABLE forms (
    id                                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id                            INT UNSIGNED NOT NULL,
    title                                VARCHAR(200) NOT NULL,
    description                         TEXT NULL,
    slug                                 VARCHAR(220) NOT NULL UNIQUE,
    status                               ENUM('DRAFT', 'ACTIVE', 'CLOSED', 'ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    allow_multiple_submissions           TINYINT(1) NOT NULL DEFAULT 0,
    login_requirement                    TINYINT(1) NOT NULL DEFAULT 0,
    public_visibility                    ENUM('PUBLIC', 'PRIVATE') NOT NULL DEFAULT 'PUBLIC',
    require_website_registration         TINYINT(1) NOT NULL DEFAULT 0,
    registration_message                 TEXT NULL,
    registration_button_text             VARCHAR(100) NULL,
    external_url                          VARCHAR(500) NULL COMMENT 'If set, Apply Now sends candidates here (e.g. a Google Form) instead of the built-in form',
    created_at                           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_forms_admin
        FOREIGN KEY (admin_id) REFERENCES admin_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. form_fields
-- ------------------------------------------------------------
CREATE TABLE form_fields (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id             INT UNSIGNED NOT NULL,
    field_label         VARCHAR(150) NOT NULL,
    field_name          VARCHAR(150) NOT NULL,
    field_type          ENUM('TEXT','EMAIL','PHONE','NUMBER','DATE','TEXTAREA','SELECT','RADIO','CHECKBOX','FILE') NOT NULL,
    is_required         TINYINT(1) NOT NULL DEFAULT 0,
    placeholder         VARCHAR(200) NULL,
    help_text           VARCHAR(255) NULL,
    display_order       INT UNSIGNED NOT NULL DEFAULT 0,
    options              TEXT NULL COMMENT 'JSON array of options, used for SELECT / RADIO',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_form_fields_form
        FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    INDEX idx_form_fields_form (form_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. form_submissions
-- ------------------------------------------------------------
CREATE TABLE form_submissions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id             INT UNSIGNED NOT NULL,
    candidate_id        INT UNSIGNED NULL COMMENT 'NULL until linked to a candidate account',
    application_status  ENUM('SUBMITTED','UNDER_REVIEW','ACCEPTED','REJECTED','ARCHIVED') NOT NULL DEFAULT 'SUBMITTED',
    submitted_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_form_submissions_form
        FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    CONSTRAINT fk_form_submissions_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE SET NULL,
    INDEX idx_form_submissions_form (form_id),
    INDEX idx_form_submissions_candidate (candidate_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. submission_values
-- Snapshot values at time of submission (never overwritten by profile changes)
-- ------------------------------------------------------------
CREATE TABLE submission_values (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id       INT UNSIGNED NOT NULL,
    form_field_id       INT UNSIGNED NOT NULL,
    field_value          TEXT NULL,
    file_path            VARCHAR(255) NULL COMMENT 'used when field_type = FILE',
    CONSTRAINT fk_submission_values_submission
        FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_values_field
        FOREIGN KEY (form_field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    INDEX idx_submission_values_submission (submission_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. tasks
-- ------------------------------------------------------------
CREATE TABLE tasks (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id            INT UNSIGNED NOT NULL,
    candidate_id        INT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    description         TEXT NULL,
    due_date            DATE NULL,
    priority            ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM',
    status              ENUM('PENDING','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_admin
        FOREIGN KEY (admin_id) REFERENCES admin_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    INDEX idx_tasks_candidate (candidate_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. task_attachments
-- Supports LINK and FILE resource types
-- ------------------------------------------------------------
CREATE TABLE task_attachments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id             INT UNSIGNED NOT NULL,
    attachment_type     ENUM('LINK','FILE') NOT NULL,
    url                 VARCHAR(500) NULL COMMENT 'used when attachment_type = LINK',
    file_path           VARCHAR(255) NULL COMMENT 'used when attachment_type = FILE',
    original_filename   VARCHAR(255) NULL,
    mime_type           VARCHAR(100) NULL,
    file_size           INT UNSIGNED NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_attachments_task
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_attachments_task (task_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. task_comments
-- ------------------------------------------------------------
CREATE TABLE task_comments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id             INT UNSIGNED NOT NULL,
    user_id             INT UNSIGNED NOT NULL COMMENT 'author of the comment (admin or candidate)',
    message              TEXT NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_comments_task
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task_comments_task (task_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. task_status_history
-- ------------------------------------------------------------
CREATE TABLE task_status_history (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id             INT UNSIGNED NOT NULL,
    status              ENUM('PENDING','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL,
    changed_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_status_history_task
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_status_history_task (task_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 13. candidate_documents
-- ------------------------------------------------------------
CREATE TABLE candidate_documents (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id        INT UNSIGNED NOT NULL,
    admin_id            INT UNSIGNED NOT NULL COMMENT 'admin who uploaded the document',
    document_type       ENUM(
                            'Internship Acceptance Letter',
                            'Internship Completion Certificate',
                            'Offer Letter',
                            'Participation Certificate',
                            'Payment Receipt',
                            'ID Card',
                            'Training Certificate',
                            'Other'
                        ) NOT NULL,
    document_title       VARCHAR(200) NOT NULL,
    file_path             VARCHAR(255) NOT NULL,
    original_filename     VARCHAR(255) NOT NULL,
    mime_type              VARCHAR(100) NOT NULL,
    file_size               INT UNSIGNED NOT NULL,
    message_note             TEXT NULL,
    document_status           ENUM('AVAILABLE','ARCHIVED') NOT NULL DEFAULT 'AVAILABLE',
    uploaded_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_documents_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_candidate_documents_admin
        FOREIGN KEY (admin_id) REFERENCES admin_profiles(id) ON DELETE CASCADE,
    INDEX idx_candidate_documents_candidate (candidate_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 14. notifications
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    message              TEXT NULL,
    is_read              TINYINT(1) NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 15. admin_activity_logs
-- ------------------------------------------------------------
CREATE TABLE admin_activity_logs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id            INT UNSIGNED NOT NULL,
    activity             VARCHAR(255) NOT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_activity_logs_admin
        FOREIGN KEY (admin_id) REFERENCES admin_profiles(id) ON DELETE CASCADE,
    INDEX idx_admin_activity_logs_admin (admin_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
