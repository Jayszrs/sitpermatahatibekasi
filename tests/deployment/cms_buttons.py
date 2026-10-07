"""HTTP form/action audit on disposable databases via localhost:8768 only.
Uploads must point to tmp/central-uploads; never run against client databases.
"""
import base64
from datetime import date
from html.parser import HTMLParser
import re
import secrets
import requests

B='http://127.0.0.1:8768'
TAG=secrets.token_hex(4)
PNG=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j3WQAAAAASUVORK5CYII=')
PDF=b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n'


class Forms(HTMLParser):
    def __init__(self, text):
        super().__init__(convert_charrefs=True)
        self.forms=[];self.current=None;self.select=None;self.textarea=None
        self.feed(text)
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='form': self.current={};self.forms.append(self.current)
        if self.current is None:return
        name=a.get('name')
        if tag=='input' and name and a.get('type') not in ['file','submit','button']:
            if a.get('type') in ['checkbox','radio'] and 'checked' not in a:return
            self.current[name]=a.get('value','')
        if tag=='select':self.select=name
        if tag=='option' and self.select and ('selected' in a or self.select not in self.current):self.current[self.select]=a.get('value','')
        if tag=='textarea' and name:self.textarea=name;self.current[name]=''
    def handle_data(self, data):
        if self.current is not None and self.textarea:self.current[self.textarea]+=data
    def handle_endtag(self, tag):
        if tag=='form':self.current=None
        if tag=='select':self.select=None
        if tag=='textarea':self.textarea=None


def check(r,status=200):
    assert r.status_code==status,(r.url,r.status_code,r.text[:100])
    assert 'Fatal error' not in r.text and '<b>Warning</b>' not in r.text
    return r


def find_form(text,action):
    return next(f for f in Forms(text).forms if f.get('action')==action)


def row(text,marker):
    return next(r for r in re.findall(r'<tr\b[^>]*>.*?</tr>',text,re.S) if marker in r)


admin=requests.Session();r=check(admin.get(B+'/portal/admin',timeout=30))
f=Forms(r.text).forms[0];f.update(username='admin',password='AdminPHB#2026')
check(admin.post(B+'/portal/admin',data=f,timeout=30))
units=requests.Session();check(units.post(B+'/daycare/admin/index.php',data={'action':'login','username':'superadmin','password':'SuperUnit#2026'},timeout=30))

