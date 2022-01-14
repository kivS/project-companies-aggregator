CREATE TABLE stonks(
    id INTEGER NOT NULL PRIMARY KEY,
    symbol TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    ipo_year INTEGER,
    country TEXT,
    sector TEXT,
    industry TEXT,
    is_public BOOLEAN,
    description TEXT,
    extraction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uid text,
    tags text,
    clean_name text,
    media_links text
);

CREATE TABLE user_searches (
    id integer, 
    problem text NOT NULL, 
    user_ip text, 
    user_agent text, 
    nb_hits int, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    PRIMARY KEY (id)
);