#!/usr/bin/env python3
'''
    Script that will read a csv file with a list of stocks and populate/append into the sqlite database.
'''


import sqlite3
import pandas as pd
from datetime import datetime

con = sqlite3.connect('db.sqlite3')
con.row_factory = sqlite3.Row
cursor = con.cursor()

extraction_datetime = datetime.now()
stocks_file = 'stocks.csv'


df: pd.DataFrame = pd.read_csv(stocks_file, usecols=['Symbol', 'Name', 'Country', 'IPO Year', 'Sector', 'Industry'])

# lowercase all the columns
df.columns = [x.lower() for x in df.columns]

df.rename(columns={'ipo year': 'ipo_year'}, inplace=True)
df['extraction_date'] = extraction_datetime
df['is_public'] = True

# create a temp table to hold the new data that'll be diffed against the final table
df.to_sql('stonks_temp', con, if_exists='replace', index=False, dtype={'ipo_year': 'INTEGER'})
q = cursor.execute('''
    INSERT INTO stonks(symbol, name, country, ipo_year, sector, industry, is_public, extraction_date)
    SELECT 
        stonks_temp.symbol, 
        stonks_temp.name, 
        stonks_temp.country, 
        stonks_temp.ipo_year, 
        stonks_temp.sector, 
        stonks_temp.industry,
        stonks_temp.is_public,
        stonks_temp.extraction_date
    FROM 
        stonks_temp
    WHERE NOT EXISTS (
        SELECT 1 FROM stonks WHERE stonks.symbol = stonks_temp.symbol
    )
''')
con.execute('DROP TABLE IF EXISTS stonks_temp')
con.commit()

print(f'done. {q.rowcount} rows added')