for n,unit in enumerate(['daycare','tkit','sdit','smpit']):
    base=B+'/'+unit+'/admin/index.php'
    def get(tab,**params):return check(units.get(base,params={'tab':tab,'unit':unit,**params},timeout=30))
    def post(tab,data,files=None,notice=None):
        if 'csrf' not in data:data={'csrf':re.search(r'name="csrf" value="([^"]+)"',get(tab).text)[1],**data}
        r=check(units.post(base+'?tab='+tab+'&unit='+unit,data=data,files=files,timeout=30))
        if notice:assert notice in r.text,(notice,re.findall(r'<div class="error">(.*?)</div>',r.text))
        return r
    for tab in ['dashboard','content','gallery','social','enrollments','careers','settings','users']:get(tab)
    for page in ['index','profile','programs','achievements','news','gallery','spmb','contact','karir']:
        check(requests.get(B+'/'+unit+'/'+page+'.php',timeout=30))

    title='Audit '+unit+' '+TAG
    r=post('content',{'action':'save_content','content_type':'news','title':title,'summary':'Uji','body':'Isi uji','sort_order':'0'},notice='Konten disimpan')
    rid=re.search(r'edit_content=(\d+)',row(r.text,title))[1]
    r=get('content',edit_content=rid);f=find_form(r.text,'save_content');f['title']=title+' edited';post('content',f,notice='Konten disimpan')
    post('content',{'action':'delete_content','id':rid},notice='Konten dihapus')

    r=post('gallery',{'action':'save_album','title':title,'description':'Album uji','sort_order':'0'},files={'cover_image':('audit.png',PNG,'image/png')},notice='Album disimpan')
    album_id=re.search(r'edit_album=(\d+)',row(r.text,title))[1]
    f=find_form(get('gallery',edit_album=album_id).text,'save_album');f['description']='Diperbarui';post('gallery',f,notice='Album disimpan')
    r=post('gallery',{'action':'save_photo','album_id':album_id,'title':title+' photo','description':'Foto uji','sort_order':'0'},files={'image':('audit.png',PNG,'image/png')},notice='Foto ditambahkan')
    photo_row=row(r.text,title+' photo');photo=find_form(photo_row,'delete_photo')
    photo_url=re.search(r'<img src="([^"]+)"',photo_row)[1]
    image=check(requests.get(B+photo_url if photo_url.startswith('/') else photo_url,timeout=30));assert image.content==PNG
    post('gallery',photo,notice='Foto dihapus');post('gallery',{'action':'delete_album','id':album_id},notice='Album dihapus')

    url='https://www.instagram.com/p/Audit'+TAG+str(n)+'/'
    r=post('social',{'action':'save_social','instagram_url':url,'caption':title},notice='Instagram unit disimpan')
    social_id=re.search(r'edit_social=(\d+)',row(r.text,title))[1]
    f=find_form(get('social',edit_social=social_id).text,'save_social');f['caption']=title+' edited';post('social',f,notice='Instagram unit disimpan')
    post('social',{'action':'toggle_social','id':social_id,'is_active':'0'},notice='Status tautan Instagram diperbarui')
    post('social',{'action':'toggle_social','id':social_id,'is_active':'1'},notice='Status tautan Instagram diperbarui')
    r=post('social',{'action':'save_social_bulk','instagram_urls':url+'\n'+url},notice='0 tautan Instagram ditambahkan')
    post('social',{'action':'delete_social','id':social_id},notice='Item galeri Instagram unit dihapus')

    f=find_form(get('settings').text,'save_settings');post('settings',f,notice='Kontak dan identitas diperbarui')
    r=post('settings',{'action':'save_hero','hero_media_type':'image'},files={'hero_media':('audit.png',PNG,'image/png')},notice='Media hero beranda diperbarui')
    media=re.search(r'href="([^"]+)">lihat file',r.text)[1]
    check(requests.get(B+media if media.startswith('/') else media,timeout=30))
    post('settings',{'action':'reset_hero'},notice='Media hero dikembalikan')

    public=requests.Session();spmb=B+'/'+unit+'/spmb.php';r=check(public.get(spmb,timeout=30));f=Forms(r.text).forms[0]
    f.update(student_name=title+' student',parent_name='Orang Tua Uji',whatsapp='08000000000',academic_year=f'{date.today().year}/{date.today().year+1}')
    r=check(public.post(spmb,data=f,timeout=30));assert 'berhasil' in r.text.lower()
    r=get('enrollments');registration=row(r.text,title+' student');f=find_form(registration,'update_spmb_status');f['status']='verifikasi'
    post('enrollments',f,notice='Status pendaftar diperbarui')

    job={'action':'save_job','title':title+' job','department':'Pengajar','employment_type':'Full Time','work_location':'Bekasi','summary':'Uji','description':'Deskripsi uji','responsibilities':'Tanggung jawab uji','requirements':'Kualifikasi uji','is_active':'1'}
    r=post('careers',job,notice='Lowongan berhasil disimpan');job_row=row(r.text,title+' job');job_id=re.search(r'edit_job=(\d+)',job_row)[1]
    f=find_form(get('careers',edit_job=job_id).text,'save_job');f['summary']='Diperbarui';post('careers',f,notice='Lowongan berhasil disimpan')
    job_url=re.search(r'href="([^"]+karir.php\?slug=[^"]+)"',job_row)[1]
    applicant=requests.Session();r=check(applicant.get(job_url,timeout=30));f=Forms(r.text).forms[0]
    f.update(full_name=title+' applicant',email='audit-'+TAG+str(n)+'@example.test',phone='08000000000',cover_letter='Lamaran pengujian',experience_years='0')
    r=check(applicant.post(job_url,data=f,files={'cv':('audit.pdf',PDF,'application/pdf')},timeout=30));assert 'berhasil' in r.text.lower()
    r=get('careers');application=row(r.text,title+' applicant');f=find_form(application,'update_application');f['status']='ditinjau'
    post('careers',f,notice='Status pelamar diperbarui')
    app_id=re.search(r'download_cv=(\d+)',application)[1];r=get('careers',download_cv=app_id);assert r.content==PDF
    post('careers',{'action':'archive_job','id':job_id},notice='Lowongan diarsipkan')
    print(unit+': all 8 tabs, 9 public pages, content/gallery/IG/settings/hero/SPMB/careers/CV actions OK',flush=True)

# Foundation hero upload, edit, delete and public media/range delivery.
url=B+'/portal/hero-media'
r=check(admin.get(url+'?new=1',timeout=30));f=find_form(r.text,'save');f.update(title='Hero '+TAG,media_type='image',is_active='1')
r=check(admin.post(url,data=f,files={'media':('audit.png',PNG,'image/png')},timeout=30));assert 'berhasil disimpan' in r.text
hero=row(r.text,'Hero '+TAG);hero_id=re.search(r'edit=(\d+)',hero)[1]
r=check(admin.get(url+'?edit='+hero_id,timeout=30));f=find_form(r.text,'save');f['title']='Hero updated '+TAG
r=check(admin.post(url,data=f,timeout=30));assert 'berhasil disimpan' in r.text
hero=row(r.text,'Hero updated '+TAG);media=re.search(r'<img[^>]+src="([^"]+)"',hero)[1]
media_url=B+media if media.startswith('/') else media
r=check(requests.get(media_url,headers={'Range':'bytes=0-7'},timeout=30),206);assert r.content==PNG[:8]
check(requests.head(media_url,timeout=30))
check(requests.get(media_url,headers={'Range':'bytes=99999-'},timeout=30),416)
check(requests.get(B+'/media/private/careers/cv-audit.pdf',timeout=30),404)
f=find_form(hero,'delete');r=check(admin.post(url,data=f,timeout=30));assert 'berhasil dihapus' in r.text
check(requests.get(media_url,timeout=30),404)
print('Foundation hero create/edit/delete; media GET/HEAD/range; private media protection: OK',flush=True)
