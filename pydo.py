#!/home/bbs/local/bin/python2.7
# -*- coding: utf-8 -*-

import requests
import re
import sys
import json
from pyquery import PyQuery as pq

def hello_world(*paras):
    return 'Hello, World! [%s]' % repr(paras)

def logappend(msg):
    open('/home/bbs/phpbbs/log', 'a').write(msg+'\n')

def check_netid(netid, passwd):
    m = requests.get('https://sso.sysu.edu.cn/cas/login')
    html = pq(m.text)
    values = pq('[name]', html)
    d = {}
    for v in values :
        d[v.name] = v.value
    d['username'] = netid
    d['password'] = passwd
    p = requests.post("https://sso.sysu.edu.cn/cas/login", data=d, cookies=m.cookies)
    return p.text.find('<input') < 0

def guess_userinfo(netid, passwd):
    r = requests.get('http://elearning.sysu.edu.cn/webapps/bb-caszsdx-bb_bb60/index.jsp')
    n = requests.get('http://elearning.sysu.edu.cn/webapps/bb-caszsdx-bb_bb60/index.jsp', cookies=r.cookies)
    html = pq(n.text)
    values = pq('[name]', html)
    d = {}
    for v in values :
        d[v.name] = v.value
    d['username'] = netid
    d['password'] = passwd
    m = requests.post("https://sso.sysu.edu.cn/cas/login?service=http%3A%2F%2Felearning.sysu.edu.cn%2Fwebapps%2Fbb-caszsdx-bb_bb60%2Findex.jsp", data=d, cookies=n.cookies)
    m.cookies['cookies_enabled'] = 'yes'
    f = requests.get('http://elearning.sysu.edu.cn/webapps/portal/execute/topframe', cookies=m.cookies)
    txt = f.text
    z = pq(txt)
    txt = pq('#loggedInUserName', z)
    if not txt :
        return None
    group = re.split(' (\d+)', txt.text())
    clsinfo = ''.join(group[:-2])
    sno = group[-2]
    realname = group[-1]        
    return (clsinfo, sno, realname)

mod = locals()

if __name__ == '__main__' :
    logappend(repr(sys.argv))
    func = sys.argv[1]
    paras = json.loads(sys.argv[2])
    if isinstance(paras, list) :
        print json.dumps(dict(data=(mod[func](*paras))))
    elif isinstance(paras, dict) :
        print json.dumps(dict(data=(mod[func](**paras))))
    else :
        print json.dumps(dict(data=(mod[func](paras))))
