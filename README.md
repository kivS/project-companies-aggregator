# Discover what companies are working on what problems

## TODO
- https://github.com/meilisearch/MeiliSearch

## 


## Instructions
- Download all the public listed companies csv file and save it as stocks.csv:  https://www.nasdaq.com/market-activity/stocks/screener

- Run `./csv_to_db.py` file to populate database from the file

- Run `./get_ticker_description` to get description of each company from MarketWatch

## MeiliSearch


### Configs

- See all configs: `GET: http://127.0.0.1:7700/indexes/companies-aggregator/settings`

- Create companies aggregator index:
```bash
wget --no-check-certificate --quiet \
  --method POST \
  --timeout=0 \
  --header 'Cache-Control: no-cache' \
  --header 'Accept: */*' \
  --header 'Accept-Encoding: gzip, deflate' \
  --header 'Connection: keep-alive' \
  --body-data '{
    "uid": "companies-aggregator",
    "primaryKey": "company_uid"  
}' \
   'http://127.0.0.1:7700/indexes/'
```

- Choose what fields can be searched:
```bash
wget --no-check-certificate --quiet \
  --method POST \
  --timeout=0 \
  --header 'Cache-Control: no-cache' \
  --header 'Accept: */*' \
  --header 'Accept-Encoding: gzip, deflate' \
  --header 'Connection: keep-alive' \
  --body-data '[
    "tags"

]' \
   'http://127.0.0.1:7700/indexes/companies-aggregator/settings/searchable-attributes'
```