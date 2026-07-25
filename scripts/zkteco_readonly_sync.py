"""Strictly read-only ZKTeco attendance fetcher. Never calls disable, clear, set, restart, or delete APIs.

Works with LAN devices such as the ZKTeco MB1000 / MB1000-ID on TCP port 4370.
Some firmware only answers over UDP, so the connection tries TCP first and then
falls back to UDP before giving up.
"""
import argparse, datetime, json, os, sys
from pathlib import Path

# Use the project-vendored pyzk so the import never depends on the interpreter's
# site-packages (system, user, or service accounts all work).
sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), 'vendor'))
from zk import ZK

p = argparse.ArgumentParser()
p.add_argument('--ip', required=True)
p.add_argument('--port', type=int, default=4370)
p.add_argument('--comm-key', type=int, default=0)
p.add_argument('--timeout', type=int, default=8)
p.add_argument('--since')   # only return punches at/after this 'YYYY-MM-DD HH:MM:SS' (incremental)
p.add_argument('--output')
a = p.parse_args()


def parse_since(value):
    if not value:
        return None
    for fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%d'):
        try:
            return datetime.datetime.strptime(value, fmt)
        except ValueError:
            continue
    return None


def connect(force_udp):
    return ZK(a.ip, port=a.port, timeout=a.timeout, password=a.comm_key,
              force_udp=force_udp, ommit_ping=True).connect()


conn = None
try:
    last_error = None
    for force_udp in (False, True):   # TCP first, then UDP fallback for older MB1000 firmware
        try:
            conn = connect(force_udp)
            break
        except Exception as e:
            last_error = e
    if conn is None:
        raise last_error if last_error else RuntimeError('Unable to connect to device')

    # Load the user table first: pyzk needs it to correctly map and parse
    # attendance records (without it, MB1000/ID rows come back as garbage).
    try:
        conn.get_users()
    except Exception:
        pass

    since = parse_since(a.since)
    rows = []
    for item in conn.get_attendance() or []:
        # Incremental: only keep punches at/after the last sync (device can't filter server-side).
        if since is not None and item.timestamp < since:
            continue
        rows.append({
            'device_user_id': str(item.user_id),
            'punch_at': item.timestamp.strftime('%Y-%m-%d %H:%M:%S'),
            'punch_type': str(getattr(item, 'punch', '')),
            'verification_type': str(getattr(item, 'status', '')),
        })

    result = {'ok': True, 'records': rows}
    if a.output:
        output = Path(a.output)
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')

    print(json.dumps({'ok': True, 'records': rows if not a.output else [], 'record_count': len(rows), 'output': a.output}))
except Exception as e:
    print(json.dumps({'ok': False, 'error': str(e)}))
    sys.exit(1)
finally:
    if conn:
        try:
            conn.disconnect()
        except Exception:
            pass
