'''
Source -> https://www.nasdaq.com/market-activity/stocks/screener
'''

import sqlite3
import pandas as pd
from datetime import datetime

con = sqlite3.connect('stonks.sqlite3')
extraction_datetime = datetime.now()


df = pd.read_csv('nasdaq.csv', usecols=['Symbol', 'Name', 'Country', 'IPO Year', 'Sector', 'Industry'])
df.rename(columns={'IPO Year': 'ipo_year'}, inplace=True)
df['exchange'] = 'nasdaq'
df['extraction_date'] = extraction_datetime
# convert ipo year float to int and handling for null
df.ipo_year.fillna(0.0).astype(int)

df.to_sql('stonks', con, if_exists='append', index=False)
