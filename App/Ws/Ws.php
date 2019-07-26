<?php


namespace App\Ws;


use GatewayWorker\Lib\Gateway as GateWayWorker;


/**
 * 对接客户端websocket的处理(onMessage)
 * Class Ws
 * @package app\ws
 */
class Ws
{

    /**
     * Ping心跳数据包
     * @param array $msg
     */
    public static function Ping(array $msg=[]) : void {
        $msg['Msg'] = 'Pong';
        $cid = $msg[FromCid];
        unset($msg[FromCid]);
        GateWayWorker::sendToClient($cid,\Process::MsgPack($msg,__FUNCTION__));
    }

    /**
     * 绑定Uid
     * @param array $msg
     */
    public static function Bind(array $msg=[]) : void {
        if (!isset($msg['Uid'])){
            return;
        }
        WebSocket::BindUid($msg[FromCid],$msg['Uid']);
    }

    /**
     * 取消绑定Uid
     * @param array $msg
     */
    public static function UnBind(array $msg=[]) : void {
        if (!isset($msg['Uid'])){
            return;
        }
        WebSocket::UnBindUid($msg[FromCid],$msg['Uid']);
    }

    /**
     * 加入群组
     * @param array $msg
     */
    public static function JoinGroup(array $msg=[]) : void {
        if (!isset($msg['GroupId'])){
            return;
        }
        GateWayWorker::joinGroup($msg[FromCid],$msg['GroupId']);
    }

    /**
     * 离开群组
     * @param array $msg
     */
    public static function LeaveGroup(array $msg=[]) : void {
        if (!isset($msg['GroupId'])){
            return;
        }
        GateWayWorker::leaveGroup($msg[FromCid],$msg['GroupId']);
    }

    /**
     * 解散群组(剔除所有成员)
     * @param array $msg
     */
    public static function AbandonGroup(array $msg=[]) : void {
        if (!isset($msg['GroupId'])){
            return;
        }
        $cids = GateWayWorker::getClientIdListByGroup($msg['GroupId']);
        foreach ($cids as $k => $v) {
            GateWayWorker::leaveGroup($v,$msg['GroupId']);// GateWayWorker::leaveGroup($k,$msg['GroupId']);
        }
    }

    /**
     * 断开连接
     * @param array $msg
     */
    public static function Close(array $msg=[]) : void {
        GateWayWorker::closeClient($msg[FromCid],\Process::MsgPack($msg,__FUNCTION__));
    }



    /**
     * 发送到某个用户(Cid)
     * @param array $msg
     */
    public static function SendToCid(array $msg=[]) : void {
        if (!isset($msg['Cid'])){
            return;
        }
        GateWayWorker::sendToClient($msg['Cid'],\Process::MsgPack($msg));
    }

    /**
     * 发送到某个用户(Uid)
     * @param array $msg
     */
    public static function SendToUid(array $msg=[]) : void {
        if (!isset($msg['Uid'])){
            return;
        }
        GateWayWorker::sendToUid($msg['Uid'],\Process::MsgPack($msg));
    }

    /**
     * 发送消息到某个组
     * @param array $msg
     */
    public static function SendToGid(array $msg=[]) : void {
        if (!isset($msg['Gid'])){
            return;
        }
        GateWayWorker::sendToGroup($msg['Gid'],\Process::MsgPack($msg));
    }

    /**
     * @param array $msg
     * @throws \Exception
     */
    public static function SendToAll(array $msg=[]) : void {
        GateWayWorker::sendToAll(\Process::MsgPack($msg));
    }

}