"""Run against disposable codex_railway_central_* databases on localhost:8768.
Requires requests. Mutates test users, roles, content, and audit only.
"""
import csv
import io
import re
import secrets
import requests

B = 'http://127.0.0.1:8768'
P = secrets.token_urlsafe(18)
TAG = secrets.token_hex(4)
ROLE = 'audit_' + TAG


def check(response, status=200):
    assert response.status_code == status, (response.status_code, response.url, response.text[:200])
    assert 'Fatal error' not in response.text and '<b>Warning</b>' not in response.text
    return response


def token(response, name='_token'):
    return re.search(r'name="' + name + r'" value="([^"]+)"', response.text)[1]


def portal_login(username='admin', password='AdminPHB#2026'):
    s = requests.Session()
    r = check(s.get(B+'/portal/admin', timeout=30))
    r = check(s.post(B+'/portal/admin', data={'_token':token(r),'username':username,'password':password}, timeout=30))
    return s, r


def unit_login(username, unit='daycare', password=P):
    s = requests.Session()
    r = check(s.post(B+f'/{unit}/admin/index.php', data={'action':'login','username':username,'password':password}, timeout=30))
    return s, r


def save(s, data, scope='unit', expect='berhasil'):
    data=dict(data)
    if 'permissions' in data: data['permissions[]']=data.pop('permissions')
    url = B+'/portal/users?scope='+scope
    r=check(s.get(url, timeout=30))
    r=check(s.post(url,data={'_token':token(r),'scope':scope,**data},timeout=30))
    if expect: assert expect.lower() in r.text.lower(), expect
    return r


def user_id(s, username, scope='unit'):
    r=check(s.get(B+'/portal/users',params={'scope':scope,'q':username},timeout=30))
    row=re.search(r'<tr><td><strong>.*?<code>'+re.escape(username)+r'</code>.*?</tr>',r.text,re.S)[0]
    return int(re.search(r'&edit=(\d+)',row)[1])


admin,r=portal_login();assert r.url.endswith('/portal/dashboard')
for scope in ['portal','unit','roles']:
    check(admin.get(B+'/portal/users?scope='+scope,timeout=30))
save(admin,{'action':'save_role','role_key':ROLE,'name':'Editor Uji '+TAG,'permissions':['content','social']},'roles',expect='Role disimpan')
save(admin,{'action':'save_role','role_key':'unit_admin','name':'Tidak boleh','permissions':['content'],'editing':'1'},'roles',expect='bawaan tidak dapat')
save(admin,{'action':'save_role','role_key':'invalid_'+TAG,'name':'Tidak boleh','permissions':['users']},'roles',expect='Hak akses role tidak valid')

limited_sessions=[]
for unit in ['daycare','tkit','sdit','smpit']:
    name='editor.'+unit+'.'+TAG
    save(admin,{'action':'save_user','username':name,'name':name,'role_key':ROLE,'user_unit':unit,'password':P,'is_active':'1'})
    uid=user_id(admin,name)
    session,r=unit_login(name,unit);assert 'name="action" value="login"' not in r.text
    limited_sessions.append((unit,name,uid,session))
    check(session.get(B+f'/{unit}/admin/index.php?tab=content',timeout=30))
    check(session.get(B+f'/{unit}/admin/index.php?tab=enrollments',timeout=30),403)
    check(session.get(B+f'/{unit}/admin/index.php?download_cv=1',timeout=30),403)
    r=check(session.get(B+f'/{unit}/admin/index.php?tab=content',timeout=30))
    csrf=token(r,'csrf')
    r=check(session.post(B+f'/{unit}/admin/index.php?tab=content',data={'csrf':csrf,'action':'save_user','username':'forged','password':P,'role':'superadmin'},timeout=30))
    assert 'Akses tindakan ditolak' in r.text
    r=check(session.post(B+f'/{unit}/admin/index.php?tab=content',data={'csrf':csrf,'action':'save_settings','name':'forged'},timeout=30))
    assert 'Akses tindakan ditolak' in r.text
    # An allowed mutation proves permission checks do not disable valid buttons.
    r=check(session.post(B+f'/{unit}/admin/index.php?tab=content',data={'csrf':csrf,'action':'save_content','content_type':'news','title':'Audit '+TAG,'summary':'Konten uji','body':'Uji akses','sort_order':0},timeout=30))
    assert 'Konten disimpan' in r.text
    # Another unit URL redirects to the assigned unit, and cannot acquire its data.
    other='tkit' if unit!='tkit' else 'sdit'
    r=check(session.get(B+f'/{other}/admin/index.php?tab=content',timeout=30))
    assert '/'+unit+'/admin/' in r.url
