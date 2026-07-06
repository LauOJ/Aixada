-- Torns module upgrade
-- Run once on each environment (proves, PRD)

ALTER TABLE aixada_torns
    ADD COLUMN task_type     VARCHAR(20)  NOT NULL DEFAULT 'repartiment' AFTER ufTorn,
    ADD COLUMN is_responsible TINYINT(1)  NOT NULL DEFAULT 0              AFTER task_type;

CREATE TABLE IF NOT EXISTS aixada_torns_config (
    setting VARCHAR(50)  NOT NULL,
    value   VARCHAR(255) NOT NULL,
    PRIMARY KEY (setting)
);

INSERT IGNORE INTO aixada_torns_config VALUES
    ('repartiment_count',  '6'),
    ('repartiment_freq',   '1'),
    ('neteja_count',       '3'),
    ('neteja_freq',        '2'),
    ('advance_months',     '2');

CREATE TABLE IF NOT EXISTS aixada_torns_restriction (
    type   VARCHAR(20) NOT NULL,
    uf_id  INT         NOT NULL,
    PRIMARY KEY (type, uf_id)
);

CREATE TABLE IF NOT EXISTS aixada_torns_incompatible (
    uf_id_1 INT NOT NULL,
    uf_id_2 INT NOT NULL,
    PRIMARY KEY (uf_id_1, uf_id_2)
);
