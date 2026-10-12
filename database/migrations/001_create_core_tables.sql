USE job_tracker;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    company_name VARCHAR(180) NOT NULL,
    job_title VARCHAR(180) NOT NULL,
    posting_url VARCHAR(2048) NULL,
    location VARCHAR(180) NULL,
    employment_type ENUM(
        'full_time',
        'part_time',
        'contract',
        'internship',
        'temporary',
        'other'
    ) NULL,
    salary_min DECIMAL(12, 2) NULL,
    salary_max DECIMAL(12, 2) NULL,
    salary_currency CHAR(3) NULL,
    applied_on DATE NULL,
    status ENUM(
        'saved',
        'applied',
        'assessment',
        'interview',
        'offer',
        'rejected',
        'withdrawn'
    ) NOT NULL DEFAULT 'saved',
    contact_person VARCHAR(180) NULL,
    notes TEXT NULL,
    follow_up_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_applications_user_id_id (user_id, id),
    KEY idx_job_applications_user_status_date (user_id, status, applied_on),
    KEY idx_job_applications_user_follow_up (user_id, follow_up_date),
    CONSTRAINT chk_job_applications_salary_range
        CHECK (
            salary_min IS NULL
            OR salary_max IS NULL
            OR salary_min <= salary_max
        ),
    CONSTRAINT fk_job_applications_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    application_id BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'saved',
        'applied',
        'assessment',
        'interview',
        'offer',
        'rejected',
        'withdrawn'
    ) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status_history_user_application_time
        (user_id, application_id, changed_at),
    CONSTRAINT fk_status_history_application_owner
        FOREIGN KEY (user_id, application_id)
        REFERENCES job_applications (user_id, id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE interviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    application_id BIGINT UNSIGNED NOT NULL,
    interview_at DATETIME NOT NULL,
    interview_type ENUM(
        'in_person',
        'phone',
        'video',
        'other'
    ) NOT NULL DEFAULT 'other',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_interviews_user_application_time
        (user_id, application_id, interview_at),
    KEY idx_interviews_user_time (user_id, interview_at),
    CONSTRAINT fk_interviews_application_owner
        FOREIGN KEY (user_id, application_id)
        REFERENCES job_applications (user_id, id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
