<?php
// Swoole PHP FastCGI HTTP2 Server
use Swoole\Websocket\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Coroutine\FastCGI\Proxy;
//  Project root directory on the server
$documentRoot = '/var/www/html';
// Use swoole Websocket Server localhost port 8080  
$server = new  Swoole\Websocket\Server( "127.0.0.1" , 8080, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
$fpmProxy = new Swoole\Coroutine\FastCGI\Proxy('127.0.0.1:9000', $documentRoot);

$server->set([
'user' => 'www-data',
'group' => 'www-data',
'open_http2_protocol' => true ,
'ssl_cert_file' =>  '/etc/ssl/certs/localhost.crt',
'ssl_key_file' =>  '/etc/ssl/private/localhost.key',
'http_compression' => true,    
'http_compression_level' => 7,
'worker_num' => swoole_cpu_num() * 2,
'http_parse_cookie' => false,
'http_parse_post' => false,
'document_root'  => $documentRoot,  
'enable_static_handler' => true ,   
//'http_autoindex' => true,
//'http_index_files' => ['index.html', 'index.txt'],
//  static resource paths
//'static_handler_locations'  => ['/css','/img'],
'compression_min_length' => 32,

]);
$server->on("WorkerStart", function($server, $workerId)
{
    echo "Worker Started: $workerId\n";
});
$server->on('Request', function( $request,  $response) use ($fpmProxy){ 
$uri = $request->server['request_uri'];
  echo "$uri\n";
  if(preg_match('/.php/', $uri))
  $uri = '/.php';
  echo "$uri\n";
  if(preg_match('/.css/', $uri))
  $uri = '/.css';
  echo "$uri\n";
  if(preg_match('/.webp|.png|.jpg|.svg/', $uri))
  $uri = '/.webp';
  echo "$uri\n";
     switch($uri) 
        {
         case '/.php':
         $fpmProxy->pass($request, $response);
         break;
         case '/.css':
            $css = $request->server['request_uri'];
            $response -> header ( "Cache-Control" , "public" );
            $response -> Header( "Cache-Control" , "max-age=31536000");
            $response->sendfile($css);
            break;
            case '/.webp':
                $img = $request->server['request_uri'];
                $response -> header ( "Cache-Control" , "public" );
                $response -> Header( "Cache-Control" , "max-age=31536000");
                $response->sendfile($img);
                break;
                case '/manifest.json':
                    $response -> header ( "Cache-Control" , "public" );
                    $response -> Header( "Cache-Control" , "max-age=31536000");
                    $response->sendfile('/var/www/html/manifest.json');
                    break;
                    case '/robots.txt':
                        $response -> header ( "Cache-Control" , "public" );
                        $response -> Header( "Cache-Control" , "max-age=31536000");
                        $response->sendfile('/var/www/html/robots.txt');
                        break;                     
                    }   
       });
$server->on("message", function ($server) {
});
$server->start();
