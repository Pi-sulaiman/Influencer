"""Run isolated browser checks without shell child processes left on fixed ports."""
import os
from pathlib import Path
import socket
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[1]


def free_port():
    with socket.socket() as connection:
        connection.bind(('127.0.0.1', 0))
        return connection.getsockname()[1]


with tempfile.TemporaryDirectory(prefix='social-test-', dir=ROOT / 'storage') as temporary:
    port, service_port = free_port(), free_port()
    env = dict(os.environ, SOCIAL_TEST_BASE_URL=f'http://127.0.0.1:{port}',
               SOCIAL_TEST_SERVICE_URL=f'http://127.0.0.1:{service_port}',
               SOCIAL_TEST_SERVICE_PORT=str(service_port), SOCIAL_TEST_DB=str(Path(temporary) / 'test.sqlite'))
    commands = [
        [os.environ.get('PHP_BINARY', r'D:\xampp\php\php.exe'), '-S', f'127.0.0.1:{port}', 'tests/social_browser_router.php'],
        [sys.executable, 'tests/social_fixture_service.py'],
    ]
    processes = []
    with open(Path(temporary) / 'server.log', 'w+') as log:
        try:
            for command, ready_port in zip(commands, [port, service_port]):
                process = subprocess.Popen(command, cwd=ROOT, env=env, stdout=log, stderr=log)
                processes.append(process)
                for attempt in range(100):
                    try:
                        with socket.create_connection(('127.0.0.1', ready_port), timeout=.2):
                            break
                    except OSError:
                        if process.poll() is not None:
                            raise RuntimeError('Test server exited')
                        time.sleep(.1)
                else:
                    raise RuntimeError('Test server did not start')
            result = subprocess.run([sys.executable, 'tests/social_browser_test.py'], cwd=ROOT, env=env)
            if result.returncode:
                log.seek(0)
                print(log.read()[-5000:])
                raise RuntimeError('Browser test failed')
        finally:
            for process in processes:
                process.terminate()
            for process in processes:
                process.wait(timeout=5)
