API 接口说明文档 v0.5
==============

----
目录
----
- 基本规范说明
- Section
- User
- Board
- Post
- Mail
- Misc
- 附录A


------------
基本规范说明
------------
* 浏览器通过GET/POST方法访问对应的url获取/修改数据，
* 其中GET用于读取数据(只读)，如果有需要修改后台数据的接口，使用POST方法。后台返回JSON格式的数据，其中编码为UTF-8。
* 如果操作成功，后台将返回 ```{success: "1", data: <some object> } ``` 格式的数据，其中data根据不同接口有不同的对象。
* 如果操作失败，返回 ``` {success: "", error: <error msg>, code: <error code> } ``` ，```<error msg>```是string，表示相关的错误信息，```<error code>```是整数，返回对应的错误号码（错误号对照表见最后附录A）
* 接口说明中的"..."表示任意的内容（内容与特定的接口相关）。
* 接口分为以下几个部分：
     - Section  和讨论区相关的接口
     - User 涉及用户相关接口。如登陆，查询用户，好友访问等    
     - Board 版面操作。如版面信息，清除版面未读等
     - Post 看帖发贴相关操作。如帖子列表，发帖，同主题等
     - Mail 邮件相关
     - Misc 其他杂项（如十大）
     - Admin 管理相关，TODO状态
     - Message  消息提醒、feed流等，TODO状态

-------
Section
-------
Section是和讨论区相关的接口

