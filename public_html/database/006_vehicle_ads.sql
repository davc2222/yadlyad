DROP TABLE IF EXISTS vehicle_ads;

CREATE TABLE vehicle_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,

    title VARCHAR(150) NOT NULL,
    description TEXT,

    price INT DEFAULT NULL,
    is_price_flexible TINYINT(1) DEFAULT 0,

    region_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,

    manufacturer_id INT DEFAULT NULL,
    model_id INT DEFAULT NULL,

    year SMALLINT DEFAULT NULL,
    road_month TINYINT DEFAULT NULL,
    hand TINYINT DEFAULT NULL,
    km INT DEFAULT NULL,

    body_type_id INT DEFAULT NULL,
    gearbox_id INT DEFAULT NULL,
    fuel_type_id INT DEFAULT NULL,
    engine_volume INT DEFAULT NULL,
    horse_power INT DEFAULT NULL,
    drive_type_id INT DEFAULT NULL,

    doors TINYINT DEFAULT NULL,
    seats TINYINT DEFAULT NULL,

    color_id INT DEFAULT NULL,
    ownership_type_id INT DEFAULT NULL,
    condition_id INT DEFAULT NULL,

    test_until DATE DEFAULT NULL,

    has_abs TINYINT(1) DEFAULT 0,
    has_esp TINYINT(1) DEFAULT 0,
    has_airbags TINYINT(1) DEFAULT 0,
    has_reverse_camera TINYINT(1) DEFAULT 0,
    has_parking_sensors TINYINT(1) DEFAULT 0,
    has_sunroof TINYINT(1) DEFAULT 0,
    has_multimedia TINYINT(1) DEFAULT 0,
    has_navigation TINYINT(1) DEFAULT 0,
    has_cruise_control TINYINT(1) DEFAULT 0,
    has_alloy_wheels TINYINT(1) DEFAULT 0,
    has_leather_seats TINYINT(1) DEFAULT 0,
    has_android_auto TINYINT(1) DEFAULT 0,
    has_apple_carplay TINYINT(1) DEFAULT 0,

    phone VARCHAR(30) DEFAULT NULL,
    hide_phone TINYINT(1) DEFAULT 0,
    allow_whatsapp TINYINT(1) DEFAULT 1,

    status ENUM('pending','active','inactive','deleted') DEFAULT 'pending',

    views INT DEFAULT 0,
    favorites INT DEFAULT 0,

    is_featured TINYINT(1) DEFAULT 0,
    is_urgent TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_category (category_id, subcategory_id),
    INDEX idx_location (region_id, city_id),
    INDEX idx_vehicle_main (manufacturer_id, model_id, year),
    INDEX idx_price (price),
    INDEX idx_status (status, is_deleted),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;