import asyncio
from datetime import datetime
from brother import Brother, SnmpError, UnsupportedModel
import json
import logging
from os import getenv
import requests
from sys import argv

class DateTimeEncoder(json.JSONEncoder):
    def default(self, o):
        if isinstance(o, datetime):
            return o.isoformat()
        return json.JSONEncoder.default(self, o)

async def main():
    if len(argv) <= 2:
        logging.error('usage: %s <host> <ink/laser>', argv[0])
        exit(1)

    newlevel = {
        'debug': logging.DEBUG,
        'info': logging.INFO,
        'warning': logging.WARNING,
        'error': logging.ERROR
    }.get(getenv("LOGLEVEL", "error"), logging.ERROR)
    logging.basicConfig(level = newlevel)

    callback = getenv("CALLBACK", None) # with APIKEY included
    if callback is None:
        logging.error('Missing callback url (use ENV var CALLBACK="<url>")')
        exit(2)

    try:
        brother = Brother(argv[1], kind = argv[2])
        data = await brother.async_update()
    except (ConnectionError, SnmpError, UnsupportedModel) as e:
        logging.error(f'{e}')
        data = {'unreachable': True}

    r = requests.post(callback, json.dumps(data, cls=DateTimeEncoder))

if __name__ == '__main__':
    asyncio.get_event_loop().run_until_complete(main())
