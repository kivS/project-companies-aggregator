''''
    Retriveves the company website from google finance.
    Only processes companies that have a google finance media_links entry in the database.
'''

import sys
import os
# root project dir
sys.path.append(os.path.abspath("/var/www/project-companies-aggregator"))
from env import * # local env file
import sqlite3
import requests
import random
import time
import json
from bs4 import BeautifulSoup


def get_ticker_website_url(ticker_symbol) -> str:
    r = requests.get(f'https://www.google.com/finance/quote/{ticker_symbol}:NASDAQ', timeout=10)
    if not r.ok:
        raise Exception(f'{ticker_symbol} not found on marketwatch: {r.reason}')

    soup = BeautifulSoup(r.text, 'html.parser')

    website_url = soup.select_one('span[role="region"] div:-soup-contains("Website") a')
   
    if not website_url:
        raise Exception(f'Website url not found for {ticker_symbol}')
    
    return website_url.get('href')   
    


if __name__ == '__main__':
    con = sqlite3.connect(DB_PATH)
    con.row_factory = sqlite3.Row
    cursor = con.cursor()

    print('Fetching company websites...')

    # update single ticker from command line
    if len(sys.argv) > 1:
        ticker_symbol = sys.argv[1]

        tickers = cursor.execute(f"SELECT clean_name, symbol FROM stonks WHERE symbol = ? LIMIT 1", (ticker_symbol,)).fetchall()
        print(f"Found {tickers[0]['clean_name']}")
        input_result = input("Get website link? (y/n) ")

        if input_result == 'n':
            print("Exiting...")
            exit()
    else: 
        tickers = cursor.execute('''
            SELECT symbol,clean_name, media_links 
            FROM stonks 
            WHERE (website_url IS NULL OR website_url = '') 
            AND (media_links IS NOT NULL)
            ORDER BY RANDOM()
        ''').fetchall()

    if bool(tickers) is False:
        print('No tickers, nothing to do...')
        exit()

    tickers_size = len(tickers)
    print(f'Processing {tickers_size} items..')

    total_rows_inserted = 0
    for index, ticker in enumerate(tickers, start=1): 

        # only process if google finance url is in the media_links field
        media_links = json.loads(ticker['media_links'])

        is_google_finance_present = any([item['site_name'] == 'Google Finance' for item in media_links])
        if not is_google_finance_present:
            print(f'Skipping {ticker["clean_name"]}. Reason: Google finance not present in media_links')
            continue

        print(f'processing: {ticker["symbol"]}. item {index}/{tickers_size}')

        # sleep random amount of seconds
        sleep_time = round(random.uniform(0.5, 1.0), 2)
        print(f'Sleeping for {sleep_time}s...')
        time.sleep(sleep_time)

        try:
            website_url = get_ticker_website_url(ticker['symbol'])
        except Exception as err:
            print(err)
            continue
        else:
            q = cursor.execute('UPDATE stonks SET website_url = ? WHERE symbol = ?', (website_url, ticker['symbol']))
            con.commit()
            total_rows_inserted += q.rowcount

    print(f'Inserted {total_rows_inserted} rows.')

