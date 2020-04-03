create table users (
  user_id varchar(255) NOT NULL CHECK (user_id <> ''),
  username varchar(255) NOT NULL CHECK (username <> ''),
  name_override varchar(255) NULL,
  PRIMARY KEY(user_id)
); 

create table games (
  game_code varchar(55) NOT NULL,
  owner varchar(255) NOT NULL CHECK (owner <> ''),
  completed boolean DEFAULT FALSE,
  unlocked_by VARCHAR(255) DEFAULT NULL,
  has_started boolean DEFAULT FALSE,
  secret_code INTEGER(5) ZEROFILL NOT NULL,
  hint_code integer DEFAULT NULL, 
  veto_used BOOLEAN DEFAULT FALSE,
  blind_used BOOLEAN DEFAULT FALSE,
  mute_used BOOLEAN DEFAULT FALSE,
  kill_used BOOLEAN DEFAULT FALSE,
  PRIMARY KEY(game_code)
); 

create table players (
  game_code VARCHAR(55) NOT NULL,
  user_id VARCHAR(255) NOT NULL CHECK (user_id <> ''),
  died BOOLEAN DEFAULT FALSE,
  character_type VARCHAR(55),
  blinded_until TIMESTAMP DEFAULT NULL,
  muted_until TIMESTAMP DEFAULT NULL,
  known_signals VARCHAR(255) DEFAULT NULL,
  unlock_attempted INTEGER(5) DEFAULT NULL,
  PRIMARY KEY(game_code, user_id)
); 
