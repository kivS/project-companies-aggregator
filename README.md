# Discover what companies are working on what problems

## TODO:
- List of interesting searches on webpage
- seed companies doing computer vision 
- lazy load more companies


## Dependencies

### PHP

- composer

### Python

- requests
- BeautifulSoup
- meilisearch



## Instructions

- Install _php composer_ dependencies in `web` folder

    ```bash
    composer install
    ```


- Install _pyenv_ virtualenv:

    ```bash
    # in root folder
    pyenv virtualenv 3.10.2 companies-aggregator-3.10.2-env  
    ```

- Activate virtualenv:

    ```bash 
    pyenv activate companies-aggregator-3.10.2-env
    ```

- Install dependencies
```bash
python -m pip install -r requirements.txt 
```

- create a dynamic link of `nginx.conf` to `/etc/nginx/sites-available/`

- add `companies-aggregator.local` to the `/etc/hosts` file.

- create a local dev ssl certificate

    ```bash
    mkcert -cert-file fullchain.pem -key-file privkey.pem companies-aggregator.local
    ```

- duplicate and fill the env files: `.env.template.php` and `env.template.py`

- Download all the public listed companies csv file and save it as stocks.csv:  https://www.nasdaq.com/market-activity/stocks/screener

- Run `./csv_to_db.py` file to populate database from the file

- Run `./get_ticker_description` to get description of each company from MarketWatch

## MeiliSearch


### Configs

- See all configs:
```bash
# httpie
 http :7700/indexes/companies-aggregator/settings  X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
 ```

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

- Choose what fields can be filtered:
```bash
# httpie
echo '["tags"]' | http POST :7700/indexes/companies-aggregator/settings/filterable-attributes X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
```

- Set synonyms:
```bash
# httpie
echo '
{
    "cancer": [
        "oncology"
    ],
    "oncology": [
        "cancer"
    ]
}' | http POST :7700/indexes/companies-aggregator/settings/synonyms  X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
```

- Set ranking rules order:
```bash
# httpie
echo '
    [
        "sort",
        "words",
        "typo",
        "proximity",
        "attribute",
        "exactness"
    ]
' | http POST :7700/indexes/companies-aggregator/settings/ranking-rules  X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
```

- Choose fields that can be sortable:
```bash
# httpie
echo '["name", "symbol"]' | http POST :7700/indexes/companies-aggregator/settings/sortable-attributes  X-MEILI-API-KEY:$MEILI_MASTER_KEY  -v
```

- tailwind vscode auto-complete:

```bash
npm install --no-save --no-package-lock tailwindcss  @tailwindcss/forms
```