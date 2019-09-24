<?php


namespace app\ws;


use GatewayWorker\Lib\Gateway as GateWayWorker;
use \p;
use \v;


/**
 * 对接客户端websocket的处理(onMessage)
 * Class Ws
 * @package app\ws
 */
class ws
{

    /**
     * business logic : binding client_id and user_id
     * @param string $cid
     * @param int $uid
     * @return string
     */
    protected static function BindUid(string $cid='',int $uid=0) : string {
        if ($cid === ''){
            return 'client_id is not allowed to be ""';
        }
        if ($uid === 0){
            return 'user_id is not allowed to be 0';
        }
        if (GateWayWorker::isOnline($cid) == 0){
            return 'the current user(client_id) is not online';
        }
        GateWayWorker::bindUid($cid,$uid);
        $cids = GateWayWorker::getClientIdByUid($uid);
        if ([] === $cids){
            return 'binding failed';
        }
        $len = count($cids);
        for ($i=0;$i<$len;$i++){
            if ($cids[$i] != $cid){
                GateWayWorker::closeClient($cids[$i]);
            }
        }
        $guid = GateWayWorker::getUidByClientId($cid);
        if (is_null($guid)){
            return 'binding failed';
        }
        if ($guid != $uid){
            return 'binding error';
        }
        return '';
    }

    /**
     * business logic : unbinding client_id and user_id
     * @param string $cid
     * @param int $uid
     * @return string
     */
    protected static function UnBindUid(string $cid='',int $uid=0) : string {
        if ($cid === ''){
            return 'client_id is not allowed to be ""';
        }
        if ($uid === 0){
            return 'user_id is not allowed to be 0';
        }
        GateWayWorker::unbindUid($cid,$uid);
        $guid = GateWayWorker::getUidByClientId($cid);
        if (!is_null($guid)){
            return 'unbinding failed';
        }
        return '';
    }



    /**
     * Ping heart data
     * @param object $msg
     */
    public static function ping(object $msg) : void {
        $pong = v::$pong;
        $from_client_id = v::$from_client_id;
        $mk = v::$msg;
        $msg->$mk = $pong;
        $cid = $msg->$from_client_id;
        unset($msg->$from_client_id);
        GateWayWorker::sendToClient($cid,p::MsgPack($msg,$pong));
    }

    /**
     * binding client_id and user_id
     * @param object $msg
     */
    public static function bind(object $msg) : void {
        $uid = v::$uid;
        if (!isset($msg->$uid)){
            return;
        }
        $cid = v::$cid;
        if (GateWayWorker::isOnline($msg->$cid) == 0){
            return;
        }
        $from_client_id = v::$from_client_id;
        $result = static::BindUid($msg->$from_client_id,intval($msg->$uid));
        if ($result !== '') {
            echo $result."\n";
        }
        // success or failure . . .
    }

    /**
     * unbinding client_id and user_id
     * @param object $msg
     */
    public static function un_bind(object $msg) : void {
        $uid = v::$uid;
        if (!isset($msg->$uid)){
            return;
        }
        $cid = v::$cid;
        if (GateWayWorker::isOnline($msg->$cid) == 0){
            return;
        }
        $from_client_id = v::$from_client_id;
        $result = static::UnBindUid($msg->$from_client_id,intval($msg->$uid));
        if ($result !== '') {
            echo $result."\n";
        }
        // success or failure . . .
    }

    /**
     * 加入群组
     * @param object $msg
     */
    public static function join_group(object $msg) : void {
        $gid = v::$gid;
        if (!isset($msg->$gid)){
            return;
        }
        $cid = v::$cid;
        if (GateWayWorker::isOnline($msg->$cid) == 0){
            return;
        }
        $from_client_id = v::$from_client_id;
        GateWayWorker::joinGroup($msg->$from_client_id,$msg->$gid);
        // success or failure . . .
    }

    /**
     * 离开群组
     * @param object $msg
     */
    public static function leave_group(object $msg) : void {
        $gid = v::$gid;
        if (!isset($msg->$gid)){
            return;
        }
        $from_client_id = v::$from_client_id;
        GateWayWorker::leaveGroup($msg->$from_client_id,$msg->$gid);
        // success or failure . . .
    }

    /**
     * 解散群组(剔除所有成员)
     * @param object $msg
     */
    public static function abandon_group(object $msg) : void {
        $gid = v::$gid;
        if (!isset($msg->$gid)){
            return;
        }
        $cids = GateWayWorker::getClientIdListByGroup($msg->$gid);
        foreach ($cids as $k => $v) {
            GateWayWorker::leaveGroup($v,$msg->$gid);// GateWayWorker::leaveGroup($k,$msg->$gid);
        }
        // success or failure . . .
    }

    /**
     * 断开连接
     * @param object $msg
     */
    public static function close(object $msg) : void {
        $from_client_id = v::$from_client_id;
        GateWayWorker::closeClient($msg->$from_client_id,p::MsgPack($msg,v::$event_close));
    }



    /**
     * 发送到某个用户(client_id)
     * @param object $msg
     */
    public static function send_to_cid(object $msg) : void {
        $cid = v::$cid;
        if (!isset($msg->$cid)){
            return;
        }
        // not online
        if (GateWayWorker::isOnline($msg->$cid) == 0){
            return;
        }
        GateWayWorker::sendToClient($msg->$cid,p::MsgPack($msg));
    }

    /**
     * 发送到某个用户(user_id)
     * @param object $msg
     */
    public static function send_to_uid(object $msg) : void {
        $uid = v::$uid;
        if (!isset($msg->$uid)){
            return;
        }
        GateWayWorker::sendToUid($msg->$uid,p::MsgPack($msg));
    }

    /**
     * 发送消息到某个组
     * @param object $msg
     */
    public static function send_to_gid(object $msg) : void {
        $gid = v::$gid;
        if (!isset($msg->$gid)){
            return;
        }
        GateWayWorker::sendToGroup($msg->$gid,p::MsgPack($msg));
    }

    /**
     * @param object $msg
     * @throws \Exception
     */
    public static function send_to_all(object $msg) : void {
        GateWayWorker::sendToAll(p::MsgPack($msg));
    }

}