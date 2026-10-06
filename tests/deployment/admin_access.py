"""HTTP account replacement test; run only on the disposable local test server.

Bootstrap codex_railway_admin_main + codex_railway_admin_units, then run PHP's
tests/deployment/router.php on 127.0.0.1:8767 with those database variables.
"""
import re
import secrets

import requests

BASE = 'http://127.0.0.1:8767'
USERNAME = 'test.replacement'
PASSWORD = secrets.token_urlsafe(18)


def token(response, name):
    response.raise_for_status()
    return re.search(r'name="' + name + r'" value="([^"]+)"', response.text)[1]


def unit_login(username, password, unit='daycare'):
    session = requests.Session()
    response = session.post(BASE + f'/{unit}/admin/index.php', data={
        'action': 'login', 'username': username, 'password': password,
    }, timeout=30)
    response.raise_for_status()
    return session, response


def portal_login(username, password):
    session = requests.Session()
    response = session.get(BASE + '/portal/admin', timeout=30)
    response = session.post(BASE + '/portal/admin', data={
        '_token': token(response, '_token'), 'username': username, 'password': password,
    }, timeout=30)
    response.raise_for_status()
    return session, response


old_portal, response = portal_login('admin', 'AdminPHB#2026')
assert response.url.endswith('/portal/dashboard')
old_unit, response = unit_login('superadmin', 'SuperUnit#2026')
assert 'name="action" value="login"' not in response.text
replacement = {
    'action': 'replace_foundation_admin', 'new_username': USERNAME,
    'new_password': PASSWORD, 'confirm_password': PASSWORD,
    'current_password': 'SuperUnit#2026',
}
user_url = BASE + '/daycare/admin/index.php?tab=users'
response = old_unit.get(user_url, timeout=30)
csrf = token(response, 'csrf')
assert 'Ganti Admin Yayasan' in response.text

# Authorization, CSRF, reauthentication and confirmation must all be enforced.
limited, response = unit_login('daycare-admin', 'Daycare#2026')
response = limited.get(BASE + '/daycare/admin/index.php?tab=settings', timeout=30)
response = limited.post(user_url, data={**replacement, 'csrf': token(response, 'csrf')}, timeout=30)
assert 'Hanya superadmin' in response.text
response = old_unit.post(user_url, data={**replacement, 'csrf': 'invalid'}, timeout=30)
assert 'Sesi admin tidak valid' in response.text
response = old_unit.post(user_url, data={**replacement, 'csrf': csrf, 'current_password': 'wrong'}, timeout=30)
assert 'Password superadmin saat ini tidak sesuai' in response.text
response = old_unit.post(user_url, data={**replacement, 'csrf': csrf, 'confirm_password': 'wrong'}, timeout=30)
assert 'Konfirmasi password baru tidak sesuai' in response.text
response = old_unit.post(user_url, data={**replacement, 'csrf': csrf, 'new_username': 'daycare-admin'}, timeout=30)
assert 'Username sudah digunakan' in response.text
print('Access controls and collision handling: OK')

response = old_unit.post(user_url, data={**replacement, 'csrf': csrf}, timeout=30)
assert 'name="action" value="login"' in response.text
new_portal, response = portal_login(USERNAME, PASSWORD)
assert response.url.endswith('/portal/dashboard')
response = new_portal.get(BASE + '/portal/users', timeout=30)
assert response.status_code == 200 and USERNAME in response.text
assert '<code>admin</code>' not in response.text
assert '<code>humas</code>' in response.text and '<code>kasir</code>' in response.text
for unit in ['daycare', 'tkit', 'sdit', 'smpit']:
    session, response = unit_login(USERNAME, PASSWORD, unit)
    assert 'name="action" value="login"' not in response.text
    response = session.get(BASE + f'/{unit}/admin/index.php?tab=users', timeout=30)
    assert 'Ganti Admin Yayasan' in response.text and USERNAME in response.text
    assert '<td>superadmin</td><td>superadmin</td>' not in response.text
print('New foundation admin and all four unit superadmin logins: OK')

# Repeated page bootstraps must neither restore deleted users nor preserve sessions.
response = old_portal.get(BASE + '/portal/users', timeout=30)
assert response.url.endswith('/portal/admin')
_, response = portal_login('admin', 'AdminPHB#2026')
assert response.url.endswith('/portal/admin')
_, response = unit_login('superadmin', 'SuperUnit#2026')
assert 'name="action" value="login"' in response.text
_, response = unit_login('daycare-admin', 'Daycare#2026')
assert 'name="action" value="login"' not in response.text
print('Deleted accounts stay deleted; old sessions revoked; unit admin preserved: OK')
