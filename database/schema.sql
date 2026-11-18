-- ------------------------------------------------------------------
-- Ontmoet de Oudegracht – databaseschema (Technisch Ontwerp §4)
-- MySQL 8 / MariaDB 10.4+
-- ------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS oudegracht
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oudegracht;

-- Beheerders van het CMS
CREATE TABLE IF NOT EXISTS gebruikers (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  naam            VARCHAR(100)  NOT NULL,
  email           VARCHAR(150)  NOT NULL,
  wachtwoord_hash VARCHAR(255)  NOT NULL,
  aangemaakt_op   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gebruikers_email (email)
) ENGINE=InnoDB;

-- Infopunten langs de gracht
CREATE TABLE IF NOT EXISTS punten (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  titel             VARCHAR(150)  NOT NULL,
  tijdstip_seconden INT UNSIGNED  NOT NULL,          -- moment in de video
  latitude          DECIMAL(9,6)  NOT NULL,
  longitude         DECIMAL(9,6)  NOT NULL,
  volgorde          INT           NOT NULL DEFAULT 0,
  korte_tekst       VARCHAR(300)  NOT NULL,          -- voor kinderen
  uitleg            TEXT          NULL,
  wist_je_dat       VARCHAR(300)  NULL,              -- 'wist je dat'-regel (TO §7)
  actief            BOOLEAN       NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  KEY idx_punten_tijdstip (tijdstip_seconden)
) ENGINE=InnoDB;

-- Foto's, kaarten, films en documenten bij een punt (1-op-n)
CREATE TABLE IF NOT EXISTS bronnen (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  punt_id        INT UNSIGNED  NOT NULL,
  type           ENUM('foto','kaart','film','document') NOT NULL,
  titel          VARCHAR(150)  NOT NULL,
  bestandspad    VARCHAR(255)  NOT NULL,
  bijschrift     VARCHAR(300)  NULL,
  bronvermelding VARCHAR(200)  NULL,
  jaar           SMALLINT      NULL,
  PRIMARY KEY (id),
  KEY idx_bronnen_punt (punt_id),
  CONSTRAINT fk_bronnen_punt FOREIGN KEY (punt_id) REFERENCES punten (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Route van de boot: tijdstip-coördinaatparen
CREATE TABLE IF NOT EXISTS route_punten (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  tijdstip_seconden INT UNSIGNED  NOT NULL,
  latitude          DECIMAL(9,6)  NOT NULL,
  longitude         DECIMAL(9,6)  NOT NULL,
  volgorde          INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_route_tijdstip (tijdstip_seconden)
) ENGINE=InnoDB;

-- Sleutel-waardeparen (videobestand, inactiviteitstijd, ...)
CREATE TABLE IF NOT EXISTS instellingen (
  sleutel VARCHAR(50)  NOT NULL,
  waarde  VARCHAR(255) NOT NULL,
  PRIMARY KEY (sleutel)
) ENGINE=InnoDB;

-- Mislukte inlogpogingen, voor de beperking van het aantal pogingen (TO §6)
CREATE TABLE IF NOT EXISTS inlogpogingen (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip       VARCHAR(45)  NOT NULL,
  email    VARCHAR(150) NOT NULL,
  tijdstip DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pogingen (ip, email, tijdstip)
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Databasegebruikers (TO §6): voer uit als root en kies eigen wachtwoorden.
-- De publieke API leest alleen; het CMS mag schrijven.
-- ------------------------------------------------------------------
-- CREATE USER IF NOT EXISTS 'hua_lezen'@'%'     IDENTIFIED BY 'kies-een-wachtwoord';
-- CREATE USER IF NOT EXISTS 'hua_schrijven'@'%' IDENTIFIED BY 'kies-een-wachtwoord';
-- GRANT SELECT ON oudegracht.punten       TO 'hua_lezen'@'%';
-- GRANT SELECT ON oudegracht.bronnen      TO 'hua_lezen'@'%';
-- GRANT SELECT ON oudegracht.route_punten TO 'hua_lezen'@'%';
-- GRANT SELECT ON oudegracht.instellingen TO 'hua_lezen'@'%';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON oudegracht.* TO 'hua_schrijven'@'%';
-- FLUSH PRIVILEGES;
