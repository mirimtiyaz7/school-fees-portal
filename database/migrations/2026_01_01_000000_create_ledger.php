<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE schools (
    id bigserial PRIMARY KEY, name varchar(120) NOT NULL
);
CREATE TABLE users (
    id bigserial PRIMARY KEY, school_id bigint NOT NULL REFERENCES schools,
    name varchar(120) NOT NULL, email varchar(255) UNIQUE NOT NULL,
    password varchar(255) NOT NULL, role varchar(16) NOT NULL CHECK (role IN ('accountant','parent')),
    UNIQUE (school_id, id)
);
CREATE TABLE accounts (
    id bigserial PRIMARY KEY, school_id bigint NOT NULL REFERENCES schools,
    parent_id bigint NOT NULL, student varchar(120) NOT NULL,
    fee_paise bigint NOT NULL CHECK (fee_paise > 0),
    FOREIGN KEY (school_id, parent_id) REFERENCES users (school_id, id),
    UNIQUE (school_id, id)
);
CREATE TABLE payments (
    id bigserial PRIMARY KEY, school_id bigint NOT NULL, account_id bigint NOT NULL,
    posted_by bigint NOT NULL, request_key uuid NOT NULL, amount_paise bigint NOT NULL CHECK (amount_paise > 0),
    created_at timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id, account_id) REFERENCES accounts (school_id, id),
    FOREIGN KEY (school_id, posted_by) REFERENCES users (school_id, id),
    UNIQUE (account_id, request_key)
);
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE payments, accounts, users, schools');
    }
};
