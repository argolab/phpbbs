<html>
  <head>
    <style>body{ text-align: center; height: 100%; margin: 5% 0 0 0; }</style>
  <body>
    <p>
      绑定成功！窗口 <span id="counter">3</span> 秒后将关闭。
    </p>
    <script>
      counter = document.getElementById('counter');
      setTimeout(function(){counter.innerText = 2}, 1000);
      setTimeout(function(){counter.innerText = 1}, 2000);
      setTimeout(function(){window.close();}, 3000);
    </script>
  </body>
</html>
