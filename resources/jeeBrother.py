from asyncio import run
from datetime import datetime
from brother import Brother, BrotherSensors, SnmpError, UnsupportedModel
from json import dumps, load, JSONEncoder
import logging
from logging.config import dictConfig
from os import getenv, getpid
from os.path import dirname, realpath
from platform import python_version, system, version
from requests import post
from sys import argv


logger = logging.getLogger('jeebrother')


logconfig: dict = {
    'version': 1,
    'disable_existing_loggers': False,
    'formatters': {
        'withFunction': {
            'format': '[%(asctime)s][%(levelname)s] : #' + \
                (argv[1] if len(argv) > 3 else '[???]') + \
                '# in %(name)s.%(funcName)s() %(message)s',
            'datefmt': '%Y-%m-%d %H:%M:%S',
        },
        'normal': {
            'format': '[%(asctime)s][%(levelname)s] : #' + \
                (argv[1] if len(argv) > 3 else '[???]') + '# %(message)s',
            'datefmt': '%Y-%m-%d %H:%M:%S',
        },
    },
    'handlers': {
        'fileHandler': {
            'class': 'logging.handlers.WatchedFileHandler',
            # 'level': 'DEBUG',
            # 'formatter': 'withFunction',
            'formatter': 'normal',
            'filename': getenv('LOGFILE', '/tmp/brotherd.log'),
        },
    },
    'root': {
        'level': 'WARNING',
        'handlers': ['fileHandler'],
    },
    # 'loggers': {
    #     'urllib3': {
    #         'level': 'WARNING',
    #     },
    # },
}


class DateTimeEncoder(JSONEncoder):
    def default(self, o):
        if isinstance(o, datetime):
            return o.isoformat()
        if isinstance(o, BrotherSensors):
            return dict(o)
        return JSONEncoder.default(self, o)


async def main():
    # Load logging configuration
    dictConfig(logconfig)

    # Get loglevel from ENV
    newlevel = {
        'debug': logging.DEBUG,
        'info': logging.INFO,
        'warning': logging.WARNING,
        'error': logging.ERROR,
        'critical': logging.CRITICAL,
        'none': logging.CRITICAL,
        'notset': logging.NOTSET,
        'emergency': logging.CRITICAL,
    }.get(getenv('LOGLEVEL', 'error'), logging.ERROR)
    logging.getLogger().setLevel(newlevel)

    # Welcome message
    with open(
        dirname(realpath(__file__)) + '/../plugin_info/info.json'
    ) as json_file:
        logger.debug(
            '❤ Thanks for using Brother v%s with Python v%s on %s %s ❤',
            load(json_file)['pluginVersion'],
            python_version(),
            system(),
            version()
        )

    if logger.isEnabledFor(logging.DEBUG):
        logger.debug('┌─► Loggers ◄────────────────────────────')
        for name, level in {
            name: logging.getLevelName(logging.getLogger(name).getEffectiveLevel())
            for name in [''] + sorted(logging.root.manager.loggerDict)
        }.items():
            logger.debug('│ %-30s%s', name, level)
        logger.debug('└────────────────────────────────────────')

    # Display informations
    logger.debug('┌─► Script ◄─────────────────────────────')
    logger.debug('│ PID         : %s', getpid())
    logger.debug('│ Equipment   : %s', argv[1] if len(argv) > 3 else "")
    logger.debug('│ Host        : %s', argv[2] if len(argv) > 3 else "")
    logger.debug('│ Printer type: %s', argv[3] if len(argv) > 3 else "")
    logger.debug('│ Log file    : %s', getenv('LOGFILE', '/tmp/brotherd.log'))
    logger.debug('│ Log level   : %s', getenv("LOGLEVEL", "error"))
    logger.debug('│ Callback url: %s', getenv("CALLBACK", None))
    logger.debug('└────────────────────────────────────────')

    if len(argv) <= 3:
        logger.error('usage: %s <eqName> <host> <ink/laser>', argv[0])
        exit(1)

    callback = getenv("CALLBACK", None) # with APIKEY included
    if callback is None:
        logger.error('Missing callback url (use ENV var CALLBACK="<url>")')
        exit(2)

    try:
        brother = Brother(argv[2], kind=argv[3])
        # brother = await Brother.create(host=argv[2], printer_type=argv[3])
        data = await brother.async_update()
        # brother.shutdown()
    except (ConnectionError, SnmpError) as e:
        logger.debug(f'{e}')
        data = {'unreachable': True}
    except UnsupportedModel as e:
        logger.error(f'{e}')
        data = {'unreachable': True}

    r = post(callback, dumps(data, cls=DateTimeEncoder))


if __name__ == '__main__':
    # Run main task
    try:
        run(main())
    except KeyboardInterrupt:
        logger.info('Exiting')
    except Exception:
        logger.exception('Exception in main:')
