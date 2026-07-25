"""ZKTeco provisioning helper — WRITES to devices.

Unlike zkteco_readonly_sync.py, this script mutates device state: it copies user
records + fingerprint templates between machines and deletes users. It is invoked
only from an explicit, user-initiated provisioning action.

Actions:
  read   --ids 10006,20001      Return the matching users and their fingerprint
                                templates from the device (templates base64-encoded).
  write  --input payload.json    Create/update the given users and save their
                                templates on the device.
  delete --ids 10006,20001      Remove the matching users (and their templates).

Every response is a single JSON object on stdout: {"ok": bool, ...}.
"""
import argparse
import base64
import json
import os
import sys

# Use the project-vendored pyzk so the import never depends on the interpreter's
# site-packages (system, user, or service accounts all work).
sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), 'vendor'))
from zk import ZK
from zk.finger import Finger


def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument('--ip', required=True)
    p.add_argument('--port', type=int, default=4370)
    p.add_argument('--comm-key', type=int, default=0)
    p.add_argument('--timeout', type=int, default=15)
    p.add_argument('--action', required=True, choices=['read', 'write', 'delete'])
    p.add_argument('--ids', default='')       # comma-separated device user IDs (read/delete)
    p.add_argument('--input', default='')     # JSON payload file (write)
    return p.parse_args()


def wanted_ids(raw):
    return {s.strip() for s in raw.split(',') if s.strip()}


def do_read(conn, ids):
    users = {u.uid: u for u in (conn.get_users() or [])}
    templates = {}
    for finger in (conn.get_templates() or []):
        templates.setdefault(finger.uid, []).append(finger)

    out = []
    for uid, user in users.items():
        if ids and str(user.user_id) not in ids:
            continue
        fingers = [{
            'fid': f.fid,
            'valid': f.valid,
            'template': base64.b64encode(f.template).decode('ascii'),
        } for f in templates.get(uid, [])]
        out.append({
            'uid': user.uid,
            'user_id': str(user.user_id),
            'name': user.name or '',
            'privilege': user.privilege,
            'password': user.password or '',
            'group_id': str(user.group_id or ''),
            'card': user.card or 0,
            'fingers': fingers,
        })
    return {'ok': True, 'users': out}


def next_free_uid(existing_uids, preferred):
    if preferred and preferred not in existing_uids:
        return preferred
    candidate = 1
    while candidate in existing_uids:
        candidate += 1
    return candidate


def do_write(conn, payload):
    users = payload.get('users', [])
    existing = conn.get_users() or []
    uid_by_userid = {str(u.user_id): u.uid for u in existing}
    used_uids = {u.uid for u in existing}

    written = 0
    templates_written = 0
    failed = []

    conn.disable_device()
    try:
        for u in users:
            try:
                user_id = str(u['user_id'])
                uid = uid_by_userid.get(user_id) or next_free_uid(used_uids, u.get('uid'))
                used_uids.add(uid)

                conn.set_user(
                    uid=uid,
                    name=u.get('name', '')[:24],
                    privilege=int(u.get('privilege', 0)),
                    password=str(u.get('password', '')),
                    group_id=str(u.get('group_id', '')),
                    user_id=user_id,
                    card=int(u.get('card', 0) or 0),
                )
                written += 1

                fingers = []
                for f in u.get('fingers', []):
                    fingers.append(Finger(
                        uid=uid,
                        fid=int(f['fid']),
                        valid=int(f.get('valid', 1)),
                        template=base64.b64decode(f['template']),
                    ))
                if fingers:
                    conn.save_user_template(uid, fingers)
                    templates_written += len(fingers)
            except Exception as e:  # noqa: BLE001 — report per-user, keep going
                failed.append({'user_id': u.get('user_id'), 'error': str(e)})
    finally:
        conn.enable_device()

    return {'ok': True, 'written': written, 'templates': templates_written, 'failed': failed}


def do_delete(conn, ids):
    deleted = 0
    failed = []
    conn.disable_device()
    try:
        for user in (conn.get_users() or []):
            if ids and str(user.user_id) not in ids:
                continue
            try:
                conn.delete_user(uid=user.uid, user_id=user.user_id)
                deleted += 1
            except Exception as e:  # noqa: BLE001
                failed.append({'user_id': str(user.user_id), 'error': str(e)})
    finally:
        conn.enable_device()

    return {'ok': True, 'deleted': deleted, 'failed': failed}


def main():
    a = parse_args()
    conn = None
    try:
        conn = ZK(a.ip, port=a.port, timeout=a.timeout, password=a.comm_key,
                  force_udp=False, ommit_ping=True).connect()

        if a.action == 'read':
            result = do_read(conn, wanted_ids(a.ids))
        elif a.action == 'write':
            with open(a.input, 'r', encoding='utf-8') as fh:
                payload = json.load(fh)
            result = do_write(conn, payload)
        else:
            result = do_delete(conn, wanted_ids(a.ids))

        print(json.dumps(result, ensure_ascii=False))
    except Exception as e:  # noqa: BLE001
        print(json.dumps({'ok': False, 'error': str(e)}))
        sys.exit(1)
    finally:
        if conn:
            try:
                conn.disconnect()
            except Exception:
                pass


if __name__ == '__main__':
    main()
