# Discover what companies are working on what problems

## TODO
- https://github.com/meilisearch/MeiliSearch


## Instructions
- Download all the public listed companies csv file and save it as stocks.csv:  https://www.nasdaq.com/market-activity/stocks/screener

- Run `./csv_to_db.py` file to populate database from the file

- Run `./get_ticker_description` to get description of each company from MarketWatch

## MeiliSearch


### Configs

- See all configs: `GET: http://127.0.0.1:7700/indexes/companies-aggregator/settings`