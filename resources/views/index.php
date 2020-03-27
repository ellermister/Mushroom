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
</div>

<!-- jQuery (Bootstrap 的所有 JavaScript 插件都依赖 jQuery，所以必须放在前边) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
<!-- 加载 Bootstrap 的所有 JavaScript 插件。你也可以根据需要只加载单个插件。 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js"></script>
</body>
</html>