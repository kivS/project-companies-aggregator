# Discover what companies are working on what problems


## Dependencies

### PHP

- composer

### Python

- requests
- BeautifulSoup
- meilisearch



## Instructions

### Python Bots

- create virtual environment
```bash
# in project folder
python3 -m venv python_venv
```

- Install dependencies
```bash
./python_venv/bin/python -m pip install -r requirements.txt 
```



- Download all the public listed companies csv file and save it as stocks.csv:  https://www.nasdaq.com/market-activity/stocks/screener

- Run `./csv_to_db.py` file to populate database from the file

- Run `./get_ticker_description` to get description of each company from MarketWatch

## MeiliSearch


### Configs

- See all configs: `GET: http://127.0.0.1:7700/indexes/companies-aggregator/settings`

- Create companies aggregator index:
```bash
# httpie
http POST :7700/indexes uid="companies-aggregator"  primaryKey="company_uid"  X-MEILI-API-KEY:$MEILI_MASTER_KEY
```

- Choose what fields can be searched:
```bash
# httpie
echo '["tags"]' | http POST :7700/indexes/companies-aggregator/settings/searchable-attributes  X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
```

- tailwind vscode auto-complete:

```bash
npm install --no-save --no-package-lock tailwindcss  @tailwindcss/forms
```