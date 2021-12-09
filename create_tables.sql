CREATE TABLE IF NOT EXISTS stonks(
    id INTEGER NOT NULL PRIMARY KEY
    symbol TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    ipo_year INTEGER,
    country TEXT,
    sector TEXT,
    industry TEXT,
    is_public BOOLEAN,
    description TEXT,
    extraction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
)

