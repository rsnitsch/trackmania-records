import argparse
import json
import logging
import os
import platform
import re
import sys

import requests

__version__ = '1.0.0'

logger = logging.getLogger(__name__)


def get_replay_directory():
    """
    Return the Trackmania 2020 Autosaves replay directory.
    
    Note:
        In some cases, Trackmania 2020 uses the Documents\\Trackmania2020 subfolder. Sometimes,
        it uses the Documents\\Trackmania subfolder (without any 2020 indication).

        I assume that it uses Trackmania2020 if another (older) Trackmania version is
        installed on the system. Therefore, it seems prudent to try the Trackmania2020 subfolder
        first and use the Trackmania subfolder as a fallback.
    """
    replay_directory = os.path.expanduser(r'~\Documents\Trackmania2020\Replays\Autosaves')
    if os.path.isdir(replay_directory):
        return replay_directory
    else:
        logging.warning("Replay directory was not found at expected location: %s.", replay_directory)

    replay_directory = os.path.expanduser(r'~\Documents\Trackmania\Replays\Autosaves')
    if os.path.isdir(replay_directory):
        return replay_directory
    else:
        logging.warning("Replay directory was not found at expected location: %s.", replay_directory)
        logging.warning("Will now try to use the Windows API workaround for finding the directory...")

    # Windows allows to change the location of the Documents folder, e.g. to move
    # it to a separate harddrive or a network drive. In this case, the above code must fail. Instead,
    # we have to ask the Windows API for the location of the Documents folder.
    import ctypes.wintypes
    CSIDL_PERSONAL = 5  # My Documents
    SHGFP_TYPE_CURRENT = 0  # Get current value (instead of default value)

    buf = ctypes.create_unicode_buffer(ctypes.wintypes.MAX_PATH)
    ctypes.windll.shell32.SHGetFolderPathW(None, CSIDL_PERSONAL, None, SHGFP_TYPE_CURRENT, buf)

    replay_directory = buf.value + r"\Trackmania2020\Replays\Autosaves"
    if os.path.isdir(replay_directory):
        return replay_directory

    replay_directory = buf.value + r"\Trackmania\Replays\Autosaves"
    if os.path.isdir(replay_directory):
        return replay_directory

    return None


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
    parser.add_argument('--replay-directory', type=str, default=None)
    args = parser.parse_args()

    logger.debug("System: %s" % platform.system())
    if platform.system() != 'Windows':
        logger.error('You are not running Windows. Currently, this tool only supports Windows.')
        sys.exit(1)

    if not args.server.startswith("http://") and not args.server.startswith("https://"):
        logger.error("Invalid server URL. Must start with http:// or https://")
        sys.exit(1)

    if args.replay_directory:
        replay_directory = args.replay_directory
        if not os.path.isdir(replay_directory):
            logging.error("The replay directory you specified does not exist: %s", replay_directory)
            sys.exit(1)
        else:
            logging.info("Using user-specified replay directory at: %s", replay_directory)
    else:
        replay_directory = get_replay_directory()
        if not replay_directory:
            logging.error(
                "Replay directory could not be found... aborting. You can specify it manually with --replay-directory")
            sys.exit(1)
        else:
            logger.info('Replay directory found at: %s', replay_directory)

    training_autosave_regexp = re.compile(
        r'^(.*)_((?:Summer|Fall|Winter|Spring) 202[0-9] - [0-9]+|Training - [0-9]+)_PersonalBest_TimeAttack\.Replay\.Gbx$'
    )
    replay_files = []
    for item in os.listdir(replay_directory):
        match = training_autosave_regexp.search(item)
        if match:
            replay_files.append((match.group(1), match.group(2), os.path.join(replay_directory, item)))

    records = []
    for user, track, replay_file in replay_files:
        logger.debug('Processing file "%s"...', replay_file)
        best = extract_record_from_gbx_file(replay_file)
        logger.info("Record for track '%s': %.3fs", track, best / 1000.0)
        records.append({'track': track, 'user': user, 'best': best})

    logger.info("Uploading records...")
    try:
        r = requests.post(args.server,
                          data={
                              'records': json.dumps(records),
                              'client_name': 'upload_tm_records',
                              'client_version': __version__
                          })
        if r.status_code == 200:
            logger.info("SUCCESS!")
        else:
            logger.error("FAILED! Status code: %d. Server response: %s.", r.status_code, r.text)
    except Exception as e:
        logger.error("Could not upload to server. Reason: %s", e)


if __name__ == '__main__':
    main()
