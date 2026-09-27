ALTER TABLE users
    MODIFY COLUMN password VARCHAR(255)
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
    NULL;

CREATE TABLE IF NOT EXISTS user_auth_providers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    provider VARCHAR(32)
        CHARACTER SET ascii
        COLLATE ascii_bin
        NOT NULL,
    provider_user_id VARCHAR(255)
        CHARACTER SET ascii
        COLLATE ascii_bin
        NOT NULL,
    provider_email VARCHAR(254)
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
        DEFAULT NULL,
    provider_email_verified TINYINT(1) NOT NULL DEFAULT 0,
    avatar_url TEXT
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_provider_identity (provider, provider_user_id),
    UNIQUE KEY uq_user_auth_provider (user_id, provider),
    CONSTRAINT fk_user_auth_providers_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