print('4 unit assignments, custom roles, allowed writes, forged writes and data isolation: OK',flush=True)

save(admin,{'action':'delete_role','role_key':ROLE},'roles',expect='masih dipakai')
unit,name,uid,session=limited_sessions[0]
save(admin,{'action':'toggle_user','id':uid})
r=check(session.get(B+'/daycare/admin/index.php',timeout=30));assert 'name="action" value="login"' in r.text
save(admin,{'action':'toggle_user','id':uid})
session,r=unit_login(name);assert 'name="action" value="login"' not in r.text
new_password=secrets.token_urlsafe(18)
save(admin,{'action':'save_user','id':uid,'username':name,'name':name,'role_key':ROLE,'user_unit':'daycare','password':new_password,'is_active':'1'})
r=check(session.get(B+'/daycare/admin/index.php',timeout=30));assert 'name="action" value="login"' in r.text
_,r=unit_login(name);assert 'name="action" value="login"' in r.text
session,r=unit_login(name,password=new_password);assert 'name="action" value="login"' not in r.text
save(admin,{'action':'save_role','role_key':ROLE,'name':'Humas Revisi','permissions':['social'],'editing':'1'},'roles',expect='Role disimpan')
r=check(session.get(B+'/daycare/admin/index.php',timeout=30));assert 'name="action" value="login"' in r.text
session,r=unit_login(name,password=new_password)
check(session.get(B+'/daycare/admin/index.php?tab=content',timeout=30),403)
print('Disable/reactivate, reset password, role changes, and session revocation: OK',flush=True)

# Last superadmin and self-protection.
sid=user_id(admin,'superadmin')
save(admin,{'action':'toggle_user','id':sid},expect='terakhir tidak boleh')
aid=user_id(admin,'admin','portal')
save(admin,{'action':'toggle_user','id':aid},'portal',expect='Akun sendiri harus tetap')
save(admin,{'action':'save_user','name':'Tidak aktif','username':'inactive.'+TAG,'password':P,'role':'humas','is_active':'0'},'portal')
_,r=portal_login('inactive.'+TAG,P);assert r.url.endswith('/portal/admin')
humas,_=portal_login('humas','HumasPHB#2026')
check(humas.get(B+'/portal/users',timeout=30),403)
check(humas.get(B+'/portal/activity?export=1',timeout=30),403)
r=admin.post(B+'/portal/users?scope=unit',data={'_token':'invalid','action':'toggle_user','id':uid,'scope':'unit'},timeout=30);check(r,419)

# Create/edit/delete an unused custom role.
empty_role='unused_'+TAG
save(admin,{'action':'save_role','role_key':empty_role,'name':'Unused','permissions':['social']},'roles',expect='Role disimpan')
save(admin,{'action':'delete_role','role_key':empty_role},'roles',expect='Role dihapus')

r=check(admin.get(B+'/portal/activity',params={'scope':'unit','unit':'daycare','q':name,'export':1},timeout=30))
assert 'text/csv' in r.headers.get('Content-Type','')
rows=list(csv.reader(io.StringIO(r.content.decode('utf-8-sig'))))
assert len(rows)>1 and all(row[1]=='unit' and row[2]==name and row[4]=='daycare' for row in rows[1:])
assert any(row[6]=='failure' for row in rows[1:]) and any(row[6]=='success' for row in rows[1:])
assert P not in r.text and new_password not in r.text
check(admin.get(B+'/portal/activity?page=2',timeout=30))
print('Account controls, CSRF, last-admin protection, audit filters/pagination/export: OK',flush=True)

# Every central navigation target should open for its authorized admin.
r=check(admin.get(B+'/portal/users',timeout=30))
links=set(re.findall(r'href="('+re.escape(B)+r'/portal/[^"]+)"',r.text))
links={url.replace('&amp;','&') for url in links if '/logout' not in url}
for url in sorted(links):check(admin.get(url,timeout=30))
print(f'{len(links)} central navigation and management links: OK',flush=True)
