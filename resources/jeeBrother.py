from asyncio import new_event_loop
from datetime import datetime
from dataclasses import asdict
from brother import Brother, BrotherSensors, SnmpError, UnsupportedModelError
from json import dumps, load
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
    # TODO: Fix this dirty workaround
    #  Task was destroyed but it is pending!
    #    task: <Task pending name='Task-3' coro=<AsyncioDispatcher.handle_timeout() running at /var/www/html/plugins/brother/resources/venv/lib/python3.11/site-packages/pysnmp/carrier/asyncio/dispatch.py:62> wait_for=<Future pending cb=[Task.task_wakeup()]>>
    #  Related to:
    #  And: https://github.com/lextudio/pysnmp/issues/58

    'loggers': {
        'asyncio': {
            'level': 'CRITICAL',
        },
    },
    # END OF TODO
    # 'loggers': {
    #     'urllib3': {
    #         'level': 'WARNING',
    #     },
    # },
}


async def setup():
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


async def main():
    await setup()

    if len(argv) <= 3:
        logger.error('usage: %s <eqName> <host> <ink/laser>', argv[0])
        exit(1)

    callback = getenv("CALLBACK", None) # with APIKEY included
    if callback is None:
        logger.error('Missing callback url (use ENV var CALLBACK="<url>")')
        exit(2)

    try:
        brother = await Brother.create(argv[2], printer_type=argv[3])
        data = await brother.async_update()
        brother.shutdown()
        result: dict = {}
        result['model'] = brother.model
        result['firmware'] = brother.firmware
        result['serial'] = brother.serial
        for k, v in asdict(data).items():
            if isinstance(v, datetime):
                result[k] = v.timestamp()
            elif v is not None:
                result[k] = v
    except (ConnectionError, TimeoutError, SnmpError) as e:
        logger.debug(f'{e}')
        result = {'unreachable': True}
    except UnsupportedModelError as e:
        logger.error(f'{e}')
        result = {'unreachable': True}

    r = post(callback, dumps(result))


if __name__ == '__main__':
    # Run main task
    try:
        loop = new_event_loop()
        loop.run_until_complete(main())
        loop.close()
    except KeyboardInterrupt:
        logger.info('Exiting')
    except Exception:
        logger.exception('Exception in main:')
