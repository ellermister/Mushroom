<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pulbic API</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!--[if lt IE 9]>
    <script src="https://cdn.jsdelivr.net/npm/html5shiv@3.7.3/dist/html5shiv.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/respond.js@1.4.2/dest/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div class="container">
    <h1>通用接口</h1>
    <h3>获取IP国别代码（仅亚洲）</h3>
    <h4>请求地址</h4>
    <pre>GET <?php echo $api_host ?>/ip/country?ip=IP地址</pre>
    <h4>响应结果</h4>
    <samp>CN</samp>
    <pre>
        <?php echo $header ?>
    </pre>

    <h3>获取访客IP地址</h3>
    <h4>请求地址</h4>
    <pre>GET <?php echo $api_host ?>/ip</pre>
    <h4>响应结果</h4>
    <samp>192.157.1.1</samp>

    <h3>检测IP服务器状态</h3>
    <h4>请求地址</h4>
    <pre>WS ws://<?php echo $host ?>/ip/add</pre>
    <h4>握手信息</h4>
    <p>1.服务端响应<code>auth:</code></p>
    <p>2.客户端回应 <code>client</code></p>
    <p>3.服务端响应 <code>auth success</code></p>
    <p>4.客户端发送IP <code>192.168.32.1</code></p>
    <p>5.服务端响应IP状态 <code>192.168.32.130,检测结果:1</code></p>
    <h4>在线测试</h4>
    <form class="form-inline">
        <div class="form-group">
            <label for="exampleInputName2">IP地址</label>
            <input type="text" class="form-control" id="ip_addr" placeholder="输入要检测的IP">
        </div>
        <button type="button" class="btn btn-default" id="checkIP">发起检测</button>
    </form>
    <h4>响应结果</h4>
    <samp id="ip_result">192.168.32.130,检测结果:1</samp>



    <div style="margin-bottom:200px">
    </div>
</div>

<!-- jQuery (Bootstrap 的所有 JavaScript 插件都依赖 jQuery，所以必须放在前边) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
<!-- 加载 Bootstrap 的所有 JavaScript 插件。你也可以根据需要只加载单个插件。 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js"></script>
<script type="text/javascript">

    function WebSocketConnect() {
        if ("WebSocket" in window) {
            var ws = new WebSocket("ws://<?php echo $host;?>/ip/add");
            ws.onopen = function () {

            };
            ws.onmessage = function (evt) {
                var received_msg = evt.data;
                if (received_msg == "auth:") {
                    ws.send("client");
                }
                $('#ip_result').append(received_msg+"<br>");
            };
            ws.onclose = function (evt) {
                // 关闭 websocket
                var received_msg = evt.data;
                $('#ip_result').append(received_msg+"-- close -- <br>");
            };

        } else {
            // 浏览器不支持 WebSocket
        }
        return ws;
    }
    var ws = null;
    $('#checkIP').click(function () {
        if(ws == null){
            ws = WebSocketConnect()

        }
        let ip = $('#ip_addr').val();
        setTimeout(function(){
            ws.send(ip);
        },500);
        return false;
    });
</script>
</body>
</html>