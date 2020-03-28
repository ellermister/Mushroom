<?php


namespace App\Http\Controllers;


use Mushroom\Core\Http\Request;

class HomeController
{
    /**
     * 显示首页介绍
     * @return mixed
     * @author ELLER
     */
    public function indexPage(Request $request)
    {
        $header = json_encode($request->headers->all(), JSON_PRETTY_PRINT);
        $host = $request->getHttpHost();
        return view('index',['api_host' =>  $request->getSchemeAndHttpHost(),'header' => $header,'host' => $host]);
    }
}