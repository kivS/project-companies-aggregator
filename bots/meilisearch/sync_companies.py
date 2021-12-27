#!/usr/bin/env python3
'''
Sync companies from db to meilisearch.

- Adds new companies
- Updates existing companies' tags
'''


import sys
import os
import sqlite3
import meilisearch
import uuid
import json
# root project dir
sys.path.append(os.path.abspath("/var/www/project-companies-aggregator"))
from env import * # local env file


client = meilisearch.Client(MEILISEARCH_CLIENT_URL)
# An index is where the documents are stored.
index = client.index(MEILISEARCH_APP_INDEX)

con = sqlite3.connect(DB_PATH)
con.row_factory = sqlite3.Row
cursor = con.cursor()

query = cursor.execute('SELECT * FROM stonks WHERE tags is not null')
companies = query.fetchall()

print(f'Found {len(companies)} companies')


for company in companies:

    uid = None

    # generate a new UUID if not existent
    if not company['uid']:
        uid = uuid.uuid4().hex
        query = cursor.execute('UPDATE stonks SET uid = ? WHERE id = ?;', (uid, company['id']))
        con.commit()
    

    document = {}
    document['company_uid'] = company['uid'] if company['uid'] else uid
    document['name'] = company['clean_name']
    document['symbol'] = company['symbol']
    document['tags'] = json.loads(company['tags'])
    # add/replace document in meilisearch
    index.add_documents([document])




