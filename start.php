<?php

ini_set('date.timezone','Asia/Shanghai');//ini_set('date.timezone','PRC');
ini_set('display_errors', 'on');

if(strpos(strtolower(PHP_OS), 'win') === 0){
    exit("start.php not support windows, please use start_for_win.bat\n");
}

if(!extension_loaded('pcntl')){
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix')){
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use GatewayWorker\{
    BusinessWorker,
    Gateway,
    Register
};
use GatewayWorker\Lib\Gateway as GateWayWorker;
use Workerman\Lib\Timer;

// 消息导航标记(用于解析数据,定向处理数据的位置)
define('Uri','Uri');
define('Separator','#');
define('NamespacePrefix','\\App\\Ws\\');

// 标记消息来源
define('FromCid','FromCid');

start();

/**
 * 开始创建服务
 */
function start() : void {
    // 参数配置
    // 名称
    $workerName = 'WebSocket';
    // 进程数
    $workerCount = 4;
    // 服务地址
    $workerServiceAddress = 'websocket://0.0.0.0:1122';
    // 服务器内网IP 可以是: 127.0.0.1 , 服务器真实内网IP
    $serverLanIP = '127.0.0.1';
    // gateway注册地址
    $gatewayRegisterAddress = '127.0.0.1:1123';
    // 注册地址
    $registerAddress = 'text://0.0.0.0:1123';
    // gateway 进程开始端口
    $gatewayProcessStartPort = 5000;
    // 心跳间隔时间 单位:秒
    $gatewayInterval = 5;
    // 客户端不发消息的容忍次数
    $gatewayNotReceiveTimes = 3;
    // gateway心跳数据
    $gatewayPingData = '';

    // register
    new Register($registerAddress);

    // 业务worker
    $worker = new BusinessWorker();
    $worker->name = $workerName;
    $worker->count = $workerCount;
    $worker->registerAddress = $gatewayRegisterAddress;

    // gateway
    $gateway = new Gateway($workerServiceAddress);
    $gateway->name = $workerName;
    $gateway->count = $workerCount;
    $gateway->lanIp = $serverLanIP;
    // 内部通讯起始端口,假如$gateway->count=4,起始端口为4000,则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
    $gateway->startPort = $gatewayProcessStartPort;
    // 服务注册地址
    $gateway->registerAddress = $gatewayRegisterAddress;
    // 心跳间隔
    // pingInterval*pingNotResponseLimit秒内没有任何请求则服务端认为对应客户端已经掉线,服务端关闭连接并触发onClose回调
    $gateway->pingInterval = $gatewayInterval;
    // 不发送心跳包的次数,若为0表示允许客户端不发送心跳包(长连接必须设置心跳,否则会导致服务器内存沾满,所以这个值最好不要设置为0哦)
    $gateway->pingNotResponseLimit = $gatewayNotReceiveTimes;
    // 心跳数据
    $gateway->pingData = $gatewayPingData;
    // 以上配置含义是客户端连接 pingInterval * pingNotResponseLimit = N 秒内没有任何请求则服务端认为对应客户端已经掉线,服务端关闭连接并触发onClose回调 (包括客户端只接收消息的情况,所以客户端一定要向服务器发送心跳包数据)

    /*
    // 当客户端连接上来时,设置连接的onWebSocketConnect,即在websocket握手时的回调
    $gateway->onConnect = function($connection)
    {
        $connection->onWebSocketConnect = function($connection , $http_header)
        {
            // 可以在这里判断连接来源是否合法,不合法就关掉连接
            // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
            if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
            {
                $connection->close();
            }
            // onWebSocketConnect 里面$_GET $_SERVER是可用的
            // var_dump($_GET, $_SERVER);
        };
    };
    */

    // 运行所有服务
    Worker::runAll();
}

/**
 * Gateway 框架要求需要实现的回调函数,且类名不可更改
 * Class Events
 */
class Events {

    public static function onWorkerStart(businessWorker $businessWorker) : void {
        Process::OnWorkerStart($businessWorker);
    }

    public static function onConnect($client_id) : void {
        Process::OnConnect($client_id);
    }

    public static function onMessage($client_id, $message) : void {
        Process::OnMessage($client_id,$message);
    }

    public static function onClose($client_id) : void {
        Process::OnClose($client_id);
    }

    public static function onWorkerStop(businessWorker $businessWorker) : void {
        Process::OnWorkerStop($businessWorker);
    }

}

/**
 * 自定义进程处理类
 * Class Process
 */
class Process {

    // 加入群组 *****
    //将client_id加入某个组,以便通过Gateway::sendToGroup发送数据
    //可以通过Gateway::getClientSessionsByGroup($group)获得该组所有在线成员数据 可以通过Gateway::getClientCountByGroup($group)获得该组所有在线连接数（人数）
    //该方法对于分组发送数据例如房间广播非常有用
    //注意:
    //1、同一个client_id可以加入多个分组,以便接收不同组发来的数据
    //2、当client_id下线(连接断开)后,该client_id会自动从该分组中删除,开发者无需调用Gateway::leaveGroup
    //3、如果对应分组的所有client_id都下线,则对应分组会被自动删除
    //4、目前没有获得某个client_id加入哪些分组的接口,建议client_id加入分组的时候可以用$_SESSION来记录加入的分组,获取的时候利用$_SESSION或者Gateway::getSession($client_id)来获取
    //5、目前没有获得所有分组id接口,所有分组可以自行存入数据库或者其它存储中



    /**
     * 进程启动回调事件
     * @param businessWorker $businessWorker
     */
    public static function OnWorkerStart(businessWorker $bw) : void {
        if($bw->id === 0){
            Timer::add(5,function () use ($bw){
                echo static::MillisecondTimestamp(),"\n";
            });
        }
        return;
    }

    /**
     * 客户端握手回调事件
     * @param string $clientId
     * @throws \Exception
     */
    public static function OnConnect(string $clientId='') : void {
        GateWayWorker::sendToCurrentClient(static::MsgPack(['Cid'=>$clientId],'Connect'));
        return;
    }

    /**
     * 客户端发送消息回调事件
     * @param $clientId
     * @param $message
     */
    public static function OnMessage(string $clientId='',string $message='') : void {
        $message = static::MsgDecrypt($message);
        $msg = json_decode($message,true);
        if (!is_array($msg)){
            GateWayWorker::sendToCurrentClient(static::MsgPack([Msg=>'illegal message'],'Error'));
            return;
        }
        if (!isset($msg[Uri])){
            GateWayWorker::sendToCurrentClient(static::MsgPack([Msg=>'wrong format'],'Error'));
            return;
        }
        $actions = explode(Separator,$msg[Uri]);// abc.def.ghi.jkl.mn || abc#def#ghi#jkl#mn ...
        $suffix = end($actions);
        array_pop($actions);
        $prefix = NamespacePrefix.implode('\\',$actions);
        if (!method_exists($prefix,$suffix)){
            GateWayWorker::sendToCurrentClient(static::MsgPack([Msg=>'illegal message'],'Error'));
            return;
        }
        $msg[FromCid] = $clientId;
        $prefix::$suffix($msg);//\app\abc\def\ghi\jkl::mn($msg)
        return;
    }

    /**
     * 客户端断开连接回调事件
     * @param string $clientId
     */
    public static function OnClose(string $clientId='') : void {
        if (0 === GateWayWorker::isOnline($clientId)){
            return;
        }
        // 告知客户端他已下线,调用closeClient方法后,开发者无需调用离开群组方法,系统将自动处理
        GateWayWorker::closeClient($clientId,static::MsgPack([Msg=>'you have been disconnected'],'Close'));
        return;
    }

    /**
     * 进程关闭回调事件
     * @param businessWorker $businessWorker
     */
    public static function OnWorkerStop(businessWorker $bw) : void {
        if ($bw->id === 0){
            echo date('Y-m-d H:i:s'),"\n";
        }
        return;
    }



    /**
     * 消息打包
     * @param array $msg
     * @param string $event
     * @return string
     */
    public static function MsgPack(array $msg=[],string $event='') : string {
        // Event 作为返回给客户端的标记(如:HTML5),非必要可不传
        if ('' !== $event){
            $msg['Event'] = $event;
        }
        // 所有打包消息均带上以下信息
        $msg['Note'] = [
            'Time' => static::MillisecondTimestamp(),
            'Nonce' => static::Nonce(6),
        ];
        return static::MsgEncrypt(json_encode($msg,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 消息加密
     * message encrypt
     * @param string $msg
     * @return string
     */
    public static function MsgEncrypt(string $msg) : string {
        return $msg;
    }

    /**
     * 消息解密
     * message decrypt
     * @param string $msg
     * @return string
     */
    public static function MsgDecrypt(string $msg) : string {
        return $msg;
    }

    /**
     * 当前毫秒时间戳
     * @return float
     */
    public static function MillisecondTimestamp() : float {
        list($ms, $s) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($ms) + floatval($s)) * 1000);
    }

    /**
     * 随机字符串
     * @param int $length
     * @param string $char
     * @return string
     */
    public static function Nonce(int $length=6,string $char='1234567890') : string {
        if(!is_int($length) || $length < 1) {
            $length = 6;
        }
        $str = '';
        for($i=0;$i<$length;$i++) {
            $str .= $char[mt_rand(0,strlen($char)-1)];
        }
        return $str;
    }

}
