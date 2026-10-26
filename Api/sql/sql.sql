-- FindMyVehicle database schema

CREATE TABLE app_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(100) NULL,
  password      VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vehicles (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  type       VARCHAR(30)   NOT NULL,
  brand      VARCHAR(50)   NULL,
  model      VARCHAR(50)   NULL,
  notes      TEXT          NULL,
  photo      LONGBLOB      NULL,
  photo_mime VARCHAR(100)  NULL,
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicles_user
    FOREIGN KEY (user_id) REFERENCES app_users(id)
    ON DELETE CASCADE,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parkings (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  vehicle_id  INT UNSIGNED NOT NULL,
  latitude    DECIMAL(10,7) NOT NULL,
  longitude   DECIMAL(10,7) NOT NULL,
  level       VARCHAR(20)   NULL,
  description TEXT          NULL,
  started_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at  DATETIME      NULL,
  closed_at   DATETIME      NULL,
  photo       LONGBLOB      NULL,
  photo_mime  VARCHAR(100)  NULL,
  CONSTRAINT fk_parkings_user
    FOREIGN KEY (user_id) REFERENCES app_users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_parkings_vehicle
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (vehicle_id),
  INDEX (closed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
