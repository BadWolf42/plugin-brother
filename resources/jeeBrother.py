from asyncio import run
from datetime import datetime
from brother import Brother, SnmpError, UnsupportedModel
from json import dumps, JSONEncoder
import logging
from os import getenv
from requests import post
from sys import argv

class DateTimeEncoder(JSONEncoder):
    def default(self, o):
        if isinstance(o, datetime):
            return o.isoformat()
        return JSONEncoder.default(self, o)

async def main():
    newlevel = {
        'debug': logging.DEBUG,
        'info': logging.INFO,
        'warning': logging.WARNING,
        'error': logging.ERROR
    }.get(getenv("LOGLEVEL", "error"), logging.ERROR)
    logging.basicConfig(level = newlevel)

    logging.debug('VARS:')
    logging.debug('  host:         %s', argv[1])
    logging.debug('  printer_type: %s', argv[2])
    logging.debug('  LOGLEVEL:     %s', getenv("LOGLEVEL", "error"))
    logging.debug('  CALLBACK:     %s', getenv("CALLBACK", None))

    if len(argv) <= 2:
        logging.error('usage: %s <host> <ink/laser>', argv[0])
        exit(1)

    callback = getenv("CALLBACK", None) # with APIKEY included
    if callback is None:
        logging.error('Missing callback url (use ENV var CALLBACK="<url>")')
        exit(2)

    try:
        brother = Brother(argv[1], kind=argv[2])
        data = await brother.async_update()
    except (ConnectionError, SnmpError) as e:
        logging.debug(f'{e}')
        data = {'unreachable': True}
    except UnsupportedModel as e:
        logging.error(f'{e}')
        data = {'unreachable': True}

    r = post(callback, dumps(data, cls=DateTimeEncoder))

if __name__ == '__main__':
    # Run main task
    try:
        run(main())
    except KeyboardInterrupt:
        logging.info('Exiting')
    except Exception:
        logging.exception('Exception in main:')
