import unittest, urllib2, subprocess

HOST = 'wordpress.trustedhttp.org'
ADMIN_USER = ('admin', 'secret')

CHROME_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.27 (KHTML, like Gecko) Ubuntu/10.10 Chromium/12.0.714.0 Chrome/12.0.714.0 Safari/534.27'

def get_url(url, tlsuser=None):
    cmd = ['curl', '--user-agent', CHROME_UA]
    if tlsuser:
        cmd+= ['-k',
               '--tlsuser', tlsuser[0],
               '--tlspassword', tlsuser[1], url]
    else:
        cmd += [url]
    p = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    return p.communicate()[0]

def HTTPS(path): return 'https://' + HOST + path
def HTTP(path): return 'http://' + HOST + path
def get_HTTPS(path, **kwargs): return get_url(HTTPS(path), **kwargs)
def get_HTTP(path, **kwargs): return get_url(HTTP(path), **kwargs)

class TestHelpers(object):
    def assertIn(self, a, b):
        self.assertNotEquals(None, b)
        self.assertEquals(str, type(b))
        self.assertTrue(a in b, "%r not in %r" % (a, b))

    def assertNotIn(self, a, b):
        self.assertFalse(a in b, "%r in %r" % (a, b))
        
    def assertWPUser(self, res, wpuser):
        if not wpuser:
            wpuser = 'none'
        self.assertIn('@WPUser(%s)' % wpuser, res)

    def assertTLSUser(self, res, tlsuser):
        if not tlsuser:
            tlsuser = 'none'
        self.assertIn('@TLSUser(%s)' % tlsuser, res)

class PluginTest(unittest.TestCase, TestHelpers):
    def test_get_home_http(self):
        res = get_HTTP('/')
        self.assertWPUser(res, None)
        self.assertTLSUser(res, None)

    def test_get_home_https(self):
        res = get_HTTPS('/', tlsuser=ADMIN_USER)
        self.assertWPUser(res, 'admin')
        self.assertTLSUser(res, 'admin')

    def test_get_admin_https(self):
        res = get_HTTPS('/wp-admin/', tlsuser=ADMIN_USER)
        self.assertIn('<ul id="adminmenu">', res)

    def test_get_admin_http(self):
        res = get_HTTP('/wp-admin/')
        self.assertIn('not have sufficient permissions', res)
