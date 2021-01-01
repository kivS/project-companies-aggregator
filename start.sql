CREATE TABLE IF NOT EXISTS stonks (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	symbol TEXT NOT NULL UNIQUE,
	name TEXT NOT NULL,
	country TEXT,
	ipo_year INTEGER,
	sector TEXT,
	industry TEXT,
	exchange TEXT,
	extraction_date TEXT
);
