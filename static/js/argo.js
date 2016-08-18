/********************** ��������� ***********************/

var basic_libs = [
    "/js/lib/fallback.js",
    "/js/lib/jquery-ui.js",
    "/js/lib/jquery.corner.js",
    "/js/lib/jquery.cookie.js",
    "/js/lib/jquery.form.js",
    "/js/lib/jquery.linkify.js",
    "/js/lib/sammy.js",
    "/js/lib/sammy.template.js",
    "/js/common.js",
];

require(basic_libs, function() {

    if (window.location.pathname != "/") {
	    return;
    }
    initTopNav();
    initSideNav();
    initHashLink();
    initOther();
    

    // the hash url router
    var app = $.sammy(function() {
	    this.debug = true;

	    // other
	    this.get(/#!\/.*/, function() {
	        notifyObj = notify("������...", 6000);
 	        this.load(window.location.hash.substring(2) + "?nocache=" + Math.random(), {cache:false})
		        .then(function(context) {
		            notifyObj.fadeOut();
		            $("#content-column").html(context);
		        });
	    });

    });
    
    // default page��Ĭ��Ϊ��ҳ
    app.run('#!/main/');
    
});

/*********************** ���� *****************/

function initTopNav() {
    if ($("#top-nav").length <= 0) {
	    return;
    }
    $("#top-nav a[href='/login/']").click(function(event) {
	    event.preventDefault();
	    showWindow('fwin-login', "/login/", "�û���¼", 440, 220);
    });
    $("#top-nav a[href='/profile/']").click(function(event) {
	    event.preventDefault();
    });
    //$("#top-nav a[href='/reg/']").click(function(event) {
	//    event.preventDefault();
    //    alert("��δ����Ŷ��");
    //});
}

/*********************** ����� *****************/

function initSideNav() {

    if ($("#side-nav").length <= 0) {
	    return;
    }
    var sec_list = ['BBSϵͳ', 'У԰����', 'Ժϵ����', '���ԿƼ�',
		            '��������', '�Ļ�����', 'ѧ����ѧ', '̸��˵��',
		            '�����Ϣ', '��������'];

    var a_sec_list = sec_list.map(function(sec, idx, arr) {
	    return '<a class="subitem" href="/sec/' + idx + '/">' + sec + '</a>';
    });

    // ���������������б�
    $("#side-nav a[href='/sec/']").after($('<div id="side-sec">' + a_sec_list.join('') + '</div>').hide());

    // ���չ������������
    $("#side-nav a[href='/sec/']").click(function(event) {
	    event.preventDefault();
	    var hidden = $.cookie("side-sec-hidden") || $("#side-sec").is(":hidden");
	    if (hidden) {
	        $("#side-sec").show('fast');
	        $.cookie("side-sec-hidden", null);
	    } else {
	        $("#side-sec").hide('fast');
	        $.cookie("side-sec-hidden", "true");
	    }
	    
    });

    $("#side-nav").delegate("a", "click", function(event) {
	    // prevent location jump
	    event.preventDefault();
	    // first change the selection
	    $("#side-nav .selected").removeClass("selected");
	    $(this).addClass("selected");
	    // change url :-)
	    window.location.hash = "#!" + $(this).attr('href');
        /* ÿ�ε������ˢ��content-column */
        /*$.get($(this).attr('href'), function(resp){            
            $('#content-column').html(resp);
        }); */
        
    });
    
    
    // ����hoverЧ��
    $('#side-nav').delegate('.item, .subitem', 'hover', function() {
	    $(this).toggleClass("hover");
    });

    // ��ʼ��ѡ����
    $("#side-nav a[href='" + window.location.pathname + "']").addClass("selected");
    
    //���ڼ������δ������Ϣ ,30sһ��      
    setInterval('checkall()', 30000);
    
    $("#mail-box").click(function(){
        $("#has-new-mail").hide();
    });
    
    $("#message-box").click(function(){
        $("#has-new-message").hide();
    });
}


/************* ת��վ��url���� ***************/
function initHashLink() {
    $("#content-column").delegate("a", "click", function(event) {
        if($(this).attr('class') == 'load-exclude') return ;
 
	    pat = /http:\/\/(.+?)[\/$]/;
	    var hosts = pat.exec(this.href);
	    if (!hosts || hosts.length < 2) return;
	    var host = hosts[1];
        
	    event.preventDefault();
	    if (host == window.location.host) { // 	վ��url
	        // console.log("open url in page: " + this.href);
	        window.location.hash = "!" + $(this).attr("href");
            //url��ת�Զ� scroll ������
            $('html, body').animate({scrollTop:0}, 0);
	    } else {		// վ��url���´��ڴ�
	        window.open(this.href);
	    }
    });
}


function initOther() {
    $('.to-top').click(function(event) {
        $('html, body').animate({scrollTop:0}, 'fast');
        return false;
    });

    $("#content-column").delegate(".common-button", "hover", function(event) {
        $(this).toggleClass("button-hover"); 
    }); 
    
    //�û�ע��
    $('#top-reg').click(function(){
        var win = $('#fwin-register');
	    if (win.is(":visible")) {
            win.dialog('close');
	    } else {
            showWindow("fwin-register", "/reg/", "�û�ע��", 700, 490);
	    } 
    });

}
