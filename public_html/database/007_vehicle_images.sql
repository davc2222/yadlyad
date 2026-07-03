CREATE TABLE IF NOT EXISTS vehicle_images (
    id INT AUTO_INCREMENT PRIMARY KEY,

    ad_id INT NOT NULL,

    image_name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,

    sort_order INT DEFAULT 0,
    is_main TINYINT(1) DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ad_id (ad_id),
    INDEX idx_main (ad_id, is_main),
    INDEX idx_sort (ad_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;