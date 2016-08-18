(function(){

    var entityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': '&quot;',
        "'": '&#39;',
        "/": '&#x2F;',
        " ": '&nbsp;'
    };

    var urls = [

        // 0. force url
        [/(^|\s|<br>|&nbsp;|\n|>)&lt;(((https?|ftp):&#x2F;&#x2F;).+?)&gt;(\s|$|<br>|&nbsp;|<)/g,	'$1<a target="_blank" href="$2">$2</a>$5'],


        // 1. jpg|png|gif pic to <img> tag, class from link
        [/(^|\n(&nbsp;)*)(http:&#x2F;&#x2F;.+?\.)(jpg|png|gif|jpeg)/ig, '$1<img src="$3$4" class="" alt="" />'],
        [/(^|\s|<br>|&nbsp;|\n|>)(http:&#x2F;&#x2F;.+\.)(mp3)/g, 
         '<audio src="$2$3" controls="controls" />'],

        // 2. (http://)v.youku.com... to <embed> tag
        [/(^|\n)(http:&#x2F;&#x2F;)?v\.youku\.com&#x2F;v_show&#x2F;id_(\w+)\.(html|htm)/g,
         '<embed wmode="opaque" src="http://player.youku.com/player.php/sid/$3/v.swf" allowFullScreen="true" quality="high" width="480" height="400" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>'],

        // youku.swf
        [/(^|\n)(http:&#x2F;&#x2F;player.youku.com&#x2F;player.php&#x2F;sid&#x2F;\w*&#x2F;v.swf)/g,
         '<embed wmode="opaque" src="$2" allowFullScreen="true" quality="high" width="480" height="400" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>'],         

        // xiami
        [/(^|\n)(http:&#x2F;&#x2F;www.xiami.com&#x2F;widget&#x2F;\d*_\d*&#x2F;singlePlayer\.swf)/g,
         '<embed wmode="opaque" src="$2" align="middle" width="257" height="33" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>'],

        // tudou
        [/(^|\n)(http:&#x2F;&#x2F;www\.tudou\.com&#x2F;\w&#x2F;[\w\d\-]*(&#x2F;?)(&amp;[\w\d=_]*)*&#x2F;v\.swf)/g,
         '<embed src="$2" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="480" height="400"></embed>'],

        // gist
        [/(^|\n)(https?:&#x2F;&#x2F;gist\.github\.com&#x2F;([0-9]+)\.js)/g,
         '<div class="gist" data-submit="load-gist" data-gistid="$3">: �����gist����</div>'],

        // url
	    [/(^|\s|<br>|&nbsp;|\n|>)(www\..+?\..+?)(\s|$|<br>|&nbsp;|<)/g,		'$1<a target="_blank" href="http://$2">$2</a>$3'],
	    [/(^|\s|<br>|&nbsp;|\n|>)(((https?|ftp):&#x2F;&#x2F;).+?)(\s|$|<br>|&nbsp;|<)/g,	'$1<a target="_blank" href="$2">$2</a>$5'],
        //@gcc
        [/(^|&nbsp;|<br>|\n)@([a-zA-Z]{2,12})/g,	'$1<a href="#!user?userid=$2">@$2</a>'],

        // xxx ������
        [/&nbsp;(<span class="c32">)?([a-zA-Z]{3,20})&nbsp;(<span class="c37">)?������/g,
         '&nbsp;$1<a href="#!board?boardname=$2">$2</a>&nbsp;$3������']
        
    ];

    function format_escape(string) {
        return String(string).replace(/[&<>"'\/\ ]/g, function (s) {
            return entityMap[s];
        });
    }

    format_cr = function(s){
        return s.replace(/\r\n/gm, "\n").replace(/\r/gm, "\n");
    } 
    format_br = function(s){
        return s.replace(/\n/gm, '<br\>\n');
    }
    function format_quote(s){
        return s.replace(/^(��&nbsp;��&nbsp.*&nbsp;.*�Ĵ������ᵽ[:��]&nbsp;��)\n((?:[:��].*\n)*)\n*/gm, '<div class="postquote"><div class="postquote-header">$1</div><div class="postquote-content">$2</div></div>')
    }
    format_color = function(s){
        return s.replace(/\[%\d+(;\d+)*#\]/gm, function(s){
            return s.replace('[%', '<span class="ac').replace(/;/gm, ' ac').replace('#]', '">');
        }).replace(/\[#%\]/gm, '</span>');
    }

    format_esc = function(s) {
        var segments = s.replace(/\x1b\[(?:\d{1,2};?)+m/gm, function(t) {
            //console.log(['zz', t.substring(2, t.length-1)]);
            var colors = t.substring(2, t.length-1).split(';');
            for (var i = 0; i< colors.length; i++) {
              colors[i] = 'c'+colors[i];
            }
            return '<span class="'+ colors.join(' ') + '">';
        });
        segments = segments.split(/\x1b\[m/gm);
        var res = '';
        for (var i = 0; i < segments.length; i++) {
           var seg = segments[i];
           var matches = seg.match(/<span class=\"(c\d{1,2}\s?)+\"/gm);
           var cnt = 0;
           if (matches) cnt = matches.length;
           res += seg;
           while(cnt--) res += '</span>';
        }
        //console.log(['split', s, segments, res]);
        return res;
    }
    
    format_linkify = function(s) {
        var before = s;
        for (u in urls)  {
            var s = s.replace(urls[u][0], urls[u][1]);
        }
        //console.log(["debug-linkify", before, s]);
        return s;
    }
    function format(text){
        if(text[0] == '\n')
            text = text.slice(1);
        // if(text[0] == '#' ){
        //     if(text.substr(0, 9) == '#markdown'){
        //         return '<div class="markdown">' +
        //             markdown.toHTML(text.substr(9)) + '</div>';
        //     }
        // }            
        text = format_escape(text);
        text = format_cr(text);
        text = format_quote(text);
        text = format_br(text);
        text = format_esc(text);
        text = format_linkify(text);
        return text;
    }


    var now = new Date()
    , TSnow = now / 1000
    , today = new Date(now.getFullYear(),
                       now.getMonth(),
                       now.getDate())
    , yesterday = new Date(now.getFullYear(),
                           now.getMonth(),
                           now.getDate()-1)
    , thisYear = new Date(now.getFullYear(), 0, 0)
    , TSYesterday = Math.ceil(Number(yesterday) / 1000);

    function toTwoBit(num){
        return (num<10?('0'+num):num);
    }

    function toHoursMinutes(date){
        return toTwoBit(date.getHours()) + ":" + toTwoBit(date.getMinutes());
    }

    function toThreeBit(n) {
        if (n < 100) {
            n = '0' + n;
        }
        if (n < 10) {
            n = '0' + n;
        }
        return n;     
    }    

    function toISOString(d) {
        return d.getUTCFullYear() + '-' +  toTwoBit(d.getUTCMonth() + 1)
            + '-' + toTwoBit(d.getUTCDate()) + ' ' + toTwoBit(d.getUTCHours())
            + ':' +  toTwoBit(d.getUTCMinutes()) + ':'
            + toTwoBit(d.getUTCSeconds());
    }        

    function niceTimeWord(time){
        var now = new Date() ,
        distance = Math.round((Math.abs(now - time) / 1000));

        /*

          43200:3600            3500:60    60:1 
          ... |_____________________|__________|_____| <-now
          12h                  60min        1s

          return the distance if less that 12hour
          
        */
        if(distance<60){
            return distance + '��ǰ';
        }
        else if(distance < 3600){
            return Math.round(distance/60) + '����ǰ';
        }
        else if(distance < 43200){
            return Math.round(distance/3600) + 'Сʱǰ';
        }

        /* Today, Yesterday, This Year, or other. */
        if( time > today ){
            return '���� ' + toHoursMinutes(time);
        }
        else if ( time > yesterday){
            return '���� ' + toHoursMinutes(time);
        }
        else if ( time > thisYear){
            return (time.getMonth() + 1) + '��' + time.getDate() + '�� '
                + toHoursMinutes(time);
        }
        else{
            return toISOString(time);
        }
    }
    
    function niceTimestamp(timestamp){
        var time = new Date(timestamp * 1000);
        return niceTimeWord(time);
    }

    (function(){
        var ms = Array.prototype.slice.call(document.getElementsByTagName("pre"));
        for(var i=0; i<ms.length; ++i){
            var it = ms[i];
            it.parentNode.innerHTML = format(it.innerHTML);
        }
        var ts = document.getElementsByClassName("time");
        for(i=0; i<ts.length; ++i){
            var it = ts[i];
            console.log(it);
            it.innerHTML = niceTimestamp(it.innerHTML);
        }
    })();

})();
