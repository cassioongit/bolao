-- Bolão da Copa do Mundo 2026 — schema MySQL
-- Charset utf8mb4 em tudo. Datas/horários armazenados em UTC.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome                VARCHAR(80)  NOT NULL,
    email               VARCHAR(190) NOT NULL,
    senha_hash          VARCHAR(255) NOT NULL,
    is_admin            TINYINT(1)   NOT NULL DEFAULT 0,
    email_verificado    TINYINT(1)   NOT NULL DEFAULT 0,
    token_verificacao   VARCHAR(64)  DEFAULT NULL,
    token_reset         VARCHAR(64)  DEFAULT NULL,
    token_reset_exp     DATETIME     DEFAULT NULL,
    criado_em           DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teams (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome      VARCHAR(60)  NOT NULL,
    sigla     VARCHAR(3)   NOT NULL,
    bandeira  VARCHAR(8)   DEFAULT NULL,   -- emoji da bandeira
    grupo     CHAR(1)      DEFAULT NULL,   -- A..L (NULL = ainda indefinido / repescagem)
    PRIMARY KEY (id),
    UNIQUE KEY uq_sigla (sigla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS matches (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero           INT UNSIGNED DEFAULT NULL,            -- nº oficial do jogo (1..104)
    fase             ENUM('grupos','r32','r16','qf','sf','terceiro','final') NOT NULL,
    grupo            CHAR(1)      DEFAULT NULL,
    rodada_label     VARCHAR(40)  DEFAULT NULL,            -- ex.: "Rodada 1", "Oitavas"
    home_team_id     INT UNSIGNED DEFAULT NULL,
    away_team_id     INT UNSIGNED DEFAULT NULL,
    home_placeholder VARCHAR(30)  DEFAULT NULL,            -- ex.: "1A", "2B", "Venc. J73"
    away_placeholder VARCHAR(30)  DEFAULT NULL,
    kickoff_utc      DATETIME     NOT NULL,
    sede             VARCHAR(80)  DEFAULT NULL,
    home_score       TINYINT UNSIGNED DEFAULT NULL,
    away_score       TINYINT UNSIGNED DEFAULT NULL,
    status           ENUM('agendado','encerrado') NOT NULL DEFAULT 'agendado',
    PRIMARY KEY (id),
    KEY idx_kickoff (kickoff_utc),
    KEY idx_fase (fase),
    CONSTRAINT fk_match_home FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_away FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pools (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(80)  NOT NULL,
    owner_user_id INT UNSIGNED NOT NULL,
    invite_token  VARCHAR(40)  NOT NULL,
    -- pontuação de placares
    pts_exato     SMALLINT NOT NULL DEFAULT 10,
    pts_saldo     SMALLINT NOT NULL DEFAULT 7,
    pts_gols_um   SMALLINT NOT NULL DEFAULT 5,
    pts_vencedor  SMALLINT NOT NULL DEFAULT 3,
    -- pontuação de bônus
    pts_campeao    SMALLINT NOT NULL DEFAULT 30,
    pts_vice       SMALLINT NOT NULL DEFAULT 20,
    pts_terceiro   SMALLINT NOT NULL DEFAULT 15,
    pts_quarto     SMALLINT NOT NULL DEFAULT 10,
    pts_artilheiro SMALLINT NOT NULL DEFAULT 15,
    criado_em     DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (invite_token),
    CONSTRAINT fk_pool_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pool_members (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pool_id             INT UNSIGNED NOT NULL,
    user_id             INT UNSIGNED NOT NULL,
    papel               ENUM('owner','membro') NOT NULL DEFAULT 'membro',
    palpite_padrao_home TINYINT UNSIGNED DEFAULT NULL,
    palpite_padrao_away TINYINT UNSIGNED DEFAULT NULL,
    entrou_em           DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pool_user (pool_id, user_id),
    CONSTRAINT fk_pm_pool FOREIGN KEY (pool_id) REFERENCES pools(id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS predictions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pool_id       INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    match_id      INT UNSIGNED NOT NULL,
    home_pred     TINYINT UNSIGNED NOT NULL,
    away_pred     TINYINT UNSIGNED NOT NULL,
    pontos        SMALLINT DEFAULT NULL,   -- NULL = jogo ainda sem resultado
    atualizado_em DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pred (pool_id, user_id, match_id),
    KEY idx_match (match_id),
    CONSTRAINT fk_pred_pool  FOREIGN KEY (pool_id)  REFERENCES pools(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pred_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pred_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bonus_predictions (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pool_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    chave   ENUM('campeao','vice','terceiro','quarto','artilheiro') NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    pontos  SMALLINT DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bonus (pool_id, user_id, chave),
    CONSTRAINT fk_bp_pool FOREIGN KEY (pool_id) REFERENCES pools(id) ON DELETE CASCADE,
    CONSTRAINT fk_bp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bp_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Gabarito dos bônus (linha única, id=1). NULL = ainda não definido.
CREATE TABLE IF NOT EXISTS tournament_results (
    id                 TINYINT UNSIGNED NOT NULL DEFAULT 1,
    campeao_team_id    INT UNSIGNED DEFAULT NULL,
    vice_team_id       INT UNSIGNED DEFAULT NULL,
    terceiro_team_id   INT UNSIGNED DEFAULT NULL,
    quarto_team_id     INT UNSIGNED DEFAULT NULL,
    artilheiro_team_id INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tournament_results (id) VALUES (1)
    ON DUPLICATE KEY UPDATE id = id;
