-- SQL pentru crearea tabelelor necesare configurărilor fiscale

-- Tabel pentru configurări fiscale generale
CREATE TABLE IF NOT EXISTS configurari_fiscale (
    id INT PRIMARY KEY DEFAULT 1,
    cota_tva DECIMAL(5,2) NOT NULL DEFAULT 19.00,
    moneda_implicita VARCHAR(3) NOT NULL DEFAULT 'RON',
    tara_selectata VARCHAR(100) NOT NULL DEFAULT 'România',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel pentru conturi bancare (dacă nu există deja)
CREATE TABLE IF NOT EXISTS conturi_bancare (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_companie INT NOT NULL DEFAULT 1,
    nume_banca VARCHAR(255) NOT NULL,
    iban VARCHAR(34) NOT NULL,
    swift VARCHAR(11),
    adresa_banca TEXT,
    moneda VARCHAR(3) NOT NULL DEFAULT 'RON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_companie (id_companie),
    INDEX idx_moneda (moneda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserează o înregistrare implicită pentru configurări fiscale
INSERT INTO configurari_fiscale (id, cota_tva, moneda_implicita, tara_selectata) 
VALUES (1, 19.00, 'RON', 'România') 
ON DUPLICATE KEY UPDATE 
    id = id;  -- Nu actualiza nimic dacă înregistrarea există deja
