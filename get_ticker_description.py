import random
import time
import requests
from bs4 import BeautifulSoup
import sqlite3


def get_ticker_description(ticker_symbol) -> str:
    r = requests.get(f'https://www.marketwatch.com/investing/stock/{ticker_symbol}/company-profile')
    if not r.ok:
        raise Exception(f'{ticker_symbol} not found on marketwatch')

    soup = BeautifulSoup(r.text, 'html.parser')

    description = soup.select_one('.description__text')
    if not description:
        raise Exception(f'Description not found for {ticker_symbol}')

    return description.get_text()

if __name__ == '__main__':
    con = sqlite3.connect('db.sqlite3')
    con.row_factory = sqlite3.Row
    cursor = con.cursor()

    tickers = cursor.execute('SELECT symbol from stonks WHERE description is null').fetchall()

    if bool(tickers) is False:
        print('No tickers, nothing to do...')
        exit()

    tickers_size = len(tickers)
    print(f'Processing {tickers_size} items..')

    for index, item in enumerate(tickers, start=1):

        # every random from 5 to 10 items let's chill for a random amount of seconds
        if index % round(random.uniform(5, 10)) == 0:
            sleep_time = round(random.uniform(0.5, 2.0), 2)
            print(f'Sleeping for {sleep_time}s...')
            time.sleep(sleep_time)

        print(f'processing: {item["symbol"]}. item {index}/{tickers_size}')
        try:
            ticker_description = get_ticker_description(item['symbol'])
        except Exception as err:
            print(err)
            continue     
        else:
            q = cursor.execute(f'UPDATE stonks SET description = ? WHERE symbol = ?;', (ticker_description, item['symbol']))
            print(f'Inserted {q.rowcount} rows')
            con.commit()
