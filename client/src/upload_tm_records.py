import argparse
import json
import logging
import os
import platform
import re
import sys

import requests

__version__ = '1.0.0.dev1'

logger = logging.getLogger(__name__)


def extract_record_from_gbx_file(path):
    best_regexp = re.compile(b'<times best="([0-9]+)"')
    with open(path, 'rb') as fh:
        content = fh.read()
        match = best_regexp.search(content)
        if match:
            best = int(match.group(1).decode('utf-8'))
            return best
    return -1


def main():
    logging.basicConfig(level=logging.INFO)

    parser = argparse.ArgumentParser()
    parser.add_argument('server')
    args = parser.parse_args()

    logger.debug("System: %s" % platform.system())
    if platform.system() != 'Windows':
        logger.error('You are not running Windows. Currently, this tool only supports Windows.')
        sys.exit(1)

    if not args.server.startswith("http://") and not args.server.startswith("https://"):
        logger.error("Invalid server URL. Must start with http:// or https://")
        sys.exit(1)

    replay_directory = os.path.expanduser(r'~\Documents\Trackmania\Replays\Autosaves')
    if not os.path.isdir(replay_directory):
        logger.error("Replay directory does not exist on your system. Expected location: %s", replay_directory)
        sys.exit(1)

    logger.info('Replay directory found at: %s', replay_directory)
    training_autosave_regexp = re.compile(r'^(.*)_Training - ([0-9]+)_PersonalBest_TimeAttack\.Replay\.Gbx$')
    replay_files = []
    for item in os.listdir(replay_directory):
        match = training_autosave_regexp.search(item)
        if match:
            replay_files.append((match.group(1), int(match.group(2).lstrip('0')), os.path.join(replay_directory, item)))

    records = []
    for user, track_number, replay_file in replay_files:
        logger.debug('Processing file "%s"...', replay_file)
        best = extract_record_from_gbx_file(replay_file)
        logger.info('Record for track %d: %.3fs', track_number, best / 1000.0)
        records.append({'track': track_number, 'user': user, 'best': best})

    logger.info("Uploading records...")
    try:
        r = requests.post(args.server, data={'records': json.dumps(records)})
        logger.debug('Server returned:\n%s', r.text)
        logger.info("DONE!")
    except Exception as e:
        logger.error("Could not upload to server. Reason: %s", e)


if __name__ == '__main__':
    main()
