-- My Game Circle — MySQL/MariaDB schema
-- Run once against a fresh database: `mysql -u USER -p DBNAME < sql/schema.sql`

CREATE TABLE IF NOT EXISTS invite_codes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(32) NOT NULL UNIQUE,
  max_uses      INT NOT NULL DEFAULT 1,
  uses_count    INT NOT NULL DEFAULT 0,
  expires_at    DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(255) NOT NULL UNIQUE,
  username        VARCHAR(64) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  accent_color    VARCHAR(7) NULL,        -- e.g. "#e63946"; NULL = default site color
  invite_code_id  INT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invite_code_id) REFERENCES invite_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS play_groups (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(120) NOT NULL,
  invite_code     VARCHAR(16) NOT NULL UNIQUE,
  created_by_id   INT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS play_group_members (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  play_group_id   INT NOT NULL,
  role            ENUM('OWNER','ADMIN','MEMBER') NOT NULL DEFAULT 'MEMBER',
  joined_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member (user_id, play_group_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (play_group_id) REFERENCES play_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  bgg_id          INT NULL UNIQUE,
  is_manual       TINYINT(1) NOT NULL DEFAULT 0,
  name            VARCHAR(255) NOT NULL,
  year_published  SMALLINT NULL,
  image           VARCHAR(255) NULL,      -- local optimized "detail" image path
  thumbnail       VARCHAR(255) NULL,      -- local optimized "grid" thumbnail path
  description     TEXT NULL,
  tagline         VARCHAR(200) NULL,     -- short one-line blurb, set by the user (not auto-filled from BGG)
  min_players     SMALLINT NULL,
  max_players     SMALLINT NULL,
  playing_time    SMALLINT NULL,
  min_play_time   SMALLINT NULL,
  max_play_time   SMALLINT NULL,
  min_age         SMALLINT NULL,
  weight          DECIMAL(3,2) NULL,
  bgg_rating      DECIMAL(4,2) NULL,
  bgg_rank        INT NULL,
  categories      JSON NULL,              -- ["Strategie", ...]
  primary_category VARCHAR(100) NULL,     -- which of `categories` to show on hover; falls back to categories[0]
  mechanics       JSON NULL,              -- ["Dice Rolling", ...]
  designers       JSON NULL,              -- [{"bggId":26,"name":"Klaus Teuber"}, ...]
  artists         JSON NULL,
  publishers      JSON NULL,
  expansions      JSON NULL,              -- [{"bggId":X,"name":"..."}] — this game's own expansions
  expansion_of    JSON NULL,              -- {"bggId":X,"name":"..."} if this game IS an expansion, else NULL
  show_in_collection TINYINT(1) NOT NULL DEFAULT 0, -- override: show in collection/similar/tag grids even though this is an expansion
  how_to_play_url VARCHAR(500) NULL,
  cached_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_entries (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  game_id     INT NOT NULL,
  notes       TEXT NULL,
  loaned_to   VARCHAR(100) NULL,      -- name of who this copy is currently lent out to, NULL if not loaned
  loaned_at   DATETIME NULL,          -- when it was marked as loaned out
  added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_entry (user_id, game_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wishlist_entries (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  game_id     INT NOT NULL,
  added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_wishlist_entry (user_id, game_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
