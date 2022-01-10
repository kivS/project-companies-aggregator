#!/usr/bin/env python3
'''
    The script will fetch all the companies in the database and fetch media links for each company, if they're not present.
    The format for the media links is:

    [
        {
            "url": "https://finance.yahoo.com/quote/TSLA/",
            "site_name": "Yahoo Finance"
        },
        {
            "url": "https://www.google.com/finance?q=TSLA",
            "site_name": "Google Finance"
        },
        {
            "url": "https://www.marketwatch.com/investing/stock/tsla",
            "site_name": "MarketWatch"
        },
        {
            "url": "https://www.bloomberg.com/quote/TSLA:US",
            "site_name": "Bloomberg"
        },
        {
            "url": "https://money.cnn.com/quote/quote.html?symb=TSLA",
            "site_name": "CNN Money"
        }
    ]

'''

import sys
import os
# root project dir
sys.path.append(os.path.abspath("/var/www/project-companies-aggregator"))
from env import * # local env file
import sqlite3
import requests
import random
import json
import time

MEDIA_SOURCES = [
    {
        "url": "https://finance.yahoo.com/quote/{symbol}/",
        "site_name": "Yahoo Finance"
    },
    {
        "url": "https://www.google.com/finance?q={symbol}",
        "site_name": "Google Finance"
    },
    {
        "url": "https://www.marketwatch.com/investing/stock/{symbol}",
        "site_name": "MarketWatch"
    },
    {
        "url": "https://www.bloomberg.com/quote/{symbol}:US",
        "site_name": "Bloomberg"
    },
    {
        "url": "https://money.cnn.com/quote/quote.html?symb={symbol}",
        "site_name": "CNN Money"
    }
]


if __name__ == '__main__':
    con = sqlite3.connect(DB_PATH)
    con.row_factory = sqlite3.Row
    cursor = con.cursor()

    print('Fetching media links...')

    # update single ticker from command line
    if len(sys.argv) > 1:
        ticker_symbol = sys.argv[1]

        tickers = cursor.execute(f"SELECT clean_name, symbol FROM stonks WHERE symbol = ? LIMIT 1", (ticker_symbol,)).fetchall()
        print(f"Found {tickers[0]['clean_name']}")
        input_result = input("Get media links? (y/n) ")

        if input_result == 'n':
            print("Exiting...")
            exit()
    else: 
        tickers = cursor.execute('SELECT symbol from stonks WHERE media_links IS NULL or media_links == "" ORDER BY RANDOM()').fetchall()

    if bool(tickers) is False:
        print('No tickers, nothing to do...')
        exit()

    tickers_size = len(tickers)
    print(f'Processing {tickers_size} items..')

    total_rows_inserted = 0
    for index, ticker in enumerate(tickers[:5], start=1):   

        media_links = []

        for source in MEDIA_SOURCES:

            print(f'Fetching media links for {ticker["symbol"]} from {source["site_name"]}...')

            # sleep random amount of seconds
            sleep_time = round(random.uniform(0.5, 2.0), 2)
            print(f'Sleeping for {sleep_time}s...')
            time.sleep(sleep_time)

            try:
                r = requests.get(source['url'].format(symbol=ticker['symbol']), timeout=10)
                if not r.ok:
                    raise Exception(f'{ticker["symbol"]} not found on {source["site_name"]}: {r.reason}')
            except Exception as err:
                print(err)
                continue
            else:
                media_links.append({
                    "url": source['url'].format(symbol=ticker['symbol']),
                    "site_name": source['site_name']
                })

       
        q = cursor.execute('UPDATE stonks SET media_links = ? WHERE symbol = ?', (json.dumps(media_links), ticker['symbol']))
        con.commit()
        total_rows_inserted += q.rowcount

    print(f'Inserted {total_rows_inserted} rows.')

