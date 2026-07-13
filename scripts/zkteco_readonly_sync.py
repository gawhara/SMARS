"""Strictly read-only ZKTeco attendance fetcher. Never calls disable, clear, set, restart, or delete APIs."""
import argparse, json, sys
from pathlib import Path
from zk import ZK

p=argparse.ArgumentParser(); p.add_argument('--ip',required=True); p.add_argument('--port',type=int,default=4370); p.add_argument('--comm-key',type=int,default=0); p.add_argument('--timeout',type=int,default=8); p.add_argument('--output'); a=p.parse_args()
conn=None
try:
    conn=ZK(a.ip,port=a.port,timeout=a.timeout,password=a.comm_key,force_udp=False,ommit_ping=True).connect()
    rows=[]
    for item in conn.get_attendance() or []:
        rows.append({'device_user_id':str(item.user_id),'punch_at':item.timestamp.strftime('%Y-%m-%d %H:%M:%S'),'punch_type':str(getattr(item,'punch','')),'verification_type':str(getattr(item,'status',''))})
    result={'ok':True,'records':rows}
    if a.output:
        output=Path(a.output)
        output.parent.mkdir(parents=True,exist_ok=True)
        output.write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf-8')
    print(json.dumps({'ok':True,'records':rows if not a.output else [],'record_count':len(rows),'output':a.output}))
except Exception as e:
    print(json.dumps({'ok':False,'error':str(e)})); sys.exit(1)
finally:
    if conn:
        try: conn.disconnect()
        except Exception: pass
