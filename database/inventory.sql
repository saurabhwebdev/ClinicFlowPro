CREATE TABLE IF NOT EXISTS inventory_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    category VARCHAR(100),
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50),
    min_quantity INT DEFAULT 0,
    max_quantity INT,
    unit_cost DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    supplier VARCHAR(255),
    location VARCHAR(255),
    expiry_date DATE,
    last_restock_date DATE,
    status ENUM('active', 'low_stock', 'out_of_stock', 'discontinued') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    reference_number VARCHAR(100),
    notes TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
); 