#### /ajax/section/
- Usage：获取所有讨论区的信息列表
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<section object>, …]}```
           <section object>:{
                                seccode: 讨论区的编码（0,u,z,c,r,a …)
                                secname: 讨论区名称
                            }
- Fail: ```{success: "", error: "...", code: "..."}```

----
User
----
User是和用户相关的接口，包括用户的注册登陆，修改属性，查询用户信息等。

#### /ajax/register/
- Usage: 提交注册信息
- Method: POST
- Param:
     - 必须参数:
          - userid: 用户id（全站唯一的id），匹配/^[a-zA-Z]{2,12}$/
          - passwd: 密码
          - passwd-confirm: 确认密码,需要和$passwd一致
     - 可选参数:
          - username: 用户昵称，如argo(逸仙时空)，那么argo是userid，逸仙时空是username
          - realname: 用户真实名字
          - address: 地址
          - email: 邮件地址
          - gender: 性别，只有三种值可选：'M' or 'F' or 'U'
          - birthyear: 出生年,50-99
          - birthmonth: 月，1-12
          - birthday: 日，1-31
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/auth/????/

 * 验证信息，有两种方式
 * # 用 netid 验证 
 *  1.  浏览器跳转到https://cas.sysu.edu.cn/cas/login?service=<service-url>上
 *      其中service-url是bbs的验证页面url 后面会用到
 *      按目前设计，这个<service-url>应该设置为http://<bbs-site>/ajax/auth/netid/
 *  2. 用户在cas页面验证netid，如果成功，会将用户跳转到:
 *     http://<bbs-site>/ajax/auth/netid/?ticket=<the-ticket>
 *     然后后台获取ticket
 *  3. 后台根据ticket，使用curl发请求到:
 *      https://cas.sysu.edu.cn/cas/validate?service=<service-url>&ticket=<the-ticket>
 *      然后如果返回yes，那么表明这个netid是有效的。
 *  4. 前端需要做的是在用户选择验证方式时让用户点击1中的链接即可。并在3中返回成功时重
 *      引导用户到首页。
 *  @param: 
 *          $ticket 
 *
 * #. 校友信息验证（2008年前毕业的校友使用）
 *  1. 毕业年份，真实姓名，专业（这个要先根据毕业年份ajax取(misc提供获取接口)，用户select来选择）
 *     出生年月，学号
 *     需要以上信息都匹配到bbs_home/auth/1995_2008 
 *     中的资料（但譬如资料里没有学号，那么可以不匹配）
 * @param: 
 *         $year: 毕业年份19xx
 *         $realname: 真实姓名
 *         $major: 专业
 *         $birthyear: 1900-1999
 *         $birthmonth: 1-12
 *         $birthday: 1-31
 *         $student_id: 学号

    - /ajax/auth/info/
        - Usage: 利用校友信息验证
        - Method: POST
        - Param: 如上述 
        - Success: ```{success: "1", data: "..."}```

    - /ajax/auth/netid/
        - Usage: 用NetID进行验证，使用方式请看上述
        - Method: GET
        - Param: 如上述
        - Success: ```{success: "1", data: "..."}```

#### /ajax/login/
- Usage: 登陆
- Method: POST
- Param:
    - userid: 用户名
    - passwd: 密码
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/logout/
- Usage: 登出
- Method: POST
- Param: none
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/friend/
- Usage: 获取好友列表
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<relation object>, ...]}```
        <relation object>: {id: "...",  // 好友userid
                            exp: "..."} // 好友备注
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/addfriend/
- Usage: 添加好友
- Method: POST
- Param:
    - id: 好友userid
    - exp: 好友备注
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/delfriend/
- Usage: 删除好友
- Method: POST
- Param:
    - userid: 好友id
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/user/fav/
- Usage: 获取收藏夹版面的信息
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<board object>, ...]}```
        <board object>: {
                        filename: 版面名（如water，Linux)
                        title: 版面描述（如'你一瓢来我一瓢'）
                        BM: [<userid>, ...]:版主id列表
                        lastpost: 最后发帖时间
                        total: 帖子总数
                        seccode: 所属讨论区的code（0,u,z,c,r...)
                        type: 版面类型
                        unread: 是否还有未读帖子
                        lastpostfile: 最后发帖的标题
                        lastfilename: 最后发帖的帖子id（M.12243454646.A)
                        lastauthor: 最后发帖的作者
                    }
- Fail：```{success: "", error: "...", code: "..."}```

#### /ajax/user/addfav/
- Usage: 添加收藏版面
- Method: POST
- Param:
    - boardname：版面名
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/user/delfav/
- Usage: 删除收藏版面
- Method: POST
- Param:
    - boardname：版面名
- Success: ```{success: "1", data: "..."}```
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/user/query/
- Usage: 查询用户信息（对SYSOP，会显示真实名字）
- Method: GET
- Param:
    - userid: 查询的用户id
- Success: ```{success: "1", data: <user_info object>}```
        <user_info object>: {
                        userid: 查询用户id
                        username: 昵称
                        usertitle: 称号
                        life_value: 生命值
                        has_mail: 是否有未读email
                        lastlogout: 最后登出时间
                        lastlogin: 最后登录时间
                        stay: 在线时间
                        constellation: 星座
                        male: 是否是male，True | False
                        plan: 个人说明
                        signature: 签名档
                        realname: 真实姓名 （只有对SYSOP才显示）
                        online: 是否在线
                        mode: 在线状态
                        }
- Fail: ```{success: "", error: "...", code: "..."}```

#### /ajax/user/update/
- Usage: 更新用户的信息
- Method: POST
- Param: (all optional)  
    - passwd: 新密码，如果需要修改新密码，那么下面两个参数都必须出现
        - old-passwd: 旧密码
        - confirm-passwd: 确认密码
    - username： 用户名
    - realname： 真实姓名
    - gender: 'M' or 'F'
    - address： 地址
    - email： 邮件地址（不一定是学校邮箱）
    - birthyear: 50-99
    - birthmonth: 1-12                                             
    - birthday: 1-31
    - plan: 个人说明
    - signature： 签名档
    - avatar: FILE文件，上传的头像
- Success: ```{success: "1", data: "...."}```
- Fail: ```{success: "", error: "...", code: "..."```

#### /ajax/user/info/
- Usage: 获得自己的用户信息（和/ajax/user/query/不同的是这个只能查看自己的信息，一般用于设置用户资料）
- Method：GET
- Param: none
- Success: ```{success: "1", data: <user_rec object>}```
        <user_rec object>: {
                            userid:  用户的id
                            firstlogin: 第一次login的时间（tiemstamp）
                            lasthost: 最后登录的地址（IP)
                            numlogins: 登陆次数
                            numposts: 发帖数
                            flags: //忽略
                            username: 用户昵称
                            usertitle: 称号
                            lastlogin: 最后登录时间（timestamp）
                            lastlogout: 最后登出时间（timestamp）
                            stay: 在线时间
                            realname: 真实名字
                            address: 地址
                            email: 邮件
                            nummails: 邮件数
                            gender: M or F
                            birthyear: 50-99
                            birthmonth: 1-12
                            birthday: 1-31
                            signature: 签名档
                            plan: 个人描述
                            }
- Fail: ```{success: "", error: "...", code: "..."```

-----
Board
-----


#### /ajax/board/all/
- Usage: 获取所有版面的boardname列表(在权限可见范围内）
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<boardname>, ...]}```


#### /ajax/board/alls/
- Usage: 获取所有板块信息（按照讨论区分类）
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<sec-board object>]}```
        <sec-board object>: {
                            seccode: 讨论区码（0,z,u, etc...)
                            secname: 讨论区名
                            boards: [<board object>, ...]
                            }
        <board object>:  {
                            title: 版面描述（如 你一瓢来我一瓢）
                            BM: 版主（多个版主用空格分开）
                            lastpost: 最后发帖时间（timestamp)
                            total: 帖子总数
                            boardname: 版面名字（如water）
                        }

#### /ajax/board/get/
- Usage: 获取版面信息
- Method: GET
- Param:
    - boardname: 需要查询的版面boardname（如water）
- Success:```{success: "1", data: <board object>}```
        <board object> 和/ajax/user/fav/中的<board object>一致

#### /ajax/board/getbysec/
- Usage: 获取某个讨论区的seccode所对应的那些版面信息
- Method：GET
- Param:
    - sec_code: 讨论区代码（如0,u,z，etc，在/ajax/board/alls/中的那些seccode）
- Success: ```{success: "1", data: [<board object>, ...]}```
        <board object>和/ajax/user/fav/的<board object>一致

#### /ajax/board/clear/
- Usage: 清除某个版面的所有未读标志
- Method: POST
- Param:
    - boardname
- Success: ```{success: "1", data: "..."}```

#### /ajax/board/readmark/
- Usage: 获取某个版面下的所有已读帖子的index列表（index也即是序号, 譬如1，3，4，5...）
- Method: GET
- Param:
    - boardname
- Success: ```{success: "1", data: [<digit>, ...]}```


-----
Post
-----

#### /ajax/post/list/
- Usage: 获取某个版面的帖子列表（列表信息），根据具体的type参数有不同的返回
- Method: GET
- Param:
    - type: 列表类型（normal 普通模式， digest 文摘模式， topic 同主题模式）
    - start (可选): 开始的位置（譬如start=3，那么返回序号从3开始的20个帖子列表，根据type属性来过滤）。如果不设置则默认为返回最后的20个帖子列表
    - boardname： 版名
- Success: ```{success: "1", data: [<posthead object>, ...]}```
        <posthead object>: {
                        index: 该帖子的序号
                        flag: //忽略
                        id: timestamp，这个帖子所属主题的第一个帖子的filename中间的那个时间戳（如M.123456789.A的时间戳是123456789。)，同个主题的所有帖子id是一致的。
                        update: 最后的修改时间戳
                        owner: 作者
                        title: 标题
                        filename: 类似M.12343546.A的文件名，是帖子在版内的唯一id
                        unread: 是否未读
                        mark: g/m 之类的
                        }

#### /ajax/post/get/
- Usage: 获取某个帖子的内容信息
- Method: GET
- Param:
    - boardname: 版面名
    - filename: 类似M.123435345.A的文件名
- Success: ```{success: "1", data: <post object>}```
        <post object>: {
                        userid: 帖子作者id
                        username: 昵称
                        title: 标题
                        board: 版面名
                        post_time: 发表时间
                        rawcontent: 内容（原始内容，可能带esc)
                        rawsignature: 签名（原始内容）
                        bbsname: "逸仙时空 Yat-sen Channel"
                        ah: <attach object>
                        filename: 类似M.123432345.A，应该和参数的filename一致
                        perm_del: 是否有权利去删除（用于辅助是否需要显示编辑、删除按钮）
                        }
        <attach object>: {
                        filename: A.xxxxxxx.A，附件的唯一标识，中间的时间戳和帖子的时间戳一致
                        origname: 附件的原文件名
                        desc: 附件上传时的类型， 如image/jpeg
                        filetype: 文件名后缀，如jpeg
                        articleid: 附件时间戳
                        link: /attach/$boardname/$filename，
                        is_picture: 0 or 1
                        }
        如果<attach object>为空，则这个帖子没有附件

#### /ajax/post/nearname/
- Usage: 返回某个帖子的上一篇/下一篇的filename
- Method: GET
- Param:
    - boardname： 版面
    - direction: prev | next 决定上一篇/下一篇
    - filename: 类似M.123343545.A的帖子标识名
- Success: ```{success: "1", data: "<filename>"}```
 
#### /ajax/post/topiclist/
- Usage: 返回同个主题的所有filename列表
- Method: GET
- Param:
    - bordname
    - filename: 查找所有和这个filename同主题的filename列表
- Success: ```{success: "1", data: [<filename>, ...]}```

#### /ajax/post/add/
- Usage: 发帖，回帖，修改帖子
- Method: POST
- Param:
    - type: new | reply | update
    - boardname
    - articleid（可选）：回帖时所回复帖子的filename（type = reply时有用）
    - title: 帖子主题
    - content： 内容
    - attach（可选）：附件，FILE文件
- Success: ```{success: "1", data: "..."}```

#### /ajax/post/del/
- Usage： 删帖
- Method: POST
- Param:
    - boardname
    - filename
- Success: ```{success: "1", data: "..."}```


----
Mail
----

#### /ajax/mail/mailbox/
- Usage: 返回邮箱的基本信息
- Method: GET
- Param: none
- Success:
                {success: "1", data: {
                                     total: 总邮件数
                                     used_size: 已使用空间
                                     total_size: 邮箱总空间
                                    }}


#### /ajax/mail/list/
- Usage: 返回邮件列表信息
- Method: GET
- Param:
    - start: 列表开始的序号（index），如start=2，那么返回2-22之间的邮件列表
- Success：```{success: "1", data: [<mailhead object>, ...]}
        <mailhead object>: {
                            index: 邮件序号
                            flag: 邮件相关信息（& 0 ==> new mail， >31 ==> 已回复)
                            filetime: 发邮件时间
                            owner: 发邮件作者
                            title: 标题
                            reply: 1 or 0，是否已经回复了这封邮件
                            }

