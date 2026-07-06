-- Torns module upgrade
-- Run once on each environment (proves, PRD)
-- Safe to run on environments where aixada_torns does not yet exist.

CREATE TABLE IF NOT EXISTS aixada_torns (
    dataTorn       DATE        NOT NULL,
    ufTorn         INT         NOT NULL,
    task_type      VARCHAR(20) NOT NULL DEFAULT 'repartiment',
    is_responsible TINYINT(1)  NOT NULL DEFAULT 0,
    PRIMARY KEY (dataTorn, ufTorn, task_type)
);

-- If the table already existed without the new columns, add them:
ALTER TABLE aixada_torns
    ADD COLUMN IF NOT EXISTS task_type      VARCHAR(20) NOT NULL DEFAULT 'repartiment' AFTER ufTorn,
    ADD COLUMN IF NOT EXISTS is_responsible TINYINT(1)  NOT NULL DEFAULT 0             AFTER task_type;

CREATE TABLE IF NOT EXISTS aixada_torns_config (
    setting VARCHAR(50)  NOT NULL,
    value   VARCHAR(255) NOT NULL,
    PRIMARY KEY (setting)
);

INSERT IGNORE INTO aixada_torns_config VALUES
    ('repartiment_count', '6'),
    ('repartiment_freq',  '1'),
    ('neteja_count',      '3'),
    ('neteja_freq',       '2'),
    ('advance_months',    '2');

CREATE TABLE IF NOT EXISTS aixada_torns_restriction (
    type  VARCHAR(20) NOT NULL,
    uf_id INT         NOT NULL,
    PRIMARY KEY (type, uf_id)
);

CREATE TABLE IF NOT EXISTS aixada_torns_incompatible (
    uf_id_1 INT NOT NULL,
    uf_id_2 INT NOT NULL,
    PRIMARY KEY (uf_id_1, uf_id_2)
);
