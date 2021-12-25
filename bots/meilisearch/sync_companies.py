'''
Sync companies from db to meilisearch.

- Adds new companies
- Updates existing companies' tags
'''

import sqlite3
import meilisearch
import uuid
import json

client = meilisearch.Client('http://127.0.0.1:7700')
# An index is where the documents are stored.
index = client.index('companies-aggregator')

con = sqlite3.connect('/var/www/project-companies-aggregator/db.sqlite3')
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
    document['name'] = company['name']
    document['symbol'] = company['symbol']
    document['tags'] = json.loads(company['tags'])
    # add/replace document in meilisearch
    index.add_documents([document])