#### /ajax/mail/get/
- Usage: 返回某封邮件信息
- Method:  GET
- Param:
    - index: 邮件的序号
- Success: ```{success: "1", data: "..."}```

#### /ajax/mail/send/
- Usage: 发邮件
- Method: POST
- Param:
    - title: 标题
    - content: 内容
    - receiver: 收件人
    - articleid（可选）:如果是回复则需要这个（类似于帖子的同主题，第一个邮件的filename）
- Success: ```{success: "1", data: "..."}```

#### /ajax/mail/del/
- Usage: 删除邮件
- Method: POST
- Param:
    - indexes: array(a1, a2, ...an)  需要删除的帖子的index列表
- Success: ```{success: "1", data: "..."}```


----
Misc
----

#### /ajax/ann/
- Usage: 返回某个精华路径目录下的项列表
- Method: GET
- Param:
    - path: 路径，总体上与BBSHOME/0Announce基本对应
            一般只需要按照返回的url来组织当前目录下的链接即可
            （按照浏览文件目录来理解精华区就容易很多了）
- Success: ```{succes: "1", data: [<annhead object>] }```
        <annhead object> : {
                            flag:  f:FILE, d:DIR, l:LINK, r:READONLY, n: GUESTBOOK, a:PERSONAL
                            mtime: 修改时间
                            filename: D.xxxxxx.A 或者 M.xxxxx.A
                            title: 标题
                            owner: 原作者
                            editor: 收录人
                            url: 链接
                            type: ann | anc 用于决定使用ann来获取列表或者anc来获取内容
                            }

#### /ajax/anc/
- Usage: 返回某个精华文内容(raw)
- Method: GET
- Param:
    - path: 精华文路径
- Success: ```{success: "1", data: "<ann content>"}```


#### /ajax/comm/topten/
;; TODO


#### /ajax/misc/errorcode/
- Usage: 获取错误码对照表
- Method: GET
- Param: none
- Success: ```{success: "1", data: [<code>: <code_explanation>, ...]}```


